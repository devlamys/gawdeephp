<?php
require __DIR__ . '/../includes/data.php';

$prods = gawdee_products();
echo "Total products: " . count($prods) . "\n\n";

$families = [];
foreach ($prods as $p) {
    $fKey = $p['family_key'];
    if (!isset($families[$fKey])) {
        $families[$fKey] = [];
    }
    $families[$fKey][] = $p;
}

foreach ($families as $fKey => $items) {
    echo "=== Family: $fKey (" . count($items) . " variants) ===\n";
    foreach ($items as $item) {
        echo "  - [" . $item['id'] . "] " . $item['full_name'] . " | " . $item['weight'] . " | ₹" . $item['price'] . "\n";
    }
    echo "\n";
}
