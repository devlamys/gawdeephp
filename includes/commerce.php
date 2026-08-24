<?php

declare(strict_types=1);

require_once __DIR__ . '/platform.php';

function gawdee_order_status_labels(): array
{
    return [
        'pending' => 'Payment pending',
        'processing' => 'Confirmed',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'on_hold' => 'Needs attention',
        'cancelled' => 'Cancelled',
        'refunded' => 'Refunded',
    ];
}

function gawdee_order_by_id(int $orderId): ?array
{
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE id = ?');
    $statement->execute([$orderId]);
    return $statement->fetch() ?: null;
}

function gawdee_order_by_number(string $orderNumber): ?array
{
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE order_number = ?');
    $statement->execute([$orderNumber]);
    return $statement->fetch() ?: null;
}

function gawdee_order_items(int $orderId): array
{
    $statement = gawdee_db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $statement->execute([$orderId]);
    return $statement->fetchAll();
}

function gawdee_order_events(int $orderId): array
{
    $statement = gawdee_db()->prepare('SELECT * FROM order_status_events WHERE order_id = ? ORDER BY id DESC');
    $statement->execute([$orderId]);
    return $statement->fetchAll();
}

function gawdee_checkout_pricing(array $requestedItems, string $couponCode = ''): array
{
    if (!$requestedItems || count($requestedItems) > 30) {
        throw new RuntimeException('Your cart is empty or contains too many line items.');
    }

    $items = [];
    $subtotal = 0;
    foreach ($requestedItems as $requested) {
        $product = gawdee_product_by_id((string) ($requested['id'] ?? ''));
        $quantity = min(10, max(1, (int) ($requested['quantity'] ?? 1)));
        if (!$product) {
            throw new RuntimeException('A product in the cart is no longer available.');
        }
        if ((int) $product['stock'] < $quantity) {
            throw new RuntimeException($product['name'] . ' does not have enough stock for that quantity.');
        }
        $items[] = ['product' => $product, 'quantity' => $quantity];
        $subtotal += (int) $product['price'] * $quantity;
    }

    $couponCode = strtoupper(trim($couponCode));
    $discount = 0;
    $appliedCoupon = '';
    if ($couponCode !== '') {
        $activeCode = strtoupper(trim(gawdee_setting('offer_code', 'FREEDOM10')));
        if (!hash_equals($activeCode, $couponCode)) {
            throw new RuntimeException('That offer code is not valid.');
        }
        $percent = min(100, max(0, (int) gawdee_setting('offer_percent', '10')));
        $discount = intdiv($subtotal * $percent, 100);
        $appliedCoupon = $activeCode;
    }

    $shipping = $subtotal >= (int) gawdee_setting('free_shipping_threshold', '999')
        ? 0
        : max(0, (int) gawdee_setting('shipping_fee', '99'));

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'total' => max(0, $subtotal - $discount + $shipping),
        'coupon_code' => $appliedCoupon,
    ];
}

