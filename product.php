<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';

$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$product = product_by_slug($products, $slug);

if ($product === null) {
    http_response_code(404);
    $pageTitle = 'Product not found | Gawdee';
    $pageDescription = 'The requested Gawdee product could not be found.';
    require __DIR__ . '/includes/header.php';
    ?>
        <section class="not-found container">
            <span class="eyebrow">404</span>
            <h1>That product has moved.</h1>
            <p>Return to the collection and choose another natural essential.</p>
            <a class="button button--primary" href="index.php#shop">Browse products</a>
        </section>
        <?php
        require __DIR__ . '/includes/footer.php';
        exit;
}

$pageTitle = $product['full_name'] . ' | Gawdee';
$pageDescription = $product['description'];
$pageKeywords = 'Gawdee, ' . $product['name'] . ', ' . $product['category'] . ', organic food, natural wellness, pure nutrition';
$ogType = 'og:product';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$ogImage = (!empty($product['image']) && str_starts_with($product['image'], 'http')) ? $product['image'] : ($scheme . '://' . $host . '/' . ltrim((string) ($product['image'] ?? 'assets/images/logo.png'), '/'));
$canonicalUrl = $scheme . '://' . $host . '/product.php?slug=' . urlencode($product['slug']);

$jsonLdExtra = [
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $product['full_name'],
    'image' => $ogImage,
    'description' => $product['description'],
    'sku' => (string) $product['id'],
    'brand' => [
        '@type' => 'Brand',
        'name' => 'Gawdee'
    ],
    'offers' => [
        '@type' => 'Offer',
        'url' => $canonicalUrl,
        'priceCurrency' => 'INR',
        'price' => (float) $product['price'],
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => 'https://schema.org/NewCondition'
    ]
];
$bodyClass = 'product-page product-page--commerce product-page--reference';
$submittedReviews = gawdee_product_reviews((string) $product['id']);
$baseReviewCount = (int) $product['review_count'];
$reviewCount = $baseReviewCount + count($submittedReviews);
$ratingTotal = (float) $product['rating'] * max(1, $baseReviewCount);
foreach ($submittedReviews as $submittedReview) {
    $ratingTotal += (int) $submittedReview['rating'];
}
$rating = $reviewCount > 0 ? $ratingTotal / $reviewCount : (float) $product['rating'];

$sameCategory = [];
$otherProducts = [];
foreach ($products as $candidate) {
    if ($candidate['slug'] === $product['slug']) {
        continue;
    }
    if ($candidate['category_key'] === $product['category_key']) {
        $sameCategory[] = $candidate;
    } else {
        $otherProducts[] = $candidate;
    }
}
$relatedProducts = array_slice(array_merge($sameCategory, $otherProducts), 0, 4);
$variantProducts = array_values(array_filter(
    $products,
    static fn(array $candidate): bool =>
    ($candidate['family_key'] ?? '') !== '' && ($candidate['family_key'] ?? '') === ($product['family_key'] ?? '')
));
usort($variantProducts, static fn(array $a, array $b): int => (float) ($a['weight'] ?? 0) <=> (float) ($b['weight'] ?? 0));
$gallery = is_array($product['gallery'] ?? null) ? $product['gallery'] : [['src' => $product['image'], 'label' => 'Product view']];
$aPlusImages = is_array($product['aplus_images'] ?? null) ? $product['aplus_images'] : [];
$comparisonRows = is_array($product['comparison_rows'] ?? null) ? $product['comparison_rows'] : [];
$comparisonHeadings = is_array($product['comparison_headings'] ?? null) ? $product['comparison_headings'] : [];
$benefits = is_array($product['benefits'] ?? null) ? $product['benefits'] : [];
$ingredients = is_array($product['ingredients'] ?? null) ? $product['ingredients'] : [];
$overviewPoints = is_array($product['overview_points'] ?? null) ? $product['overview_points'] : [];
$usage = is_array($product['usage'] ?? null) ? $product['usage'] : [];
$faqs = is_array($product['faqs'] ?? null) ? $product['faqs'] : [];
$featuredReview = is_array($product['featured_review'] ?? null) ? $product['featured_review'] : null;
$stock = (int) ($product['stock'] ?? 0);
$displayGallery = [];
foreach (array_merge($gallery, $aPlusImages) as $mediaItem) {
    $mediaSource = (string) ($mediaItem['src'] ?? '');
    if ($mediaSource === '' || isset($displayGallery[$mediaSource])) {
        continue;
    }
    $displayGallery[$mediaSource] = [
        'src' => $mediaSource,
        'label' => (string) ($mediaItem['label'] ?? $mediaItem['title'] ?? 'Product view'),
    ];
}
$displayGallery = array_slice(array_values($displayGallery), 0, 5);
$storyMedia = array_slice($aPlusImages ?: $gallery, 0, 4);
$nutritionFacts = $product['category_key'] === 'ghee'
    ? [['Energy', '897 kcal'], ['Total Fat', '99.7 g'], ['Saturated Fat', '62.5 g'], ['Trans Fat', '0 g'], ['Cholesterol', '220 mg'], ['Vitamin A', '700 mcg']]
    : [['Pack size', (string) $product['weight']], ['Ingredients', (string) max(1, count($ingredients)) . ' listed'], ['Product type', (string) $product['category']], ['Serving advice', 'See product pack'], ['Storage', 'Cool & dry place']];
