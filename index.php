<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$pageTitle = 'Gawdee — Pure by Nature. Trusted for Generations.';
$pageDescription = 'Shop Gawdee A2 Gir cow ghee, raw honey, natural nutrition blends and traditional pantry essentials.';
$bodyClass = 'commerce-home gawdee-reference-home';

$homepageSections = gawdee_sections();
$homepageMedia = gawdee_homepage_media('reels');
$videoTestimonials = array_slice(gawdee_video_testimonials(), 0, 6);
$heroSection = $homepageSections['hero'];
$heroTitle = trim((string) $heroSection['title']) ?: 'Pure by Nature. Trusted for Generations.';
$heroTitleParts = preg_split('/(?=Trusted\b)/', $heroTitle, 2) ?: [$heroTitle];
$heroLead = rtrim((string) ($heroTitleParts[0] ?? $heroTitle));
$heroRest = trim((string) ($heroTitleParts[1] ?? ''));

$firstProductIn = static function (string $category) use ($products): ?array {
    foreach ($products as $product) {
        if (($product['category_key'] ?? '') === $category) {
            return $product;
        }
    }
    return $products[0] ?? null;
};

$ghee = $firstProductIn('ghee');
$honey = $firstProductIn('honey');
$sugar = $firstProductIn('sugar');
$nutrition = $firstProductIn('nutrition');
$wellness = $firstProductIn('wellness');

$homePackshot = static function (?array $product): string {
    if (!$product) {
        return 'assets/images/logo.png';
    }
    return match ((string) ($product['id'] ?? '')) {
        'ghee-500' => 'assets/images/hero-products/ghee-cutout-v1.png',
        'burra-sugar' => 'assets/images/hero-products/sugar-cutout-v1.png',
        'mixme-choco' => 'assets/images/products/mixme-choco.webp',
        'mixme-elaichi' => 'assets/images/products/mixme-elaichi.webp',
        default => (string) ($product['image'] ?? 'assets/images/logo.png'),
    };
};

$categories = [
    ['name' => 'A2 Gir Cow Ghee', 'subtitle' => 'Pure, bilona-churned', 'filter' => 'ghee', 'product' => $ghee, 'class' => 'is-dark'],
    ['name' => 'Natural Honey', 'subtitle' => 'Raw & naturally rich', 'filter' => 'honey', 'product' => $honey, 'class' => 'is-honey'],
    ['name' => 'Natural Sugars', 'subtitle' => 'Traditional sweetness', 'filter' => 'sugar', 'product' => $sugar, 'class' => 'is-sugar'],
    ['name' => 'Grains & Millets', 'subtitle' => 'Everyday nourishment', 'filter' => 'nutrition', 'product' => $nutrition, 'class' => 'is-grain'],
    ['name' => 'Wellness Products', 'subtitle' => 'Clean daily support', 'filter' => 'wellness', 'product' => $wellness, 'class' => 'is-wellness'],
];

$featuredProducts = array_slice($products, 0, 5);

$testimonials = [];
foreach (array_slice(gawdee_testimonials(), 0, 3) as $story) {
    $relatedProduct = product_by_slug($products, (string) $story['product_slug']) ?? ($products[0] ?? null);
    $testimonials[] = [
        'name' => $story['name'],
        'avatar' => $story['avatar'],
        'initials' => $story['initials'],
        'location' => $story['product_name'] ?: ($relatedProduct['name'] ?? 'Gawdee Customer'),
        'quote' => $story['quote'],
        'rating' => (int) $story['rating'],
    ];
}

$benefitItems = gawdee_section_items('benefits');
$processItems = gawdee_section_items('process');
$assuranceItems = gawdee_section_items('assurance');
$whyItems = gawdee_section_items('why');
$newsletterPerks = gawdee_section_items('newsletter-perks');