function gawdee_create_local_order(array $fields, array $requestedItems, string $paymentMethod, ?int $userId, string $checkoutToken, string $couponCode = ''): array
{
    $paymentMethod = $paymentMethod === 'cod' ? 'cod' : 'razorpay';
    if (!preg_match('/^[A-Za-z0-9_-]{16,100}$/', $checkoutToken)) {
        throw new RuntimeException('Checkout session is invalid. Refresh the checkout page and try again.');
    }

    $existing = gawdee_db()->prepare('SELECT * FROM orders WHERE checkout_token = ?');
    $existing->execute([$checkoutToken]);
    $existingOrder = $existing->fetch();
    if ($existingOrder) {
        $existingOrder['is_duplicate'] = true;
        return $existingOrder;
    }

    $pricing = gawdee_checkout_pricing($requestedItems, $couponCode);
    $orderNumber = 'GD' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
    $status = $paymentMethod === 'cod' ? 'processing' : 'pending';
    $paymentStatus = $paymentMethod === 'cod' ? 'cod_pending' : 'initializing';
    $inventoryStatus = $paymentMethod === 'cod' ? 'deducted' : 'reserved';
    $dtdcReady = gawdee_setting('dtdc_enabled', '0') === '1'
        && gawdee_setting('dtdc_booking_endpoint') !== ''
        && (gawdee_setting('dtdc_api_token') !== '' || (gawdee_setting('dtdc_username') !== '' && gawdee_setting('dtdc_password') !== ''));
    $fulfillmentMode = $dtdcReady ? 'dtdc' : 'manual';

    $db = gawdee_db();
    $db->beginTransaction();
    try {
        foreach ($pricing['items'] as $line) {
            $stock = $db->prepare('SELECT stock FROM products WHERE id = ?');
            $stock->execute([$line['product']['id']]);
            if ((int) $stock->fetchColumn() < (int) $line['quantity']) {
                throw new RuntimeException($line['product']['name'] . ' sold out while checkout was being prepared.');
            }
        }

        $statement = $db->prepare(<<<'SQL'
INSERT INTO orders
(user_id, order_number, status, payment_method, payment_status, shipment_status, currency, subtotal, shipping, discount, total, coupon_code, checkout_token, customer_name, email, phone, address1, address2, city, state, pincode, notes, fulfillment_mode, inventory_status)
VALUES (?, ?, ?, ?, ?, 'awaiting_fulfillment', 'INR', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL);
        $statement->execute([
            $userId, $orderNumber, $status, $paymentMethod, $paymentStatus,
            $pricing['subtotal'], $pricing['shipping'], $pricing['discount'], $pricing['total'], $pricing['coupon_code'], $checkoutToken,
            $fields['name'], $fields['email'], $fields['phone'], $fields['address1'], $fields['address2'], $fields['city'], $fields['state'], $fields['pincode'], $fields['notes'],
            $fulfillmentMode, $inventoryStatus,
        ]);
        $orderId = (int) $db->lastInsertId();

        $itemStatement = $db->prepare('INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, image) VALUES (?, ?, ?, ?, ?, ?)');
        $reduce = $db->prepare("UPDATE products SET stock = stock - ?, stock_status = CASE WHEN stock - ? <= 0 THEN 'out_of_stock' ELSE 'in_stock' END WHERE id = ? AND stock >= ?");
        foreach ($pricing['items'] as $line) {
            $product = $line['product'];
            $quantity = (int) $line['quantity'];
            $itemStatement->execute([$orderId, $product['id'], $product['full_name'], $quantity, $product['price'], $product['image']]);
            $reduce->execute([$quantity, $quantity, $product['id'], $quantity]);
            if ($reduce->rowCount() !== 1) {
                throw new RuntimeException($product['name'] . ' sold out while checkout was being prepared.');
            }
        }

        if ($userId) {
            $db->prepare('UPDATE users SET name=?, phone=?, address1=?, address2=?, city=?, state=?, pincode=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')
                ->execute([$fields['name'], $fields['phone'], $fields['address1'], $fields['address2'], $fields['city'], $fields['state'], $fields['pincode'], $userId]);
        }

        gawdee_record_order_event(
            $orderId,
            $status,
            'Order received',
            $paymentMethod === 'cod'
                ? 'Cash on delivery order confirmed and sent to the fulfilment queue.'
                : 'Order saved securely. Online payment is being prepared.'
        );
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }

    $order = gawdee_order_by_id($orderId);
    if (!$order) {
        throw new RuntimeException('The order was saved but could not be reloaded.');
    }
    $order['is_duplicate'] = false;
    return $order;
}

function gawdee_attach_razorpay_order(int $orderId, string $razorpayOrderId): void
{
    gawdee_db()->prepare("UPDATE orders SET razorpay_order_id=?, payment_status='pending', payment_error='', updated_at=CURRENT_TIMESTAMP WHERE id=? AND payment_status!='paid'")
        ->execute([$razorpayOrderId, $orderId]);
    gawdee_record_order_event($orderId, 'pending', 'Secure payment ready', 'Razorpay Checkout is ready for payment.');
}

function gawdee_release_order_inventory(int $orderId, string $newInventoryStatus = 'released'): bool
{
    $db = gawdee_db();
    $ownsTransaction = !$db->inTransaction();
    if ($ownsTransaction) {
        $db->beginTransaction();
    }
    try {
        $statement = $db->prepare('SELECT inventory_status FROM orders WHERE id = ?');
        $statement->execute([$orderId]);
        $inventoryStatus = $statement->fetchColumn();
        if (!in_array($inventoryStatus, ['reserved', 'deducted'], true)) {
            if ($ownsTransaction) {
                $db->commit();
            }
            return false;
        }
        $restore = $db->prepare("UPDATE products SET stock = stock + ?, stock_status='in_stock' WHERE id = ?");
        foreach (gawdee_order_items($orderId) as $item) {
            $restore->execute([(int) $item['quantity'], $item['product_id']]);
        }
        $db->prepare('UPDATE orders SET inventory_status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$newInventoryStatus, $orderId]);
        if ($ownsTransaction) {
            $db->commit();
        }
        return true;
    } catch (Throwable $error) {
        if ($ownsTransaction && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function gawdee_mark_payment_failed(int $orderId, string $message): void
{
    $db = gawdee_db();
    $db->beginTransaction();
    try {
        $order = gawdee_order_by_id($orderId);
        if (!$order || $order['payment_status'] === 'paid') {
            $db->commit();
            return;
        }
        gawdee_release_order_inventory($orderId, 'released');
        $db->prepare("UPDATE orders SET payment_status='failed', status='on_hold', payment_error=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
            ->execute([mb_substr($message, 0, 500), $orderId]);
        gawdee_record_order_event($orderId, 'on_hold', 'Payment not completed', 'The order remains visible to the store team. Inventory was released and the customer may try again.');
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function gawdee_order_allowed_transitions(array $order): array
{
    $map = [
        'pending' => ['processing', 'on_hold', 'cancelled'],
        'processing' => ['packed', 'on_hold', 'cancelled'],
        'packed' => ['processing', 'shipped', 'cancelled'],
        'shipped' => ['delivered', 'on_hold'],
        'delivered' => ['refunded'],
        'on_hold' => ['processing', 'cancelled', 'refunded'],
        'cancelled' => [],
        'refunded' => [],
    ];
    $allowed = $map[$order['status']] ?? [];
    if ($order['payment_method'] === 'razorpay' && $order['payment_status'] !== 'paid') {
        $allowed = array_values(array_intersect($allowed, ['on_hold', 'cancelled']));
    }
    return $allowed;
}

function gawdee_update_order_status(int $orderId, string $newStatus, string $note = ''): void
{
    if (!array_key_exists($newStatus, gawdee_order_status_labels())) {
        throw new RuntimeException('Unknown order status.');
    }
    $db = gawdee_db();
    $db->beginTransaction();
    try {
        $order = gawdee_order_by_id($orderId);
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ($order['status'] === $newStatus) {
            $db->commit();
            return;
        }
        if (!in_array($newStatus, gawdee_order_allowed_transitions($order), true)) {
            throw new RuntimeException('That workflow change is not allowed from ' . str_replace('_', ' ', (string) $order['status']) . '.');
        }

        if ($newStatus === 'cancelled') {
            gawdee_release_order_inventory($orderId, 'restocked');
        }
        $shipmentStatus = match ($newStatus) {
            'processing' => 'awaiting_fulfillment',
            'packed' => 'awaiting_shipment',
            'shipped' => 'in_transit',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            default => $order['shipment_status'],
        };
        $paymentStatus = $order['payment_status'];
        $paidAtSql = '';
        if ($newStatus === 'delivered' && $order['payment_method'] === 'cod' && $paymentStatus !== 'paid') {
            $paymentStatus = 'paid';
            $paidAtSql = ', paid_at=CURRENT_TIMESTAMP';
        }
        if ($newStatus === 'refunded') {
            $paymentStatus = 'refunded';
        }
        $timestamps = $newStatus === 'delivered'
            ? ', fulfilled_at=CURRENT_TIMESTAMP'
            : ($newStatus === 'cancelled' ? ', cancelled_at=CURRENT_TIMESTAMP' : '');
        $db->prepare("UPDATE orders SET status=?, shipment_status=?, payment_status=?, admin_note=?, updated_at=CURRENT_TIMESTAMP{$paidAtSql}{$timestamps} WHERE id=?")
            ->execute([$newStatus, $shipmentStatus, $paymentStatus, mb_substr(trim($note), 0, 500), $orderId]);

        $titles = [
            'processing' => 'Order confirmed', 'packed' => 'Packed with care', 'shipped' => 'Shipment dispatched',
            'delivered' => 'Order delivered', 'on_hold' => 'Order needs attention', 'cancelled' => 'Order cancelled', 'refunded' => 'Order refunded',
        ];
        gawdee_record_order_event($orderId, $newStatus, $titles[$newStatus] ?? 'Order updated', trim($note) ?: 'Status updated by the Gawdee fulfilment team.');
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function gawdee_set_manual_shipment(int $orderId, string $courierName, string $trackingNumber = '', string $trackingUrl = ''): void
{
    $order = gawdee_order_by_id($orderId);
    if (!$order) {
        throw new RuntimeException('Order not found.');
    }
    if (!in_array($order['status'], ['processing', 'packed'], true)) {
        throw new RuntimeException('Confirm and pack the order before dispatch.');
    }
    if ($order['payment_method'] === 'razorpay' && $order['payment_status'] !== 'paid') {
        throw new RuntimeException('Online payment must be confirmed before dispatch.');
    }
    $courierName = trim($courierName) ?: 'Manual delivery';
    $trackingNumber = trim($trackingNumber);
    $trackingUrl = trim($trackingUrl);
    if ($trackingUrl !== '' && !filter_var($trackingUrl, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Enter a valid tracking URL or leave it blank.');
    }
    gawdee_db()->prepare("UPDATE orders SET status='shipped', shipment_status='in_transit', fulfillment_mode='manual', courier_name=?, tracking_number=?, tracking_url=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
        ->execute([$courierName, $trackingNumber, $trackingUrl, $orderId]);
    gawdee_record_order_event($orderId, 'shipped', 'Shipment dispatched', $trackingNumber !== '' ? $courierName . ' tracking reference ' . $trackingNumber . '.' : 'Dispatched through ' . $courierName . '.');
}

function gawdee_expire_stale_payment_orders(int $minutes = 45): int
{
    $minutes = min(1440, max(15, $minutes));
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));
    $statement = gawdee_db()->prepare("SELECT id FROM orders WHERE payment_method='razorpay' AND payment_status IN ('initializing','pending') AND created_at <= ?");
    $statement->execute([$cutoff]);
    $expired = 0;
    foreach ($statement->fetchAll() as $row) {
        $orderId = (int) $row['id'];
        gawdee_release_order_inventory($orderId, 'released');
        gawdee_db()->prepare("UPDATE orders SET payment_status='expired', status='cancelled', shipment_status='cancelled', payment_error='Payment window expired.', cancelled_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=? AND payment_status!='paid'")
            ->execute([$orderId]);
        gawdee_record_order_event($orderId, 'cancelled', 'Payment window expired', 'No payment was captured. Reserved inventory was returned to the catalogue.');
        $expired++;
    }
    return $expired;
}

function gawdee_admin_orders(array $filters = []): array
{
    $where = [];
    $params = [];
    $query = trim((string) ($filters['q'] ?? ''));
    if ($query !== '') {
        $where[] = '(order_number LIKE ? OR customer_name LIKE ? OR email LIKE ? OR phone LIKE ? OR tracking_number LIKE ? OR dtdc_reference LIKE ?)';
        $needle = '%' . $query . '%';
        array_push($params, $needle, $needle, $needle, $needle, $needle, $needle);
    }
    foreach (['status', 'payment_status', 'fulfillment_mode'] as $key) {
        $value = trim((string) ($filters[$key] ?? ''));
        if ($value !== '') {
            $where[] = $key . ' = ?';
            $params[] = $value;
        }
    }
    $sql = 'SELECT * FROM orders' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 250';
    $statement = gawdee_db()->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function gawdee_order_tracking_reference(array $order): string
{
    return (string) ($order['tracking_number'] ?: $order['dtdc_reference']);
}

function gawdee_order_tracking_url(array $order): string
{
    return (string) ($order['tracking_url'] ?: $order['dtdc_tracking_url']);
}
