<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$orderNumber = trim((string) ($_GET['order'] ?? ''));
$customer = gawdee_require_customer('account-order.php?order=' . rawurlencode($orderNumber));
$order = $orderNumber !== '' ? gawdee_customer_order((int) $customer['id'], $orderNumber) : null;
if (!$order) {
    http_response_code(404);
}

$pageTitle = $order ? 'Order #' . $order['order_number'] . ' — Gawdee' : 'Order Not Found — Gawdee';
$pageDescription = 'Gawdee order details and tracked delivery progress.';
$bodyClass = 'customer-account-page account-order-page';
require __DIR__ . '/includes/header.php';

$steps = [
    'pending' => ['Order Received', 'We received your order and payment choice.', 'ph-receipt'],
    'processing' => ['Confirmed', 'Your order is confirmed and being prepared.', 'ph-check-circle'],
    'packed' => ['Packed with Care', 'Your products are packed for dispatch.', 'ph-package'],
    'shipped' => ['On the Way', 'The shipment has left with the courier.', 'ph-truck'],
    'delivered' => ['Delivered', 'Your Gawdee order has reached its destination.', 'ph-house-line'],
];
$positions = array_keys($steps);
$currentIndex = $order ? (array_search($order['status'], $positions, true) ?: 0) : 0;
$trackingReference = $order ? gawdee_order_tracking_reference($order) : '';
$trackingUrl = $order ? gawdee_order_tracking_url($order) : '';
?>
<section class="order-detail-shell section">
    <div class="container">
        <a class="order-detail-back" href="account.php#orders"><i class="ph ph-arrow-left"></i> Back to My Orders</a>
        
        <?php if (!$order): ?>
            <div class="account-empty reveal text-center">
                <i class="ph ph-magnifying-glass"></i>
                <span class="eyebrow">Order Not Found</span>
                <h1>This order is not available in your account.</h1>
                <p>For privacy and security, order details are accessible only to the account owner.</p>
                <a class="button button--primary" href="account.php">Go to My Account</a>
            </div>
        <?php else: ?>
            <div class="order-detail-heading reveal">
                <div class="order-detail-heading__info">
                    <span class="eyebrow"><i class="ph ph-map-pin-line"></i> Order Tracking · #<?= htmlspecialchars($order['order_number']) ?></span>
                    <h1>Order #<?= htmlspecialchars($order['order_number']) ?></h1>
                    <p><i class="ph ph-clock"></i> Placed on <?= htmlspecialchars(date('j F Y \a\t g:i a', strtotime((string) $order['created_at']))) ?></p>
                </div>
                <div class="order-detail-heading__actions">
                    <span class="account-status account-status--<?= htmlspecialchars($order['status']) ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $order['status']))) ?>
                    </span>
                    <?php if ($trackingUrl): ?>
                        <a class="button button--primary" href="<?= htmlspecialchars($trackingUrl) ?>" target="_blank" rel="noopener">
                            Track Courier Shipment <i class="ph ph-arrow-up-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (in_array($order['status'], ['cancelled', 'refunded'], true)): ?>
                <div class="order-cancelled reveal">
                    <i class="ph ph-warning-circle"></i>
                    <div>
                        <strong><?= htmlspecialchars(ucfirst($order['status'])) ?> Order</strong>
                        <p>This order is no longer moving through fulfilment. Please contact customer support if you require assistance.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="order-detail-grid">
                <main class="order-detail-main">
                    <section class="order-detail-card order-journey reveal">
                        <div class="order-detail-card__head">
                            <i class="ph ph-map-trifold"></i>
                            <div>
                                <h2>Delivery Journey</h2>
                                <p>Live status updates from Gawdee fulfilment centre</p>
                            </div>
                        </div>
                        <div class="order-journey__steps">
                            <?php foreach ($steps as $status => [$title, $description, $icon]): 
                                $index = array_search($status, $positions, true); 
                                $complete = $index <= $currentIndex && !in_array($order['status'], ['cancelled','refunded'], true);
                                $isCurrent = $status === $order['status'];
                            ?>
                                <article class="journey-step <?= $complete ? 'is-complete' : '' ?> <?= $isCurrent ? 'is-current' : '' ?>">
                                    <span class="journey-step__icon"><i class="ph <?= $icon ?>"></i></span>
                                    <div class="journey-step__content">
                                        <strong><?= htmlspecialchars($title) ?></strong>
                                        <p><?= htmlspecialchars($description) ?></p>
                                        <?php if ($status === 'shipped' && $trackingReference): ?>
                                            <small><i class="ph ph-barcode"></i> <?= htmlspecialchars($order['courier_name'] ?: 'Courier') ?> Ref: <strong><?= htmlspecialchars($trackingReference) ?></strong></small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="journey-step__badge">
                                        <i class="ph <?= $complete ? 'ph-check-circle-fill' : 'ph-circle' ?>"></i>
                                    </span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <?php if (!empty($order['events'])): ?>
                        <section class="order-detail-card reveal">
                            <div class="order-detail-card__head">
                                <i class="ph ph-clock-counter-clockwise"></i>
                                <div>
                                    <h2>Order Updates &amp; Activity Log</h2>
                                    <p>Recorded status changes for this order</p>
                                </div>
                            </div>
                            <div class="order-event-list">
                                <?php foreach (array_reverse($order['events']) as $event): ?>
                                    <article class="order-event">
                                        <span class="order-event__dot"></span>
                                        <div class="order-event__content">
                                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                                            <p><?= htmlspecialchars($event['description']) ?></p>
                                        </div>
                                        <time><?= htmlspecialchars(date('j M, g:i a', strtotime((string) $event['created_at']))) ?></time>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="order-detail-card reveal">
                        <div class="order-detail-card__head">
                            <i class="ph ph-shopping-bag-open"></i>
                            <div>
                                <h2>Items in This Order</h2>
                                <p><?= count($order['items']) ?> product line<?= count($order['items']) === 1 ? '' : 's' ?></p>
                            </div>
                        </div>
                        <div class="order-item-list">
                            <?php foreach ($order['items'] as $item): 
                                $itemProduct = gawdee_product_by_id((string) $item['product_id']); 
                            ?>
                                <article class="order-item">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                    <div class="order-item__info">
                                        <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                        <span>Quantity <?= (int) $item['quantity'] ?> · <?= money((int) $item['unit_price']) ?> each</span>
                                    </div>
                                    <b class="order-item__total"><?= money((int) $item['quantity'] * (int) $item['unit_price']) ?></b>
                                    <?php if ($itemProduct): ?>
                                        <a class="order-item__link" href="product.php?slug=<?= rawurlencode((string) $itemProduct['slug']) ?>" aria-label="View product page">
                                            <i class="ph ph-arrow-up-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </main>

                <aside class="order-detail-aside reveal">
                    <section class="order-detail-card order-summary-card">
                        <h2>Order Summary</h2>
                        <div class="order-summary-card__rows">
                            <p><span>Subtotal</span><strong><?= money((int) $order['subtotal']) ?></strong></p>
                            <?php if ((int) $order['discount'] > 0): ?>
                                <p class="order-summary-card__discount"><span>Offer Discount (<?= htmlspecialchars($order['coupon_code']) ?>)</span><strong>−<?= money((int) $order['discount']) ?></strong></p>
                            <?php endif; ?>
                            <p><span>Delivery Shipping</span><strong><?= (int) $order['shipping'] === 0 ? 'FREE' : money((int) $order['shipping']) ?></strong></p>
                            <div class="order-summary-card__total">
                                <span>Total Amount Paid</span>
                                <strong><?= money((int) $order['total']) ?></strong>
                            </div>
                        </div>
                        <div class="order-summary-card__payment">
                            <i class="ph ph-credit-card"></i>
                            <div>
                                <strong><?= htmlspecialchars($order['payment_method'] === 'cod' ? 'Cash on Delivery (COD)' : 'Razorpay Secure Online') ?></strong>
                                <small><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $order['payment_status']))) ?></small>
                            </div>
                        </div>
                    </section>

                    <section class="order-detail-card order-address-card">
                        <div class="order-detail-card__head">
                            <i class="ph ph-map-pin"></i>
                            <div>
                                <h2>Delivery Destination</h2>
                                <p>Shipping address</p>
                            </div>
                        </div>
                        <address>
                            <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                            <?= htmlspecialchars($order['address1']) ?><br>
                            <?php if (!empty($order['address2'])): ?>
                                <?= htmlspecialchars($order['address2']) ?><br>
                            <?php endif; ?>
                            <?= htmlspecialchars($order['city'] . ', ' . $order['state'] . ' ' . $order['pincode']) ?><br>
                            <i class="ph ph-phone"></i> <?= htmlspecialchars($order['phone']) ?>
                        </address>
                    </section>

                    <section class="order-help-card">
                        <i class="ph ph-headset"></i>
                        <h3>Need Help With This Order?</h3>
                        <p>Our customer care team is available Mon – Sat (9AM – 7PM) to assist with shipment questions.</p>
                        <a class="button button--cream" href="mailto:<?= htmlspecialchars(gawdee_setting('store_email', 'info@gawdee.com')) ?>?subject=Help with order <?= rawurlencode((string) $order['order_number']) ?>">
                            Contact Order Support <i class="ph ph-arrow-right"></i>
                        </a>
                    </section>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
