<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/data.php';

header('Content-Type: application/xml; charset=utf-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $scheme . '://' . $host;
$today = date('Y-m-d');

$products = gawdee_products(true);

$blogPosts = [];
try {
    $stmt = gawdee_db()->query("SELECT slug, updated_at, created_at FROM blog_posts WHERE status='published' ORDER BY id DESC");
    $blogPosts = $stmt->fetchAll();
} catch (\Throwable $e) {
    // Fallback if table query is empty or failed
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Static Storefront Pages -->
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/') ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/products') ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/blog') ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Category Pages -->
    <?php
    $categories = ['ghee', 'honey', 'nutrition', 'sugar', 'wellness'];
    foreach ($categories as $cat):
    ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/products?category=' . $cat) ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Product Detail Pages -->
    <?php foreach ($products as $product): ?>
    <?php if (empty($product['is_active'])) continue; ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/product?slug=' . urlencode((string) $product['slug'])) ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
        <?php if (!empty($product['image'])): ?>
        <?php
        $imgUrl = str_starts_with((string) $product['image'], 'http')
            ? $product['image']
            : $baseUrl . '/' . ltrim((string) $product['image'], '/');
        ?>
        <image:image>
            <image:loc><?= htmlspecialchars($imgUrl) ?></image:loc>
            <image:title><?= htmlspecialchars((string) ($product['full_name'] ?? $product['name'])) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <?php endforeach; ?>

    <!-- Journal Article Pages -->
    <?php foreach ($blogPosts as $post): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/blog-post?slug=' . urlencode((string) $post['slug'])) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime((string) ($post['updated_at'] ?? $post['created_at'] ?? $today))) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

</urlset>