$instagramImages = [
    'assets/images/catalog/gawdee-gir-cow-a2-ghee-500-ml/gallery-02.webp',
    'assets/images/catalog/gawdee-gir-cow-a2-ghee-500-ml/story-01.webp',
    'assets/images/catalog/gawdee-raw-wild-forest-honey-650-g/gallery-01.webp',
    'assets/images/catalog/gawdee-raw-wild-forest-honey-650-g/story-01.webp',
    'assets/images/catalog/gawdee-mixme-choco-500-g/gallery-01.webp',
    'assets/images/catalog/gawdee-moringa-powder-300-g/gallery-01.webp',
    'assets/images/catalog/gawdee-bura-sugar-1-kg/gallery-01.webp',
    'assets/images/catalog/gawdee-gir-cow-a2-ghee-1-ltr/gallery-01.webp',
];
$instagramFeed = [];
foreach ($instagramImages as $index => $image) {
    $media = $homepageMedia ? $homepageMedia[$index % count($homepageMedia)] : [];
    $instagramFeed[] = [
        'image' => $image,
        'link' => (string) ($media['link_url'] ?? $media['external_url'] ?? 'https://www.instagram.com/gawdee_organic/'),
        'title' => (string) ($media['alt_text'] ?? $media['title'] ?? 'Gawdee farm and product story'),
    ];
}

$videoEmbedUrl = static function (string $url): string {
    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https:\/\//i', $url)) {
        return '';
    }
    $parts = parse_url($url);
    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = trim((string) ($parts['path'] ?? ''), '/');
    if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
        parse_str((string) ($parts['query'] ?? ''), $query);
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($query['v'] ?? ''));
        return $id !== '' ? 'https://www.youtube.com/embed/' . $id : '';
    }
    if ($host === 'youtu.be') {
        $id = preg_replace('/[^A-Za-z0-9_-]/', '', $path);
        return $id !== '' ? 'https://www.youtube.com/embed/' . $id : '';
    }
    if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true)) {
        $id = preg_replace('/[^0-9]/', '', $path);
        return $id !== '' ? 'https://player.vimeo.com/video/' . $id : '';
    }
    return '';
};

require __DIR__ . '/includes/header.php';
?>

<?php if ($homepageSections['hero']['is_active'] ?? 1): ?>
<section class="gawdee-hero" aria-labelledby="gawdee-hero-title">
    <img class="gawdee-hero__art" src="<?= htmlspecialchars($heroSection['image'] ?: 'assets/images/gawdee-a2-farm-hero-v1.png') ?>" alt="Gawdee A2 Gir Cow Ghee with Gir cows on an Indian farm" fetchpriority="high">
    <div class="container gawdee-hero__inner">
        <div class="gawdee-hero__copy reveal">
            <span class="gawdee-kicker"><i class="ph ph-leaf"></i> <?= htmlspecialchars($heroSection['eyebrow'] ?: '100% Pure • Natural • Tested') ?></span>
            <h1 id="gawdee-hero-title"><em><?= htmlspecialchars($heroLead) ?></em><?= $heroRest !== '' ? '<br><span>' . htmlspecialchars($heroRest) . '</span>' : '' ?></h1>
            <p><?= htmlspecialchars($heroSection['subtitle'] ?: 'Made from the milk of free-grazed Gir cows. Our A2 Ghee is bilona-churned in small batches to bring you pure nutrition that your family deserves.') ?></p>
            <div class="gawdee-hero__actions">
                <a class="button button--primary" href="<?= htmlspecialchars($heroSection['button_url'] ?: 'products.php?category=ghee') ?>"><?= htmlspecialchars($heroSection['button_label'] ?: 'Shop A2 Ghee') ?> <i class="ph ph-arrow-right"></i></a>
                <a class="button button--outline" href="#farms">Explore Our Farms</a>
            </div>
            <div class="gawdee-hero__proof" aria-label="A2 ghee qualities">
                <span><i class="ph ph-cow"></i> A2 Protein Rich</span>
                <span><i class="ph ph-stomach"></i> Easy to Digest</span>
                <span><i class="ph ph-flask"></i> Bilona Churned</span>
                <span><i class="ph ph-seal-check"></i> Lab Tested</span>
            </div>
        </div>
        <div class="gawdee-hero__farm-notes" aria-label="Farm sourcing highlights">
            <span><i class="ph ph-cow"></i><strong>Made with Love</strong>from Our Farms</span>
            <span><i class="ph ph-drop"></i><strong>Get Cow Milk</strong>100% Pure</span>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['benefits']['is_active'] ?? 1): ?>
