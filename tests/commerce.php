<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';
require_once __DIR__ . '/../includes/integrations.php';

$failures = [];
$check = static function (bool $condition, string $label) use (&$failures): void {
    echo ($condition ? 'PASS' : 'FAIL') . '  ' . $label . PHP_EOL;
    if (!$condition) {
        $failures[] = $label;
    }
};

$db = gawdee_db();
$product = $db->query("SELECT id, stock FROM products WHERE is_active=1 AND stock >= 4 ORDER BY id LIMIT 1")->fetch();
if (!$product) {
    fwrite(STDERR, "A product with at least four units is required for the commerce test.\n");
    exit(1);
}

$productId = (string) $product['id'];
$initialStock = (int) $product['stock'];
$originalDtdcEnabled = gawdee_setting('dtdc_enabled', '0');
$db->prepare("UPDATE settings SET setting_value='0', is_secret=0 WHERE setting_key='dtdc_enabled'")->execute();
$prefix = 'qa-commerce-' . bin2hex(random_bytes(5));
$fields = [
    'name' => 'Commerce QA', 'email' => 'commerce-qa@example.test', 'phone' => '9876543210',
    'address1' => '10 Test Street', 'address2' => '', 'city' => 'New Delhi', 'state' => 'Delhi',
    'pincode' => '110001', 'notes' => 'Automated lifecycle verification.',
];
$items = [['id' => $productId, 'quantity' => 1]];

try {
    $cod = gawdee_create_local_order($fields, $items, 'cod', null, $prefix . '-cod', gawdee_setting('offer_code', 'FREEDOM10'));
    $check($cod['payment_status'] === 'cod_pending' && $cod['status'] === 'processing', 'COD order received into processing queue');
    $check($cod['fulfillment_mode'] === 'manual', 'DTDC-off order uses manual fulfilment');
    $check((int) $cod['discount'] > 0 && $cod['coupon_code'] !== '', 'server-side offer applied');
    $check((int) $db->query("SELECT stock FROM products WHERE id=" . $db->quote($productId))->fetchColumn() === $initialStock - 1, 'COD inventory deducted once');

    $duplicate = gawdee_create_local_order($fields, $items, 'cod', null, $prefix . '-cod', gawdee_setting('offer_code', 'FREEDOM10'));
    $check((int) $duplicate['id'] === (int) $cod['id'] && !empty($duplicate['is_duplicate']), 'checkout token prevents duplicate order');
    $check((int) $db->query("SELECT stock FROM products WHERE id=" . $db->quote($productId))->fetchColumn() === $initialStock - 1, 'duplicate submit does not deduct inventory twice');

    gawdee_update_order_status((int) $cod['id'], 'packed', 'QA packing check.');
    gawdee_set_manual_shipment((int) $cod['id'], 'QA Local Courier', 'QA-AWB-100', 'https://example.test/track/QA-AWB-100');
    gawdee_update_order_status((int) $cod['id'], 'delivered', 'QA delivery check.');
    $delivered = gawdee_order_by_id((int) $cod['id']);
    $check($delivered && $delivered['status'] === 'delivered' && $delivered['payment_status'] === 'paid', 'pack, manual dispatch, delivery and COD collection lifecycle');
    $check($delivered && gawdee_order_tracking_reference($delivered) === 'QA-AWB-100', 'manual courier tracking saved');

    $cancelled = gawdee_create_local_order($fields, $items, 'cod', null, $prefix . '-cancel');
    gawdee_update_order_status((int) $cancelled['id'], 'cancelled', 'QA cancellation check.');
    $cancelled = gawdee_order_by_id((int) $cancelled['id']);
    $check($cancelled && $cancelled['inventory_status'] === 'restocked', 'cancelled order restores inventory');

    $online = gawdee_create_local_order($fields, $items, 'razorpay', null, $prefix . '-online');
    $check($online['payment_status'] === 'initializing' && $online['inventory_status'] === 'reserved', 'online order is visible before gateway initialization');
    gawdee_attach_razorpay_order((int) $online['id'], 'order_qa_' . bin2hex(random_bytes(3)));
    gawdee_mark_payment_failed((int) $online['id'], 'QA simulated gateway failure.');
    $failed = gawdee_order_by_id((int) $online['id']);
    $check($failed && $failed['status'] === 'on_hold' && $failed['payment_status'] === 'failed', 'failed payment remains visible for admin attention');
    $check($failed && $failed['inventory_status'] === 'released', 'failed payment releases reserved stock');
    $check(count(gawdee_admin_orders(['q' => (string) $online['order_number']])) === 1, 'failed order appears in admin Orders query');

    $paidOnline = gawdee_create_local_order($fields, $items, 'razorpay', null, $prefix . '-paid');
    gawdee_attach_razorpay_order((int) $paidOnline['id'], 'order_qa_paid_' . bin2hex(random_bytes(3)));
    gawdee_mark_order_paid((int) $paidOnline['id'], 'pay_qa_paid', 'signature_qa_paid');
    $paidOnline = gawdee_order_by_id((int) $paidOnline['id']);
    $stockAfterPayment = (int) $db->query("SELECT stock FROM products WHERE id=" . $db->quote($productId))->fetchColumn();
    gawdee_mark_order_paid((int) $paidOnline['id'], 'pay_qa_paid', 'signature_qa_paid');
    $check($paidOnline && $paidOnline['payment_status'] === 'paid' && $paidOnline['status'] === 'processing', 'verified online payment enters fulfilment');
    $check((int) $db->query("SELECT stock FROM products WHERE id=" . $db->quote($productId))->fetchColumn() === $stockAfterPayment, 'duplicate payment confirmation is inventory-idempotent');
} finally {
    $db->prepare('UPDATE products SET stock=? WHERE id=?')->execute([$initialStock, $productId]);
    $delete = $db->prepare("DELETE FROM orders WHERE checkout_token LIKE ?");
    $delete->execute([$prefix . '%']);
    $db->prepare("UPDATE settings SET setting_value=?, is_secret=0 WHERE setting_key='dtdc_enabled'")->execute([$originalDtdcEnabled]);
}

exit($failures ? 1 : 0);
