<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$pageTitle = 'Gawdee — Authentic organic food for everyday wellness';
$pageDescription = 'Shop Gawdee A2 Gir cow ghee, raw honey, natural nutrition blends and traditional pantry essentials.';
$bodyClass = 'commerce-home';

$categories = [
    ['name' => 'Ghee', 'filter' => 'ghee', 'image' => $products[2]['image']],
    ['name' => 'Honey', 'filter' => 'honey', 'image' => $products[3]['image']],
    ['name' => 'Drops', 'filter' => 'wellness', 'image' => $products[4]['image']],
    ['name' => 'Mix Me', 'filter' => 'nutrition', 'image' => $products[0]['image']],
    ['name' => 'Sugar', 'filter' => 'sugar', 'image' => $products[6]['image']],
    ['name' => 'Combo Offers', 'filter' => 'all', 'image' => $products[3]['image']],
    ['name' => 'Gift Packs', 'filter' => 'all', 'icon' => 'ph-gift'],
    ['name' => 'New Arrivals', 'filter' => 'all', 'image' => $products[5]['image']],
];

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
        'name' => $story['name'], 'initials' => $story['initials'], 'avatar' => $story['avatar'],
        'product' => $story['product_name'] ?: $relatedProduct['name'], 'quote' => $story['quote'],
        'image' => $relatedProduct['image'], 'slug' => $relatedProduct['slug'], 'theme' => $story['theme'],
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
$stories = [
    ['tag' => 'Better eating', 'title' => 'Why Small Daily Food Improvements Matter More Than Big Changes', 'excerpt' => 'When it comes to improving eating habits, small thoughtful choices can create lasting change.', 'image' => $blogCovers[0]],
    ['tag' => 'Mindful habits', 'title' => 'How to Gradually Shift to Better Eating Without Sudden Changes', 'excerpt' => 'Improving everyday nutrition can feel simpler when each new habit is introduced with care.', 'image' => $blogCovers[1]],
    ['tag' => 'Modern wellness', 'title' => 'How Modern Lifestyle Is Changing Food Choices in India', 'excerpt' => 'Busy routines are reshaping how families plan, prepare and enjoy everyday nourishment.', 'image' => $blogCovers[2]],
    ['tag' => 'Ingredients', 'title' => 'Why Quality Matters More Than Quantity in Daily Food Consumption', 'excerpt' => 'Thoughtful ingredients and balanced portions can matter more than simply eating more.', 'image' => $blogCovers[3]],
];

$reelProducts = [$products[2], $products[3], $products[0]];
$homepageSections = gawdee_sections();
$homepageBanners = gawdee_banners();
$homepageReels = gawdee_homepage_media('reels');
$publishedStories = gawdee_db()->query("SELECT title, slug, excerpt, featured_image, category FROM blog_posts WHERE status='published' ORDER BY is_featured DESC, COALESCE(published_at, created_at) DESC LIMIT 4")->fetchAll();
if ($publishedStories) {
    foreach ($publishedStories as $index => $post) {
        $stories[$index] = [
            'tag' => trim((string) $post['category']) ?: 'Gawdee journal',
            'title' => $post['title'],
            'excerpt' => trim((string) $post['excerpt']) ?: $stories[$index]['excerpt'],
            'image' => trim((string) $post['featured_image']) ?: $blogCovers[$index],
            'url' => 'blog-post.php?slug=' . rawurlencode($post['slug']),
        ];
    }
}

require __DIR__ . '/includes/header.php';
?>

