<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/integrations.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    gawdee_json_response(['ok' => false], 405);
}

$raw = (string) file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');
if (!gawdee_razorpay_verify_webhook($raw, $signature)) {
    gawdee_log_integration('razorpay', 'webhook', 'failed', 'Invalid webhook signature.');
    gawdee_json_response(['ok' => false, 'message' => 'Invalid signature.'], 400);
}

$event = json_decode($raw, true);
if (!is_array($event)) {
    gawdee_json_response(['ok' => false, 'message' => 'Invalid JSON.'], 400);
}

$eventName = (string) ($event['event'] ?? '');
$payment = $event['payload']['payment']['entity'] ?? [];
$razorpayOrderId = (string) ($payment['order_id'] ?? $event['payload']['order']['entity']['id'] ?? '');
$paymentId = (string) ($payment['id'] ?? '');
try {
    if ($razorpayOrderId !== '' && in_array($eventName, ['payment.captured', 'order.paid'], true)) {
        $statement = gawdee_db()->prepare('SELECT id FROM orders WHERE razorpay_order_id=?');
        $statement->execute([$razorpayOrderId]);
        $orderId = $statement->fetchColumn();
        if ($orderId !== false) {
            gawdee_mark_order_paid((int) $orderId, $paymentId);
        }
    }
    if ($razorpayOrderId !== '' && $eventName === 'payment.failed') {
        $statement = gawdee_db()->prepare('SELECT id FROM orders WHERE razorpay_order_id=?');
        $statement->execute([$razorpayOrderId]);
        $orderId = $statement->fetchColumn();
        if ($orderId !== false) {
            $reason = (string) ($payment['error_description'] ?? $payment['error_reason'] ?? 'Razorpay reported that payment was not completed.');
            gawdee_mark_payment_failed((int) $orderId, $reason);
        }
    }
} catch (Throwable $error) {
    gawdee_log_integration('razorpay', 'webhook', 'failed', $error->getMessage(), $paymentId ?: $razorpayOrderId);
    gawdee_json_response(['ok' => false, 'message' => 'Webhook processing will be retried.'], 500);
}
gawdee_log_integration('razorpay', 'webhook', 'success', $eventName, $paymentId ?: $razorpayOrderId);
gawdee_json_response(['ok' => true]);
