<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$pageTitle = 'Watch & Discover — Gawdee Short Reels & Videos';
$pageDescription = 'Discover Gawdee in motion. Watch short video reels demonstrating traditional Bilona ghee preparation, raw forest honey harvesting, and organic wellness.';
$pageKeywords = 'Gawdee reels, organic food videos, A2 ghee making video, Gawdee stories, traditional food videos';
$bodyClass = 'reels-page';

$selectedCategory = isset($_GET['category']) ? trim((string) $_GET['category']) : '';
$selectedProductSlug = isset($_GET['product']) ? trim((string) $_GET['product']) : '';

$allMedia = gawdee_homepage_media(null, false);
$videoReels = array_values(array_filter($allMedia, static function (array $m): bool {
    return in_array($m['media_type'], ['video', 'external_video'], true) && (!empty($m['file_path']) || !empty($m['external_url']));
}));

if (!empty($selectedProductSlug)) {
    $videoReels = array_values(array_filter($videoReels, static function (array $m) use ($selectedProductSlug): bool {
        return !empty($m['product_slug']) && $m['product_slug'] === $selectedProductSlug;
    }));
} elseif (!empty($selectedCategory) && $selectedCategory !== 'all') {
    $videoReels = array_values(array_filter($videoReels, static function (array $m) use ($products, $selectedCategory): bool {
        if (empty($m['product_slug'])) {
            return false;
        }
        $prod = product_by_slug($products, (string) $m['product_slug']);
        return $prod && isset($prod['category_key']) && $prod['category_key'] === $selectedCategory;
    }));
}

$categoriesList = [
    ['key' => 'all', 'label' => 'All Videos', 'icon' => 'ph-squares-four'],
    ['key' => 'ghee', 'label' => 'A2 Ghee', 'icon' => 'ph-drop'],
    ['key' => 'honey', 'label' => 'Forest Honey', 'icon' => 'ph-flower'],
    ['key' => 'nutrition', 'label' => 'Mix Me', 'icon' => 'ph-lightning'],
    ['key' => 'wellness', 'label' => 'Drops', 'icon' => 'ph-first-aid'],
];

require __DIR__ . '/includes/header.php';
?>

<main class="reels-hub">
    <section class="reels-hero container">
        <div class="reels-hero__content">
            <span class="eyebrow"><i class="ph ph-film-strip"></i> Gawdee in Motion</span>
            <h1>Watch &amp; Discover Gawdee Stories</h1>
            <p>A closer look at how pure A2 Gir cow ghee, raw forest honey, and organic foods move from nature to your everyday family table.</p>
        </div>
    </section>

    <section class="reels-filter-bar container">
        <div class="category-pills">
            <?php foreach ($categoriesList as $cat): 
                $isActive = (empty($selectedCategory) && $cat['key'] === 'all') || ($selectedCategory === $cat['key']);
                ?>
                <a href="reels.php<?= $cat['key'] !== 'all' ? '?category=' . urlencode($cat['key']) : '' ?>" 
                   class="category-pill <?= $isActive ? 'is-active' : '' ?>">
                    <i class="ph <?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="reels-grid-section container">
        <?php if (empty($videoReels)): ?>
            <div class="reels-empty">
                <i class="ph ph-video-camera-slash"></i>
                <h3>No video reels found</h3>
                <p>We couldn't find videos for this selection. Explore all our Gawdee video stories below.</p>
                <a href="reels.php" class="button button--primary">View All Videos</a>
            </div>
        <?php else: ?>
            <div class="reels-hub-grid">
                <?php foreach ($videoReels as $index => $media):
                    $product = product_by_slug($products, (string) $media['product_slug']);
                    $videoSrc = $media['file_path'] ?: $media['external_url'];
                    $posterSrc = $media['poster_path'] ?: ($product['image'] ?? 'assets/images/logo.png');
                    ?>
                    <article class="reel-hub-card reveal" data-delay="<?= ($index % 4) * 60 ?>">
                        <div class="reel-hub-card__media"
                             data-reel-trigger
                             data-video-src="<?= htmlspecialchars($videoSrc) ?>"
                             data-video-type="<?= htmlspecialchars($media['media_type']) ?>"
                             data-video-title="<?= htmlspecialchars($media['title'] ?: ($product['name'] ?? 'Gawdee Reel')) ?>"
                             data-video-subtitle="<?= htmlspecialchars($media['subtitle']) ?>"
                             data-video-poster="<?= htmlspecialchars($posterSrc) ?>"
                             data-product-id="<?= htmlspecialchars($product['id'] ?? '') ?>"
                             data-product-name="<?= htmlspecialchars($product['full_name'] ?? '') ?>"
                             data-product-price="<?= htmlspecialchars((string)($product['price'] ?? '')) ?>"
                             data-product-image="<?= htmlspecialchars($product['image'] ?? '') ?>"
                             data-product-url="<?= $product ? 'product.php?slug=' . rawurlencode($product['slug']) : '' ?>">

                            <img src="<?= htmlspecialchars($posterSrc) ?>" alt="<?= htmlspecialchars($media['alt_text'] ?: $media['title']) ?>" loading="lazy">
                            
                            <div class="reel-hub-card__overlay">
                                <button type="button" class="reel-hub-card__play" aria-label="Watch video reel">
                                    <i class="ph-fill ph-play"></i>
                                </button>
                                <span class="reel-hub-card__tag"><i class="ph ph-film-reel"></i> Reel</span>
                            </div>
                        </div>

                        <div class="reel-hub-card__content">
                            <h3><?= htmlspecialchars($media['title'] ?: ($product['name'] ?? 'Gawdee Story')) ?></h3>
                            <p><?= htmlspecialchars($media['subtitle'] ?: 'Pure food thoughtfully made for modern families.') ?></p>
                            
                            <?php if ($product): ?>
                                <a href="product?slug=<?= rawurlencode($product['slug']) ?>" class="reel-hub-card__product-link" aria-label="View details for <?= htmlspecialchars($product['name']) ?>">
                                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($product['name']) ?></strong>
                                        <small><?= money($product['price']) ?> · <?= htmlspecialchars($product['weight']) ?></small>
                                    </div>
                                    <span class="reel-hub-card__product-arrow" aria-hidden="true">
                                        <i class="ph ph-arrow-right"></i>
                                    </span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