<?php if (($homepageSections['hero']['is_active'] ?? 1) && $homepageBanners): ?>
<section class="hero-slider reveal reveal--scale" data-hero-slider aria-roledescription="carousel" aria-label="Featured Gawdee offers" tabindex="0">
    <div class="hero-slider__track" data-hero-track>
        <?php foreach ($homepageBanners as $index => $banner): ?>
        <a class="hero-slide <?= $index === 0 ? 'is-active' : '' ?>" href="<?= htmlspecialchars($banner['link_url']) ?>" data-hero-slide aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>" <?= $index === 0 ? '' : 'tabindex="-1"' ?> aria-label="<?= htmlspecialchars($banner['title']) ?>">
            <picture>
                <?php if (!empty($banner['mobile_image'])): ?><source media="(max-width: 700px)" srcset="<?= htmlspecialchars($banner['mobile_image']) ?>"><?php endif; ?>
                <img src="<?= htmlspecialchars($banner['desktop_image']) ?>" alt="<?= htmlspecialchars($banner['alt_text'] ?: $banner['title']) ?>" <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
            </picture>
        </a>
        <?php endforeach; ?>
    </div>

    <button class="hero-slider__arrow hero-slider__arrow--prev" type="button" data-hero-prev aria-label="Previous banner"><i class="ph ph-caret-left"></i></button>
    <button class="hero-slider__arrow hero-slider__arrow--next" type="button" data-hero-next aria-label="Next banner"><i class="ph ph-caret-right"></i></button>

    <div class="hero-slider__dots" role="tablist" aria-label="Choose a banner">
        <?php foreach ($homepageBanners as $index => $banner): ?><button class="<?= $index === 0 ? 'is-active' : '' ?>" type="button" data-hero-dot="<?= $index ?>" role="tab" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="Show <?= htmlspecialchars($banner['title']) ?>"></button><?php endforeach; ?>
    </div>
    <div class="hero-slider__progress" aria-hidden="true"><span data-hero-progress></span></div>
</section>
<?php endif; ?>

<section class="trust-strip reveal" aria-label="Shopping benefits">
    <div><i class="ph ph-leaf"></i><span><strong>100% Authentic</strong>Carefully chosen products</span></div>
    <div><i class="ph ph-truck"></i><span><strong>Free Shipping</strong>On orders above ₹999</span></div>
    <div><i class="ph ph-wallet"></i><span><strong>Secure Payments</strong>Safe and protected</span></div>
    <div><i class="ph ph-clock-counter-clockwise"></i><span><strong>Easy Support</strong>Helpful customer care</span></div>
    <div><i class="ph ph-headset"></i><span><strong>Customer Support</strong>Questions are welcome</span></div>
</section>

<?php if ($homepageSections['shop']['is_active'] ?? 1): ?><section class="commerce-section" id="shop">
    <div class="commerce-section__heading reveal">
        <div><h2><span>🔥</span> <?= htmlspecialchars($homepageSections['shop']['title']) ?></h2><p><?= htmlspecialchars($homepageSections['shop']['subtitle']) ?></p></div>
        <div class="commerce-section__actions">
            <a href="<?= htmlspecialchars($homepageSections['shop']['button_url'] ?: 'products.php') ?>"><?= htmlspecialchars($homepageSections['shop']['button_label'] ?: 'View all products') ?> <i class="ph ph-arrow-right"></i></a>
            <div class="section-rail-controls home-slider-controls" aria-label="Bestseller slider controls">
                <button type="button" data-scroll-rail="#home-product-rail" data-scroll-direction="-1" aria-label="Previous products"><i class="ph ph-arrow-left"></i></button>
                <button type="button" data-scroll-rail="#home-product-rail" data-scroll-direction="1" aria-label="Next products"><i class="ph ph-arrow-right"></i></button>
            </div>
        </div>
    </div>

    <div class="compact-product-grid home-product-rail" id="home-product-rail" data-product-grid data-sliding-rail data-auto-slide="3600" tabindex="0" aria-label="Bestselling products">
        <?php foreach ($featuredProducts as $index => $product): ?>
            <article class="compact-product-card reveal" data-delay="<?= $index * 45 ?>" data-category="<?= htmlspecialchars($product['category_key']) ?>" data-search-name="<?= htmlspecialchars(strtolower($product['full_name'] . ' ' . $product['category'])) ?>">
                <a class="compact-product-card__media" href="product.php?slug=<?= urlencode($product['slug']) ?>">
                    <span class="compact-product-card__badge <?= $index % 3 === 2 ? 'is-blue' : ($index % 2 === 0 ? 'is-orange' : '') ?>"><?= $index === 5 ? 'New arrival' : ($index % 2 === 0 ? 'Best seller' : 'Popular') ?></span>
                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['full_name']) ?>" loading="lazy">
                </a>
                <div class="compact-product-card__body">
                    <h3><a href="product.php?slug=<?= urlencode($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
                    <span class="compact-product-card__weight"><?= htmlspecialchars($product['weight']) ?></span>
                    <div class="compact-product-card__price"><strong><?= money($product['price']) ?></strong><s><?= money($product['original_price']) ?></s></div>
                    <div class="compact-product-card__actions">
                        <button type="button" data-add-to-cart data-id="<?= htmlspecialchars($product['id']) ?>" data-name="<?= htmlspecialchars($product['full_name']) ?>" data-price="<?= (int) $product['price'] ?>" data-image="<?= htmlspecialchars($product['image']) ?>">Add to cart</button>
                        <button type="button" data-wishlist aria-label="Add <?= htmlspecialchars($product['name']) ?> to wishlist" aria-pressed="false"><i class="ph ph-heart"></i></button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="product-empty" data-product-empty hidden>No products match your search.</p>