<section class="gawdee-benefits container reveal" aria-label="<?= htmlspecialchars($homepageSections['benefits']['title'] ?: 'Product qualities') ?>">
    <?php foreach ($benefitItems as $item): ?><div><?php if ($item['image']): ?><img src="<?= htmlspecialchars($item['image']) ?>" alt="" loading="lazy"><?php else: ?><i class="ph <?= htmlspecialchars($item['icon']) ?>"></i><?php endif; ?><span><strong><?= htmlspecialchars($item['title']) ?></strong><?= htmlspecialchars($item['subtitle']) ?></span></div><?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($homepageSections['categories']['is_active'] ?? 1): ?>
<section class="gawdee-section gawdee-category-section container" id="categories" aria-labelledby="category-title">
    <header class="gawdee-section-title reveal">
        <span class="gawdee-title-leaf" aria-hidden="true"><i class="ph ph-plant"></i></span>
        <h2 id="category-title"><?= htmlspecialchars($homepageSections['categories']['title'] ?: 'Shop by Category') ?></h2>
        <span class="gawdee-title-leaf is-right" aria-hidden="true"><i class="ph ph-plant"></i></span>
    </header>
    <div class="gawdee-category-grid">
        <?php foreach ($categories as $index => $category): ?>
        <a class="gawdee-category-card <?= htmlspecialchars($category['class']) ?> reveal" data-delay="<?= $index * 45 ?>" href="products.php?category=<?= rawurlencode($category['filter']) ?>" data-category-link="<?= htmlspecialchars($category['filter']) ?>">
            <span class="gawdee-category-card__copy"><strong><?= htmlspecialchars($category['name']) ?></strong><small><?= htmlspecialchars($category['subtitle']) ?></small></span>
            <img src="<?= htmlspecialchars($homePackshot($category['product'])) ?>" alt="<?= htmlspecialchars($category['product']['name'] ?? $category['name']) ?>" loading="lazy">
            <span class="gawdee-mini-cta">Shop Now <i class="ph ph-arrow-right"></i></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['process']['is_active'] ?? 1): ?>
<section class="gawdee-section gawdee-process container" id="farms" aria-labelledby="process-title">
    <header class="gawdee-section-title gawdee-section-title--compact reveal"><h2 id="process-title"><?= htmlspecialchars($homepageSections['process']['title'] ?: 'From Our Farms to Your Family') ?></h2></header>
    <div class="gawdee-process__track">
        <?php foreach ($processItems as $index => $step): ?>
        <div class="gawdee-process__step reveal" data-delay="<?= $index * 45 ?>">
            <span class="gawdee-process__icon"><?php if ($step['image']): ?><img src="<?= htmlspecialchars($step['image']) ?>" alt="" loading="lazy"><?php else: ?><i class="ph <?= htmlspecialchars($step['icon']) ?>"></i><?php endif; ?></span>
            <strong><?= htmlspecialchars($step['title']) ?></strong>
            <small><?= htmlspecialchars($step['subtitle']) ?></small>
        </div>
        <?php if ($index < count($processItems) - 1): ?><i class="ph ph-arrow-right gawdee-process__arrow" aria-hidden="true"></i><?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['shop']['is_active'] ?? 1): ?>
