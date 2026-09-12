<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$pageTitle = 'Gawdee — Authentic organic food for everyday wellness';
$pageDescription = 'Shop Gawdee A2 Gir cow ghee, raw honey, natural nutrition blends and traditional pantry essentials.';
$pageKeywords = 'Gawdee, Gawdee organic, A2 Gir cow ghee, raw wild forest honey, MixMe nutrition, Taral drops, organic food store India, pure ghee, natural sweeteners';
$bodyClass = 'commerce-home';

$categories = gawdee_categories();

$pickProduct = static function (string $slug) use ($products): ?array {
    $found = product_by_slug($products, $slug);
    if ($found) {
        return $found;
    }
    return $products[0] ?? null;
};

$comboCandidates = [
    ['tag' => 'Wellness Combo', 'title' => 'A2 Ghee 500ml + Honey 650g', 'slugs' => ['gawdee-gir-cow-a2-ghee-500-ml', 'gawdee-raw-wild-forest-honey-650-g'], 'price' => 1499, 'original' => 1748],
    ['tag' => 'Energy Combo', 'title' => 'MixMe Choco + Raw Honey', 'slugs' => ['gawdee-mixme-choco-500-g', 'gawdee-raw-wild-forest-honey-650-g'], 'price' => 1299, 'original' => 1458],
    ['tag' => 'Immunity Combo', 'title' => 'Taral Drop + Moringa Powder', 'slugs' => ['gawdee-taral-drop-30-ml', 'gawdee-moringa-powder-300-g'], 'price' => 399, 'original' => 558],
    ['tag' => 'Daily Nutrition', 'title' => 'MixMe Elaichi + A2 Ghee', 'slugs' => ['gawdee-mixme-elaichi-500-g', 'gawdee-gir-cow-a2-ghee-500-ml'], 'price' => 1549, 'original' => 1808],
];
$combos = [];
foreach ($comboCandidates as $candidate) {
    $items = [];
    foreach ($candidate['slugs'] as $slug) {
        $prod = $pickProduct($slug);
        if ($prod) {
            $items[] = $prod;
        }
    }
    if (count($items) === count($candidate['slugs'])) {
        $combos[] = ['tag' => $candidate['tag'], 'title' => $candidate['title'], 'items' => $items, 'price' => $candidate['price'], 'original' => $candidate['original']];
    }
}

$featuredSlugs = [
    'gawdee-mixme-choco-500-g',
    'gawdee-mixme-elaichi-500-g',
    'gawdee-taral-drop-30-ml',
    'gawdee-white-sugar-1kg',
    'gawdee-gir-cow-a2-ghee-500-ml',
    'gawdee-raw-wild-forest-honey-650-g',
];
$featuredProducts = [];
foreach ($featuredSlugs as $slug) {
    $prod = $pickProduct($slug);
    if ($prod && !in_array($prod['slug'] ?? '', array_column($featuredProducts, 'slug'), true)) {
        $featuredProducts[] = $prod;
    }
}
if (!$featuredProducts) {
    $featuredProducts = array_slice($products, 0, 6);
} else {
    // Top up with catalogue rows when some slugs are missing (e.g. renamed items).
    foreach ($products as $prod) {
        if (count($featuredProducts) >= 6) {
            break;
        }
        if (!in_array($prod['slug'] ?? '', array_column($featuredProducts, 'slug'), true)) {
            $featuredProducts[] = $prod;
        }
    }
}

$testimonialFavourites = [
    'honey' => $pickProduct('gawdee-raw-wild-forest-honey-650-g') ?? $featuredProducts[0] ?? null,
    'ghee' => $pickProduct('gawdee-gir-cow-a2-ghee-500-ml') ?? $featuredProducts[0] ?? null,
    'mixme' => $pickProduct('gawdee-mixme-choco-500-g') ?? $featuredProducts[0] ?? null,
    'moringa' => $pickProduct('gawdee-moringa-powder-300-g') ?? $featuredProducts[0] ?? null,
];
$testimonialFavourites = array_filter($testimonialFavourites);