</section>
<?php endif; ?>

<?php if ($homepageSections['categories']['is_active'] ?? 1): ?><section class="commerce-section category-section" id="categories">
    <div class="commerce-section__heading reveal"><div><h2><?= htmlspecialchars($homepageSections['categories']['title']) ?></h2><p><?= htmlspecialchars($homepageSections['categories']['subtitle']) ?></p></div></div>
    <div class="category-grid">
        <?php foreach ($categories as $index => $category): ?>
            <a class="category-card reveal" data-delay="<?= $index * 35 ?>" href="products.php?category=<?= rawurlencode((string) $category['filter']) ?>" data-category-link="<?= htmlspecialchars($category['filter']) ?>">
                <span class="category-card__visual">
                    <?php if (isset($category['image'])): ?><img src="<?= htmlspecialchars($category['image']) ?>" alt="" loading="lazy"><?php else: ?><i class="ph <?= htmlspecialchars($category['icon']) ?>"></i><?php endif; ?>
                </span>
                <strong><?= htmlspecialchars($category['name']) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['offer']['is_active'] ?? 1): ?><section class="commerce-section" id="offers">
    <?php $offerSection = $homepageSections['offer']; $offerDesktop = $offerSection['image'] ?: 'assets/images/independence-day-offer-banner-v1.png'; $offerMobile = $offerSection['mobile_image'] ?: 'assets/images/independence-day-offer-banner-mobile-v1.png'; ?>
    <a class="independence-image-offer reveal reveal--scale" href="<?= htmlspecialchars($offerSection['button_url'] ?: '#shop') ?>" aria-label="<?= htmlspecialchars($offerSection['title'] . '. ' . $offerSection['subtitle']) ?>">
        <picture>
            <?php if ($offerMobile): ?><source media="(max-width: 700px)" srcset="<?= htmlspecialchars($offerMobile) ?>"><?php endif; ?>
            <img src="<?= htmlspecialchars($offerDesktop) ?>" alt="<?= htmlspecialchars($offerSection['title'] . '. ' . $offerSection['subtitle']) ?>" loading="lazy">
        </picture>
    </a>
</section>
<?php endif; ?>

