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
$initialSearch = trim((string) ($_GET['search'] ?? ''));

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
                <input id="catalog-search-input" type="search" placeholder="Search products, ingredients…" data-catalog-search autocomplete="off" value="<?= htmlspecialchars($initialSearch) ?>">
            </div>
        </div>

<?php
// Dynamic catalogue: one card per ITEM (family), variants loaded from item_variants.
// $products is one row per variant (compat shape); group by family_key (= item slug).
$catalogFamilies = [];
foreach ($products as $p) {
    $fKey = (string) (($p['family_key'] ?? '') !== '' ? $p['family_key'] : ($p['item_slug'] ?? $p['id']));
    if (!isset($catalogFamilies[$fKey])) {
        $catalogFamilies[$fKey] = $p;
    }
}
$displayCatalogProducts = array_values($catalogFamilies);
?>

        <div class="catalog-results-head reveal">
            <span class="catalog-badge"><i class="ph ph-shield-check"></i> 100% Certified Authentic</span>
        </div>

        <?php if (!$displayCatalogProducts): ?>
            <div class="product-empty catalog-empty">
                <i class="ph ph-magnifying-glass"></i>
                <h2>No products available yet</h2>
                <p>New natural essentials are on their way. Please check back soon.</p>
            </div>
        <?php else: ?>
        <div class="product-grid catalog-product-grid" data-product-grid data-initial-category="<?= htmlspecialchars($activeCategory) ?>" data-initial-search="<?= htmlspecialchars($initialSearch) ?>">
            <?php foreach ($displayCatalogProducts as $index => $catalogProduct):
                $cVariants = gawdee_family_variants($products, (string) ($catalogProduct['family_key'] ?? ''));
                if (!$cVariants) {
                    $cVariants = [$catalogProduct];
                }
                $searchKeywords = strtolower(($catalogProduct['full_name'] ?? '') . ' ' . ($catalogProduct['category'] ?? '') . ' ' . ($catalogProduct['tag'] ?? '') . ' ' . ($catalogProduct['flavor'] ?? '') . ' ' . ($catalogProduct['sku'] ?? ''));
                foreach ($cVariants as $cv) {
                    $searchKeywords .= ' ' . strtolower(($cv['full_name'] ?? '') . ' ' . ($cv['weight'] ?? '') . ' ' . ($cv['sku'] ?? ''));
                }
                $cardDiscount = discount_percentage($catalogProduct);
                // List card: first image = ItemTable image; hover reveals ItemTable hover_image.
                $cardImage = (string) (($catalogProduct['item_image'] ?? '') !== '' ? $catalogProduct['item_image'] : ($catalogProduct['image'] ?? 'assets/images/logo.png'));
                if ($cardImage === '') {
                    $cardImage = 'assets/images/logo.png';
                }
                $cardHover = (string) ($catalogProduct['hover_image'] ?? '');
            ?>
                <article class="product-card catalog-product-card reveal" data-delay="<?= ($index % 4) * 40 ?>" data-category="<?= htmlspecialchars((string) ($catalogProduct['category_key'] ?? 'all')) ?>" data-search-name="<?= htmlspecialchars($searchKeywords) ?>">
                    <a class="product-card__media" href="product?slug=<?= rawurlencode((string) $catalogProduct['slug']) ?>" style="--product-accent:<?= htmlspecialchars((string) ($catalogProduct['accent'] ?? '#0a7540')) ?>" aria-label="View <?= htmlspecialchars((string) $catalogProduct['full_name']) ?>">
                        <?php if (!empty($catalogProduct['tag'])): ?>
                            <span class="product-card__tag"><?= htmlspecialchars((string) $catalogProduct['tag']) ?></span>
                        <?php endif; ?>
                        <?php if ($cardDiscount > 0): ?>
                            <span class="product-card__discount"><?= $cardDiscount ?>% OFF</span>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($cardImage) ?>" alt="<?= htmlspecialchars((string) $catalogProduct['full_name']) ?>" loading="lazy" decoding="async" data-card-main-image onerror="this.onerror=null;this.src='assets/images/logo.png'">
                        <?php if ($cardHover !== '' && $cardHover !== $cardImage): ?>
                            <img class="product-card__hover" src="<?= htmlspecialchars($cardHover) ?>" alt="" loading="lazy" decoding="async" aria-hidden="true">
                        <?php endif; ?>
                    </a>
                    <div class="product-card__body">
                        <div class="product-card__meta">
                            <span><?= htmlspecialchars((string) $catalogProduct['category']) ?></span>
                            <span>·</span>
                            <span data-card-weight><?= htmlspecialchars((string) $catalogProduct['weight']) ?></span>
                        </div>
                        <h3>
                            <a href="product?slug=<?= rawurlencode((string) $catalogProduct['slug']) ?>"><?= htmlspecialchars((string) $catalogProduct['name']) ?></a>
                        </h3>
                        <p class="catalog-product-card__copy"><?= htmlspecialchars((string) $catalogProduct['description']) ?></p>
                        <div class="catalog-product-card__rating">
                            <?php if ((int) ($catalogProduct['review_count'] ?? 0) > 0): ?>
                                <span class="stars" aria-hidden="true">★★★★★</span>
                                <strong><?= number_format((float) ($catalogProduct['rating'] ?? 0), 1) ?></strong>
                                <small>(<?= (int) $catalogProduct['review_count'] ?>)</small>
                            <?php else: ?>
                                <span class="stars" aria-hidden="true">★★★★★</span>
                                <strong>4.9</strong>
                                <small>New</small>
                            <?php endif; ?>
                        </div>
                        <?php if (count($cVariants) > 1): ?>
                            <div class="card-variant-pills" role="group" aria-label="Select pack size for <?= htmlspecialchars((string) $catalogProduct['name']) ?>">
                                <?php foreach ($cVariants as $cv):
                                    $isCur = ($cv['slug'] ?? '') === ($catalogProduct['slug'] ?? '');
                                    $cvDiscount = discount_percentage($cv);
                                    $cvStock = (int) ($cv['stock'] ?? 0);
                                ?>
                                    <button type="button"
                                            class="card-variant-pill <?= $isCur ? 'is-active' : '' ?>"
                                            data-card-variant-switch
                                            data-slug="<?= htmlspecialchars((string) ($cv['slug'] ?? '')) ?>"
                                            data-id="<?= htmlspecialchars((string) ($cv['id'] ?? '')) ?>"
                                            data-name="<?= htmlspecialchars((string) ($cv['full_name'] ?? '')) ?>"
                                            data-weight="<?= htmlspecialchars((string) ($cv['weight'] ?? '')) ?>"
                                            data-price="<?= (int) ($cv['price'] ?? 0) ?>"
                                            data-price-formatted="<?= money((int) ($cv['price'] ?? 0)) ?>"
                                            data-original-price-formatted="<?= money((int) ($cv['original_price'] ?? 0)) ?>"
                                            data-discount="<?= $cvDiscount ?>"
                                            data-stock="<?= $cvStock ?>"
                                            data-sku="<?= htmlspecialchars((string) ($cv['sku'] ?? '')) ?>"
                                            data-image="<?= htmlspecialchars((string) (($cv['image'] ?? '') !== '' ? $cv['image'] : $cardImage)) ?>"
                                            <?= $isCur ? 'aria-pressed="true"' : 'aria-pressed="false"' ?>>
                                        <?= htmlspecialchars((string) ($cv['weight'] ?? '')) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="catalog-single-variant"><span><?= htmlspecialchars((string) $catalogProduct['weight']) ?></span><?php if (!empty($catalogProduct['sku'])): ?><small>SKU <?= htmlspecialchars((string) $catalogProduct['sku']) ?></small><?php endif; ?></p>
                        <?php endif; ?>
                        <div class="product-card__buy">
                            <div class="product-card__price">
                                <strong data-card-price><?= money((int) ($catalogProduct['price'] ?? 0)) ?></strong>
                                <s data-card-original-price><?= money((int) ($catalogProduct['original_price'] ?? 0)) ?></s>
                            </div>
                            <button class="add-button" type="button"
                                    data-add-to-cart
                                    data-id="<?= htmlspecialchars((string) ($catalogProduct['id'] ?? '')) ?>"
                                    data-name="<?= htmlspecialchars((string) ($catalogProduct['full_name'] ?? '')) ?>"
                                    data-price="<?= (int) ($catalogProduct['price'] ?? 0) ?>"
                                    data-image="<?= htmlspecialchars($cardImage) ?>"
                                    <?= ((int) ($catalogProduct['stock'] ?? 0) <= 0) ? 'disabled' : '' ?>
                                    aria-label="Add <?= htmlspecialchars((string) $catalogProduct['name']) ?> to cart">
                                <span><?= ((int) ($catalogProduct['stock'] ?? 0) <= 0) ? 'Out of stock' : 'Add to cart' ?></span>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="product-empty catalog-empty" data-product-empty hidden>
            <i class="ph ph-magnifying-glass"></i>
            <h2>No matching products found</h2>
            <p>Try searching for another keyword or select a different category filter.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
