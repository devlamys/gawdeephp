<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$pageTitle = 'Gawdee — Authentic organic food for everyday wellness';
$pageDescription = 'Shop Gawdee A2 Gir cow ghee, raw honey, natural nutrition blends and traditional pantry essentials.';
$pageKeywords = 'Gawdee, Gawdee organic, A2 Gir cow ghee, raw wild forest honey, MixMe nutrition, Taral drops, organic food store India, pure ghee, natural sweeteners';
$bodyClass = 'commerce-home';

$categories = gawdee_categories();

$combos = [
    ['tag' => 'Wellness Combo', 'title' => 'A2 Ghee 500ml + Honey 650g', 'items' => [$products[2], $products[3]], 'price' => 1499, 'original' => 1748],
    ['tag' => 'Energy Combo', 'title' => 'MixMe Choco + Raw Honey', 'items' => [$products[0], $products[3]], 'price' => 1299, 'original' => 1458],
    ['tag' => 'Immunity Combo', 'title' => 'Taral Drop + Moringa Powder', 'items' => [$products[4], $products[5]], 'price' => 399, 'original' => 558],
    ['tag' => 'Daily Nutrition', 'title' => 'MixMe Elaichi + A2 Ghee', 'items' => [$products[1], $products[2]], 'price' => 1549, 'original' => 1808],
];

$featuredProducts = [$products[0], $products[1], $products[4], $products[6], $products[2], $products[3]];

$testimonialFavourites = [
    'honey' => product_by_slug($products, 'gawdee-raw-wild-forest-honey-650-g') ?? $products[0],
    'ghee' => product_by_slug($products, 'gawdee-gir-cow-a2-ghee-500-ml') ?? $products[0],
    'mixme' => product_by_slug($products, 'gawdee-mixme-choco-500-g') ?? $products[0],
    'moringa' => product_by_slug($products, 'gawdee-moringa-powder-300-g') ?? $products[0],
];

