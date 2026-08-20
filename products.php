<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$pageTitle = 'All Gawdee products | Natural pantry & wellness';
$pageDescription = 'Browse the complete Gawdee catalogue: A2 Gir Cow Ghee, raw honey, MixMe nutrition, traditional sugar, powders and wellness drops.';
$bodyClass = 'catalog-page';
$categoryFilters = [
    'all' => ['All products', 'ph-squares-four'],
    'ghee' => ['Ghee', 'ph-bowl-steam'],
    'honey' => ['Honey', 'ph-drop'],
    'nutrition' => ['Mix Me', 'ph-grains'],
    'sugar' => ['Sugar', 'ph-cube'],
    'wellness' => ['Wellness', 'ph-leaf'],
];
$activeCategory = array_key_exists((string) ($_GET['category'] ?? ''), $categoryFilters) ? (string) $_GET['category'] : 'all';
require __DIR__ . '/includes/header.php';
?>
<section class="catalog-image-hero" aria-label="The complete Gawdee pantry">
    <div class="container">
        <picture>
            <source media="(max-width: 700px)" srcset="assets/images/catalog-hero-complete-pantry-mobile-v1.png">
            <img src="assets/images/catalog-hero-complete-pantry-v1.png" alt="The complete Gawdee pantry. Pure essentials, every product. Explore all 15 current Gawdee products with official descriptions, pack details and imagery.">
        </picture>
    </div>
</section>

<section class="catalog-shell section" id="product-catalog">
    <div class="container">
        <div class="catalog-toolbar">
            <div class="catalog-filters" role="group" aria-label="Filter products by category">
                <?php foreach ($categoryFilters as $key => [$label, $icon]): ?><button type="button" data-filter="<?= htmlspecialchars($key) ?>" class="<?= $key === $activeCategory ? 'is-active' : '' ?>"><i class="ph <?= $icon ?>"></i><?= htmlspecialchars($label) ?></button><?php endforeach; ?>
            </div>
            <label class="catalog-search"><i class="ph ph-magnifying-glass"></i><span class="sr-only">Search products</span><input type="search" placeholder="Search the catalogue…" data-catalog-search></label>
        </div>

        <div class="catalog-results-head"><p><strong data-catalog-count><?= count($products) ?></strong> products</p><span>Official catalogue imported 14 August 2026</span></div>
        <div class="product-grid catalog-product-grid" data-product-grid data-initial-category="<?= htmlspecialchars($activeCategory) ?>">
            <?php foreach ($products as $index => $catalogProduct): ?>
                <article class="product-card catalog-product-card reveal" data-delay="<?= ($index % 4) * 45 ?>" data-category="<?= htmlspecialchars($catalogProduct['category_key']) ?>" data-search-name="<?= htmlspecialchars(strtolower($catalogProduct['full_name'] . ' ' . $catalogProduct['category'] . ' ' . $catalogProduct['tag'])) ?>">
                    <a class="product-card__media" href="product.php?slug=<?= rawurlencode((string) $catalogProduct['slug']) ?>" style="--product-accent:<?= htmlspecialchars($catalogProduct['accent']) ?>">
                        <span class="product-card__tag"><?= htmlspecialchars($catalogProduct['tag']) ?></span><span class="product-card__discount"><?= discount_percentage($catalogProduct) ?>% off</span>
                        <img src="<?= htmlspecialchars($catalogProduct['image']) ?>" alt="<?= htmlspecialchars($catalogProduct['full_name']) ?>" loading="lazy"><span class="product-card__view">View complete details <i class="ph ph-arrow-up-right"></i></span>
                    </a>
                    <div class="product-card__body"><div class="product-card__meta"><span><?= htmlspecialchars($catalogProduct['category']) ?></span><span><?= htmlspecialchars($catalogProduct['weight']) ?></span></div><h3><a href="product.php?slug=<?= rawurlencode((string) $catalogProduct['slug']) ?>"><?= htmlspecialchars($catalogProduct['name']) ?></a></h3><p class="catalog-product-card__copy"><?= htmlspecialchars($catalogProduct['description']) ?></p><div class="catalog-product-card__rating"><?php if ((int) $catalogProduct['review_count'] > 0): ?><span>★★★★★</span><?= number_format((float) $catalogProduct['rating'], 1) ?><?php else: ?><i class="ph ph-star"></i> New · No ratings yet<?php endif; ?></div><div class="product-card__buy"><p><strong><?= money($catalogProduct['price']) ?></strong> <s><?= money($catalogProduct['original_price']) ?></s></p><button class="add-button" type="button" data-add-to-cart data-id="<?= htmlspecialchars($catalogProduct['id']) ?>" data-name="<?= htmlspecialchars($catalogProduct['full_name']) ?>" data-price="<?= (int) $catalogProduct['price'] ?>" data-image="<?= htmlspecialchars($catalogProduct['image']) ?>" aria-label="Add <?= htmlspecialchars($catalogProduct['name']) ?> to cart"><i class="ph ph-plus"></i></button></div></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="product-empty catalog-empty" data-product-empty hidden><i class="ph ph-magnifying-glass"></i><h2>No matching products</h2><p>Try another search or category.</p></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
