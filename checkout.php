<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/integrations.php';
$pageTitle = 'Secure Checkout — Gawdee';
$pageDescription = 'Complete your Gawdee order with secure online payment or cash on delivery.';
$bodyClass = 'checkout-page';
$checkoutCustomer = gawdee_customer();
require __DIR__ . '/includes/header.php';
?>
<section class="checkout-shell section" data-checkout data-csrf="<?= htmlspecialchars(gawdee_csrf_token()) ?>" data-cod-enabled="<?= gawdee_setting('cod_enabled', '1') ?>" data-razorpay-enabled="<?= gawdee_razorpay_configured() ? '1' : '0' ?>" data-offer-code="<?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?>" data-offer-percent="<?= (int) gawdee_setting('offer_percent', '10') ?>" data-free-shipping="<?= (int) gawdee_setting('free_shipping_threshold', '999') ?>" data-shipping-fee="<?= (int) gawdee_setting('shipping_fee', '99') ?>">
    <div class="container">
        <div class="checkout-heading reveal">
            <a class="checkout-back" href="index.php"><i class="ph ph-arrow-left"></i> Continue Shopping</a>
            <span class="eyebrow"><i class="ph ph-shield-check"></i> Secure Checkout</span>
            <h1>Almost there.<br><em>Let’s deliver goodness.</em></h1>
            <p>Your order total is securely calculated and verified on our server before payment.</p>
        </div>

        <?php if ($checkoutCustomer): ?>
            <div class="checkout-account-banner reveal">
                <div class="checkout-account-banner__icon"><i class="ph ph-user-circle-check"></i></div>
                <div class="checkout-account-banner__info">
                    <strong>Logged in as <?= htmlspecialchars($checkoutCustomer['name']) ?></strong>
                    <p>This order will automatically link to your Gawdee customer account for real-time tracking.</p>
                </div>
                <a class="button button--cream" href="account.php">My Account <i class="ph ph-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <div class="checkout-account-banner checkout-account-banner--guest reveal">
                <div class="checkout-account-banner__icon"><i class="ph ph-user-circle"></i></div>
                <div class="checkout-account-banner__info">
                    <strong>Want order tracking &amp; history?</strong>
                    <p>Sign in before completing your order to save your delivery address and track shipments.</p>
                </div>
                <a class="button button--cream" href="login.php?return=checkout.php">Sign In <i class="ph ph-arrow-right"></i></a>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <form class="checkout-form reveal" data-checkout-form method="POST" action="api/create-order.php">
                <section class="checkout-card">
                    <div class="checkout-card__heading">
                        <span>01</span>
                        <div>
                            <h2>Contact Information</h2>
                            <p>For order confirmation and shipment status alerts</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <label class="checkout-span-2">
                            <span>Full Name <small>*</small></span>
                            <input name="name" autocomplete="name" required placeholder="Full Name" value="<?= htmlspecialchars((string) ($checkoutCustomer['name'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>Email Address <small>*</small></span>
                            <input type="email" name="email" autocomplete="email" required placeholder="email@example.com" value="<?= htmlspecialchars((string) ($checkoutCustomer['email'] ?? '')) ?>" <?= $checkoutCustomer ? 'readonly' : '' ?>>
                        </label>
                        <label>
                            <span>Phone Number <small>*</small></span>
                            <input type="tel" name="phone" autocomplete="tel" required placeholder="10-digit mobile number" value="<?= htmlspecialchars((string) ($checkoutCustomer['phone'] ?? '')) ?>">
                        </label>
                    </div>
                </section>

                <section class="checkout-card">
                    <div class="checkout-card__heading">
                        <span>02</span>
                        <div>
                            <h2>Delivery Address</h2>
                            <p>Currently delivering across all pincodes in India</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <label class="checkout-span-2">
                            <span>Street Address / House No. <small>*</small></span>
                            <input name="address1" autocomplete="address-line1" required placeholder="Flat, House no., Building, Street" value="<?= htmlspecialchars((string) ($checkoutCustomer['address1'] ?? '')) ?>">
                        </label>
                        <label class="checkout-span-2">
                            <span>Apartment, Suite, Landmark <small>Optional</small></span>
                            <input name="address2" autocomplete="address-line2" placeholder="Near landmark or area" value="<?= htmlspecialchars((string) ($checkoutCustomer['address2'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>City <small>*</small></span>
                            <input name="city" autocomplete="address-level2" required placeholder="City" value="<?= htmlspecialchars((string) ($checkoutCustomer['city'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>State <small>*</small></span>
                            <input name="state" autocomplete="address-level1" required placeholder="State" value="<?= htmlspecialchars((string) ($checkoutCustomer['state'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>Pincode <small>*</small></span>
                            <input name="pincode" inputmode="numeric" pattern="[1-9][0-9]{5}" maxlength="6" autocomplete="postal-code" required placeholder="6-digit pincode" value="<?= htmlspecialchars((string) ($checkoutCustomer['pincode'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>Delivery Note <small>Optional</small></span>
                            <input name="notes" maxlength="500" placeholder="Special delivery instructions">
                        </label>
                    </div>
                </section>

                <section class="checkout-card">
                    <?php $offerEnabled = gawdee_setting('offer_popup_enabled', '1') === '1'; ?>
                    <div class="checkout-card__heading">
                        <span>03</span>
                        <div>
                            <h2><?= $offerEnabled ? 'Offer &amp; Payment' : 'Payment' ?></h2>
                            <p><?= $offerEnabled ? 'Apply discount code and choose payment method' : 'Choose payment method' ?></p>
                        </div>
                    </div>
                    <?php if ($offerEnabled): ?>
                        <div class="checkout-coupon">
                            <label>
                                <span>Have a Discount Code?</span>
                                <div class="checkout-coupon__input">
                                    <input name="coupon_code" maxlength="30" placeholder="<?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?>">
                                    <button type="button" data-apply-coupon>Apply Code</button>
                                </div>
                                <small data-coupon-message>Use <?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?> for <?= (int) gawdee_setting('offer_percent', '10') ?>% off.</small>
                            </label>
                        </div>
                    <?php endif; ?>

                    <div class="payment-options">
                        <?php if (gawdee_razorpay_configured()): ?>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="razorpay" checked>
                                <span class="payment-option__card">
                                    <i class="ph ph-credit-card"></i>
                                    <div>
                                        <strong>Razorpay Secure Online Payment</strong>
                                        <small>UPI, Credit/Debit Cards, NetBanking, Wallets</small>
                                    </div>
                                </span>
                            </label>
                        <?php endif; ?>

                        <?php if (gawdee_setting('cod_enabled', '1') === '1'): ?>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cod" <?= gawdee_razorpay_configured() ? '' : 'checked' ?>>
                                <span class="payment-option__card">
                                    <i class="ph ph-hand-coins"></i>
                                    <div>
                                        <strong>Cash on Delivery (COD)</strong>
                                        <small>Pay with cash when your package arrives</small>
                                    </div>
                                </span>
                            </label>
                        <?php endif; ?>

                        <?php if (!gawdee_razorpay_configured() && gawdee_setting('cod_enabled', '1') !== '1'): ?>
                            <div class="checkout-payment-offline">
                                <i class="ph ph-warning-circle"></i>
                                <span><strong>Checkout is temporarily paused.</strong> Please contact customer support to place your order.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <div class="checkout-error" data-checkout-error hidden></div>

                <button class="checkout-submit" type="submit" data-checkout-submit>
                    <span>Place Secure Order</span>
                    <i class="ph ph-lock-key"></i>
                </button>

                <p class="checkout-secure-note">
                    <i class="ph ph-shield-check"></i> 100% Secure &amp; Server-Verified Checkout. Your payment details are encrypted.
                </p>
            </form>

            <aside class="checkout-summary">
                <div class="checkout-summary__top">
                    <span>Order Summary</span>
                    <strong data-checkout-count>0 items</strong>
                </div>

                <div class="checkout-summary__items" data-checkout-items></div>

                <div class="checkout-summary__empty" data-checkout-empty>
                    <i class="ph ph-shopping-bag-open"></i>
                    <h2>Your shopping bag is empty</h2>
                    <a class="button button--cream" href="index.php#shop">Browse Products</a>
                </div>

                <div class="checkout-totals" data-checkout-totals hidden>
                    <p>
                        <span>Subtotal</span>
                        <strong data-checkout-subtotal>₹0</strong>
                    </p>
                    <p data-checkout-discount-row hidden>
                        <span>Offer Discount</span>
                        <strong data-checkout-discount>−₹0</strong>
                    </p>
                    <p>
                        <span>Estimated Shipping</span>
                        <strong data-checkout-shipping>₹0</strong>
                    </p>
                    <div class="checkout-totals__grand">
                        <span>Total Payable</span>
                        <strong data-checkout-total>₹0</strong>
                    </div>
                    <small data-checkout-delivery-note></small>
                </div>

                <div class="checkout-assurance">
                    <div>
                        <i class="ph ph-lock-key"></i>
                        <span><strong>Encrypted Checkout</strong>100% server verified</span>
                    </div>
                    <div>
                        <i class="ph ph-truck"></i>
                        <span><strong>Fast Dispatch</strong>Express shipping across India</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="assets/js/checkout.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
