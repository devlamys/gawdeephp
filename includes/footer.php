<?php declare(strict_types=1); ?>
</main>

<footer class="commerce-footer" id="site-footer">
    <div class="commerce-footer__watermark" aria-hidden="true"></div>
    <div class="container commerce-footer__grid">
        <div class="commerce-footer__brand">
            <a href="index" aria-label="Gawdee home"><img src="assets/images/logo.png"
                    alt="Gawdee — The Soul of Wellness"></a>
            <p>Bringing natural goodness to your daily life. Pure. Authentic. Organic food Thoughtfully crafted in
                India.</p>
            <div class="commerce-socials">
                <a href="https://www.instagram.com/gawdee_organic/" target="_blank" rel="noopener"
                    aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
                <a href="https://www.facebook.com/GawdeeOrganic/" target="_blank" rel="noopener"
                    aria-label="Facebook"><i class="ph ph-facebook-logo"></i></a>
                <a href="https://www.youtube.com/@GawdeeOrganic" target="_blank" rel="noopener" aria-label="YouTube"><i
                        class="ph ph-youtube-logo"></i></a>
            </div>
        </div>
        <div class="commerce-footer__links">
            <h2>Quick Links</h2>
            <a href="products">Our Products</a>
            <a href="index#offers">Special Offers</a>
            <a href="blog">Wellness Journal</a>
            <a href="mailto:<?= htmlspecialchars(gawdee_setting('store_email', 'info@gawdee.com')) ?>">Contact Us</a>
        </div>
        <div class="commerce-footer__links">
            <h2>Customer Service</h2>
            <a href="https://gawdee.com" target="_blank" rel="noopener">Shipping Policy</a>
            <a href="https://gawdee.com" target="_blank" rel="noopener">Return Policy</a>
            <a href="https://gawdee.com" target="_blank" rel="noopener">Terms &amp; Conditions</a>
            <a href="https://gawdee.com" target="_blank" rel="noopener">Privacy Policy</a>
            <a href="https://gawdee.com" target="_blank" rel="noopener">FAQs</a>
        </div>
        <div class="commerce-footer__links">
            <h2>Categories</h2>
            <a href="products?category=ghee">Ghee</a>
            <a href="products?category=honey">Honey</a>
            <a href="products?category=wellness">Drops</a>
            <a href="products?category=nutrition">Mix Me</a>
            <a href="products?category=sugar">Sugar</a>
        </div>
        <div class="commerce-footer__signup">
            <h2>Stay Updated</h2>
            <p>Subscribe for wellness tips, seasonal harvests, and exclusive offers.</p>
            <form class="footer-mini-form" action="#" data-newsletter-form>
                <label class="sr-only" for="footer-email">Email address</label>
                <input id="footer-email" type="email" placeholder="Enter your email" required>
                <button type="submit" aria-label="Subscribe"><i class="ph ph-paper-plane-tilt"></i></button>
            </form>
        </div>
    </div>
    <div class="container commerce-footer__bottom">
        <p>© <?= date('Y') ?> Gawdee. All rights reserved. Thoughtfully crafted in India.</p>
        <div class="payment-pills">
            <span>VISA</span>
            <span>Mastercard</span>
            <span>UPI</span>
            <span>Paytm</span>
        </div>
        <p><i class="ph ph-shield-check"></i> 100% Secure Payments</p>
    </div>
</footer>