$testimonials = [];
foreach (gawdee_testimonials() as $story) {
    $relatedProduct = product_by_slug($products, (string) $story['product_slug']) ?? $featuredProducts[0] ?? $products[0] ?? null;
    if (!$relatedProduct) {
        continue;
    }
    $testimonials[] = [
        'name' => $story['name'],
        'initials' => $story['initials'],
        'avatar' => $story['avatar'],
        'product' => $story['product_name'] ?: $relatedProduct['name'],
        'quote' => $story['quote'],
        'image' => $relatedProduct['image'],
        'slug' => $relatedProduct['slug'],
        'theme' => $story['theme'],
        'rating' => $story['rating'],
    ];
}
$testimonialDeck = array_merge($testimonials, array_slice($testimonials, 0, min(2, count($testimonials))));

$blogCovers = [
    'assets/images/blogs/small-daily-improvements-v1.webp',
    'assets/images/blogs/gradual-better-eating-v1.webp',
    'assets/images/blogs/modern-food-choices-v1.webp',
    'assets/images/blogs/quality-over-quantity-v1.webp',
];
$reelProducts = array_values(array_filter([
    $pickProduct('gawdee-gir-cow-a2-ghee-500-ml'),
    $pickProduct('gawdee-raw-wild-forest-honey-650-g'),
    $pickProduct('gawdee-mixme-choco-500-g'),
]));
if (!$reelProducts) {
    $reelProducts = array_slice($featuredProducts, 0, 3);
}
$homepageSections = gawdee_sections();
$homepageBanners = gawdee_banners();
$homepageReels = gawdee_homepage_media('reels', false, true);
if (empty($homepageReels)) {
    $homepageReels = gawdee_homepage_media('reels', false);
}
$publishedPosts = gawdee_db()->query("SELECT * FROM blog_posts WHERE status='published' ORDER BY is_featured DESC, COALESCE(published_at, created_at) DESC LIMIT 5")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<?php
// Animated product-spotlight hero (dynamic via Admin > Animated hero).
// Replaces the retired scroll-video hero (hero_scrub_* settings no longer used).
require __DIR__ . '/includes/hero-animated.php';
?>

<section class="trust-strip reveal" aria-label="Shopping benefits">
    <div class="container trust-strip__inner">
        <div class="trust-item"><i class="ph ph-leaf"></i><span><strong>100% Authentic</strong>Carefully chosen
                products</span></div>
        <div class="trust-item"><i class="ph ph-truck"></i><span><strong>Free Shipping</strong>On orders above
                ₹999</span></div>
        <div class="trust-item"><i class="ph ph-wallet"></i><span><strong>Secure Payments</strong>Safe and
                protected</span></div>
        <div class="trust-item"><i class="ph ph-clock-counter-clockwise"></i><span><strong>Easy Support</strong>Helpful
                customer care</span></div>
        <div class="trust-item"><i class="ph ph-headset"></i><span><strong>Customer Support</strong>Questions are
                welcome</span></div>
    </div>
</section>

