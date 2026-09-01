<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

$categoryFilters = [
    'all' => ['All Products', 'ph-squares-four'],
    'ghee' => ['Ghee', 'ph-bowl-steam'],
    'honey' => ['Honey', 'ph-drop'],
    'nutrition' => ['Mix Me', 'ph-grains'],
    'sugar' => ['Sugar', 'ph-cube'],
    'wellness' => ['Wellness', 'ph-leaf'],
];
$activeCategory = array_key_exists((string) ($_GET['category'] ?? ''), $categoryFilters) ? (string) $_GET['category'] : 'all';

if ($activeCategory !== 'all') {
    $catName = $categoryFilters[$activeCategory][0];
    $pageTitle = 'Pure Gawdee ' . $catName . ' Products | Natural Storefront';
    $pageDescription = 'Shop Gawdee ' . $catName . ' collection. Pure, unadulterated, lab-tested traditional wellness essentials thoughtfully crafted for modern living.';
    $pageKeywords = 'Gawdee ' . $catName . ', organic ' . $catName . ', pure ' . $catName . ', natural wellness India';
} else {
    $pageTitle = 'All Gawdee Products | Natural Pantry & Wellness';
    $pageDescription = 'Browse the complete Gawdee catalogue: A2 Gir Cow Ghee, raw honey, MixMe nutrition, traditional sugar, powders and wellness essentials.';
    $pageKeywords = 'Gawdee products, A2 Gir cow ghee, raw forest honey, MixMe nutrition, organic food catalogue, wellness drops';
}

$bodyClass = 'catalog-page';
$hideCommerceNav = true;
require __DIR__ . '/includes/header.php';
?>


<section class="catalog-shell section" id="product-catalog">
    <div class="container">
        <div class="catalog-toolbar reveal">
            <div class="catalog-filters" role="group" aria-label="Filter products by category">
                <?php foreach ($categoryFilters as $key => [$label, $icon]): ?>
                    <button type="button" data-filter="<?= htmlspecialchars($key) ?>" class="<?= $key === $activeCategory ? 'is-active' : '' ?>">
                        <i class="ph <?= $icon ?>"></i>
                        <span><?= htmlspecialchars($label) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="catalog-search">
                <i class="ph ph-magnifying-glass"></i>
                <label class="sr-only" for="catalog-search-input">Search products</label>
                <input id="catalog-search-input" type="search" placeholder="Search products, ingredients…" data-catalog-search autocomplete="off">
            </div>
        </div>