<section class="gawdee-section gawdee-products container" id="shop" aria-labelledby="bestseller-title">
    <header class="gawdee-section-title reveal">
        <span class="gawdee-title-leaf" aria-hidden="true"><i class="ph ph-plant"></i></span>
        <h2 id="bestseller-title"><?= htmlspecialchars($homepageSections['shop']['title'] ?: 'Best Sellers') ?></h2>
        <span class="gawdee-title-leaf is-right" aria-hidden="true"><i class="ph ph-plant"></i></span>
        <a href="<?= htmlspecialchars($homepageSections['shop']['button_url'] ?: 'products.php') ?>"><?= htmlspecialchars($homepageSections['shop']['button_label'] ?: 'View all products') ?> <i class="ph ph-arrow-right"></i></a>
    </header>
    <div class="gawdee-product-grid" data-product-grid>
        <?php foreach ($featuredProducts as $index => $product): ?>
        <article class="gawdee-product-card reveal" data-delay="<?= $index * 40 ?>" data-category="<?= htmlspecialchars($product['category_key']) ?>" data-search-name="<?= htmlspecialchars(strtolower($product['full_name'] . ' ' . $product['category'])) ?>">
            <?php if ($index < 3): ?><span class="gawdee-product-card__badge <?= $index === 1 ? 'is-sale' : '' ?>"><?= $index === 0 ? 'Bestseller' : ($index === 1 ? 'Sale' : 'New') ?></span><?php endif; ?>
            <a class="gawdee-product-card__media" href="product.php?slug=<?= urlencode($product['slug']) ?>"><img src="<?= htmlspecialchars($homePackshot($product)) ?>" alt="<?= htmlspecialchars($product['full_name']) ?>" loading="lazy"></a>
            <div class="gawdee-product-card__body">
                <h3><a href="product.php?slug=<?= urlencode($product['slug']) ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
                <small><?= htmlspecialchars($product['weight']) ?></small>
                <div class="gawdee-rating" aria-label="4.9 out of 5 stars"><span>4.9</span> ★★★★★ <small>(<?= 95 + ($index * 47) ?>)</small></div>
                <div class="gawdee-product-card__price"><strong><?= money($product['price']) ?></strong><s><?= money($product['original_price']) ?></s></div>
                <button type="button" data-add-to-cart data-id="<?= htmlspecialchars($product['id']) ?>" data-name="<?= htmlspecialchars($product['full_name']) ?>" data-price="<?= (int) $product['price'] ?>" data-image="<?= htmlspecialchars($product['image']) ?>">Add to Cart <i class="ph ph-shopping-cart"></i></button>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <p class="product-empty" data-product-empty hidden>No products match your search.</p>
</section>
<?php endif; ?>

<?php if ($homepageSections['combos']['is_active'] ?? 1): ?>
<section class="gawdee-section container" id="offers">
    <div class="gawdee-combo-banner reveal">
        <div class="gawdee-combo-banner__copy"><h2><?= htmlspecialchars($homepageSections['combos']['title'] ?: 'Healthy Combos for a Better You!') ?></h2><p><?= htmlspecialchars($homepageSections['combos']['subtitle'] ?: 'Pure • Natural • Nutritious • Trusted') ?></p><a class="button button--primary" href="<?= htmlspecialchars($homepageSections['combos']['button_url'] ?: 'products.php') ?>"><?= htmlspecialchars($homepageSections['combos']['button_label'] ?: 'Explore Combos') ?> <i class="ph ph-arrow-right"></i></a></div>
        <div class="gawdee-combo-banner__products" aria-label="Featured Gawdee combo"><img src="<?= htmlspecialchars($ghee['image']) ?>" alt="<?= htmlspecialchars($ghee['name']) ?>" loading="lazy"><img src="<?= htmlspecialchars($honey['image']) ?>" alt="<?= htmlspecialchars($honey['name']) ?>" loading="lazy"><img src="<?= htmlspecialchars($nutrition['image']) ?>" alt="<?= htmlspecialchars($nutrition['name']) ?>" loading="lazy"></div>
        <div class="gawdee-combo-banner__points"><span><i class="ph ph-coins"></i><strong>Save More</strong>with Combos</span><span><i class="ph ph-leaf"></i><strong>100% Natural</strong>Ingredients</span><span><i class="ph ph-users-three"></i><strong>Perfect for</strong>Your Family</span></div>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['assurance']['is_active'] ?? 1): ?>
<section class="gawdee-assurance-band container reveal" aria-label="<?= htmlspecialchars($homepageSections['assurance']['title'] ?: 'Why families trust Gawdee') ?>">
    <?php foreach ($assuranceItems as $item): ?><div><?php if ($item['image']): ?><img src="<?= htmlspecialchars($item['image']) ?>" alt="" loading="lazy"><?php else: ?><i class="ph <?= htmlspecialchars($item['icon']) ?>"></i><?php endif; ?><span><strong><?= htmlspecialchars($item['title']) ?></strong><?= htmlspecialchars($item['subtitle']) ?></span></div><?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (($homepageSections['about']['is_active'] ?? 1) || ($homepageSections['why']['is_active'] ?? 1) || ($homepageSections['reviews']['is_active'] ?? 1)): ?>