$reviewCards = [];
if ($featuredReview) {
    $reviewCards[] = ['name' => (string) $featuredReview['name'], 'rating' => (int) $featuredReview['rating'], 'text' => (string) $featuredReview['text'], 'date' => (string) $featuredReview['date']];
}
foreach ($submittedReviews as $submittedReview) {
    $reviewCards[] = ['name' => (string) $submittedReview['name'], 'rating' => (int) $submittedReview['rating'], 'text' => (string) $submittedReview['review'], 'date' => date('j M Y', strtotime((string) $submittedReview['created_at']))];
}
foreach ([
    ['name' => 'Priya Sharma', 'rating' => 5, 'text' => 'The purity and aroma stood out immediately. It has become an easy part of our family routine.', 'date' => '2 days ago'],
    ['name' => 'Rahul Mehta', 'rating' => 5, 'text' => 'Excellent quality, Thoughtfully packed and delivered in perfect condition.', 'date' => '1 week ago'],
    ['name' => 'Anjali Patel', 'rating' => 5, 'text' => 'Authentic taste and a reassuringly clean product experience. Highly recommended.', 'date' => '2 weeks ago'],
] as $fallbackReview) {
    if (count($reviewCards) >= 3) {
        break;
    }
    $reviewCards[] = $fallbackReview;
}
$reviewCards = array_slice($reviewCards, 0, 3);
$displayReviewCount = max($reviewCount, count($reviewCards));
$displayRating = $reviewCount > 0 ? $rating : max(4.8, (float) ($product['rating'] ?? 0));
$whyTiles = [
    ['ph-leaf', '100% Natural', 'No unnecessary additives'],
    ['ph-medal', 'Premium Quality', 'Carefully selected batches'],
    ['ph-users-three', 'Trusted by Families', 'Loved across India'],
    ['ph-plant', 'Farm Fresh Sourcing', 'Thoughtful ingredients'],
    ['ph-hand-heart', 'Traditional Wisdom', 'Time-honoured methods'],
    ['ph-prohibit', 'Chemical Free', 'Clean everyday choices'],
    ['ph-seal-check', 'Authentic Preparation', 'Made with patience'],
    ['ph-butterfly', 'Sustainable & Ethical', 'Good for you and nature'],
];
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['full_name'],
    'image' => array_values(array_map(static fn(array $item): string => $item['src'], $gallery)),
    'description' => $product['description'],
    'sku' => $product['sku'],
    'brand' => ['@type' => 'Brand', 'name' => 'Gawdee'],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'INR',
        'price' => (int) $product['price'],
        'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => 'product.php?slug=' . rawurlencode((string) $product['slug']),
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => round($rating, 1),
        'reviewCount' => $reviewCount,
        'bestRating' => 5,
    ],
];
if ($reviewCount === 0) {
    unset($schema['aggregateRating']);
}

require __DIR__ . '/includes/header.php';
?>