$testimonials = [];
foreach (gawdee_testimonials() as $story) {
    $relatedProduct = product_by_slug($products, (string) $story['product_slug']) ?? $products[0];
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
$reelProducts = [$products[2], $products[3], $products[0]];
$homepageSections = gawdee_sections();
$homepageBanners = gawdee_banners();
$heroBannersTwo = gawdee_hero_banners_two();
$homepageReels = gawdee_homepage_media('reels', false, true);
if (empty($homepageReels)) {
    $homepageReels = gawdee_homepage_media('reels', false);
}
$publishedStories = gawdee_db()->query("SELECT title, slug, excerpt, featured_image, category FROM blog_posts WHERE status='published' AND is_featured=1 ORDER BY COALESCE(published_at, created_at) DESC LIMIT 4")->fetchAll();
$stories = [];
if ($publishedStories) {
    foreach ($publishedStories as $index => $post) {
        $stories[] = [
            'tag' => trim((string) $post['category']) ?: 'Gawdee journal',
            'title' => $post['title'],
            'excerpt' => trim((string) $post['excerpt']),
            'image' => trim((string) $post['featured_image']) ?: ($blogCovers[$index % count($blogCovers)] ?? ''),
            'url' => 'blog-post.php?slug=' . rawurlencode($post['slug']),
        ];
    }
}

require __DIR__ . '/includes/header.php';

$heroScrubEnabled = gawdee_setting('hero_scrub_enabled', '1') === '1';
$heroScrubVideo = gawdee_setting('hero_scrub_video', '');
$heroScrubPoster = gawdee_setting('hero_scrub_poster', '');
$heroScrubTitle = gawdee_setting('hero_scrub_title', '');
$heroScrubSubtitle = gawdee_setting('hero_scrub_subtitle', '');
?>

<?php
$firstSlide = $heroBannersTwo[0] ?? null;
$initialEyebrow = !empty($firstSlide['eyebrow']) ? $firstSlide['eyebrow'] : '';
$initialTitle = !empty($firstSlide['headline']) ? $firstSlide['headline'] : (!empty($firstSlide['title']) ? $firstSlide['title'] : ($heroScrubTitle ?: ''));
$initialSubtitle = !empty($firstSlide['subtitle']) ? $firstSlide['subtitle'] : ($heroScrubSubtitle ?: '');
?>

<?php if ($heroScrubEnabled): ?>
    <section class="hero-scrub-section" data-hero-scrub-section
        data-hero-slides="<?= htmlspecialchars(json_encode($heroBannersTwo, JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>"
        aria-label="Cinematic Gawdee experience">
        <div class="hero-scrub-sticky">
            <video class="hero-scrub-video is-active" data-hero-scrub-video muted playsinline autoplay loop preload="auto"
                <?= $heroScrubPoster ? 'poster="' . htmlspecialchars($heroScrubPoster) . '"' : '' ?>>
                <?php if ($heroScrubVideo): ?>
                    <source src="<?= htmlspecialchars($heroScrubVideo) ?>" type="video/mp4">
                <?php endif; ?>
            </video>
            <video class="hero-scrub-video hero-scrub-video--next" data-hero-scrub-video-next muted playsinline
                preload="auto"></video>
            <?php if ($heroScrubPoster): ?>
                <img class="hero-scrub-poster-overlay" data-hero-scrub-poster src="<?= htmlspecialchars($heroScrubPoster) ?>"
                    alt="Gawdee Pure Food">
            <?php endif; ?>
            <div class="hero-scrub-overlay" aria-hidden="true"></div>
            <div class="container hero-scrub-content">
                <span class="hero-scrub-eyebrow is-active" data-scrub-step="1"><i class="ph ph-sparkle"></i>
                    <?= htmlspecialchars($initialEyebrow) ?></span>
                <h1 class="hero-scrub-title is-active" data-scrub-title data-scrub-step="2">
                    <?= htmlspecialchars($initialTitle) ?>
                </h1>
                <p class="hero-scrub-subtitle is-active" data-scrub-step="3"><?= htmlspecialchars($initialSubtitle) ?></p>
                <div class="hero-scrub-actions is-active" data-scrub-step="4">
                    <a href="#shop" class="button button--primary">Shop Collection <i class="ph ph-arrow-right"></i></a>
                    <a href="#why-gawdee" class="button button--cream">Meet Gawdee</a>
                </div>
                <div class="hero-scrub-indicator" data-scrub-indicator>
                    <span>Scroll to explore</span>
                    <i class="ph ph-caret-down"></i>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

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

                    <div class="compact-product-grid home-product-rail" id="home-product-rail" data-product-grid data-sliding-rail
                        data-auto-slide="3600" tabindex="0" aria-label="Bestselling products">
                        <?php foreach ($featuredProducts as $index => $product): ?>
                            <article class="compact-product-card reveal" data-delay="<?= $index * 45 ?>"
                                data-category="<?= htmlspecialchars($product['category_key']) ?>"
                                data-search-name="<?= htmlspecialchars(strtolower($product['full_name'] . ' ' . $product['category'])) ?>">
                                <a class="compact-product-card__media" href="product?slug=<?= urlencode($product['slug']) ?>">
                                    <span
                                        class="compact-product-card__badge <?= $index % 3 === 2 ? 'is-blue' : ($index % 2 === 0 ? 'is-orange' : '') ?>"><?= $index === 5 ? 'New arrival' : ($index % 2 === 0 ? 'Best seller' : 'Popular') ?></span>
                                    <img src="<?= htmlspecialchars($product['image']) ?>"
                                        alt="<?= htmlspecialchars($product['full_name']) ?>" loading="lazy">
                                </a>
                                <div class="compact-product-card__body">
                                    <h3><a
                                            href="product?slug=<?= urlencode($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a>
                                    </h3>
                                    <span class="compact-product-card__weight"><?= htmlspecialchars($product['weight']) ?></span>
                                    <div class="compact-product-card__price">
                                        <strong><?= money($product['price']) ?></strong><s><?= money($product['original_price']) ?></s>
                                    </div>
                                    <div class="compact-product-card__actions">
                                        <button type="button" data-add-to-cart data-id="<?= htmlspecialchars($product['id']) ?>"
                                            data-name="<?= htmlspecialchars($product['full_name']) ?>"
                                            data-price="<?= (int) $product['price'] ?>"
                                            data-image="<?= htmlspecialchars($product['image']) ?>">Add to cart</button>
                                        <?php if (gawdee_customer() !== null): ?>
                                            <button type="button" data-wishlist
                                                aria-label="Add <?= htmlspecialchars($product['name']) ?> to wishlist"
                                                aria-pressed="false"><i class="ph ph-heart"></i></button>
                                        <?php endif; ?>
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
                        <span class="eyebrow"><i class="ph ph-plant"></i>
                            <?= htmlspecialchars($section['eyebrow'] ?: 'Why Gawdee') ?></span>
                        <h2><?= htmlspecialchars($section['title'] ?: 'Food should feel closer to nature.') ?></h2>
                        <p><?= htmlspecialchars($section['subtitle'] ?: 'We believe everyday food should be pure, unadulterated, and made with traditional Indian care for modern families.') ?>
                        </p>
                    </div>
                    <div class="story-pillar-grid">
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">01</span>
                            <h3>Nutrient-Rich Ingredients</h3>
                            <p>Carefully selected natural ingredients packed with essential nutrients to support your body and make
                                everyday meals more nourishing.</p>
                        </article>
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">02</span>
                            <h3>Clean & Wholesome</h3>
                            <p>Made with thoughtfully chosen ingredients and no unnecessary artificial additives, so you know
                                exactly what goes into your food.</p>
                        </article>
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">03</span>
                            <h3>Nutrition for Every Day</h3>
                            <p>Created to fit effortlessly into your daily routine, helping you add wholesome nourishment to your
                                breakfast, snacks, and everyday meals.</p>
                        </article>
                        <article class="story-pillar-card">
                            <span class="story-pillar-num">04</span>
                            <h3>Quality You Can Trust</h3>
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
            if (empty($homepageReels)) {
                break;
            }
            ?>
            <section class="content-section reels-section" id="made-with-care">
                <div class="container">
                    <div class="content-heading content-heading--center reveal">
                        <div>
                            <span class="eyebrow"><i class="ph ph-film-strip"></i>
                                <?= htmlspecialchars($homepageSections['reels']['eyebrow'] ?: 'Gawdee in Motion') ?></span>
                            <h2><?= htmlspecialchars($homepageSections['reels']['title']) ?></h2>
                        </div>
                        <p><?= htmlspecialchars($homepageSections['reels']['subtitle']) ?></p>
                        <a href="reels.php" class="reels-view-all-btn">Watch All Reels &amp; Stories <i
                                class="ph ph-arrow-right"></i></a>
                    </div>
                    <div class="reel-grid">
                        <?php foreach ($homepageReels as $index => $media):
                            $product = product_by_slug($products, (string) $media['product_slug']);
                            $videoSrc = $media['file_path'] ?: $media['external_url'];
                            $posterSrc = $media['poster_path'] ?: ($product['image'] ?? 'assets/images/logo.png');
                            ?>
                            <article class="reel-card reveal" data-delay="<?= $index * 75 ?>">
                                <div class="reel-card__media" data-reel-trigger data-video-src="<?= htmlspecialchars($videoSrc) ?>"
                                    data-video-type="<?= htmlspecialchars($media['media_type']) ?>"
                                    data-video-title="<?= htmlspecialchars($media['title'] ?: ($product['name'] ?? 'Gawdee Story')) ?>"
                                    data-video-subtitle="<?= htmlspecialchars($media['subtitle']) ?>"
                                    data-video-poster="<?= htmlspecialchars($posterSrc) ?>"
                                    data-product-id="<?= htmlspecialchars($product['id'] ?? '') ?>"
                                    data-product-name="<?= htmlspecialchars($product['full_name'] ?? '') ?>"
                                    data-product-price="<?= htmlspecialchars((string) ($product['price'] ?? '')) ?>"
                                    data-product-image="<?= htmlspecialchars($product['image'] ?? '') ?>"
                                    data-product-url="<?= $product ? 'product.php?slug=' . rawurlencode($product['slug']) : '' ?>">

                                    <?php if ($media['poster_path']): ?>
                                        <img src="<?= htmlspecialchars($media['poster_path']) ?>"
                                            alt="<?= htmlspecialchars($media['alt_text'] ?: $media['title']) ?>" loading="lazy">
                                    <?php elseif ($media['media_type'] === 'video' && $media['file_path']): ?>
                                        <video playsinline muted preload="metadata">
                                            <source src="<?= htmlspecialchars($media['file_path']) ?>">
                                        </video>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($product['image'] ?? 'assets/images/logo.png') ?>"
                                            alt="<?= htmlspecialchars($media['title']) ?>" loading="lazy">
                                    <?php endif; ?>

                                    <div class="reel-card__overlay">
                                        <button type="button" class="reel-card__play-btn"
                                            aria-label="Play <?= htmlspecialchars($media['title']) ?>">
                                            <i class="ph-fill ph-play"></i>
                                        </button>
                                        <span class="reel-card__badge"><i class="ph ph-video"></i> Reel</span>
                                    </div>
                                    <span class="reel-card__index"><?= sprintf('%02d', $index + 1) ?></span>
                                </div>
                                <div class="reel-card__product">
                                    <?php if ($product): ?>
                                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="">
                                        <div>
                                            <h3><?= htmlspecialchars($media['title'] ?: $product['name']) ?></h3>
                                            <p><?= money($product['price']) ?> <span><?= htmlspecialchars($product['weight']) ?></span></p>
                                        </div>
                                        <button type="button" data-add-to-cart data-id="<?= htmlspecialchars($product['id']) ?>"
                                            data-name="<?= htmlspecialchars($product['full_name']) ?>" data-price="<?= $product['price'] ?>"
                                            data-image="<?= htmlspecialchars($product['image']) ?>"
                                            aria-label="Add <?= htmlspecialchars($product['name']) ?> to cart"><i
                                                class="ph ph-shopping-bag"></i></button>
                                    <?php else: ?>
                                        <div>
                                            <h3><?= htmlspecialchars($media['title']) ?></h3>
                                            <p><?= htmlspecialchars($media['subtitle']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>ion>
            <?php
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
            if (empty($stories)) {
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
                    <div class="blog-reference-grid" id="story-rail">
                        <?php foreach ($stories as $index => $story): ?>
                            <article class="blog-reference-card reveal" data-delay="<?= $index * 60 ?>">
                                <img src="<?= htmlspecialchars($story['image']) ?>" alt="<?= htmlspecialchars($story['title']) ?>"
                                    loading="lazy">
                                <span class="blog-reference-card__accent" aria-hidden="true"></span>
                                <div class="blog-reference-card__wash" aria-hidden="true"></div>
                                <div class="blog-reference-card__content">
                                    <span><?= htmlspecialchars($story['tag']) ?></span>
                                    <h3><?= htmlspecialchars($story['title']) ?></h3>
                                    <p><?= htmlspecialchars($story['excerpt']) ?></p>
                                    <a href="<?= htmlspecialchars($story['url'] ?? 'blog') ?>">Read Story <i
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
                            <span class="eyebrow eyebrow--light"><i class="ph ph-paper-plane-tilt"></i>
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