<?php if (gawdee_setting('ai_chat_enabled', '1') === '1'): ?>
    <button class="ai-float" type="button" data-ai-toggle aria-label="Open Gawdee AI wellness assistant"
        aria-expanded="false">
        <span class="ai-float__orb"><img src="assets/images/gawdee-ai-robot-v1.png" alt="" aria-hidden="true"></span>
        <!-- <span class="ai-float__online" aria-hidden="true"></span> -->
    </button>
    <aside class="ai-chat" data-ai-chat aria-hidden="true" aria-labelledby="ai-chat-title">
        <header>
            <span class="ai-chat__mark"><img src="assets/images/gawdee-ai-robot-v1.png" alt="" aria-hidden="true"></span>
            <div><strong id="ai-chat-title">Ask Gawdee AI</strong><small><span></span> Online ·
                    <?= htmlspecialchars(ucfirst(gawdee_setting('ai_provider', 'groq'))) ?> powered</small></div>
            <button type="button" data-ai-close aria-label="Close assistant"><i class="ph ph-x"></i></button>
        </header>
        <?php if (gawdee_setting('offer_popup_enabled', '1') === '1'): ?>
            <?php
            $aiOfferCode = gawdee_setting('offer_code', 'FREEDOM10');
            $aiOfferPercent = gawdee_setting('offer_percent', '10');
            $aiOfferTitle = gawdee_setting('offer_popup_title', 'Independence Day Special');
            $aiOfferImage = gawdee_setting('offer_popup_image', 'assets/images/hero-slide-independence-v5.webp');
            $aiOfferLink = gawdee_setting('offer_popup_link', 'index#offers');
            ?>
            <article class="ai-offer-card" aria-label="Latest offer">
                <a class="ai-offer-card__media" href="<?= htmlspecialchars($aiOfferLink) ?>" aria-label="View offer">
                    <img src="<?= htmlspecialchars($aiOfferImage) ?>" alt="<?= htmlspecialchars($aiOfferTitle) ?>"
                        loading="lazy">
                    <span><i class="ph ph-sparkle"></i> Special offer</span>
                </a>
                <div class="ai-offer-card__body">
                    <div><small><?= htmlspecialchars($aiOfferTitle) ?></small><strong>Flat
                            <?= htmlspecialchars($aiOfferPercent) ?>% OFF <em>on all products</em></strong></div>
                    <?php if (!empty($aiOfferCode)): ?>
                        <button type="button" data-copy-offer="<?= htmlspecialchars($aiOfferCode) ?>"
                            aria-label="Copy offer code <?= htmlspecialchars($aiOfferCode) ?>"><strong><?= htmlspecialchars($aiOfferCode) ?></strong><span><i
                                    class="ph ph-copy"></i> Copy</span></button>
                    <?php endif; ?>
                </div>
            </article>
        <?php endif; ?>
        <div class="ai-chat__messages" data-ai-messages>
            <div class="ai-message ai-message--assistant">Namaste! How can I assist your wellness choices today?</div>
        </div>
        <div class="ai-chat__suggestions"><button type="button"
                data-ai-suggestion="Which Gawdee products are best for an everyday family pantry?"><i
                    class="ph ph-house-line"></i> Family pantry</button><button type="button"
                data-ai-suggestion="Tell me about Gawdee A2 Gir Cow Ghee."><i class="ph ph-bowl-steam"></i> A2
                Ghee</button><button type="button" data-ai-suggestion="How does delivery work?"><i class="ph ph-truck"></i>
                Delivery</button></div>
        <form data-ai-form><label class="sr-only" for="ai-question">Ask Gawdee AI</label><input id="ai-question"
                maxlength="700" placeholder="Ask Gawdee anything…" autocomplete="off" required><button type="submit"
                aria-label="Send message"><i class="ph ph-arrow-up"></i></button></form>
        <p><i class="ph ph-info"></i> AI guidance may vary. Always review product labels.</p>
    </aside>
<?php endif; ?>

<div class="variants-drawer-backdrop" data-variants-backdrop
    style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 10000; opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 0.3s ease;">
</div>
<div class="variants-drawer" data-variants-drawer
    style="position: fixed; bottom: 0; left: 0; width: 100%; background: var(--gawdee-bg, #fff); z-index: 10001; border-top-left-radius: 20px; border-top-right-radius: 20px; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); max-height: 85vh; display: flex; flex-direction: column; box-shadow: 0 -4px 20px rgba(0,0,0,0.1);">
    <div
        style="padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color, #eee);">
        <h3 data-variants-title
            style="margin: 0; font-size: 1.25rem; color: var(--gawdee-primary-dark); display: flex; align-items: center; gap: 0.5rem;">
            <i class="ph ph-shopping-cart"></i> <span>Product Name</span>
        </h3>
        <button type="button" data-variants-close
            style="background: var(--surface-2, #f5f5f5); border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-color);"><i
                class="ph ph-x"></i></button>
    </div>
    <div data-variants-list
        style="padding: 1rem 1.5rem; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 1rem;">
        <!-- Variants injected via JS -->
    </div>
</div>

<?php
$waNumber = preg_replace('/\D+/', '', gawdee_setting('whatsapp_number', '917055207030'));
$waText = "Hi Gawdee Team! 👋 I’m interested in Gawdee products and would like to know more. Please assist me.";
$waUrl = 'https://wa.me/' . $waNumber . '?text=' . rawurlencode($waText);
?>
<a class="whatsapp-float" href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener"
    aria-label="Chat with Gawdee on WhatsApp"><i class="ph ph-whatsapp-logo"></i></a>
<div class="gawdee-toast" data-toast role="status" aria-live="polite"></div>

<!-- Sticky Checkout Bar -->
<div class="checkout-sticky-bar" data-checkout-sticky
    style="display: none; position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); width: calc(100% - 40px); max-width: 400px; background: var(--gawdee-primary-dark); color: #fff; border-radius: 99px; padding: 0.6rem 1rem; z-index: 9999; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
    <div style="display: flex; align-items: center; gap: 0.8rem;">
        <div
            style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; background: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <img data-sticky-img src="" alt="Cart Item"
                style="width: 100%; height: 100%; object-fit: contain; padding: 4px;">
        </div>
        <div style="display: flex; flex-direction: column;">
            <span style="font-size: 0.8rem; font-weight: 500; opacity: 0.9;" data-sticky-count>1 item</span>
            <strong style="font-size: 1.15rem; line-height: 1.2; font-weight: 700;" data-sticky-price>₹0</strong>
        </div>
    </div>
    <button type="button" data-cart-toggle
        style="background: #17211e; color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 99px; font-weight: 700; display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
        <i class="ph ph-shopping-cart" style="font-size: 1.2rem;"></i> <i class="ph ph-arrow-right"
            style="font-size: 1rem;"></i>
    </button>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js" defer></script>
<script src="assets/js/app.js" defer></script>
</body>

</html>