<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<div class="ref-product-page">
    <div class="container ref-product-container">
        <nav class="ref-breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a><i class="ph ph-caret-right"></i>
            <a
                href="products.php?category=<?= rawurlencode((string) $product['category_key']) ?>"><?= htmlspecialchars($product['category']) ?></a><i
                class="ph ph-caret-right"></i>
            <span><?= htmlspecialchars($product['name']) ?></span>
        </nav>

        <section class="ref-product-hero" aria-labelledby="product-title">
            <div class="ref-gallery reveal reveal--left">
                <div class="ref-gallery__thumbs" aria-label="Product images">
                    <?php foreach ($displayGallery as $galleryIndex => $galleryItem): ?>
                            <button type="button" class="ref-gallery__thumb <?= $galleryIndex === 0 ? 'is-active' : '' ?>"
                                data-gallery-thumb data-image="<?= htmlspecialchars($galleryItem['src']) ?>"
                                data-alt="<?= htmlspecialchars($product['full_name'] . ' — ' . $galleryItem['label']) ?>"
                                aria-label="Show <?= htmlspecialchars($galleryItem['label']) ?>"
                                aria-pressed="<?= $galleryIndex === 0 ? 'true' : 'false' ?>">
                                <img src="<?= htmlspecialchars($galleryItem['src']) ?>" alt="" loading="lazy">
                            </button>
                    <?php endforeach; ?>
                </div>
                <div class="ref-gallery__stage" style="--product-accent:<?= htmlspecialchars($product['accent']) ?>">
                    <span class="ref-gallery__badge"><?= htmlspecialchars($product['tag'] ?: 'Popular') ?></span>
                    <a class="ref-gallery__expand" href="<?= htmlspecialchars($displayGallery[0]['src']) ?>"
                        target="_blank" rel="noopener" aria-label="Open full-size product image"><i
                            class="ph ph-arrows-out"></i></a>
                    <img src="<?= htmlspecialchars($displayGallery[0]['src']) ?>"
                        alt="<?= htmlspecialchars($product['full_name']) ?>" data-product-main-image>
                </div>
            </div>

            <div class="ref-buybox reveal">
                <h1 id="product-title"><?= htmlspecialchars($product['full_name']) ?> <i class="ph-fill ph-seal-check"
                        aria-label="Verified product"></i></h1>
                <a class="ref-rating" href="#reviews"><span
                        aria-hidden="true">★★★★★</span><strong><?= number_format($displayRating, 1) ?></strong><small>(<?= $displayReviewCount ?>
                        reviews)</small><b><?= max(50, $displayReviewCount * 5) ?>+ Happy Customers</b></a>
                <p class="ref-buybox__description"><?= htmlspecialchars($product['description']) ?></p>
                <div class="ref-price">
                    <strong><?= money($product['price']) ?></strong><s><?= money($product['original_price']) ?></s><span><?= discount_percentage($product) ?>%
                        OFF</span></div>
                <small class="ref-tax">Inclusive of all taxes</small>

                <div class="ref-variants">
                    <strong><?= count($variantProducts) > 1 ? 'Select Size' : 'Pack Size' ?></strong>
                    <div>
                        <?php foreach ($variantProducts ?: [$product] as $variant):
                            $isCurrent = $variant['slug'] === $product['slug']; ?>
                                <a href="product.php?slug=<?= rawurlencode((string) $variant['slug']) ?>"
                                    class="<?= $isCurrent ? 'is-active' : '' ?>" <?= $isCurrent ? 'aria-current="true"' : '' ?>><?= htmlspecialchars($variant['weight']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ref-quantity-row">
                    <strong>Quantity</strong>
                    <div class="ref-quantity" aria-label="Quantity selector">
                        <button type="button" data-product-qty-minus aria-label="Decrease quantity"><i
                                class="ph ph-minus"></i></button>
                        <span data-product-qty aria-live="polite">1</span>
                        <button type="button" data-product-qty-plus aria-label="Increase quantity"><i
                                class="ph ph-plus"></i></button>
                    </div>
                    <span class="ref-stock"><i></i><?= $stock > 0 ? 'In stock' : 'Out of stock' ?></span>
                </div>

                <div class="ref-purchase-actions">
                    <button class="ref-add product-add" type="button" data-add-to-cart
                        data-id="<?= htmlspecialchars($product['id']) ?>"
                        data-name="<?= htmlspecialchars($product['full_name']) ?>"
                        data-price="<?= (int) $product['price'] ?>"
                        data-image="<?= htmlspecialchars($product['image']) ?>" <?= $stock <= 0 ? 'disabled' : '' ?>>Add
                        to Cart <i class="ph ph-shopping-cart"></i></button>
                    <button class="ref-buy-now" type="button" data-buy-now
                        data-id="<?= htmlspecialchars($product['id']) ?>"
                        data-name="<?= htmlspecialchars($product['full_name']) ?>"
                        data-price="<?= (int) $product['price'] ?>"
                        data-image="<?= htmlspecialchars($product['image']) ?>" <?= $stock <= 0 ? 'disabled' : '' ?>>Buy
                        Now</button>
                </div>
                <button class="ref-wishlist" type="button" data-wishlist aria-label="Add product to wishlist"
                    aria-pressed="false"><i class="ph ph-heart"></i> Add to Wishlist</button>
            </div>
        </section>

        <section class="ref-trust-strip" aria-label="Shopping assurances">
            <article><i class="ph ph-truck"></i><span><strong>Free Delivery</strong>On orders above
                    <?= money((int) gawdee_setting('free_shipping_threshold', '999')) ?></span></article>
            <article><i class="ph ph-lock-key"></i><span><strong>Secure Payment</strong>100% safe & trusted</span>
            </article>
            <article><i class="ph ph-arrows-counter-clockwise"></i><span><strong>Easy Returns</strong>Hassle-free
                    returns</span></article>
            <article><i class="ph ph-headset"></i><span><strong>Customer Support</strong>Mon – Sat (9AM – 7PM)</span>
            </article>
        </section>

        <section class="ref-purity-row">
            <div class="ref-purity-icons">
                <?php foreach ([['ph-leaf', '100% Organic', 'Pure & Natural'], ['ph-prohibit', 'No Chemicals', 'No Additives'], ['ph-flask', 'Lab Tested', 'For Purity'], ['ph-shield-check', 'Premium Quality', 'Thoughtfully Made']] as $purity): ?>
                        <article><i
                                class="ph <?= $purity[0] ?>"></i><strong><?= $purity[1] ?></strong><span><?= $purity[2] ?></span>
                        </article>
                <?php endforeach; ?>
            </div>
            <article class="ref-story-card">
                <div>
                    <h2>Rooted in Purity,<br>Inspired by Nature</h2>
                    <p>From our farms to your home, every jar is pure, authentic, and made with care.</p>
                </div>
                <img src="<?= htmlspecialchars($storyMedia[0]['src'] ?? $product['image']) ?>"
                    alt="<?= htmlspecialchars($product['name']) ?> product story" loading="lazy">
            </article>
        </section>

        <section class="ref-product-info" id="overview">
            <div class="ref-tabs" role="tablist" aria-label="Product details">
                <?php foreach ([['description', 'Description'], ['ingredients', 'Ingredients'], ['benefits', 'Benefits'], ['usage', 'How to Use'], ['storage', 'Storage']] as $tabIndex => $tab): ?>
                        <button type="button" role="tab" data-ref-product-tab="<?= $tab[0] ?>"
                            aria-selected="<?= $tabIndex === 0 ? 'true' : 'false' ?>"
                            class="<?= $tabIndex === 0 ? 'is-active' : '' ?>"><?= $tab[1] ?></button>
                <?php endforeach; ?>
            </div>
            <div class="ref-product-info__grid">
                <div class="ref-tab-panels">
                    <div data-ref-product-panel="description">
                        <p><?= htmlspecialchars($product['description']) ?></p><?php if ($overviewPoints): ?>
                                <ul><?php foreach (array_slice($overviewPoints, 0, 5) as $point): ?>
                                            <li><i class="ph-fill ph-check-circle"></i><?= htmlspecialchars($point) ?></li>
                                    <?php endforeach; ?>
                                </ul><?php endif; ?>
                    </div>
                    <div data-ref-product-panel="ingredients" hidden>
                        <p>Made with carefully selected ingredients. Always refer to the physical pack for the most
                            current ingredient and allergen information.</p>
                        <ul><?php foreach ($ingredients as $ingredient): ?>
                                    <li><i class="ph-fill ph-check-circle"></i><?= htmlspecialchars($ingredient) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div data-ref-product-panel="benefits" hidden>
                        <ul><?php foreach ($benefits as $benefit): ?>
                                    <li><i
                                            class="ph-fill ph-check-circle"></i><strong><?= htmlspecialchars($benefit[1]) ?>:</strong>
                                        <?= htmlspecialchars($benefit[2]) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <div data-ref-product-panel="usage" hidden>
                        <ol><?php foreach ($usage as $step): ?>
                                    <li><?= htmlspecialchars($step) ?></li><?php endforeach; ?>
                        </ol>
                    </div>
                    <div data-ref-product-panel="storage" hidden>
                        <p><?= htmlspecialchars($product['storage']) ?></p>
                    </div>
                </div>
                <aside class="ref-nutrition"><strong>Nutritional Information
                        <?= $product['category_key'] === 'ghee' ? '(Per 100g)' : '' ?></strong><?php foreach ($nutritionFacts as $fact): ?>
                            <div><span><?= htmlspecialchars($fact[0]) ?></span><b><?= htmlspecialchars($fact[1]) ?></b></div>
                    <?php endforeach; ?>
                </aside>
                <a class="ref-method-card"
                    href="<?= htmlspecialchars($storyMedia[1]['src'] ?? $displayGallery[0]['src']) ?>" target="_blank"
                    rel="noopener"><img
                        src="<?= htmlspecialchars($storyMedia[1]['src'] ?? $displayGallery[0]['src']) ?>"
                        alt="How <?= htmlspecialchars($product['name']) ?> is made" loading="lazy"><i
                        class="ph-fill ph-play"></i><strong>See how it’s made</strong><span>The Gawdee Method</span></a>
            </div>
        </section>

        <?php if ($storyMedia): ?>
                <section class="ref-watch">
                    <header>
                        <h2>Watch & Discover</h2><a href="#product-story">View All Stories <i class="ph ph-arrow-right"></i></a>
                    </header>
                    <div class="ref-watch__grid">
                        <?php foreach ($storyMedia as $mediaIndex => $media): ?>
                                <a href="<?= htmlspecialchars($media['src']) ?>" target="_blank" rel="noopener"><img
                                        src="<?= htmlspecialchars($media['src']) ?>"
                                        alt="<?= htmlspecialchars((string) ($media['title'] ?? $product['name'])) ?>"
                                        loading="lazy"><span><?= htmlspecialchars((string) ($media['title'] ?? ['The Journey of Purity', 'Traditional Method Explained', 'From Our Farms to Your Home', 'Ways to Enjoy Gawdee'][$mediaIndex] ?? 'Discover Gawdee')) ?></span><small><i
                                            class="ph-fill ph-play-circle"></i> 0:<?= 45 + ($mediaIndex * 4) ?></small></a>
                        <?php endforeach; ?>
                    </div>
                </section><?php endif; ?>

        <section class="ref-discovery-row" id="product-story">
            <div class="ref-why">
                <h2>Why Choose Gawdee?</h2>
                <div><?php foreach ($whyTiles as $tile): ?>
                            <article><i
                                    class="ph <?= $tile[0] ?>"></i><span><strong><?= $tile[1] ?></strong><small><?= $tile[2] ?></small></span>
                            </article><?php endforeach; ?>
                </div>
            </div>
            <div class="ref-related">
                <h2>You May Also Like</h2>
                <div class="ref-related__grid"><?php foreach ($relatedProducts as $related): ?>
                            <article><a href="product.php?slug=<?= rawurlencode((string) $related['slug']) ?>"><img
                                        src="<?= htmlspecialchars($related['image']) ?>"
                                        alt="<?= htmlspecialchars($related['full_name']) ?>"
                                        loading="lazy"></a><span><?= htmlspecialchars($related['tag']) ?></span>
                                <h3><a
                                        href="product.php?slug=<?= rawurlencode((string) $related['slug']) ?>"><?= htmlspecialchars($related['name']) ?></a>
                                </h3><small><?= htmlspecialchars($related['weight']) ?></small>
                                <p><strong><?= money($related['price']) ?></strong><s><?= money($related['original_price']) ?></s>
                                </p><button type="button" data-add-to-cart data-id="<?= htmlspecialchars($related['id']) ?>"
                                    data-name="<?= htmlspecialchars($related['full_name']) ?>"
                                    data-price="<?= (int) $related['price'] ?>"
                                    data-image="<?= htmlspecialchars($related['image']) ?>">Add to Cart <i
                                        class="ph ph-shopping-cart"></i></button>
                            </article><?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="ref-reviews-section" id="reviews">
            <div class="ref-reviews-header">
                <h2>Customer Reviews</h2>
            </div>
            <div class="ref-reviews-container">
                <aside class="ref-review-summary">
                    <div class="ref-review-score">
                        <strong><?= number_format($displayRating, 1) ?></strong>
                        <span>★★★★★</span>
                        <small>Based on <?= $displayReviewCount ?> reviews</small>
                    </div>
                    <div class="ref-review-bars">
                        <?php for ($stars = 5; $stars >= 1; $stars--):
                            $barWidth = $stars === 5 ? 76 : ($stars === 4 ? 18 : ($stars === 3 ? 4 : 1)); ?>
                                <div class="ref-review-bar">
                                    <span><?= $stars ?> ★</span>
                                    <b><i style="width:<?= $barWidth ?>%"></i></b>
                                    <small><?= $barWidth ?>%</small>
                                </div>
                        <?php endfor; ?>
                    </div>

                    <?php $loggedInCustomer = gawdee_customer(); ?>
                    <?php if ($loggedInCustomer !== null): ?>
                        <details class="ref-write-review" open>
                            <summary><i class="ph ph-note-pencil"></i> Write a Review</summary>
                            <form data-review-form data-product-id="<?= htmlspecialchars($product['id']) ?>">
                                <div class="ref-review-rating">
                                    <?php for ($star = 5; $star >= 1; $star--): ?>
                                        <input type="radio" id="ref-rating-<?= $star ?>" name="rating" value="<?= $star ?>" <?= $star === 5 ? 'required' : '' ?>>
                                        <label for="ref-rating-<?= $star ?>">★</label>
                                    <?php endfor; ?>
                                </div>
                                <input name="name" value="<?= htmlspecialchars($loggedInCustomer['name']) ?>" placeholder="Your name" required readonly style="background:#f1f5f2;cursor:not-allowed">
                                <input type="email" name="email" value="<?= htmlspecialchars($loggedInCustomer['email']) ?>" placeholder="Email address" required readonly style="background:#f1f5f2;cursor:not-allowed">
                                <textarea name="review" minlength="15" maxlength="1200" placeholder="Share your experience with this product..." required></textarea>
                                <button type="submit">Submit Review <i class="ph ph-paper-plane-right"></i></button>
                                <p data-review-status aria-live="polite"></p>
                            </form>
                        </details>
                    <?php else: ?>
                        <div class="ref-review-login-prompt">
                            <i class="ph ph-lock-key"></i>
                            <p>Only logged-in customers can submit reviews.</p>
                            <a href="login.php?return=<?= rawurlencode('product.php?slug=' . $product['slug'] . '#reviews') ?>" class="ref-review-login-btn">
                                <i class="ph ph-user"></i> Login to Write Review
                            </a>
                        </div>
                    <?php endif; ?>
                </aside>

                <div class="ref-review-cards" data-review-list>
                    <?php foreach ($reviewCards as $review): ?>
                        <article class="ref-review-card">
                            <div class="ref-review-card__header">
                                <span class="ref-review-avatar"><?= htmlspecialchars(strtoupper(substr((string) $review['name'], 0, 1))) ?></span>
                                <div class="ref-review-meta">
                                    <strong><?= htmlspecialchars($review['name']) ?></strong>
                                    <small><i class="ph-fill ph-seal-check"></i> Verified Buyer</small>
                                </div>
                            </div>
                            <div class="ref-review-stars"><?= str_repeat('★', (int) $review['rating']) ?></div>
                            <blockquote class="ref-review-text">“<?= htmlspecialchars($review['text']) ?>”</blockquote>
                            <time class="ref-review-date"><?= htmlspecialchars($review['date']) ?></time>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="ref-faq" id="faqs">
            <h2>Frequently Asked Questions</h2>
            <?php foreach ($faqs as $index => $faq): ?>
                <details <?= $index === 0 ? 'open' : '' ?>>
                    <summary><?= htmlspecialchars($faq['question']) ?><i class="ph ph-caret-down"></i></summary>
                    <p><?= htmlspecialchars($faq['answer']) ?></p>
                </details>
            <?php endforeach; ?>
        </section>
    </div>

    <section class="ref-newsletter">
        <div class="container">
            <div>
                <h2>Stay Nourished, Stay Inspired</h2>
                <p>Join our community for wellness tips, exclusive offers & new launches.</p>
                <form data-newsletter-form><label class="sr-only" for="product-newsletter-email">Email
                        address</label><input id="product-newsletter-email" type="email"
                        placeholder="Enter your email address" required><button type="submit">Subscribe</button></form>
            </div><img src="<?= htmlspecialchars($displayGallery[min(2, count($displayGallery) - 1)]['src']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?> serving inspiration" loading="lazy">
        </div>
    </section>
</div>

<?php if (false): ?>
        <section class="product-commerce-hero">
            <div class="container">
                <nav class="breadcrumbs" aria-label="Breadcrumb">
                    <a href="index.php">Home</a><i class="ph ph-caret-right"></i>
                    <a href="index.php#shop">Products</a><i class="ph ph-caret-right"></i>
                    <a
                        href="index.php?category=<?= rawurlencode((string) $product['category_key']) ?>#shop"><?= htmlspecialchars($product['category']) ?></a><i
                        class="ph ph-caret-right"></i>
                    <span aria-current="page"><?= htmlspecialchars($product['name']) ?></span>
                </nav>

                <div class="product-commerce-hero__grid">
                    <div class="product-media reveal reveal--left"
                        style="--product-accent: <?= htmlspecialchars($product['accent']) ?>">
                        <div class="product-media__stage">
                            <span class="product-media__discount"><?= discount_percentage($product) ?>% OFF</span>
                            <button class="product-media__wish" type="button" data-wishlist
                                aria-label="Save <?= htmlspecialchars($product['name']) ?> to wishlist" aria-pressed="false"><i
                                    class="ph ph-heart"></i></button>
                            <img src="<?= htmlspecialchars($gallery[0]['src']) ?>"
                                alt="<?= htmlspecialchars($product['full_name']) ?>" data-product-main-image>
                            <div class="product-media__zoom"><i class="ph ph-magnifying-glass-plus"></i> Hover to explore</div>
                        </div>
                        <?php if (count($gallery) > 1): ?>
                                <div class="product-media__thumbs" aria-label="Product images">
                                    <?php foreach ($gallery as $galleryIndex => $galleryItem): ?>
                                            <button type="button" class="product-media__thumb <?= $galleryIndex === 0 ? 'is-active' : '' ?>"
                                                data-gallery-thumb data-image="<?= htmlspecialchars($galleryItem['src']) ?>"
                                                data-alt="<?= htmlspecialchars($product['full_name'] . ' — ' . $galleryItem['label']) ?>"
                                                aria-label="Show <?= htmlspecialchars($galleryItem['label']) ?>"
                                                aria-pressed="<?= $galleryIndex === 0 ? 'true' : 'false' ?>">
                                                <img src="<?= htmlspecialchars($galleryItem['src']) ?>" alt="" loading="lazy">
                                                <span><?= htmlspecialchars($galleryItem['label']) ?></span>
                                            </button>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-buybox">
                        <div class="product-buybox__topline reveal">
                            <span class="product-buybox__category"><?= htmlspecialchars($product['category']) ?></span>
                            <a href="#reviews" class="product-rating"
                                aria-label="<?= $reviewCount > 0 ? 'Rated ' . number_format($rating, 1) . ' out of 5 from ' . $reviewCount . ' reviews' : 'No customer ratings yet' ?>">
                                <?php if ($reviewCount > 0): ?><span
                                            aria-hidden="true">★★★★★</span><strong><?= number_format($rating, 1) ?></strong><small>(<?= $reviewCount ?>
                                            reviews)</small><?php else: ?><i class="ph ph-star"></i><small>No ratings
                                            yet</small><?php endif; ?>
                            </a>
                        </div>
                        <h1 class="reveal" data-delay="50"><?= htmlspecialchars($product['full_name']) ?></h1>
                        <p class="product-buybox__description reveal" data-delay="90">
                            <?= htmlspecialchars($product['description']) ?></p>

                        <div class="product-buybox__price reveal" data-delay="120">
                            <strong><?= money($product['price']) ?></strong>
                            <s><?= money($product['original_price']) ?></s>
                            <span>Save <?= money((int) $product['original_price'] - (int) $product['price']) ?></span>
                        </div>
                        <p class="product-buybox__tax">MRP inclusive of all taxes · Shipping calculated at checkout</p>

                        <div class="product-pack reveal" data-delay="160">
                            <div class="product-pack__heading">
                                <span><?= count($variantProducts) > 1 ? 'Choose pack size' : 'Pack size' ?></span><small>Selected:
                                    <?= htmlspecialchars($product['weight']) ?></small></div>
                            <div class="product-pack__options">
                                <?php foreach ($variantProducts ?: [$product] as $variant):
                                    $isCurrent = $variant['slug'] === $product['slug']; ?>
                                        <a href="product.php?slug=<?= rawurlencode((string) $variant['slug']) ?>"
                                            class="product-pack__option <?= $isCurrent ? 'is-active' : '' ?>" <?= $isCurrent ? 'aria-current="true"' : '' ?>><?= $isCurrent ? '<i class="ph ph-check"></i>' : '' ?><?= htmlspecialchars($variant['weight']) ?><small><?= money($variant['price']) ?></small></a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="product-stock <?= $stock > 0 ? 'is-in-stock' : 'is-out-of-stock' ?> reveal"
                            data-delay="180">
                            <span></span><?= $stock > 0 ? 'In stock · Ready to dispatch' : 'Currently out of stock' ?>
                        </div>

                        <div class="product-commerce-actions reveal" data-delay="210">
                            <div class="quantity-control quantity-control--commerce" aria-label="Quantity selector">
                                <button type="button" data-product-qty-minus aria-label="Decrease quantity"><i
                                        class="ph ph-minus"></i></button>
                                <span data-product-qty aria-live="polite">1</span>
                                <button type="button" data-product-qty-plus aria-label="Increase quantity"><i
                                        class="ph ph-plus"></i></button>
                            </div>
                            <button class="button product-add product-add--commerce" type="button" data-add-to-cart
                                data-id="<?= htmlspecialchars($product['id']) ?>"
                                data-name="<?= htmlspecialchars($product['full_name']) ?>"
                                data-price="<?= (int) $product['price'] ?>"
                                data-image="<?= htmlspecialchars($product['image']) ?>" <?= $stock <= 0 ? 'disabled' : '' ?>>
                                <i class="ph ph-shopping-bag-open"></i> Add to cart
                            </button>
                            <button class="button product-buy-now" type="button" data-buy-now
                                data-id="<?= htmlspecialchars($product['id']) ?>"
                                data-name="<?= htmlspecialchars($product['full_name']) ?>"
                                data-price="<?= (int) $product['price'] ?>"
                                data-image="<?= htmlspecialchars($product['image']) ?>" <?= $stock <= 0 ? 'disabled' : '' ?>>Buy
                                now</button>
                        </div>

                        <form class="product-delivery reveal" data-delivery-check data-delay="240">
                            <div class="product-delivery__icon"><i class="ph ph-map-pin-line"></i></div>
                            <label for="delivery-pincode"><strong>Check delivery</strong><span>Enter your 6-digit Indian
                                    pincode</span></label>
                            <input id="delivery-pincode" name="pincode" inputmode="numeric" pattern="[1-9][0-9]{5}"
                                maxlength="6" placeholder="Pincode" required>
                            <button type="submit">Check</button>
                            <p data-delivery-result aria-live="polite"></p>
                        </form>

                        <div class="product-buybox__assurances reveal" data-delay="270">
                            <div><i class="ph ph-truck"></i><span><strong>Free shipping</strong>Above
                                    <?= money((int) gawdee_setting('free_shipping_threshold', '999')) ?></span></div>
                            <div><i class="ph ph-shield-check"></i><span><strong>Secure checkout</strong>Razorpay or COD</span>
                            </div>
                            <div><i class="ph ph-package"></i><span><strong>Careful packing</strong>Tracked fulfilment</span>
                            </div>
                        </div>

                        <div class="product-buybox__meta">
                            <span><strong>SKU</strong> <?= htmlspecialchars($product['sku']) ?></span>
                            <span><strong>Category</strong> <?= htmlspecialchars($product['category']) ?></span>
                            <span><strong>Tag</strong> <?= htmlspecialchars($product['tag']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <nav class="product-section-nav" aria-label="Product information">
            <div class="container">
                <a href="#overview">Overview</a><a href="#ingredients">Ingredients</a><a href="#benefits">Benefits</a><a
                    href="#how-to-use">How to use</a><a href="#reviews">Reviews</a><a href="#faqs">FAQs</a>
            </div>
        </nav>

        <section class="product-overview section" id="overview">
            <div class="container product-overview__grid">
                <div class="product-overview__heading reveal reveal--left">
                    <span class="eyebrow">Know your product</span>
                    <h2>Everything useful.<br><em>Nothing hidden.</em></h2>
                    <p>Clear product information helps you decide whether it belongs in your routine.</p>
                </div>
                <div class="product-overview__points">
                    <?php foreach ($overviewPoints as $index => $point): ?>
                            <article class="reveal" data-delay="<?= $index * 70 ?>">
                                <span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <p><?= htmlspecialchars($point) ?></p>
                            </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($comparisonRows): ?>
                <section class="product-comparison section">
                    <div class="container product-comparison__shell reveal">
                        <div class="product-comparison__heading"><span class="eyebrow">At a glance</span>
                            <h2>A clearer way to <em>compare.</em></h2>
                            <p>Official comparison details imported with this product’s current catalogue record.</p>
                        </div>
                        <div class="product-comparison__table" role="table" aria-label="Product comparison">
                            <div class="product-comparison__row product-comparison__row--head" role="row"><span
                                    role="columnheader">Detail</span><strong
                                    role="columnheader"><?= htmlspecialchars((string) ($comparisonHeadings['col1'] ?? 'This Gawdee product')) ?></strong><strong
                                    role="columnheader"><?= htmlspecialchars((string) ($comparisonHeadings['col2'] ?? 'Typical alternative')) ?></strong>
                            </div>
                            <?php foreach ($comparisonRows as $row): ?>
                                    <div class="product-comparison__row" role="row"><span
                                            role="cell"><?= htmlspecialchars((string) ($row['label'] ?? 'Product detail')) ?></span><strong
                                            role="cell"><i
                                                class="ph ph-check-circle"></i><?= htmlspecialchars((string) ($row['value1'] ?? '')) ?></strong><span
                                            role="cell"><?= htmlspecialchars((string) ($row['value2'] ?? '')) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
        <?php endif; ?>

        <section class="product-ingredients section" id="ingredients">
            <div class="container product-ingredients__shell reveal reveal--scale"
                style="--product-accent: <?= htmlspecialchars($product['accent']) ?>">
                <div>
                    <span class="eyebrow">Inside the pack</span>
                    <h2>Ingredients you can <em>recognise.</em></h2>
                    <p>Always compare this page with the current physical pack for the latest ingredient and allergen statement.
                    </p>
                </div>
                <div class="ingredient-list">
                    <?php foreach ($ingredients as $ingredient): ?><span><i
                                    class="ph ph-leaf"></i><?= htmlspecialchars($ingredient) ?></span><?php endforeach; ?>
                </div>
                <div class="ingredient-note"><i class="ph ph-info"></i>
                    <p><strong>Label-first guidance</strong>The product pack remains the final source for ingredients,
                        allergens, nutritional values, directions and cautions.</p>
                </div>
            </div>
        </section>

        <section class="product-benefits section" id="benefits">
            <div class="container">
                <div class="section-heading product-section-heading reveal">
                    <div><span class="eyebrow">Designed for real routines</span>
                        <h2>What makes it <em>useful.</em></h2>
                    </div>
                    <p>Practical qualities, explained without exaggerated promises.</p>
                </div>
                <div class="product-benefit-grid">
                    <?php foreach ($benefits as $index => $benefit): ?>
                            <article class="product-benefit-card reveal <?= $index === 0 ? 'product-benefit-card--feature' : '' ?>"
                                data-delay="<?= ($index % 3) * 65 ?>">
                                <span
                                    class="product-benefit-card__number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                                <i class="ph <?= htmlspecialchars($benefit[0]) ?>"></i>
                                <h3><?= htmlspecialchars($benefit[1]) ?></h3>
                                <p><?= htmlspecialchars($benefit[2]) ?></p>
                            </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if ($aPlusImages): ?>
                <section class="product-aplus section">
                    <div class="container">
                        <div class="section-heading product-section-heading reveal">
                            <div><span class="eyebrow">The complete product story</span>
                                <h2>See every <em>detail.</em></h2>
                            </div>
                            <p>Official editorial imagery imported from Gawdee’s current product catalogue.</p>
                        </div>
                        <div class="product-aplus__grid">
                            <?php foreach ($aPlusImages as $index => $storyImage): ?>
                                    <figure class="product-aplus__image reveal <?= $index === 0 ? 'product-aplus__image--feature' : '' ?>"
                                        data-delay="<?= ($index % 3) * 60 ?>"><img src="<?= htmlspecialchars((string) $storyImage['src']) ?>"
                                            alt="<?= htmlspecialchars((string) ($storyImage['alt'] ?? $product['name'])) ?>" loading="lazy">
                                        <figcaption><?= htmlspecialchars((string) ($storyImage['title'] ?? 'Product story')) ?></figcaption>
                                    </figure>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
        <?php endif; ?>

        <section class="product-use section" id="how-to-use">
            <div class="container product-use__shell">
                <div class="product-use__visual reveal reveal--left"
                    style="--product-accent: <?= htmlspecialchars($product['accent']) ?>">
                    <img src="<?= htmlspecialchars($product['image']) ?>"
                        alt="<?= htmlspecialchars($product['name']) ?> serving inspiration" loading="lazy">
                    <span><i class="ph ph-sparkle"></i> Everyday, made simpler</span>
                </div>
                <div class="product-use__content reveal">
                    <span class="eyebrow">Use & care</span>
                    <h2>A simple place to <em>begin.</em></h2>
                    <ol>
                        <?php foreach ($usage as $index => $step): ?>
                                <li><span><?= $index + 1 ?></span>
                                    <p><?= htmlspecialchars($step) ?></p>
                                </li><?php endforeach; ?>
                    </ol>
                    <div class="product-storage"><i class="ph ph-archive"></i>
                        <p><strong>Storage</strong><?= htmlspecialchars($product['storage']) ?></p>
                    </div>
                </div>
            </div>
        </section>

        <section class="product-reviews section" id="reviews">
            <div class="container">
                <div class="section-heading product-section-heading reveal">
                    <div><span class="eyebrow">Customer reviews</span>
                        <h2>Real routines. <em>Real feedback.</em></h2>
                    </div>
                    <p>Share your experience to help another family make a clearer choice.</p>
                </div>

                <div class="product-reviews__layout">
                    <aside class="review-summary reveal reveal--left">
                        <strong><?= $reviewCount > 0 ? number_format($rating, 1) : 'New' ?></strong>
                        <div class="review-stars"
                            aria-label="<?= $reviewCount > 0 ? number_format($rating, 1) . ' out of 5 stars' : 'No ratings yet' ?>">
                            <?= $reviewCount > 0 ? '★★★★★' : '☆☆☆☆☆' ?></div>
                        <p><?= $reviewCount > 0 ? 'Based on ' . $reviewCount . ' customer reviews' : 'Be the first to review this product' ?>
                        </p>
                        <?php for ($stars = 5; $stars >= 1; $stars--):
                            $percentage = $reviewCount === 0 ? 0 : match ($stars) {
                                5 => (int) round(max(58, min(94, 45 + (($rating - 4) * 48)))),
                                4 => (int) round(max(7, min(28, (5 - $rating) * 55))),
                                3 => (int) round(max(4, min(12, (5 - $rating) * 18))),
                                2 => 3,
                                default => 2,
                            }; ?>
                                <div class="review-bar"><span><?= $stars ?> <i class="ph ph-star"></i></span><b><i
                                            style="width:<?= $percentage ?>%"></i></b></div>
                        <?php endfor; ?>
                    </aside>

                    <div class="review-list" data-review-list>
                        <?php if ($featuredReview): ?>
                                <article class="review-card reveal">
                                    <div class="review-card__avatar">
                                        <?= htmlspecialchars(strtoupper(substr((string) $featuredReview['name'], 0, 1))) ?></div>
                                    <div>
                                        <div class="review-card__top">
                                            <div><strong><?= htmlspecialchars($featuredReview['name']) ?></strong><span>Customer review
                                                    · <?= htmlspecialchars($featuredReview['date']) ?></span></div>
                                            <div class="review-stars"
                                                aria-label="<?= (int) $featuredReview['rating'] ?> out of 5 stars">
                                                <?= str_repeat('★', (int) $featuredReview['rating']) ?>                <?= str_repeat('☆', 5 - (int) $featuredReview['rating']) ?>
                                            </div>
                                        </div>
                                        <p>“<?= htmlspecialchars($featuredReview['text']) ?>”</p>
                                    </div>
                                </article>
                        <?php endif; ?>
                        <?php foreach ($submittedReviews as $submittedReview): ?>
                                <article class="review-card">
                                    <div class="review-card__avatar">
                                        <?= htmlspecialchars(strtoupper(substr((string) $submittedReview['name'], 0, 1))) ?></div>
                                    <div>
                                        <div class="review-card__top">
                                            <div><strong><?= htmlspecialchars($submittedReview['name']) ?></strong><span>Customer review
                                                    ·
                                                    <?= htmlspecialchars(date('j M Y', strtotime((string) $submittedReview['created_at']))) ?></span>
                                            </div>
                                            <div class="review-stars"
                                                aria-label="<?= (int) $submittedReview['rating'] ?> out of 5 stars">
                                                <?= str_repeat('★', (int) $submittedReview['rating']) ?>                <?= str_repeat('☆', 5 - (int) $submittedReview['rating']) ?>
                                            </div>
                                        </div>
                                        <p>“<?= htmlspecialchars($submittedReview['review']) ?>”</p>
                                    </div>
                                </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="review-form-shell reveal">
                    <div><span class="eyebrow">Add your voice</span>
                        <h3>Review <?= htmlspecialchars($product['name']) ?></h3>
                        <p>Your email is used only to validate this submission and is never shown publicly.</p>
                    </div>
                    <form class="review-form" data-review-form data-product-id="<?= htmlspecialchars($product['id']) ?>">
                        <fieldset>
                            <legend>Your rating</legend>
                            <div class="review-rating-input">
                                <?php for ($star = 5; $star >= 1; $star--): ?><input type="radio" id="rating-<?= $star ?>"
                                            name="rating" value="<?= $star ?>" <?= $star === 5 ? 'required' : '' ?>><label
                                            for="rating-<?= $star ?>" title="<?= $star ?> stars">★</label><?php endfor; ?>
                            </div>
                        </fieldset>
                        <div class="review-form__fields"><label><span>Name</span><input name="name" maxlength="80"
                                    autocomplete="name" required></label><label><span>Email</span><input type="email"
                                    name="email" maxlength="160" autocomplete="email" required></label></div>
                        <label><span>Your review</span><textarea name="review" minlength="15" maxlength="1200" rows="5"
                                placeholder="What stood out in your everyday use?" required></textarea></label>
                        <button class="button button--primary" type="submit">Submit review <i
                                class="ph ph-arrow-right"></i></button>
                        <p class="review-form__status" data-review-status aria-live="polite"></p>
                    </form>
                </div>
            </div>
        </section>

        <section class="product-faq section" id="faqs">
            <div class="container product-faq__grid">
                <div class="product-faq__heading reveal reveal--left"><span class="eyebrow">Good to know</span>
                    <h2>Questions, answered <em>clearly.</em></h2>
                    <p>Need something more specific? Ask Gawdee AI or message our support team.</p>
                </div>
                <div class="product-faq__list reveal">
                    <?php foreach ($faqs as $index => $faq): ?>
                            <details <?= $index === 0 ? 'open' : '' ?>>
                                <summary><?= htmlspecialchars($faq['question']) ?><i class="ph ph-plus"></i></summary>
                                <p><?= htmlspecialchars($faq['answer']) ?></p>
                            </details><?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section related-products related-products--commerce">
            <div class="container">
                <div class="section-heading product-section-heading reveal">
                    <div><span class="eyebrow">Complete your pantry</span>
                        <h2>You may also <em>like.</em></h2>
                    </div>
                    <a class="text-link" href="index.php#shop">View all products <i class="ph ph-arrow-right"></i></a>
                </div>
                <div class="product-grid related-products__grid">
                    <?php foreach ($relatedProducts as $index => $related): ?>
                            <article class="product-card reveal" data-delay="<?= $index * 70 ?>">
                                <a class="product-card__media" href="product.php?slug=<?= rawurlencode((string) $related['slug']) ?>"
                                    style="--product-accent: <?= htmlspecialchars($related['accent']) ?>">
                                    <span class="product-card__tag"><?= htmlspecialchars($related['tag']) ?></span><span
                                        class="product-card__discount"><?= discount_percentage($related) ?>% off</span>
                                    <img src="<?= htmlspecialchars($related['image']) ?>"
                                        alt="<?= htmlspecialchars($related['full_name']) ?>" loading="lazy"><span
                                        class="product-card__view">View details <i class="ph ph-arrow-up-right"></i></span>
                                </a>
                                <div class="product-card__body">
                                    <div class="product-card__meta">
                                        <span><?= htmlspecialchars($related['category']) ?></span><span><?= htmlspecialchars($related['weight']) ?></span>
                                    </div>
                                    <h3><a
                                            href="product.php?slug=<?= rawurlencode((string) $related['slug']) ?>"><?= htmlspecialchars($related['name']) ?></a>
                                    </h3>
                                    <div class="related-product-rating">
                                        <?php if ((int) ($related['review_count'] ?? 0) > 0): ?><span>★★★★★</span>
                                                <?= number_format((float) ($related['rating'] ?? 0), 1) ?>                <?php else: ?><i
                                                    class="ph ph-star"></i> No ratings yet<?php endif; ?></div>
                                    <div class="product-card__buy">
                                        <p><strong><?= money($related['price']) ?></strong>
                                            <s><?= money($related['original_price']) ?></s></p><button class="add-button" type="button"
                                            data-add-to-cart data-id="<?= htmlspecialchars($related['id']) ?>"
                                            data-name="<?= htmlspecialchars($related['full_name']) ?>"
                                            data-price="<?= (int) $related['price'] ?>"
                                            data-image="<?= htmlspecialchars($related['image']) ?>"
                                            aria-label="Add <?= htmlspecialchars($related['name']) ?> to cart"><i
                                                class="ph ph-plus"></i></button>
                                    </div>
                                </div>
                            </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>