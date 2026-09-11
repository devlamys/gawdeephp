<?php
declare(strict_types=1);
/**
 * Gawdee animated hero — product spotlight ported from the gawdee980 reference
 * (Style 01: split cream/sage stage, 3D GSAP carousel, glass badges, marquee).
 *
 * Dynamic source: hero_banners_two table via gawdee_hero_banners_two().
 * Admin: ?view=banners_two ("Animated hero"). Cutout = background-removed PNG/WebP.
 */

$gxSanitizeTitle = static function (string $raw): string {
    $raw = trim($raw);
    if ($raw === '') {
        return '';
    }
    $escaped = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    $escaped = preg_replace('/&lt;(br\s*\/?)&gt;/i', '<br>', $escaped) ?? $escaped;
    $escaped = preg_replace('/&lt;(\/?span)&gt;/i', '<$1>', $escaped) ?? $escaped;
    return $escaped;
};

$gxMapRow = static function (array $row) use ($gxSanitizeTitle): array {
    $titleHtml = trim((string) ($row['title_html'] ?? ($row['headline'] ?? '')));
    if ($titleHtml === '') {
        $titleHtml = htmlspecialchars((string) ($row['title'] ?? 'Gawdee'));
    } else {
        $titleHtml = $gxSanitizeTitle($titleHtml);
    }
    $word = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($row['word'] ?? '')));
    if ($word === '') {
        $word = 'GAWDEE';
    }
    return [
        'cat' => trim((string) ($row['cat'] ?? ($row['eyebrow'] ?? ''))) ?: 'Gawdee • Pure by Nature',
        'title' => $titleHtml,
        'word' => $word,
        'sub' => trim((string) ($row['sub'] ?? ($row['subtitle'] ?? ''))),
        'priceLabel' => trim((string) ($row['price_label'] ?? '')),
        'mrpLabel' => trim((string) ($row['mrp_label'] ?? '')),
        'off' => trim((string) ($row['off_badge'] ?? '')),
        'reviews' => trim((string) ($row['reviews_label'] ?? '')),
        'img' => trim((string) ($row['product_image'] ?? ($row['desktop_video'] ?? ''))),
        'alt' => trim((string) ($row['alt_text'] ?? ($row['title'] ?? 'Gawdee product'))),
        'url' => trim((string) ($row['link_url'] ?? '#shop')) ?: '#shop',
        'cartId' => trim((string) ($row['cart_id'] ?? '')),
        'cartName' => trim((string) ($row['cart_name'] ?? ($row['title'] ?? 'Gawdee product'))),
        'cartPrice' => max(0, (int) ($row['cart_price'] ?? 0)),
        'cartImage' => trim((string) ($row['cart_image'] ?? '')),
    ];
};

$gxHeroSlides = [];
try {
    foreach (gawdee_hero_banners_two() as $gxRow) {
        $gxMapped = $gxMapRow($gxRow);
        // Skip legacy video-only rows that were never migrated to cutouts.
        if ($gxMapped['img'] === '' || preg_match('/\.(mp4|webm|mov|ogv|mkv|avi)(\?.*)?$/i', $gxMapped['img'])) {
            continue;
        }
        $gxHeroSlides[] = $gxMapped;
    }
} catch (Throwable $gxHeroError) {
    $gxHeroSlides = [];
}