<section class="gawdee-section gawdee-story-grid container" id="about" aria-label="The Gawdee difference">
    <?php if ($homepageSections['about']['is_active'] ?? 1): ?><article class="gawdee-story-card gawdee-story-card--ingredients reveal"><div><h2><?= htmlspecialchars($homepageSections['about']['title'] ?: 'Natural Ingredients. No Shortcuts.') ?></h2><p><?= htmlspecialchars($homepageSections['about']['body'] ?: $homepageSections['about']['subtitle']) ?></p><a href="<?= htmlspecialchars($homepageSections['about']['button_url'] ?: 'blog.php') ?>"><?= htmlspecialchars($homepageSections['about']['button_label'] ?: 'Know Our Story') ?> <i class="ph ph-arrow-right"></i></a></div><img src="<?= htmlspecialchars($homepageSections['about']['image'] ?: 'assets/images/blogs/quality-over-quantity-v1.webp') ?>" alt="Natural ingredients sourced with care" loading="lazy"></article><?php endif; ?>
    <?php if ($homepageSections['why']['is_active'] ?? 1): ?><article class="gawdee-story-card gawdee-story-card--why reveal" data-delay="60"><h2><?= htmlspecialchars($homepageSections['why']['title'] ?: 'Why Choose Gawdee?') ?></h2><div class="gawdee-why-list"><?php foreach ($whyItems as $item): ?><span><i class="ph <?= htmlspecialchars($item['icon']) ?>"></i> <?= htmlspecialchars($item['title']) ?></span><?php endforeach; ?></div></article><?php endif; ?>
    <?php if ($homepageSections['reviews']['is_active'] ?? 1): ?><article class="gawdee-story-card gawdee-story-card--reviews reveal" data-delay="120"><h2><?= htmlspecialchars($homepageSections['reviews']['title'] ?: 'Loved by Thousands of Families') ?></h2><p><?= htmlspecialchars($homepageSections['reviews']['subtitle'] ?: '4.9/5 from 10,000+ happy customers worldwide') ?></p><div class="gawdee-stars">★★★★★</div><a href="<?= htmlspecialchars($homepageSections['reviews']['button_url'] ?: '#reviews') ?>"><?= htmlspecialchars($homepageSections['reviews']['button_label'] ?: 'Read Reviews') ?> <i class="ph ph-arrow-right"></i></a></article><?php endif; ?>
</section>
<?php endif; ?>

<?php if (($homepageSections['reviews']['is_active'] ?? 1) && $testimonials): ?>
<section class="gawdee-testimonials container" id="reviews" aria-label="Customer reviews">
    <?php foreach ($testimonials as $index => $testimonial): ?>
    <article class="gawdee-testimonial reveal" data-delay="<?= $index * 55 ?>">
        <?php if ($testimonial['avatar']): ?><img src="<?= htmlspecialchars($testimonial['avatar']) ?>" alt="<?= htmlspecialchars($testimonial['name']) ?>" loading="lazy"><?php else: ?><span class="gawdee-testimonial__initials"><?= htmlspecialchars($testimonial['initials']) ?></span><?php endif; ?>
        <div><strong><?= htmlspecialchars($testimonial['name']) ?></strong><small><?= htmlspecialchars($testimonial['location']) ?></small><div class="gawdee-stars" aria-label="<?= $testimonial['rating'] ?> out of 5 stars"><?= str_repeat('★', $testimonial['rating']) ?></div><blockquote>“<?= htmlspecialchars($testimonial['quote']) ?>”</blockquote></div>
    </article>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if (($homepageSections['video_testimonials']['is_active'] ?? 1) && $videoTestimonials): ?>