<?php if ($homepageSections['combos']['is_active'] ?? 1): ?><section class="commerce-section combo-section">
    <div class="commerce-section__heading reveal">
        <div><h2><?= htmlspecialchars($homepageSections['combos']['title']) ?></h2><p><?= htmlspecialchars($homepageSections['combos']['subtitle']) ?></p></div>
        <div class="commerce-section__actions">
            <a href="#shop">View all combos <i class="ph ph-arrow-right"></i></a>
            <div class="section-rail-controls home-slider-controls" aria-label="Combo slider controls">
                <button type="button" data-scroll-rail="#home-combo-rail" data-scroll-direction="-1" aria-label="Previous combos"><i class="ph ph-arrow-left"></i></button>
                <button type="button" data-scroll-rail="#home-combo-rail" data-scroll-direction="1" aria-label="Next combos"><i class="ph ph-arrow-right"></i></button>
            </div>
        </div>
    </div>
    <div class="combo-grid home-combo-rail" id="home-combo-rail" data-sliding-rail data-auto-slide="4200" tabindex="0" aria-label="Product combos">
        <?php foreach ($combos as $index => $combo): ?>
            <article class="combo-card reveal" data-delay="<?= $index * 70 ?>">
                <span class="combo-card__tag"><?= htmlspecialchars($combo['tag']) ?></span>
                <div class="combo-card__visual">
                    <?php foreach ($combo['items'] as $item): ?><img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"><?php endforeach; ?>
                </div>
                <div class="combo-card__body"><h3><?= htmlspecialchars($combo['title']) ?></h3><div><strong><?= money($combo['price']) ?></strong><s><?= money($combo['original']) ?></s><span>Save <?= (int) round((1 - $combo['price'] / $combo['original']) * 100) ?>%</span></div></div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['reviews']['is_active'] ?? 1): ?><section class="content-section testimonial-reference-section" id="reviews" aria-labelledby="testimonial-heading">
    <header class="testimonial-reference-head reveal">
        <span class="testimonial-reference-head__ghost" aria-hidden="true"><?= htmlspecialchars(strtoupper($homepageSections['reviews']['eyebrow'] ?: 'Testimonials')) ?></span>
        <h2 id="testimonial-heading"><span><?= htmlspecialchars($homepageSections['reviews']['title']) ?></span></h2>
        <p><?= htmlspecialchars($homepageSections['reviews']['subtitle']) ?></p>
    </header>

    <div class="testimonial-reference-controls reveal" aria-label="Testimonial slider controls">
        <span><i class="ph ph-hand-swipe-left"></i> Swipe stories</span>
        <div class="section-rail-controls">
            <button type="button" data-scroll-rail="#testimonial-rail" data-scroll-direction="-1" aria-label="Previous testimonial"><i class="ph ph-arrow-left"></i></button>
            <button type="button" data-scroll-rail="#testimonial-rail" data-scroll-direction="1" aria-label="Next testimonial"><i class="ph ph-arrow-right"></i></button>
        </div>
    </div>

    <div class="testimonial-reference-rail" id="testimonial-rail" data-sliding-rail data-auto-slide="4600" tabindex="0" aria-label="Customer testimonials">
        <?php foreach ($testimonialDeck as $index => $testimonial): ?>
            <article class="testimonial-reference-card reveal" data-delay="<?= min($index * 45, 180) ?>">
                <div class="testimonial-reference-card__top">
                    <?php if (!empty($testimonial['avatar'])): ?>
                        <img class="testimonial-reference-avatar" src="<?= htmlspecialchars($testimonial['avatar']) ?>" alt="<?= htmlspecialchars($testimonial['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <span class="testimonial-reference-avatar testimonial-reference-avatar--initials"><?= htmlspecialchars($testimonial['initials']) ?></span>
                    <?php endif; ?>
                    <div class="testimonial-reference-person">
                        <h3><?= htmlspecialchars($testimonial['name']) ?></h3>
                        <p>Customer</p>
                    </div>
                    <span class="testimonial-reference-quote" aria-hidden="true"><i class="ph ph-quotes"></i></span>
                </div>
                <div class="testimonial-reference-meta">
                    <span class="testimonial-reference-stars" aria-label="<?= (int) $testimonial['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $testimonial['rating']) ?></span>
                    <span class="testimonial-reference-product"><?= htmlspecialchars($testimonial['product']) ?></span>
                </div>
                <blockquote>“<?= htmlspecialchars($testimonial['quote']) ?>”</blockquote>
                <footer>
                    <a href="product.php?slug=<?= urlencode($testimonial['slug']) ?>">Read Full Story</a>
                    <a class="testimonial-reference-arrow" href="product.php?slug=<?= urlencode($testimonial['slug']) ?>" aria-label="Read <?= htmlspecialchars($testimonial['name']) ?>'s story"><i class="ph ph-caret-right"></i></a>
                </footer>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['stories']['is_active'] ?? 1): ?><section class="content-section blog-reference-section" id="stories" aria-labelledby="blog-reference-heading">
    <?php if ($homepageSections['stories']['image']): ?><img class="blog-reference-decoration" src="<?= htmlspecialchars($homepageSections['stories']['image']) ?>" alt="" aria-hidden="true" loading="lazy"><?php endif; ?>
    <header class="blog-reference-head reveal">
        <span class="blog-reference-head__ghost" aria-hidden="true"><?= htmlspecialchars(strtoupper($homepageSections['stories']['eyebrow'] ?: 'Blogs')) ?></span>
        <h2 id="blog-reference-heading"><?= htmlspecialchars($homepageSections['stories']['title']) ?> <i class="ph ph-plant"></i></h2>
        <p><?= htmlspecialchars($homepageSections['stories']['subtitle']) ?></p>
    </header>
    <div class="blog-reference-grid" id="story-rail">
        <?php foreach ($stories as $index => $story): ?>
            <article class="blog-reference-card reveal" data-delay="<?= $index * 60 ?>">
                <img src="<?= htmlspecialchars($story['image']) ?>" alt="<?= htmlspecialchars($story['title']) ?>" loading="lazy">
                <span class="blog-reference-card__accent" aria-hidden="true"></span>
                <div class="blog-reference-card__wash" aria-hidden="true"></div>
                <div class="blog-reference-card__content">
                    <span><?= htmlspecialchars($story['tag']) ?></span>
                    <h3><?= htmlspecialchars($story['title']) ?></h3>
                    <p><?= htmlspecialchars($story['excerpt']) ?></p>
                    <a href="<?= htmlspecialchars($story['url'] ?? 'blog.php') ?>">Read More <i class="ph ph-arrow-right"></i></a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <a class="blog-reference-more reveal" href="<?= htmlspecialchars($homepageSections['stories']['button_url'] ?: 'blog.php') ?>"><?= htmlspecialchars($homepageSections['stories']['button_label'] ?: 'View More') ?> <i class="ph ph-arrow-right"></i></a>
