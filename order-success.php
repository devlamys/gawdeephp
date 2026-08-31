<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$orderNumber = trim((string) ($_GET['order'] ?? ''));
$signedInCustomer = gawdee_customer();
$authorized = $orderNumber !== '' && hash_equals((string) ($_SESSION['last_order_number'] ?? ''), $orderNumber);
$order = null;
if ($authorized || $signedInCustomer) {
    $statement = $signedInCustomer
        ? gawdee_db()->prepare('SELECT * FROM orders WHERE order_number=? AND user_id=?')
        : gawdee_db()->prepare('SELECT * FROM orders WHERE order_number=?');
    $statement->execute($signedInCustomer ? [$orderNumber, (int) $signedInCustomer['id']] : [$orderNumber]);
    $order = $statement->fetch() ?: null;
}

$pageTitle = 'Order Received — Gawdee';
$bodyClass = 'order-success-page';
require __DIR__ . '/includes/header.php';
?>
<section class="order-success-shell section">
    <div class="container container--narrow">
        <div class="order-success-card reveal reveal--scale">
            <div class="order-success-icon">
                <i class="ph-fill ph-check-circle"></i>
            </div>
            
            <?php if ($order): ?>
                <span class="eyebrow"><i class="ph ph-sparkle"></i> Order Confirmed · #<?= htmlspecialchars($order['order_number']) ?></span>
                <h1>Thank you,<br><em><?= htmlspecialchars(explode(' ', (string) $order['customer_name'])[0]) ?>!</em></h1>
                <p class="order-success-lead">Your order <strong>#<?= htmlspecialchars($order['order_number']) ?></strong> has been received and is now being prepared with care. A confirmation email has been sent to <strong><?= htmlspecialchars($order['email']) ?></strong>.</p>
                
                <div class="order-success-details">
                    <div class="order-success-detail">
                        <i class="ph ph-receipt"></i>
                        <span>Order Number</span>
                        <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
                    </div>
                    <div class="order-success-detail">
                        <i class="ph ph-credit-card"></i>
                        <span>Payment Method</span>
                        <strong><?= htmlspecialchars($order['payment_method'] === 'cod' ? 'Cash on Delivery' : ucfirst($order['payment_status'])) ?></strong>
                    </div>
                    <div class="order-success-detail">
                        <i class="ph ph-wallet"></i>
                        <span>Total Amount</span>
                        <strong><?= money((int) $order['total']) ?></strong>
                    </div>
                    <div class="order-success-detail">
                        <i class="ph ph-package"></i>
                        <span>Order Status</span>
                        <strong class="status-pill status-pill--success"><?= htmlspecialchars(ucfirst($order['status'])) ?></strong>
                    </div>
                </div>

                <div class="order-timeline">
                    <div class="order-timeline-step is-complete">
                        <span class="order-timeline-step__node"><i class="ph ph-check"></i></span>
                        <strong>Order Placed</strong>
                        <small>Confirmed</small>
                    </div>
                    <div class="order-timeline-step is-active">
                        <span class="order-timeline-step__node"><i class="ph ph-package"></i></span>
                        <strong>Packaging</strong>
                        <small>In progress</small>
                    </div>
                    <div class="order-timeline-step">
                        <span class="order-timeline-step__node"><i class="ph ph-truck"></i></span>
                        <strong>Dispatch</strong>
                        <small>DTDC / Express</small>
                    </div>
                    <div class="order-timeline-step">
                        <span class="order-timeline-step__node"><i class="ph ph-house"></i></span>
                        <strong>Delivery</strong>
                        <small>To your doorstep</small>
                    </div>
                </div>
            <?php else: ?>
                <span class="eyebrow"><i class="ph ph-shield-check"></i> Order Privacy</span>
                <h1>Your order is<br><em>in good hands.</em></h1>
                <p class="order-success-lead">For privacy and security, order details are visible to logged-in customers or directly after order placement.</p>
            <?php endif; ?>

            <div class="order-success-actions">
                <?php if ($order && $signedInCustomer): ?>
                    <a class="button button--primary" href="account-order?order=<?= rawurlencode((string) $order['order_number']) ?>">Track Shipment <i class="ph ph-map-pin"></i></a>
                <?php else: ?>
                    <a class="button button--primary" href="index#shop">Continue Shopping <i class="ph ph-arrow-right"></i></a>
                <?php endif; ?>
                <a class="button button--cream" href="<?= $signedInCustomer ? 'account' : 'register' ?>"><?= $signedInCustomer ? 'My Account' : 'Create Tracking Account' ?></a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