<?php
foreach ($homepageSections as $sectionKey => $section) {
    if (!($section['is_active'] ?? 1)) {
        continue;
    }
    switch ($sectionKey) {
        case 'shop':
            ?>
            <section class="commerce-section" id="shop">
                <div class="container">
                    <div class="commerce-section__heading reveal">
                        <div>
                            <span class="eyebrow"><i class="ph ph-fire"></i>
                                <?= htmlspecialchars($homepageSections['shop']['eyebrow'] ?: 'Bestsellers') ?></span>
                            <h2><?= htmlspecialchars($homepageSections['shop']['title']) ?></h2>
                            <p><?= htmlspecialchars($homepageSections['shop']['subtitle']) ?></p>
                        </div>
                        <div class="commerce-section__actions">
                            <a class="text-link"
                                href="<?= htmlspecialchars($homepageSections['shop']['button_url'] ?: 'products') ?>"><?= htmlspecialchars($homepageSections['shop']['button_label'] ?: 'View all products') ?>
                                <i class="ph ph-arrow-right"></i></a>
                            <div class="section-rail-controls home-slider-controls" aria-label="Bestseller slider controls">
                                <button type="button" data-scroll-rail="#home-product-rail" data-scroll-direction="-1"
                                    aria-label="Previous products"><i class="ph ph-arrow-left"></i></button>
                                <button type="button" data-scroll-rail="#home-product-rail" data-scroll-direction="1"
                                    aria-label="Next products"><i class="ph ph-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="compact-product-grid home-product-rail" id="home-product-rail" data-product-grid
                        aria-label="Bestselling products">
                        <?php foreach ($featuredProducts as $index => $product):
                            $homeImage = (string) ($product['image'] ?? 'assets/images/logo.png');
                            if ($homeImage === '') {
                                $homeImage = 'assets/images/logo.png';
                            }
                            ?>
                            <article class="compact-product-card reveal" data-delay="<?= $index * 45 ?>"
                                data-category="<?= htmlspecialchars((string) ($product['category_key'] ?? 'all')) ?>"
                                data-search-name="<?= htmlspecialchars(strtolower(($product['full_name'] ?? '') . ' ' . ($product['category'] ?? '') . ' ' . ($product['sku'] ?? ''))) ?>">
                                <a class="compact-product-card__media" href="product?slug=<?= urlencode((string) $product['slug']) ?>"
                                    aria-label="View <?= htmlspecialchars((string) ($product['full_name'] ?? '')) ?>">
                                    <span
                                        class="compact-product-card__badge <?= $index === 5 ? 'is-new' : ($index % 2 === 0 ? 'is-bestseller' : 'is-fast') ?>"><?= $index === 5 ? 'New Launch' : ($index % 2 === 0 ? 'Best Seller' : 'Selling Fast') ?></span>
                                    <img src="<?= htmlspecialchars($homeImage) ?>"
                                        alt="<?= htmlspecialchars((string) ($product['full_name'] ?? '')) ?>" loading="lazy"
                                        decoding="async" data-card-main-image
                                        onerror="this.onerror=null;this.src='assets/images/logo.png'">
                                </a>
                                <div class="compact-product-card__body">
                                    <h3><a
                                            href="product?slug=<?= urlencode((string) $product['slug']) ?>"><?= htmlspecialchars((string) ($product['name'] ?? '')) ?></a>
                                    </h3>
                                    <?php
                                    $cVariants = gawdee_family_variants($products, (string) ($product['family_key'] ?? ''));
                                    if (!$cVariants) {
                                        $cVariants = [$product];
                                    }
                                    if (count($cVariants) > 1):
                                        ?>
                                        <div class="card-variant-pills" role="group"
                                            aria-label="Select size for <?= htmlspecialchars((string) ($product['name'] ?? '')) ?>">
                                            <?php foreach ($cVariants as $cv):
                                                $isCur = ($cv['slug'] ?? '') === ($product['slug'] ?? '');
                                                $cvDiscount = discount_percentage($cv);
                                                $cvStock = (int) ($cv['stock'] ?? 0);
                                                $cvImage = (string) (($cv['image'] ?? '') !== '' ? $cv['image'] : $homeImage);
                                                ?>
                                                <button type="button" class="card-variant-pill <?= $isCur ? 'is-active' : '' ?>"
                                                    data-card-variant-switch data-slug="<?= htmlspecialchars((string) ($cv['slug'] ?? '')) ?>"
                                                    data-id="<?= htmlspecialchars((string) ($cv['id'] ?? '')) ?>"
                                                    data-name="<?= htmlspecialchars((string) ($cv['full_name'] ?? '')) ?>"
                                                    data-weight="<?= htmlspecialchars((string) ($cv['weight'] ?? '')) ?>"
                                                    data-price="<?= (int) ($cv['price'] ?? 0) ?>"
                                                    data-price-formatted="<?= money((int) ($cv['price'] ?? 0)) ?>"
                                                    data-original-price-formatted="<?= money((int) ($cv['original_price'] ?? 0)) ?>"
                                                    data-discount="<?= $cvDiscount ?>" data-stock="<?= $cvStock ?>"
                                                    data-sku="<?= htmlspecialchars((string) ($cv['sku'] ?? '')) ?>"
                                                    data-image="<?= htmlspecialchars($cvImage) ?>" <?= $isCur ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>>
                                                    <?= htmlspecialchars((string) ($cv['weight'] ?? '')) ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span
                                            class="compact-product-card__weight"><?= htmlspecialchars((string) ($product['weight'] ?? '')) ?></span>
                                    <?php endif; ?>
                                    <div class="compact-product-card__price">
                                        <strong data-card-price><?= money((int) ($product['price'] ?? 0)) ?></strong><s
                                            data-card-original-price><?= money((int) ($product['original_price'] ?? 0)) ?></s>
                                    </div>
                                    <div class="compact-product-card__actions">
                                        <?php
                                        $variantDataForJs = [];
                                        foreach ($cVariants as $cv) {
                                            $cvImageForJs = (string) (($cv['image'] ?? '') !== '' ? $cv['image'] : $homeImage);
                                            $variantDataForJs[] = [
                                                'id' => (string) ($cv['id'] ?? ''),
                                                'name' => (string) ($cv['full_name'] ?? ''),
                                                'weight' => (string) ($cv['weight'] ?? ''),
                                                'price' => (int) ($cv['price'] ?? 0),
                                                'price_formatted' => money((int) ($cv['price'] ?? 0)),
                                                'original_price' => (int) ($cv['original_price'] ?? 0),
                                                'original_price_formatted' => money((int) ($cv['original_price'] ?? 0)),
                                                'discount' => discount_percentage($cv),
                                                'stock' => (int) ($cv['stock'] ?? 0),
                                                'image' => htmlspecialchars($cvImageForJs, ENT_QUOTES | ENT_HTML5),
                                            ];
                                        }
                                        $variantsJson = htmlspecialchars(json_encode($variantDataForJs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_HTML5);
                                        ?>
                                        <button type="button" data-add-to-cart
                                            data-id="<?= htmlspecialchars((string) ($product['id'] ?? '')) ?>"
                                            data-name="<?= htmlspecialchars((string) ($product['full_name'] ?? '')) ?>"
                                            data-price="<?= (int) ($product['price'] ?? 0) ?>"
                                            data-image="<?= htmlspecialchars($homeImage) ?>"
                                            data-variants="<?= $variantsJson ?>"
                                            <?= ((int) ($product['stock'] ?? 0) <= 0) ? 'disabled' : '' ?>><?= ((int) ($product['stock'] ?? 0) <= 0) ? 'Out of stock' : 'Add to cart' ?></button>

                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <p class="product-empty" data-product-empty hidden>No products match your search.</p>
                </div>
            </section>
            <?php
            break;

        case 'categories':
            ?>
            <section class="commerce-section category-section" id="categories">
                <div class="container">
                    <div class="commerce-section__heading reveal">
                        <div>
                            <span class="eyebrow"><i class="ph ph-squares-four"></i>
                                <?= htmlspecialchars($homepageSections['categories']['eyebrow'] ?: 'Organic Categories') ?></span>
                            <h2><?= htmlspecialchars($homepageSections['categories']['title']) ?></h2>
                            <p><?= htmlspecialchars($homepageSections['categories']['subtitle']) ?></p>
                        </div>
                    </div>
                    <div class="category-grid">
                        <?php foreach ($categories as $index => $category): ?>
                            <a class="category-card reveal" data-delay="<?= $index * 35 ?>"
                                href="products?category=<?= rawurlencode((string) $category['filter']) ?>"
                                data-category-link="<?= htmlspecialchars($category['filter']) ?>">
                                <span class="category-card__visual">
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="<?= htmlspecialchars($category['image']) ?>"
                                            alt="<?= htmlspecialchars($category['name']) ?>" loading="lazy">
                                    <?php elseif (!empty($category['icon'])): ?>
                                        <i class="ph <?= htmlspecialchars($category['icon']) ?>"></i>
                                    <?php else: ?>
                                        <i class="ph ph-squares-four"></i>
                                    <?php endif; ?>
                                </span>
                                <strong><?= htmlspecialchars($category['name']) ?></strong>
                                <span class="category-card__arrow"><i class="ph ph-arrow-right"></i></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'why':
        case 'why_gawdee':
            ?>
            <section class="commerce-section brand-story-section reveal" id="why-gawdee">
                <div class="container">
                    <div class="brand-story-header">
                        <span class="eyebrow eyebrow--light">
                            <i class="ph ph-plant"></i>

                            <?= htmlspecialchars($section['eyebrow'] ?: 'Why Gawdee') ?>

                        </span>
                        <h2><?= htmlspecialchars($section['title'] ?: 'Food should feel closer to nature.') ?></h2>
                        <p><?= htmlspecialchars($section['subtitle'] ?: 'We believe everyday food should be pure, unadulterated, and made with traditional Indian care for modern families.') ?>
                        </p>
                    </div>
                    <div class="story-pillar-grid">
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">01</span>
                            <h4 class="">Nutrient-Rich Ingredients</h4>
                            <p>Carefully selected natural ingredients packed with essential nutrients to support your body and make
                                everyday meals more nourishing.</p>
                        </article>
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">02</span>
                            <h4 class="gawdee-dark">Clean & Wholesome</h4>
                            <p>Made with thoughtfully chosen ingredients and no unnecessary artificial additives, so you know
                                exactly what goes into your food.</p>
                        </article>
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">03</span>
                            <h4 class="">Nutrition for Every Day</h4>
                            <p>Created to fit effortlessly into your daily routine, helping you add wholesome nourishment to your
                                breakfast, snacks, and everyday meals.</p>
                        </article>
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">04</span>
                            <h4 class="">Quality You Can Trust</h4>
                            <p>Every Gawdee product is carefully prepared, quality checked, and packed with care to preserve its
                                freshness, goodness, and nutritional value.</p>
                        </article>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'offer':
            ?>
            <section class="commerce-section campaign-offer-section" id="offers">
                <div class="container">
                    <?php
                    $offerSection = $homepageSections['offer'];
                    $offerDesktop = $offerSection['image'] ?: 'assets/images/independence-day-offer-banner-v1.png';
                    $offerMobile = $offerSection['mobile_image'] ?: 'assets/images/independence-day-offer-banner-mobile-v1.png';
                    $offerCoupon = gawdee_setting('offer_code', 'FREEDOM10');
                    ?>
                    <?php if (!empty($offerSection['title']) || !empty($offerSection['eyebrow']) || !empty($offerSection['subtitle'])): ?>
                        <div class="commerce-section__heading reveal">
                            <div>
                                <span class="eyebrow"><i class="ph ph-tag"></i>
                                    <?= htmlspecialchars($offerSection['eyebrow'] ?: 'Special Offer') ?></span>
                                <h2><?= htmlspecialchars($offerSection['title'] ?: 'Flat 10% OFF') ?></h2>
                                <?php if (!empty($offerSection['subtitle'])): ?>
                                    <p><?= htmlspecialchars($offerSection['subtitle']) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($offerSection['button_label'])): ?>
                                <div class="commerce-section__actions">
                                    <a class="button button--primary" href="<?= htmlspecialchars($offerSection['button_url'] ?: '#shop') ?>"
                                        data-copy-coupon="<?= htmlspecialchars($offerCoupon) ?>">
                                        <?= htmlspecialchars($offerSection['button_label']) ?> <i class="ph ph-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <a class="independence-image-offer reveal reveal--scale"
                        href="<?= htmlspecialchars($offerSection['button_url'] ?: '#shop') ?>"
                        data-copy-coupon="<?= htmlspecialchars($offerCoupon) ?>"
                        aria-label="<?= htmlspecialchars(($offerSection['title'] ?: 'Flat 10% OFF') . '. ' . ($offerSection['subtitle'] ?: '')) ?>">
                        <picture>
                            <?php if ($offerMobile): ?>
                                <source media="(max-width: 700px)" srcset="<?= htmlspecialchars($offerMobile) ?>"><?php endif; ?>
                            <img src="<?= htmlspecialchars($offerDesktop) ?>"
                                alt="<?= htmlspecialchars(($offerSection['title'] ?: 'Flat 10% OFF') . '. ' . ($offerSection['subtitle'] ?: '')) ?>"
                                loading="lazy">
                        </picture>
                    </a>
                </div>
            </section>
            <?php
            break;

        case 'combos':
            ?>
            <section class="commerce-section combo-section">
                <div class="container">
                    <div class="commerce-section__heading reveal">
                        <div>
                            <span class="eyebrow"><i class="ph ph-gift"></i>
                                <?= htmlspecialchars($homepageSections['combos']['eyebrow'] ?: 'Value Bundles') ?></span>
                            <h2><?= htmlspecialchars($homepageSections['combos']['title']) ?></h2>
                            <p><?= htmlspecialchars($homepageSections['combos']['subtitle']) ?></p>
                        </div>
                        <div class="commerce-section__actions">
                            <a class="text-link" href="#shop">Explore all combos <i class="ph ph-arrow-right"></i></a>
                            <div class="section-rail-controls home-slider-controls" aria-label="Combo slider controls">
                                <button type="button" data-scroll-rail="#home-combo-rail" data-scroll-direction="-1"
                                    aria-label="Previous combos"><i class="ph ph-arrow-left"></i></button>
                                <button type="button" data-scroll-rail="#home-combo-rail" data-scroll-direction="1"
                                    aria-label="Next combos"><i class="ph ph-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="combo-grid home-combo-rail" id="home-combo-rail" data-sliding-rail data-auto-slide="4200"
                        tabindex="0" aria-label="Product combos">
                        <?php foreach ($combos as $index => $combo): ?>
                            <article class="combo-card reveal" data-delay="<?= $index * 70 ?>">
                                <span class="combo-card__tag"><?= htmlspecialchars($combo['tag']) ?></span>
                                <div class="combo-card__visual">
                                    <?php foreach ($combo['items'] as $item): ?><img src="<?= htmlspecialchars($item['image']) ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"><?php endforeach; ?>
                                </div>
                                <div class="combo-card__body">
                                    <h3><?= htmlspecialchars($combo['title']) ?></h3>
                                    <div><strong><?= money($combo['price']) ?></strong><s><?= money($combo['original']) ?></s><span
                                            class="combo-save">Save
                                            <?= (int) round((1 - $combo['price'] / $combo['original']) * 100) ?>%</span></div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;
        case 'reels':
            break;

        case 'reviews':
            ?>
            <section class="content-section testimonial-reference-section" id="reviews" aria-labelledby="testimonial-heading">
                <div class="container">
                    <header class="testimonial-reference-head reveal">
                        <span class="eyebrow"><i class="ph ph-quotes"></i>
                            <?= htmlspecialchars($homepageSections['reviews']['eyebrow'] ?: 'Customer Love') ?></span>
                        <h2 id="testimonial-heading"><span><?= htmlspecialchars($homepageSections['reviews']['title']) ?></span>
                        </h2>
                        <p><?= htmlspecialchars($homepageSections['reviews']['subtitle']) ?></p>
                    </header>

                    <div class="testimonial-reference-controls reveal" aria-label="Testimonial slider controls">
                        <span><i class="ph ph-hand-swipe-left"></i> Real stories from real families</span>
                        <div class="section-rail-controls">
                            <button type="button" data-scroll-rail="#testimonial-rail" data-scroll-direction="-1"
                                aria-label="Previous testimonial"><i class="ph ph-arrow-left"></i></button>
                            <button type="button" data-scroll-rail="#testimonial-rail" data-scroll-direction="1"
                                aria-label="Next testimonial"><i class="ph ph-arrow-right"></i></button>
                        </div>
                    </div>

                    <div class="testimonial-reference-rail" id="testimonial-rail" data-sliding-rail data-auto-slide="4600"
                        tabindex="0" aria-label="Customer testimonials">
                        <?php foreach ($testimonialDeck as $index => $testimonial): ?>
                            <article class="testimonial-reference-card reveal" data-delay="<?= min($index * 45, 180) ?>">
                                <div class="testimonial-reference-card__top">
                                    <?php if (!empty($testimonial['avatar'])): ?>
                                        <img class="testimonial-reference-avatar" src="<?= htmlspecialchars($testimonial['avatar']) ?>"
                                            alt="<?= htmlspecialchars($testimonial['name']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <span
                                            class="testimonial-reference-avatar testimonial-reference-avatar--initials"><?= htmlspecialchars($testimonial['initials']) ?></span>
                                    <?php endif; ?>
                                    <div class="testimonial-reference-person">
                                        <h3><?= htmlspecialchars($testimonial['name']) ?></h3>
                                        <p><i class="ph ph-seal-check"></i> Verified Buyer</p>
                                    </div>
                                    <span class="testimonial-reference-quote" aria-hidden="true"><i class="ph ph-quotes"></i></span>
                                </div>
                                <div class="testimonial-reference-meta">
                                    <span class="testimonial-reference-stars"
                                        aria-label="<?= (int) $testimonial['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $testimonial['rating']) ?></span>
                                    <span class="testimonial-reference-product"><?= htmlspecialchars($testimonial['product']) ?></span>
                                </div>
                                <blockquote>“<?= htmlspecialchars($testimonial['quote']) ?>”</blockquote>
                                <footer>
                                    <a href="product?slug=<?= urlencode($testimonial['slug']) ?>">Read Full Story</a>
                                    <a class="testimonial-reference-arrow" href="product?slug=<?= urlencode($testimonial['slug']) ?>"
                                        aria-label="Read <?= htmlspecialchars($testimonial['name']) ?>'s story"><i
                                            class="ph ph-caret-right"></i></a>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'stories':
            if (empty($publishedPosts)) {
                break;
            }
            ?>
            <section class="content-section blog-reference-section" id="stories" aria-labelledby="blog-reference-heading">
                <div class="container">
                    <header class="blog-reference-head reveal">
                        <span class="eyebrow"><i class="ph ph-book-open"></i>
                            <?= htmlspecialchars($homepageSections['stories']['eyebrow'] ?: 'Wellness Journal') ?></span>
                        <h2 id="blog-reference-heading"><?= htmlspecialchars($homepageSections['stories']['title']) ?></h2>
                        <p><?= htmlspecialchars($homepageSections['stories']['subtitle']) ?></p>
                    </header>

                    <div class="testimonial-reference-controls reveal" aria-label="Journal slider controls">
                        <span><i class="ph ph-hand-swipe-left"></i> Swipe to explore wellness stories</span>
                        <div class="section-rail-controls">
                            <button type="button" data-scroll-rail="#journal-rail" data-scroll-direction="-1"
                                aria-label="Previous story"><i class="ph ph-arrow-left"></i></button>
                            <button type="button" data-scroll-rail="#journal-rail" data-scroll-direction="1"
                                aria-label="Next story"><i class="ph ph-arrow-right"></i></button>
                        </div>
                    </div>

                    <div class="journal-rail" id="journal-rail" data-sliding-rail tabindex="0"
                        aria-label="Wellness journal stories">
                        <?php foreach ($publishedPosts as $index => $post): ?>
                            <article class="journal-card reveal" data-delay="<?= ($index % 5) * 45 ?>">
                                <a class="journal-card__visual" href="blog-post?slug=<?= rawurlencode($post['slug']) ?>">
                                    <?php if (!empty($post['featured_image'])): ?>
                                        <img src="<?= htmlspecialchars($post['featured_image']) ?>"
                                            alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="journal-card__placeholder"><i class="ph ph-leaf"></i></div>
                                    <?php endif; ?>
                                    <span
                                        class="journal-card__badge"><?= htmlspecialchars(($post['category'] ?: 'Wellness') . ' · ' . (($post['source'] ?? '') === 'ai' ? 'AI-assisted' : 'Editorial')) ?></span>
                                </a>
                                <div class="journal-card__body">
                                    <div class="journal-card__meta">
                                        <i class="ph ph-calendar-blank"></i>
                                        <span><?= htmlspecialchars(date('d M Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></span>
                                        <span>·</span>
                                        <span><?= htmlspecialchars($post['author'] ?: 'Gawdee Editorial') ?></span>
                                    </div>
                                    <h2><a
                                            href="blog-post?slug=<?= rawurlencode($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a>
                                    </h2>
                                    <a class="journal-card__link" href="blog-post?slug=<?= rawurlencode($post['slug']) ?>">Read Story <i
                                            class="ph ph-arrow-right"></i></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="blog-reference-footer reveal">
                        <a class="button button--secondary"
                            href="<?= htmlspecialchars($homepageSections['stories']['button_url'] ?: 'blog') ?>"><?= htmlspecialchars($homepageSections['stories']['button_label'] ?: 'View All Stories') ?>
                            <i class="ph ph-arrow-right"></i></a>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'newsletter':
            ?>
            <section class="commerce-section newsletter-section reveal">
                <div class="container">
                    <div class="newsletter-panel">
                        <div class="newsletter-panel__icon"><i class="ph ph-envelope-simple"></i></div>
                        <div>
                            <span class="eyebrow"><i class="ph ph-paper-plane-tilt"></i>
                                <?= htmlspecialchars($homepageSections['newsletter']['eyebrow'] ?: 'Stay Connected') ?></span>
                            <h2><?= htmlspecialchars($homepageSections['newsletter']['title']) ?></h2>
                            <p><?= htmlspecialchars($homepageSections['newsletter']['subtitle']) ?></p>
                        </div>
                        <form action="#" data-newsletter-form><label class="sr-only" for="newsletter-email">Enter your
                                email</label><input id="newsletter-email" type="email" placeholder="Enter your email"
                                required><button
                                type="submit"><?= htmlspecialchars($homepageSections['newsletter']['button_label'] ?: 'Subscribe') ?>
                                <i class="ph ph-arrow-right"></i></button></form>
                        <div class="newsletter-panel__leaf" aria-hidden="true"><i class="ph ph-plant"></i></div>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
?>

<?php if (gawdee_setting('offer_popup_enabled', '1') === '1'): ?>
    <?php
    $offerPopupImage = gawdee_setting('offer_popup_image', 'assets/images/independence-offer-popup-v1.webp');
    $offerPopupCode = gawdee_setting('offer_code', 'FREEDOM10');
    $offerPopupPercent = gawdee_setting('offer_percent', '10');
    $offerPopupTitle = gawdee_setting('offer_popup_title', 'Independence Day Special');
    $offerPopupRawText = gawdee_setting('offer_popup_text', 'Use code %code% at checkout');
    $offerPopupLink = gawdee_setting('offer_popup_link', '#shop');
    $offerPopupBtnText = gawdee_setting('offer_popup_btn_text', 'Shop offer');
    $offerPopupDelay = min(10000, max(0, (int) gawdee_setting('offer_popup_delay_ms', '850')));

    if (str_contains($offerPopupRawText, '%code%')) {
        $offerPopupDescription = str_replace(
            '%code%',
            '<strong>' . htmlspecialchars($offerPopupCode) . '</strong>',
            htmlspecialchars($offerPopupRawText, ENT_QUOTES, 'UTF-8')
        );
    } else {
        $offerPopupDescription = htmlspecialchars($offerPopupRawText, ENT_QUOTES, 'UTF-8');
    }

    $offerPopupKey = substr(hash('sha256', implode('|', [
        $offerPopupImage,
        $offerPopupCode,
        $offerPopupPercent,
        $offerPopupTitle,
        $offerPopupRawText,
        $offerPopupLink,
        $offerPopupBtnText,
        $offerPopupDelay
    ])), 0, 14);
    ?>
    <div class="offer-popup" data-offer-popup data-popup-key="<?= htmlspecialchars($offerPopupKey) ?>"
        data-popup-delay="<?= $offerPopupDelay ?>" hidden>
        <section class="offer-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="independence-offer-title"
            aria-describedby="independence-offer-description">
            <div class="offer-popup__flag" aria-hidden="true"><span></span><span></span><span></span></div>
            <button class="offer-popup__close" type="button" data-offer-popup-close aria-label="Close offer popup"><i
                    class="ph ph-x"></i></button>
            <a class="offer-popup__art" href="<?= htmlspecialchars($offerPopupLink) ?>" data-offer-popup-shop>
                <img src="<?= htmlspecialchars($offerPopupImage) ?>"
                    alt="<?= htmlspecialchars($offerPopupTitle) ?>. Flat <?= htmlspecialchars($offerPopupPercent) ?> percent off with code <?= htmlspecialchars($offerPopupCode) ?>.">
            </a>
            <div class="offer-popup__actions">
                <div class="offer-popup__copy">
                    <span id="independence-offer-title"><?= htmlspecialchars($offerPopupTitle) ?></span>
                    <p id="independence-offer-description"><?= $offerPopupDescription ?></p>
                </div>
                <?php if (!empty($offerPopupCode)): ?>
                    <button type="button" class="offer-popup__code" data-copy-offer="<?= htmlspecialchars($offerPopupCode) ?>"
                        aria-label="Copy offer code <?= htmlspecialchars($offerPopupCode) ?>"><strong><?= htmlspecialchars($offerPopupCode) ?></strong><span><i
                                class="ph ph-copy"></i> Copy code</span></button>
                <?php endif; ?>
                <a class="offer-popup__shop" href="<?= htmlspecialchars($offerPopupLink) ?>"
                    data-offer-popup-shop><?= htmlspecialchars($offerPopupBtnText) ?> <i class="ph ph-arrow-right"></i></a>
            </div>
        </section>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>