<?php
$catalogFamilies = [];
foreach ($products as $p) {
    $fKey = (string) ($p['family_key'] ?? $p['id']);
    if (!isset($catalogFamilies[$fKey])) {
        $catalogFamilies[$fKey] = $p;
    }
}
$displayCatalogProducts = array_values($catalogFamilies);
?>

        <div class="catalog-results-head reveal">
            <p>Showing <strong data-catalog-count><?= count($displayCatalogProducts) ?></strong> pure essential products</p>
            <span class="catalog-badge"><i class="ph ph-shield-check"></i> 100% Certified Authentic</span>
        </div>

        <div class="product-grid catalog-product-grid" data-product-grid data-initial-category="<?= htmlspecialchars($activeCategory) ?>">
            <?php foreach ($displayCatalogProducts as $index => $catalogProduct): 
                $cVariants = gawdee_family_variants($products, (string) ($catalogProduct['family_key'] ?? ''));
                $searchKeywords = strtolower($catalogProduct['full_name'] . ' ' . $catalogProduct['category'] . ' ' . $catalogProduct['tag']);
                foreach ($cVariants as $cv) {
                    $searchKeywords .= ' ' . strtolower($cv['full_name'] . ' ' . $cv['weight']);
                }
            ?>
                <article class="product-card catalog-product-card reveal" data-delay="<?= ($index % 4) * 40 ?>" data-category="<?= htmlspecialchars($catalogProduct['category_key']) ?>" data-search-name="<?= htmlspecialchars($searchKeywords) ?>">
                    <a class="product-card__media" href="product?slug=<?= rawurlencode((string) $catalogProduct['slug']) ?>" style="--product-accent:<?= htmlspecialchars($catalogProduct['accent']) ?>">
                        <?php if (!empty($catalogProduct['tag'])): ?>
                            <span class="product-card__tag"><?= htmlspecialchars($catalogProduct['tag']) ?></span>
                        <?php endif; ?>
                        <span class="product-card__discount"><?= discount_percentage($catalogProduct) ?>% OFF</span>
                        <img src="<?= htmlspecialchars($catalogProduct['image']) ?>" alt="<?= htmlspecialchars($catalogProduct['full_name']) ?>" loading="lazy">
                        <span class="product-card__view">View Details <i class="ph ph-arrow-right"></i></span>
                    </a>
                    <div class="product-card__body">
                        <div class="product-card__meta">
                            <span><?= htmlspecialchars($catalogProduct['category']) ?></span>
                            <span>·</span>
                            <span><?= htmlspecialchars($catalogProduct['weight']) ?></span>
                        </div>
                        <h3>
                            <a href="product?slug=<?= rawurlencode((string) $catalogProduct['slug']) ?>"><?= htmlspecialchars($catalogProduct['name']) ?></a>
                        </h3>
                        <p class="catalog-product-card__copy"><?= htmlspecialchars($catalogProduct['description']) ?></p>
                        <div class="catalog-product-card__rating">
                            <?php if ((int) $catalogProduct['review_count'] > 0): ?>
                                <span class="stars" aria-hidden="true">★★★★★</span>
                                <strong><?= number_format((float) $catalogProduct['rating'], 1) ?></strong>
                                <small>(<?= (int) $catalogProduct['review_count'] ?>)</small>
                            <?php else: ?>
                                <span class="stars" aria-hidden="true">★★★★★</span>
                                <strong>4.9</strong>
                                <small>New</small>
                            <?php endif; ?>
                        </div>
                        <?php if (count($cVariants) > 1): ?>
                            <div class="card-variant-pills" aria-label="Select pack size">
                                <?php foreach ($cVariants as $cv): 
                                    $isCur = $cv['slug'] === $catalogProduct['slug'];
                                    $cvDiscount = discount_percentage($cv);
                                ?>
                                    <button type="button" 
                                            class="card-variant-pill <?= $isCur ? 'is-active' : '' ?>"
                                            data-card-variant-switch
                                            data-slug="<?= htmlspecialchars($cv['slug']) ?>"
                                            data-id="<?= htmlspecialchars($cv['id']) ?>"
                                            data-name="<?= htmlspecialchars($cv['full_name']) ?>"
                                            data-weight="<?= htmlspecialchars($cv['weight']) ?>"
                                            data-price="<?= (int) $cv['price'] ?>"
                                            data-price-formatted="<?= money($cv['price']) ?>"
                                            data-original-price-formatted="<?= money($cv['original_price']) ?>"
                                            data-discount="<?= $cvDiscount ?>"
                                            data-image="<?= htmlspecialchars($cv['image']) ?>">
                                        <?= htmlspecialchars($cv['weight']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="product-card__buy">
                            <div class="product-card__price">
                                <strong><?= money($catalogProduct['price']) ?></strong>
                                <s><?= money($catalogProduct['original_price']) ?></s>
                            </div>
                            <button class="add-button" type="button"
                                    data-add-to-cart
                                    data-id="<?= htmlspecialchars($catalogProduct['id']) ?>"
                                    data-name="<?= htmlspecialchars($catalogProduct['full_name']) ?>"
                                    data-price="<?= (int) $catalogProduct['price'] ?>"
                                    data-image="<?= htmlspecialchars($catalogProduct['image']) ?>"
                                    aria-label="Add <?= htmlspecialchars($catalogProduct['name']) ?> to cart">
                                <span>Add</span> <i class="ph ph-plus"></i>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="product-empty catalog-empty" data-product-empty hidden>
            <i class="ph ph-magnifying-glass"></i>
            <h2>No matching products found</h2>
            <p>Try searching for another keyword or select a different category filter.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
