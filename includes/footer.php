<?php declare(strict_types=1); ?>
</main>

<section class="commerce-assurance" aria-label="Gawdee assurances">
    <div class="container commerce-assurance__grid">
        <div><i class="ph ph-hand-heart"></i><span><strong>Trusted organic brand</strong>Ingredient-first approach</span></div>
        <div><i class="ph ph-users-three"></i><span><strong>Family focused</strong>Made for daily routines</span></div>
        <div><i class="ph ph-seal-check"></i><span><strong>Clear product details</strong>Honest weights and prices</span></div>
        <div><i class="ph ph-flag"></i><span><strong>Made in India</strong>With care and purpose</span></div>
        <div><i class="ph ph-butterfly"></i><span><strong>Sustainable thinking</strong>Mindful and ethical</span></div>
    </div>
</section>

<footer class="commerce-footer" id="site-footer">
    <div class="container commerce-footer__grid">
        <div class="commerce-footer__brand">
            <img src="assets/images/logo.png" alt="Gawdee">
            <p>Bringing natural goodness to your daily life. Pure. Authentic. Organic.</p>
            <div class="commerce-socials">
                <a href="https://www.instagram.com/gawdee_organic/" target="_blank" rel="noopener" aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
                <a href="https://www.facebook.com/GawdeeOrganic/" target="_blank" rel="noopener" aria-label="Facebook"><i class="ph ph-facebook-logo"></i></a>
                <a href="https://www.youtube.com/@GawdeeOrganic" target="_blank" rel="noopener" aria-label="YouTube"><i class="ph ph-youtube-logo"></i></a>
            </div>
        </div>
        <div class="commerce-footer__links"><h2>Shop</h2><a href="products.php">All Products</a><a href="products.php?category=ghee">A2 Ghee</a><a href="products.php?category=honey">Honey</a><a href="products.php?category=nutrition">Millets &amp; Nutrition</a><a href="products.php?category=wellness">Wellness</a></div>
        <div class="commerce-footer__links"><h2>Company</h2><a href="index.php#farms">Our Farms</a><a href="index.php#about">Our Story</a><a href="index.php#about">Sustainability</a><a href="blog.php">Our Journal</a><a href="mailto:<?= htmlspecialchars(gawdee_setting('store_email', 'info@gawdee.com')) ?>">Contact</a></div>
        <div class="commerce-footer__links"><h2>Help</h2><a href="account.php">My Account</a><a href="checkout.php">Shipping &amp; Delivery</a><a href="account.php">Track Order</a><a href="https://gawdee.com" target="_blank" rel="noopener">Returns</a><a href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D+/', '', gawdee_setting('whatsapp_number', '917055207030'))) ?>" target="_blank" rel="noopener">Customer Support</a></div>
        <div class="commerce-footer__links"><h2>Policies</h2><a href="https://gawdee.com" target="_blank" rel="noopener">Privacy Policy</a><a href="https://gawdee.com" target="_blank" rel="noopener">Terms Policy</a><a href="https://gawdee.com" target="_blank" rel="noopener">Refunds &amp; Returns</a><a href="https://gawdee.com" target="_blank" rel="noopener">Cookie Policy</a></div>
        <div class="commerce-footer__app"><h2>Download Our App</h2><div class="commerce-footer__app-row"><div><span><i class="ph ph-google-play-logo"></i><small>Get it on</small><strong>Google Play</strong></span><span><i class="ph ph-apple-logo"></i><small>Download on the</small><strong>App Store</strong></span></div><i class="ph ph-qr-code commerce-footer__qr" aria-hidden="true"></i></div></div>
    </div>
    <div class="container commerce-footer__bottom"><p>© <?= date('Y') ?> Gawdee. All rights reserved.</p><div class="commerce-footer__service"><span><i class="ph ph-shield-check"></i><b>Secure Payments</b>100% Protected</span><span><i class="ph ph-truck"></i><b>Free Shipping</b>On Orders ₹<?= number_format((int) gawdee_setting('free_shipping_threshold', '999')) ?>+</span><span><i class="ph ph-arrow-counter-clockwise"></i><b>Easy Returns</b>Hassle Free</span><span><i class="ph ph-headset"></i><b>Customer Support</b>We're Here to Help</span></div></div>
</footer>

<?php if (gawdee_setting('ai_chat_enabled', '1') === '1'): ?>
<button class="ai-float" type="button" data-ai-toggle aria-label="Open Gawdee AI wellness assistant" aria-expanded="false">
    <span class="ai-float__orb"><img src="assets/images/gawdee-ai-robot-v1.png" alt="" aria-hidden="true"></span>
    <span class="ai-float__online" aria-hidden="true"></span>
</button>
<aside class="ai-chat" data-ai-chat aria-hidden="true" aria-labelledby="ai-chat-title">
    <header>
        <span class="ai-chat__mark"><img src="assets/images/gawdee-ai-robot-v1.png" alt="" aria-hidden="true"></span>
        <div><strong id="ai-chat-title">Ask Gawdee</strong><small><span></span> Online · <?= htmlspecialchars(ucfirst(gawdee_setting('ai_provider', 'groq'))) ?> powered</small></div>
        <button type="button" data-ai-close aria-label="Close assistant"><i class="ph ph-x"></i></button>
    </header>
    <article class="ai-offer-card" aria-label="Latest Independence Day offer">
        <a class="ai-offer-card__media" href="index.php#offer" aria-label="View the Independence Day offer">
            <img src="assets/images/hero-slide-independence-v5.webp" alt="Happy Independence Day Gawdee wellness collection" loading="lazy">
            <span><i class="ph ph-sparkle"></i> Latest offer</span>
        </a>
        <div class="ai-offer-card__body">
            <div><small>Happy Independence Day</small><strong>Flat 10% OFF <em>on all products</em></strong></div>
            <button type="button" data-copy-offer="<?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?>" aria-label="Copy offer code <?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?>"><strong><?= htmlspecialchars(gawdee_setting('offer_code', 'FREEDOM10')) ?></strong><span><i class="ph ph-copy"></i> Copy</span></button>
        </div>
    </article>
    <div class="ai-chat__messages" data-ai-messages><div class="ai-message ai-message--assistant">Namaste! What can I help you find today?</div></div>
    <div class="ai-chat__suggestions"><button type="button" data-ai-suggestion="Which Gawdee products are best for an everyday family pantry?"><i class="ph ph-house-line"></i> Family pantry</button><button type="button" data-ai-suggestion="Tell me about Gawdee A2 Gir Cow Ghee."><i class="ph ph-bowl-steam"></i> A2 Ghee</button><button type="button" data-ai-suggestion="How does delivery work?"><i class="ph ph-truck"></i> Delivery</button></div>
    <form data-ai-form><label class="sr-only" for="ai-question">Ask Gawdee AI</label><input id="ai-question" maxlength="700" placeholder="Ask Gawdee anything…" autocomplete="off" required><button type="submit" aria-label="Send message"><i class="ph ph-arrow-up"></i></button></form>
    <p><i class="ph ph-info"></i> AI guidance may vary. Always review product labels.</p>
</aside>
<?php endif; ?>
<a class="whatsapp-float" href="https://wa.me/<?= htmlspecialchars(preg_replace('/\D+/', '', gawdee_setting('whatsapp_number', '917055207030'))) ?>" target="_blank" rel="noopener" aria-label="Chat with Gawdee on WhatsApp"><i class="ph ph-whatsapp-logo"></i></a>
<div class="toast" role="status" aria-live="polite" data-toast></div>
<script src="assets/js/app.js" defer></script>
</body>
</html>
