<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

try {
    $payload = gawdee_request_json();
    gawdee_verify_csrf($payload['csrf_token'] ?? null);
    $orderNumber = trim((string) ($payload['order_number'] ?? ''));
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE order_number = ?');
    $statement->execute([$orderNumber]);
    $order = $statement->fetch();
    if (!$order || $order['payment_method'] !== 'razorpay') {
        throw new RuntimeException('Payment order was not found.');
    }
    $paymentId = (string) ($payload['razorpay_payment_id'] ?? '');
    $signature = (string) ($payload['razorpay_signature'] ?? '');
    if (!gawdee_razorpay_verify_payment((string) $order['razorpay_order_id'], $paymentId, $signature)) {
        gawdee_log_integration('razorpay', 'verify_payment', 'failed', 'Checkout signature did not match.', $orderNumber);
        throw new RuntimeException('Payment verification failed. The order has not been marked paid.');
    }
    gawdee_mark_order_paid((int) $order['id'], $paymentId, $signature);
    $_SESSION['last_order_number'] = $orderNumber;
    gawdee_log_integration('razorpay', 'verify_payment', 'success', 'Payment signature verified.', $paymentId);
    gawdee_json_response(['ok' => true, 'success_url' => 'order-success.php?order=' . rawurlencode($orderNumber)]);
} catch (Throwable $exception) {
    gawdee_json_response(['ok' => false, 'message' => $exception->getMessage()], 422);
}