</section>
<?php endif; ?>

<?php if ($homepageSections['reels']['is_active'] ?? 1): ?><section class="content-section reels-section" id="made-with-care">
    <div class="content-heading content-heading--center reveal">
        <div><span class="content-eyebrow"><?= htmlspecialchars($homepageSections['reels']['eyebrow']) ?></span><h2><?= htmlspecialchars($homepageSections['reels']['title']) ?></h2></div>
        <p><?= htmlspecialchars($homepageSections['reels']['subtitle']) ?></p>
    </div>
    <div class="reel-grid">
        <?php foreach ($homepageReels as $index => $media): $product = product_by_slug($products, (string) $media['product_slug']); ?>
            <article class="reel-card reveal" data-delay="<?= $index * 75 ?>">
                <div class="reel-card__media">
                    <?php if ($media['media_type'] === 'video' && $media['file_path']): ?>
                        <video controls playsinline preload="metadata" <?= $media['poster_path'] ? 'poster="' . htmlspecialchars($media['poster_path']) . '"' : '' ?>><source src="<?= htmlspecialchars($media['file_path']) ?>"></video>
                    <?php elseif ($media['media_type'] === 'external_video'): ?>
                        <?php if ($media['poster_path']): ?><img src="<?= htmlspecialchars($media['poster_path']) ?>" alt="<?= htmlspecialchars($media['alt_text'] ?: $media['title']) ?>" loading="lazy"><?php else: ?><span class="reel-card__placeholder"><i class="ph ph-video-camera"></i></span><?php endif; ?>
                        <a href="<?= htmlspecialchars($media['external_url']) ?>" target="_blank" rel="noopener" aria-label="Watch <?= htmlspecialchars($media['title']) ?>"><i class="ph ph-play"></i></a>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="<?= htmlspecialchars($media['alt_text'] ?: $media['title']) ?>" loading="lazy">
                        <?php if ($media['link_url']): ?><a href="<?= htmlspecialchars($media['link_url']) ?>" aria-label="Open <?= htmlspecialchars($media['title']) ?>"><i class="ph ph-arrow-up-right"></i></a><?php endif; ?>
                    <?php endif; ?>
                    <span><?= sprintf('%02d', $index + 1) ?></span>
                </div>
                <div class="reel-card__product">
                    <?php if ($product): ?>
                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="">
                        <div><h3><?= htmlspecialchars($media['title'] ?: $product['name']) ?></h3><p><?= money($product['price']) ?> <span><?= htmlspecialchars($product['weight']) ?></span></p></div>
                        <button type="button" data-add-to-cart data-id="<?= htmlspecialchars($product['id']) ?>" data-name="<?= htmlspecialchars($product['full_name']) ?>" data-price="<?= $product['price'] ?>" data-image="<?= htmlspecialchars($product['image']) ?>" aria-label="Add <?= htmlspecialchars($product['name']) ?> to cart"><i class="ph ph-shopping-bag"></i></button>
                    <?php else: ?><div><h3><?= htmlspecialchars($media['title']) ?></h3><p><?= htmlspecialchars($media['subtitle']) ?></p></div><?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['newsletter']['is_active'] ?? 1): ?><section class="commerce-section newsletter-section reveal">
    <div class="newsletter-panel">
        <div class="newsletter-panel__icon"><i class="ph ph-envelope-simple"></i></div>
        <div><h2><?= htmlspecialchars($homepageSections['newsletter']['title']) ?></h2><p><?= htmlspecialchars($homepageSections['newsletter']['subtitle']) ?></p></div>
        <form action="#" data-newsletter-form><label class="sr-only" for="newsletter-email">Enter your email</label><input id="newsletter-email" type="email" placeholder="Enter your email" required><button type="submit"><?= htmlspecialchars($homepageSections['newsletter']['button_label'] ?: 'Subscribe') ?> <i class="ph ph-arrow-right"></i></button></form>
        <div class="newsletter-panel__leaf" aria-hidden="true"><i class="ph ph-plant"></i></div>
    </div>