<section class="gawdee-section gawdee-video-testimonials container" id="video-testimonials" aria-labelledby="video-testimonials-title">
    <header class="gawdee-section-title gawdee-section-title--stacked reveal">
        <?php if ($homepageSections['video_testimonials']['eyebrow']): ?><span class="gawdee-kicker"><?= htmlspecialchars($homepageSections['video_testimonials']['eyebrow']) ?></span><?php endif; ?>
        <h2 id="video-testimonials-title"><?= htmlspecialchars($homepageSections['video_testimonials']['title'] ?: 'Real families. Real Gawdee experiences.') ?></h2>
        <?php if ($homepageSections['video_testimonials']['subtitle']): ?><p><?= htmlspecialchars($homepageSections['video_testimonials']['subtitle']) ?></p><?php endif; ?>
    </header>
    <div class="gawdee-video-testimonials__grid">
        <?php foreach ($videoTestimonials as $index => $item): $embedUrl = $item['video_type'] === 'external_video' ? $videoEmbedUrl((string) $item['external_url']) : ''; ?>
        <article class="gawdee-video-testimonial reveal" data-delay="<?= min($index * 55, 220) ?>">
            <div class="gawdee-video-testimonial__media">
                <?php if ($item['video_type'] === 'upload'): ?>
                    <video controls preload="metadata" <?= $item['poster_path'] ? 'poster="' . htmlspecialchars($item['poster_path']) . '"' : '' ?>><source src="<?= htmlspecialchars($item['video_path']) ?>"></video>
                <?php elseif ($embedUrl !== ''): ?>
                    <iframe src="<?= htmlspecialchars($embedUrl) ?>" title="Video testimonial from <?= htmlspecialchars($item['name']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($item['external_url']) ?>" target="_blank" rel="noopener" <?= $item['poster_path'] ? 'style="background-image:url(' . htmlspecialchars($item['poster_path']) . ')"' : '' ?>><i class="ph ph-play-circle"></i><span>Watch testimonial</span></a>
                <?php endif; ?>
            </div>
            <div class="gawdee-video-testimonial__body"><div class="gawdee-stars" aria-label="<?= (int) $item['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $item['rating']) ?></div><blockquote>“<?= htmlspecialchars($item['quote']) ?>”</blockquote><strong><?= htmlspecialchars($item['name']) ?></strong><?php if ($item['role_location']): ?><small><?= htmlspecialchars($item['role_location']) ?></small><?php endif; ?></div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($homepageSections['reels']['is_active'] ?? 1): ?>
<section class="gawdee-section gawdee-instagram container" aria-labelledby="instagram-title">
    <header class="gawdee-section-title gawdee-section-title--stacked reveal"><h2 id="instagram-title"><?= htmlspecialchars($homepageSections['reels']['title'] ?: 'From Our Instagram') ?></h2><small><?= htmlspecialchars($homepageSections['reels']['subtitle'] ?: '@gawdee_organic') ?></small></header>
    <div class="gawdee-instagram__rail">
        <?php foreach ($instagramFeed as $index => $media): ?>
        <a href="<?= htmlspecialchars($media['link']) ?>" <?= str_starts_with($media['link'], 'http') ? 'target="_blank" rel="noopener"' : '' ?> class="reveal" data-delay="<?= min($index * 35, 175) ?>"><img src="<?= htmlspecialchars($media['image']) ?>" alt="<?= htmlspecialchars($media['title']) ?>" loading="lazy"><i class="ph ph-instagram-logo" aria-hidden="true"></i></a>
        <?php endforeach; ?>
    </div>
    <a class="gawdee-instagram__follow" href="https://www.instagram.com/gawdee_organic/" target="_blank" rel="noopener">Follow us on Instagram <i class="ph ph-arrow-right"></i></a>
</section>
<?php endif; ?>

<?php if ($homepageSections['newsletter']['is_active'] ?? 1): ?>
<section class="gawdee-newsletter reveal"><div class="container gawdee-newsletter__inner"><i class="ph ph-envelope-simple-open gawdee-newsletter__icon" aria-hidden="true"></i><div><h2><?= htmlspecialchars($homepageSections['newsletter']['title'] ?: 'Join the Gawdee Family') ?></h2><p><?= htmlspecialchars($homepageSections['newsletter']['subtitle'] ?: 'Get farm news, tips & offers! Plus, be a part of something pure.') ?></p></div><form action="#" data-newsletter-form><label class="sr-only" for="newsletter-email">Email address</label><input id="newsletter-email" type="email" placeholder="Enter your e-mail address" required><button type="submit"><?= htmlspecialchars($homepageSections['newsletter']['button_label'] ?: 'Subscribe') ?></button></form><div class="gawdee-newsletter__perks"><?php foreach ($newsletterPerks as $item): ?><span><i class="ph <?= htmlspecialchars($item['icon']) ?>"></i> <?= htmlspecialchars($item['title']) ?></span><?php endforeach; ?></div></div></section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
