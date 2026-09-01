<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';

ob_start();
$_GET['slug'] = 'gawdee-raw-wild-forest-honey-650-g';
include __DIR__ . '/../product.php';
$productHtml = ob_get_clean();

echo "=== PRODUCT PAGE CHIPS (Raw Forest Honey) ===\n";
if (preg_match_all('/class="ref-variant-chip[^"]*"[^>]*>([^<]+)</i', $productHtml, $matches)) {
    foreach ($matches[1] as $idx => $weight) {
        echo "Chip #" . ($idx + 1) . ": " . trim($weight) . "\n";
    }
} else {
    echo "No variant chips found!\n";
}

echo "\n=== PRODUCT CARD PILLS (Catalog Page) ===\n";
ob_start();
include __DIR__ . '/../products.php';
$catalogHtml = ob_get_clean();

if (preg_match_all('/class="card-variant-pill[^"]*"[^>]*>([^<]+)</i', $catalogHtml, $matches)) {
    foreach ($matches[1] as $idx => $weight) {
        echo "Pill #" . ($idx + 1) . ": " . trim($weight) . "\n";
    }
} else {
    echo "No card variant pills found!\n";
}