</section>
<?php endif; ?>

<?php if (gawdee_setting('offer_popup_enabled', '1') === '1'): ?>
<?php
$offerPopupImage = gawdee_setting('offer_popup_image', 'assets/images/independence-offer-popup-v1.webp');
$offerPopupCode = gawdee_setting('offer_code', 'FREEDOM10');
$offerPopupPercent = gawdee_setting('offer_percent', '10');
$offerPopupDelay = min(10000, max(0, (int) gawdee_setting('offer_popup_delay_ms', '850')));
$offerPopupKey = substr(hash('sha256', $offerPopupImage . '|' . $offerPopupCode . '|' . $offerPopupPercent), 0, 14);
?>
<div class="offer-popup" data-offer-popup data-popup-key="<?= htmlspecialchars($offerPopupKey) ?>" data-popup-delay="<?= $offerPopupDelay ?>" hidden>
    <section class="offer-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="independence-offer-title" aria-describedby="independence-offer-description">
        <div class="offer-popup__flag" aria-hidden="true"><span></span><span></span><span></span></div>
        <button class="offer-popup__close" type="button" data-offer-popup-close aria-label="Close Independence Day offer"><i class="ph ph-x"></i></button>
        <a class="offer-popup__art" href="#shop" data-offer-popup-shop>
            <img src="<?= htmlspecialchars($offerPopupImage) ?>" alt="Happy Independence Day. Flat <?= htmlspecialchars($offerPopupPercent) ?> percent off all Gawdee products with code <?= htmlspecialchars($offerPopupCode) ?>.">
        </a>
        <div class="offer-popup__actions">
            <div class="offer-popup__copy">
                <span id="independence-offer-title">Independence Day special</span>
                <p id="independence-offer-description">Use code <strong><?= htmlspecialchars($offerPopupCode) ?></strong> at checkout</p>
            </div>
            <button type="button" class="offer-popup__code" data-copy-offer="<?= htmlspecialchars($offerPopupCode) ?>" aria-label="Copy offer code <?= htmlspecialchars($offerPopupCode) ?>"><strong><?= htmlspecialchars($offerPopupCode) ?></strong><span><i class="ph ph-copy"></i> Copy code</span></button>
            <a class="offer-popup__shop" href="#shop" data-offer-popup-shop>Shop offer <i class="ph ph-arrow-right"></i></a>
        </div>
    </section>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