if ($gxHeroSlides === []) {
    $gxFallback = [
        ['A2 Gir Cow Ghee', 'A2 Vedic • Grass-Fed', 'A2 Vedic<br><span>Gir Cow Ghee</span>', 'GHEE', 'Pure & Healthy Gir Cow A2 Ghee hand-churned using traditional Bilona method. Nutty, aromatic & nourishing.', '₹891', '₹1,049', 'Save 15%', '4.9 — 2,340 rituals', 'assets/images/Banners/IMG_1438.PNG', 'GAWDEE Pure Gir Cow A2 Bilona Ghee Jar', 'product?slug=gawdee-gir-cow-a2-ghee-500-ml', 'ghee-500', 'Gawdee Gir Cow A2 Ghee 500ml', 891, 'assets/images/products/ghee-500.webp'],
        ['MixMe Vanilla', '100% Natural • Homemade Taste', 'MixMe Powder<br><span>Vanilla Flavour</span>', 'MIXME', 'Nutritive food powder for kids (2+ yrs) & adults. Packed with wholesome grains, nuts & dates.', '₹759', '₹799', 'Save 5%', '4.8 — 1,870 rituals', 'assets/images/Banners/IMG_1439.PNG', 'GAWDEE MixMe Nutritive Food Powder Vanilla Flavour Pouch', 'product?slug=gawdee-mixme-vanilla-500-g', 'mixme-vanilla', 'Gawdee MixMe — Vanilla 500g', 759, 'assets/uploads/products/products-fb1c5b2c7521f2a1f5.png'],
        ['MixMe Elaichi', '100% Natural • Homemade Taste', 'MixMe Powder<br><span>Cardamom Flavour</span>', 'MIXME', 'Nutritive food powder blend with soothing Cardamom flavour for everyday family nutrition.', '₹759', '₹799', 'Save 5%', '4.9 — 2,110 rituals', 'assets/images/Banners/IMG_1441 (1).PNG', 'GAWDEE MixMe Nutritive Food Powder Cardamom Flavour Pouch', 'product?slug=gawdee-mixme-elaichi-500-g', 'mixme-elaichi', 'Gawdee MixMe — Elaichi 500g', 759, 'assets/images/products/mixme-elaichi.webp'],
        ['Burra Sugar', 'Traditional • Unrefined Sweetness', 'Burra Sugar<br><span>Khandsari 1kg</span>', 'BURRA', 'Naturally processed khandsari sugar. Fine crystals for tea, milk, sweets & halwa.', '₹159', '₹199', 'Save 20%', '4.9 — 3,102 rituals', 'assets/images/Banners/IMG_1442 (1).PNG', 'GAWDEE Burra Khandsari Sugar 1kg Pouch', 'product?slug=gawdee-bura-sugar-1-kg', 'burra-sugar', 'Gawdee Burra Sugar 1kg', 159, 'assets/images/products/burra-sugar.webp'],
        ['Taral Drop', 'Authentic Nasya • Belly Button Drops', 'Taral Drop<br><span>(Nasya) 30ml</span>', 'TARAL', 'Authentic organic nutrition drops for nose & belly button. Boosts clarity, breath & natural wellness.', '₹209', '₹299', 'Save 30%', '4.7 — 940 rituals', 'assets/images/Banners/IMG_1447 (1).PNG', 'GAWDEE Taral Drop Nasya Bottle 30ml', 'product?slug=gawdee-taral-drop-30-ml', 'taral-drop', 'Gawdee Taral Drop 30ml', 209, 'assets/images/products/taral-drop.webp'],
    ];
    foreach ($gxFallback as $gxF) {
        $gxHeroSlides[] = [
            'cat' => $gxF[1], 'title' => $gxF[2], 'word' => $gxF[3], 'sub' => $gxF[4],
            'priceLabel' => $gxF[5], 'mrpLabel' => $gxF[6], 'off' => $gxF[7], 'reviews' => $gxF[8],
            'img' => $gxF[9], 'alt' => $gxF[10], 'url' => $gxF[11], 'cartId' => $gxF[12],
            'cartName' => $gxF[13], 'cartPrice' => $gxF[14], 'cartImage' => $gxF[15],
        ];
    }
}

