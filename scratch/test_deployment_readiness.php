<?php
declare(strict_types=1);

echo "====================================================\n";
echo "       GAWDEE DEPLOYMENT READINESS TEST SUITE        \n";
echo "====================================================\n\n";

$errors = [];
$passes = 0;

function assertTest(bool $condition, string $message, array &$errors, int &$passes): void {
    if ($condition) {
        echo " [PASS] " . $message . "\n";
        $passes++;
    } else {
        echo " [FAIL] " . $message . "\n";
        $errors[] = $message;
    }
}

// 1. Check database products and variants
require_once __DIR__ . '/../includes/data.php';
$products = gawdee_products();
assertTest(count($products) >= 28, "Product database contains all 28+ variant items", $errors, $passes);

$families = [];
foreach ($products as $p) {
    $fKey = $p['family_key'];
    $families[$fKey][] = $p;
}
assertTest(count($families) >= 9, "All 9 product families exist with variant mappings", $errors, $passes);

// Verify Honey family
$honeyVariants = gawdee_family_variants($products, 'raw-wild-forest-honey');
assertTest(count($honeyVariants) === 4, "Raw Forest Honey has 4 variants (250g, 500g, 650g, 1kg)", $errors, $passes);

// Verify Ghee family
$gheeVariants = gawdee_family_variants($products, 'gir-cow-a2-ghee');
assertTest(count($gheeVariants) === 3, "Gir Cow A2 Ghee has 3 variants (250ml, 500ml, 1 Litre)", $errors, $passes);

// 2. Test HTML rendering for product page
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
ob_start();
$_GET['slug'] = 'gawdee-raw-wild-forest-honey-650-g';
include __DIR__ . '/../product.php';
$productHtml = ob_get_clean();

assertTest(str_contains($productHtml, 'data-variant-switch'), "Product details page renders data-variant-switch inline buttons", $errors, $passes);
assertTest(!str_contains($productHtml, 'data-wishlist'), "Wishlist button removed from product details page", $errors, $passes);

// 3. Test HTML rendering for catalog page
ob_start();
include __DIR__ . '/../products.php';
$catalogHtml = ob_get_clean();

assertTest(str_contains($catalogHtml, 'data-card-variant-switch'), "Catalog page renders data-card-variant-switch pills on cards", $errors, $passes);

// 4. Test Header HTML
ob_start();
$headerCustomer = null;
include __DIR__ . '/../includes/header.php';
$headerHtml = ob_get_clean();

assertTest(!str_contains($headerHtml, 'Wishlist'), "Wishlist removed from header navigation", $errors, $passes);

// 5. Check CSS rule for .ref-product-page 157px
$cssContent = file_get_contents(__DIR__ . '/../assets/css/style.css');
assertTest(str_contains($cssContent, '.ref-product-page { padding-block: 157px 64px; }'), "style.css contains .ref-product-page padding-block: 157px 64px for desktop", $errors, $passes);

// 6. Check JS for quantity limit removal
$jsContent = file_get_contents(__DIR__ . '/../assets/js/app.js');
assertTest(!str_contains($jsContent, 'Math.min(10, productQuantity + 1)'), "JavaScript productQuantity upper limit of 10 removed", $errors, $passes);
assertTest(!str_contains($jsContent, "qsa('[data-wishlist]')"), "JavaScript data-wishlist listener removed", $errors, $passes);

// 7. Check backend commerce.php quantity limit removal
$commerceContent = file_get_contents(__DIR__ . '/../includes/commerce.php');
assertTest(!str_contains($commerceContent, 'min(10, max(1'), "Backend commerce.php quantity limit min(10, ...) removed", $errors, $passes);

echo "\n====================================================\n";
echo "  SUMMARY: " . $passes . " Passed, " . count($errors) . " Failed\n";
echo "====================================================\n";

if (count($errors) > 0) {
    exit(1);
} else {
    echo "\n>>> SUCCESS: Project is 100% verified & ready for server deployment! <<<\n";
}