$gxFirst = $gxHeroSlides[0];
$gxCssV = (int) @filemtime(__DIR__ . '/../assets/css/hero-animated.css');
$gxJsV = (int) @filemtime(__DIR__ . '/../assets/js/hero-animated.js');
$gxNext = $gxHeroSlides[1] ?? $gxFirst;
$gxMarquee = [];
foreach ($gxHeroSlides as $gxSlide) {
    $gxMarquee[] = $gxSlide['word'] === 'MIXME' ? 'MixMe Nutritive Blend' : ucfirst(strtolower($gxSlide['word']));
}
$gxMarquee[] = 'No Refined Sugar';
$gxMarquee[] = 'Farm Fresh Purity';
?>
<link rel="stylesheet" href="assets/css/hero-animated.css?v=<?= $gxCssV ?>">
<?php if ($gxFirst['img'] !== '' && !str_starts_with($gxFirst['img'], 'data:')): ?>
<link rel="preload" as="image" href="<?= htmlspecialchars($gxFirst['img']) ?>" fetchpriority="high">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet"></noscript>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
<script type="application/json"
    id="gxHeroData"><?= json_encode($gxHeroSlides, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<section class="gx-hero grain" aria-label="Featured organic products">
    <div class="gx-hero__bg" aria-hidden="true">
        <div class="gx-hero__bg-left"></div>
        <div class="gx-hero__bg-right"></div>
    </div>
    <div class="gx-hero__glow gx-hero__glow--left" aria-hidden="true"></div>
    <div class="gx-hero__glow gx-hero__glow--right" aria-hidden="true"></div>

    <div class="gx-hero__grid">
        <div class="gx-hero__copy">
            <div class="gx-hero__kicker gx-intro">
                <span class="gx-hero__kicker-line" aria-hidden="true"></span>
                <span class="gx-hero__pill" id="gxCat"><?= htmlspecialchars($gxFirst['cat']) ?></span>
                <span class="gx-hero__count" id="gxCount">01 — <?= str_pad((string) count($gxHeroSlides), 2, '0', STR_PAD_LEFT) ?></span>
            </div>

            <div id="gxText" aria-live="polite">
                <h1 class="gx-hero__title" id="gxTitle"><?= $gxFirst['title'] ?></h1>
                <p class="gx-hero__sub" id="gxSub"><?= htmlspecialchars($gxFirst['sub']) ?></p>
                <div class="gx-hero__rating">
                    <span class="gx-hero__stars" aria-label="Rated 4.9 out of 5 stars">★★★★★</span>
                    <span class="gx-hero__reviews" id="gxReviews"><?= htmlspecialchars($gxFirst['reviews']) ?></span>
                </div>
                <div class="gx-hero__price-row">
                    <span class="gx-hero__price" id="gxPrice"><?= htmlspecialchars($gxFirst['priceLabel']) ?></span>
                    <span class="gx-hero__mrp" id="gxMrp"><?= htmlspecialchars($gxFirst['mrpLabel']) ?></span>
                    <span class="gx-hero__off" id="gxOff"><?= htmlspecialchars($gxFirst['off']) ?></span>
                </div>
            </div>

            <div class="gx-hero__cta gx-intro">
                <a class="gx-btn-lux" id="gxShopBtn" href="<?= htmlspecialchars($gxFirst['url']) ?>">
                    Shop Now <span class="gx-btn-lux__arrow" aria-hidden="true">→</span>
                </a>
                <button type="button" class="gx-btn-cart" id="gxCartBtn" data-add-to-cart
                    data-id="<?= htmlspecialchars($gxFirst['cartId']) ?>"
                    data-name="<?= htmlspecialchars($gxFirst['cartName']) ?>"
                    data-price="<?= (int) $gxFirst['cartPrice'] ?>"
                    data-image="<?= htmlspecialchars($gxFirst['cartImage']) ?>">
                    Add to Cart • <span class="gx-mini"><?= htmlspecialchars($gxFirst['priceLabel']) ?></span>
                </button>
            </div>

            <div class="gx-hero__controls gx-intro">
                <div class="gx-hero__arrows">
                    <button type="button" class="gx-arrow gx-arrow--ghost" id="gxPrev"
                        aria-label="Previous product">←</button>
                    <button type="button" class="gx-arrow gx-arrow--solid" id="gxNext"
                        aria-label="Next product">→</button>
                </div>
                <div class="gx-hero__dots" id="gxDots" role="tablist" aria-label="Choose a product"></div>
                <div class="gx-hero__progress" aria-hidden="true">
                    <div id="gxProgressBar"></div>
                </div>
            </div>

            <div class="gx-hero__trust gx-intro">
                <span>✦ Free shipping over ₹999</span>
                <span>✦ Lab-tested purity</span>
                <span>✦ COD available</span>
            </div>
        </div>

        <div class="gx-hero__stage-wrap">
            <div class="gx-hero__next gx-glass">
                <small>Next up</small>
                <strong id="gxNextName"><?= htmlspecialchars(ucfirst(strtolower($gxNext['word']))) ?></strong>
            </div>
            <div class="gx-stage" id="gxStage">
                <div class="gx-stage__ring" aria-hidden="true"></div>
                <div class="gx-stage__halo" aria-hidden="true"></div>
                <div class="gx-stage__circle" aria-hidden="true"></div>
                <div class="gx-slide" data-gx-static>
                    <img src="<?= htmlspecialchars($gxFirst['img']) ?>"
                        alt="<?= htmlspecialchars($gxFirst['alt'] !== '' ? $gxFirst['alt'] : $gxFirst['cartName']) ?>"
                        fetchpriority="high" decoding="async">
                </div>
                <div class="gx-shadow" id="gxShadow" aria-hidden="true"></div>
            </div>
            <div class="gx-hero__badge gx-glass">
                <b id="gxIndex">01</b>
                <span class="gx-hero__badge-sep" aria-hidden="true"></span>
                <small>Organic<br>Certified</small>
            </div>
        </div>
    </div>

    <div class="gx-hero__marquee" aria-hidden="true">
        <div class="gx-marquee__viewport">
            <div class="gx-marquee__track">
                <?php foreach (array_merge($gxMarquee, $gxMarquee) as $gxWord): ?><span>✦ <?= htmlspecialchars($gxWord) ?></span><?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<script src="assets/js/hero-animated.js?v=<?= $gxJsV ?>" defer></script>
