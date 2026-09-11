<?php

declare(strict_types=1);

/**
 * Gawdee Item + Variant catalogue (one-to-many).
 *
 * Canonical tables:
 *   items (1) -> item_variants (many) via item_variants.item_id
 *
 * Item: Id, Name, Flavor, Description, Image, HoverImage, CustomerReview
 *       (+ slug, category, category_key, tag, accent, rating, review_count, is_active)
 * Variant: Id, ItemId FK, VariantName, SKU, StockQuantity, MRP, Discount (%), IsInclusiveTax
 *       (+ slug, price [computed], image override, legacy_product_id, is_active)
 */

// ---------- Pricing helpers ----------

if (!function_exists('gawdee_variant_price')) {
    function gawdee_variant_price(int $mrp, float $discountPercent): int
    {
        $mrp = max(0, $mrp);
        $discountPercent = min(100.0, max(0.0, $discountPercent));
        return (int) round($mrp * (1.0 - ($discountPercent / 100.0)));
    }
}

if (!function_exists('gawdee_variant_discount')) {
    function gawdee_variant_discount(int $mrp, int $price): float
    {
        if ($mrp <= 0) {
            return 0.0;
        }
        $price = min($mrp, max(0, $price));
        return round((1.0 - ($price / $mrp)) * 100.0, 2);
    }
}

if (!function_exists('gawdee_item_family_key')) {
    function gawdee_item_family_key(string $name): string
    {
        if (function_exists('product_family_key')) {
            return product_family_key($name);
        }
        $name = strtolower($name);
        $name = preg_replace('/^gawdee\s+/i', '', $name) ?? $name;
        $name = preg_replace('/\b\d+(?:\.\d+)?\s*(?:kg|g|gm|gms|gram|grams|ml|l|ltr|litre|litres|liter|liters)\b/i', '', $name) ?? $name;
        $name = str_replace('—', ' ', $name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?? $name;
        return trim($name, '-');
    }
}

// ---------- Table existence / migration ----------

/**
 * Import legacy `products` rows that have no matching variant yet
 * (by slug or legacy_product_id). Used after external catalog imports.
 */
function gawdee_sync_missing_legacy_products(): int
{
    if (!gawdee_items_tables_ready()) {
        return 0;
    }
    $db = gawdee_db();
    try {
        $legacy = $db->query('SELECT * FROM products WHERE is_active = 1 ORDER BY created_at')->fetchAll();
    } catch (Throwable) {
        return 0;
    }
    if (!$legacy) {
        return 0;
    }
    // Skip lightweight products-mirror rows for brand-new variants
    // (numeric ids, or explicitly marked mirrors): they are derived from
    // item_variants, not a source to import from.
    $legacy = array_values(array_filter($legacy, static function (array $m): bool {
        if (ctype_digit((string) ($m['id'] ?? ''))) {
            return false;
        }
        return ((string) ($m['source_id'] ?? '')) !== 'variant-mirror';
    }));
    if (!$legacy) {
        return 0;
    }
    try {
        $knownSlugs = [];
        foreach ($db->query('SELECT slug FROM item_variants')->fetchAll(PDO::FETCH_COLUMN) as $s) {
            $knownSlugs[(string) $s] = true;
        }
        $knownLegacy = [];
        foreach ($db->query("SELECT legacy_product_id FROM item_variants WHERE legacy_product_id != ''")->fetchAll(PDO::FETCH_COLUMN) as $s) {
            $knownLegacy[(string) $s] = true;
        }
        $knownItemSlugs = [];
        foreach ($db->query('SELECT slug FROM items')->fetchAll(PDO::FETCH_COLUMN) as $s) {
            $knownItemSlugs[(string) $s] = true;
        }
    } catch (Throwable) {
        return 0;
    }
    $imported = 0;
    foreach ($legacy as $m) {
        $slug = (string) ($m['slug'] ?? '');
        $lid = (string) ($m['id'] ?? '');
        if (($slug !== '' && isset($knownSlugs[$slug])) || ($lid !== '' && isset($knownLegacy[$lid]))) {
            continue;
        }
        // Find parent item by family, or create one.
        $fk = function_exists('product_family_key')
            ? product_family_key((string) ($m['full_name'] ?? $m['name'] ?? ''))
            : gawdee_item_family_key((string) ($m['full_name'] ?? $m['name'] ?? ''));
        $item = null;
        if ($fk !== '') {
            try {
                $st = $db->prepare('SELECT * FROM items WHERE slug = ? LIMIT 1');
                $st->execute([$fk]);
                $item = $st->fetch() ?: null;
            } catch (Throwable) {
            }
        }
        if (!$item) {
            $base = $fk !== '' ? $fk : gawdee_slug((string) ($m['name'] ?? 'item'));
            $candidate = $base;
            $i = 2;
            while (isset($knownItemSlugs[$candidate])) {
                $candidate = $base . '-' . $i;
                $i++;
            }
            $knownItemSlugs[$candidate] = true;
            try {
                $ins = $db->prepare('INSERT INTO items (slug, name, flavor, description, image, hover_image, customer_review, category, category_key, tag, accent, rating, review_count, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $ins->execute([$candidate, (string) ($m['name'] ?? 'Gawdee Product'), '', (string) ($m['description'] ?? ''), (string) ($m['image'] ?? ''), '', '', (string) ($m['category'] ?? ''), (string) ($m['category_key'] ?? ''), (string) ($m['tag'] ?? ''), (string) ($m['accent'] ?? '#0a7540'), (float) ($m['rating'] ?? 0), (int) ($m['review_count'] ?? 0), (int) ($m['is_active'] ?? 1)]);
                $itemId = (int) $db->lastInsertId();
                $st = $db->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
                $st->execute([$itemId]);
                $item = $st->fetch() ?: null;
            } catch (Throwable) {
                continue;
            }
        }
        if (!$item) {
            continue;
        }
        $mrp = max(0, (int) ($m['original_price'] ?? 0));
        $price = max(0, (int) ($m['price'] ?? 0));
        if ($mrp <= 0 && $price > 0) {
            $mrp = $price;
        }
        if ($price > $mrp) {
            $price = $mrp;
        }
        $vSlug = $slug !== '' && !isset($knownSlugs[$slug]) ? $slug : $slug . '-' . substr(md5($lid), 0, 6);
        $sku = trim((string) ($m['sku'] ?? ''));
        if ($sku === '') {
            $sku = 'GWD-' . strtoupper(substr((string) preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($m['name'] ?? 'ITEM'))), 0, 8)) . '-' . strtoupper(substr(md5($lid . $slug), 0, 4));
        }
        try {
            $chk = $db->prepare('SELECT id FROM item_variants WHERE LOWER(sku) = LOWER(?) LIMIT 1');
            $chk->execute([$sku]);
            if ($chk->fetchColumn()) {
                $sku .= '-' . strtoupper(substr(md5($lid), 0, 4));
            }
            $vstmt = $db->prepare('INSERT INTO item_variants (item_id, variant_name, slug, sku, stock_quantity, mrp, discount, price, is_inclusive_tax, image, legacy_product_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $vstmt->execute([(int) $item['id'], trim((string) ($m['weight'] ?? 'Standard')) ?: 'Standard', $vSlug, $sku, max(0, (int) ($m['stock'] ?? 0)), $mrp, gawdee_variant_discount($mrp, $price), $price, 1, '', $lid, (int) ($m['is_active'] ?? 1)]);
            $knownSlugs[$vSlug] = true;
            if ($lid !== '') {
                $knownLegacy[$lid] = true;
            }
            $imported++;
        } catch (Throwable) {
            continue;
        }
    }
    return $imported;
}

function gawdee_items_tables_ready(): bool
{
    try {
        $cols = gawdee_get_table_columns(gawdee_db(), 'items');
        $vcols = gawdee_get_table_columns(gawdee_db(), 'item_variants');
        return in_array('id', $cols, true) && in_array('item_id', $vcols, true);
    } catch (Throwable) {
        return false;
    }
}

function gawdee_items_count(bool $includeInactive = false): int
{
    if (!gawdee_items_tables_ready()) {
        return 0;
    }
    try {
        $sql = 'SELECT COUNT(*) FROM items' . ($includeInactive ? '' : ' WHERE is_active = 1');
        return (int) gawdee_db()->query($sql)->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

function gawdee_variants_count(bool $includeInactive = false): int
{
    if (!gawdee_items_tables_ready()) {
        return 0;
    }
    try {
        $sql = 'SELECT COUNT(*) FROM item_variants' . ($includeInactive ? '' : ' WHERE is_active = 1');
        return (int) gawdee_db()->query($sql)->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

/**
 * One-time (and repair) migration: legacy `products` rows (one row per
 * weight variant, grouped by family_key) -> items + item_variants.
 */
function gawdee_ensure_items_migrated(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    if (!gawdee_items_tables_ready()) {
        return;
    }
    try {
        $db = gawdee_db();
        $itemCount = (int) $db->query('SELECT COUNT(*) FROM items')->fetchColumn();
        $productCount = 0;
        try {
            $productCount = (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        } catch (Throwable) {
            $productCount = 0;
        }
        if ($itemCount > 0 || $productCount === 0) {
            // Incremental repair: import any legacy products rows created after
            // migration (e.g. via scripts/import-gawdee-catalog.php) that have
            // no matching variant yet.
            try {
                gawdee_sync_missing_legacy_products();
            } catch (Throwable) {
            }
            return;
        }

        $rows = $db->query('SELECT * FROM products ORDER BY created_at, full_name')->fetchAll();
        if (!$rows) {
            return;
        }
        $groups = [];
        foreach ($rows as $r) {
            $fk = '';
            if (function_exists('product_family_key')) {
                $fk = product_family_key((string) ($r['full_name'] ?? $r['name'] ?? ''));
            } else {
                $fk = gawdee_item_family_key((string) ($r['full_name'] ?? $r['name'] ?? ''));
            }
            if ($fk === '') {
                $fk = 'item-' . preg_replace('/[^a-z0-9-]+/', '-', strtolower((string) ($r['id'] ?? uniqid())));
            }
            $groups[$fk][] = $r;
        }

        $usedItemSlugs = [];
        $usedVariantSlugs = [];
        foreach ($db->query('SELECT slug FROM items')->fetchAll(PDO::FETCH_COLUMN) as $s) {
            $usedItemSlugs[(string) $s] = true;
        }
        foreach ($db->query('SELECT slug FROM item_variants')->fetchAll(PDO::FETCH_COLUMN) as $s) {
            $usedVariantSlugs[(string) $s] = true;
        }

        $uniqueSlug = static function (string $base, array &$used): string {
            $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/', '-', $base) ?? ''));
            $base = trim($base, '-') ?: 'item';
            $candidate = $base;
            $i = 2;
            while (isset($used[$candidate])) {
                $candidate = $base . '-' . $i;
                $i++;
            }
            $used[$candidate] = true;
            return $candidate;
        };

        foreach ($groups as $familyKey => $members) {
            $main = $members[0];
            // Prefer the member whose id looks like the "base" (shortest id) for stable item fields.
            usort($members, static fn(array $a, array $b): int => strlen((string) ($a['id'] ?? '')) <=> strlen((string) ($b['id'] ?? '')));
            $main = $members[0];
            // Restore original order for variants (by weight).
            usort($members, static function (array $a, array $b): int {
                $g = static function (string $w): float {
                    $w = strtolower(trim($w));
                    if (preg_match('/^([\d.]+)\s*(kg|l|litre|litres|liter|liters)$/i', $w, $m)) {
                        return ((float) $m[1]) * 1000.0;
                    }
                    if (preg_match('/^([\d.]+)\s*(g|gm|gms|gram|grams|ml)$/i', $w, $m)) {
                        return (float) $m[1];
                    }
                    return 999999.0;
                };
                return $g((string) ($a['weight'] ?? '')) <=> $g((string) ($b['weight'] ?? ''));
            });

            $itemSlug = $uniqueSlug($familyKey, $usedItemSlugs);
            $gallery = json_decode((string) ($main['gallery_json'] ?? '[]'), true);
            $hover = '';
            if (is_array($gallery) && isset($gallery[1]['src'])) {
                $hover = (string) $gallery[1]['src'];
            }

            $stmt = $db->prepare('INSERT INTO items (slug, name, flavor, description, image, hover_image, customer_review, category, category_key, tag, accent, rating, review_count, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $anyActive = 0;
            foreach ($members as $m) {
                if (!empty($m['is_active'])) {
                    $anyActive = 1;
                    break;
                }
            }
            $stmt->execute([
                $itemSlug,
                (string) ($main['name'] ?? 'Gawdee Product'),
                '',
                (string) ($main['description'] ?? ''),
                (string) ($main['image'] ?? ''),
                $hover,
                '',
                (string) ($main['category'] ?? ''),
                (string) ($main['category_key'] ?? ''),
                (string) ($main['tag'] ?? ''),
                (string) ($main['accent'] ?? '#0a7540'),
                (float) ($main['rating'] ?? 0),
                (int) ($main['review_count'] ?? 0),
                $anyActive,
            ]);
            $itemId = (int) $db->lastInsertId();
            if ($itemId <= 0) {
                continue;
            }

            $vstmt = $db->prepare('INSERT INTO item_variants (item_id, variant_name, slug, sku, stock_quantity, mrp, discount, price, is_inclusive_tax, image, legacy_product_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($members as $m) {
                $mrp = max(0, (int) ($m['original_price'] ?? 0));
                $price = max(0, (int) ($m['price'] ?? 0));
                if ($mrp <= 0 && $price > 0) {
                    $mrp = $price;
                }
                if ($price > $mrp) {
                    $price = $mrp;
                }
                $discount = gawdee_variant_discount($mrp, $price);
                $vSlugBase = (string) ($m['slug'] ?? '');
                if ($vSlugBase === '') {
                    $vSlugBase = $itemSlug . '-' . strtolower((string) preg_replace('/[^a-z0-9]+/', '-', (string) ($m['weight'] ?? 'pack')));
                }
                $vSlug = $uniqueSlug($vSlugBase, $usedVariantSlugs);
                $sku = trim((string) ($m['sku'] ?? ''));
                if ($sku === '') {
                    $sku = 'GWD-' . strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($itemSlug . '-' . ($m['weight'] ?? ''))) ?: 'ITEM', 0, 8)) . '-' . strtoupper(substr(md5((string) ($m['id'] ?? $vSlug)), 0, 4));
                }
                $vImage = (string) ($m['image'] ?? '');
                $itemImage = (string) ($main['image'] ?? '');
                $vImageStored = ($vImage !== '' && $vImage !== $itemImage) ? $vImage : '';
                try {
                    $vstmt->execute([
                        $itemId,
                        trim((string) ($m['weight'] ?? 'Standard')) ?: 'Standard',
                        $vSlug,
                        $sku,
                        max(0, (int) ($m['stock'] ?? 0)),
                        $mrp,
                        $discount,
                        $price,
                        1,
                        $vImageStored,
                        (string) ($m['id'] ?? ''),
                        !empty($m['is_active']) ? 1 : 0,
                    ]);
                } catch (Throwable) {
                    // Skip duplicate slug/sku rows; keep migration going.
                    continue;
                }
            }
        }
    } catch (Throwable) {
        // Migration must never break boot.
    }
}

// ---------- Read helpers ----------

function gawdee_map_variant_row(array $item, array $variant): array
{
    $mrp = max(0, (int) ($variant['mrp'] ?? 0));
    $price = max(0, (int) ($variant['price'] ?? 0));
    if ($mrp <= 0 && $price > 0) {
        $mrp = $price;
    }
    if ($price > $mrp && $mrp > 0) {
        $price = $mrp;
    }
    $discount = isset($variant['discount']) ? (float) $variant['discount'] : gawdee_variant_discount($mrp, $price);
    // Recompute price from MRP+discount when they disagree (discount is source of truth with MRP).
    $computed = gawdee_variant_price($mrp, $discount);
    if ($mrp > 0 && abs($computed - $price) > 1 && $discount > 0) {
        $price = $computed;
    }
    $discountPct = $mrp > 0 ? (int) round((1 - ($price / $mrp)) * 100) : 0;
    $discountPct = min(100, max(0, $discountPct));

    $itemImage = (string) ($item['image'] ?? '');
    $variantImage = trim((string) ($variant['image'] ?? ''));
    $galleryImages = [];
    if (isset($variant['images']) && is_array($variant['images'])) {
        $galleryImages = $variant['images'];
    }
    $primaryImage = '';
    foreach ($galleryImages as $galleryRow) {
        $candidate = trim((string) (is_array($galleryRow) ? ($galleryRow['image'] ?? '') : $galleryRow));
        if ($candidate !== '') {
            $primaryImage = $candidate;
            break;
        }
    }
    // First active gallery image wins; legacy single-image columns stay as fallback.
    $image = $primaryImage !== '' ? $primaryImage : ($variantImage !== '' ? $variantImage : $itemImage);
    $variantName = trim((string) ($variant['variant_name'] ?? '')) ?: 'Standard';
    $itemName = trim((string) ($item['name'] ?? '')) ?: 'Gawdee Product';
    $flavor = trim((string) ($item['flavor'] ?? ''));
    $fullName = $itemName . ' ' . $variantName;
    if ($flavor !== '' && stripos($itemName, $flavor) === false) {
        $fullName = $itemName . ' — ' . $flavor . ' ' . $variantName;
    }
    $stockQty = max(0, (int) ($variant['stock_quantity'] ?? 0));

    return [
        // Canonical new keys.
        'item_id' => (int) ($item['id'] ?? 0),
        'variant_id' => (int) ($variant['id'] ?? 0),
        'variant_name' => $variantName,
        'sku' => (string) ($variant['sku'] ?? ''),
        'stock_quantity' => $stockQty,
        'mrp' => $mrp,
        'discount' => (float) $discount,
        'discount_percent' => $discountPct,
        'is_inclusive_tax' => !empty($variant['is_inclusive_tax']) ? 1 : 0,
        'hover_image' => (string) ($item['hover_image'] ?? ''),
        'item_image' => (string) ($item['image'] ?? ''),
        'flavor' => $flavor,
        'customer_review' => (string) ($item['customer_review'] ?? ''),
        'legacy_product_id' => (string) ($variant['legacy_product_id'] ?? ''),
        // Backward-compatible product-shape keys (frontend/cart/checkout rely on these).
        'id' => (string) ($variant['id'] ?? ''),
        'slug' => (string) ($variant['slug'] ?? ''),
        'name' => $itemName,
        'full_name' => $fullName,
        'category' => (string) ($item['category'] ?? ''),
        'category_key' => (string) ($item['category_key'] ?? ''),
        'tag' => (string) ($item['tag'] ?? ''),
        'price' => $price,
        'original_price' => $mrp,
        'weight' => $variantName,
        'image' => $image,
        'primary_image' => $primaryImage !== '' ? $primaryImage : $image,
        'images' => array_values(array_map(static function ($galleryRow): array {
            if (is_array($galleryRow)) {
                return [
                    'id' => (int) ($galleryRow['id'] ?? 0),
                    'image' => (string) ($galleryRow['image'] ?? ''),
                    'sort_order' => (int) ($galleryRow['sort_order'] ?? 0),
                    'is_active' => (int) ($galleryRow['is_active'] ?? 1),
                ];
            }
            return ['id' => 0, 'image' => (string) $galleryRow, 'sort_order' => 0, 'is_active' => 1];
        }, $galleryImages)),
        'image_count' => count($galleryImages),
        'description' => (string) ($item['description'] ?? ''),
        'accent' => (string) ($item['accent'] ?? '#0a7540') ?: '#0a7540',
        'stock' => $stockQty,
        'stock_status' => $stockQty > 0 ? 'in_stock' : 'out_of_stock',
        'rating' => (float) ($item['rating'] ?? 0),
        'review_count' => (int) ($item['review_count'] ?? 0),
        'is_active' => (int) ($variant['is_active'] ?? 1),
        'item_is_active' => (int) ($item['is_active'] ?? 1),
        'family_key' => (string) ($item['slug'] ?? ('item-' . (int) ($item['id'] ?? 0))),
        'item_slug' => (string) ($item['slug'] ?? ''),
        'variant_slug' => (string) ($variant['slug'] ?? ''),
        'created_at' => (string) ($variant['created_at'] ?? ($item['created_at'] ?? '')),
        'updated_at' => (string) ($variant['updated_at'] ?? ''),
    ];
}

/** @return array<int,array> items (without variants by default) */
function gawdee_items(bool $includeInactive = false, bool $withVariants = false): array
{
    gawdee_ensure_items_migrated();
    if (!gawdee_items_tables_ready()) {
        return [];
    }
    $sql = 'SELECT * FROM items' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY name, id';
    $rows = gawdee_db()->query($sql)->fetchAll();
    $rows = array_map(static function (array $r): array {
        $r['id'] = (int) $r['id'];
        $r['rating'] = (float) ($r['rating'] ?? 0);
        $r['review_count'] = (int) ($r['review_count'] ?? 0);
        $r['is_active'] = (int) ($r['is_active'] ?? 1);
        return $r;
    }, $rows);
    if ($withVariants) {
        foreach ($rows as &$item) {
            $item['variants'] = gawdee_variants_for_item((int) $item['id'], $includeInactive);
        }
        unset($item);
    }
    return $rows;
}

function gawdee_item_by_id(int $itemId): ?array
{
    gawdee_ensure_items_migrated();
    if ($itemId <= 0 || !gawdee_items_tables_ready()) {
        return null;
    }
    $stmt = gawdee_db()->prepare('SELECT * FROM items WHERE id = ? LIMIT 1');
    $stmt->execute([$itemId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['id'] = (int) $row['id'];
    $row['rating'] = (float) ($row['rating'] ?? 0);
    $row['review_count'] = (int) ($row['review_count'] ?? 0);
    $row['is_active'] = (int) ($row['is_active'] ?? 1);
    return $row;
}

function gawdee_item_by_slug(string $slug): ?array
{
    gawdee_ensure_items_migrated();
    $slug = trim($slug);
    if ($slug === '' || !gawdee_items_tables_ready()) {
        return null;
    }
    $stmt = gawdee_db()->prepare('SELECT * FROM items WHERE slug = ? LIMIT 1');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['id'] = (int) $row['id'];
    $row['rating'] = (float) ($row['rating'] ?? 0);
    $row['review_count'] = (int) ($row['review_count'] ?? 0);
    $row['is_active'] = (int) ($row['is_active'] ?? 1);
    return $row;
}

function gawdee_item_with_variants(int|string $ref, bool $includeInactive = false): ?array
{
    gawdee_ensure_items_migrated();
    $item = null;
    if (is_int($ref) || ctype_digit((string) $ref)) {
        $item = gawdee_item_by_id((int) $ref);
    }
    if (!$item && is_string($ref)) {
        $item = gawdee_item_by_slug($ref);
    }
    if (!$item) {
        // Maybe a variant slug/id was passed: resolve parent item.
        $variant = is_string($ref) || is_int($ref) ? gawdee_variant_by_ref($ref, true) : null;
        if ($variant) {
            $item = gawdee_item_by_id((int) ($variant['item_id'] ?? 0));
        }
    }
    if (!$item) {
        return null;
    }
    $item['variants'] = gawdee_variants_for_item((int) $item['id'], $includeInactive);
    return $item;
}

/** @return array<int,array> raw variant rows for an item */
function gawdee_variants_for_item(int $itemId, bool $includeInactive = false): array
{
    gawdee_ensure_items_migrated();
    if ($itemId <= 0 || !gawdee_items_tables_ready()) {
        return [];
    }
    $sql = 'SELECT * FROM item_variants WHERE item_id = ?' . ($includeInactive ? '' : ' AND is_active = 1') . ' ORDER BY mrp, id';
    $stmt = gawdee_db()->prepare($sql);
    $stmt->execute([$itemId]);
    $rows = array_map(static function (array $r): array {
        $r['id'] = (int) $r['id'];
        $r['item_id'] = (int) $r['item_id'];
        $r['stock_quantity'] = (int) ($r['stock_quantity'] ?? 0);
        $r['mrp'] = (int) ($r['mrp'] ?? 0);
        $r['price'] = (int) ($r['price'] ?? 0);
        $r['discount'] = (float) ($r['discount'] ?? 0);
        $r['is_inclusive_tax'] = (int) ($r['is_inclusive_tax'] ?? 1);
        $r['is_active'] = (int) ($r['is_active'] ?? 1);
        return $r;
    }, $stmt->fetchAll());
    try {
        $galleryByVariant = gawdee_variant_images_batch(array_map(static fn(array $r): int => (int) $r['id'], $rows), $includeInactive);
        foreach ($rows as &$row) {
            $row['images'] = $galleryByVariant[(int) $row['id']] ?? [];
        }
        unset($row);
    } catch (Throwable) {
    }
    return $rows;
}

/**
 * Resolve a variant by numeric id, legacy products.id, variant slug, or SKU.
 * Returns raw variant row (+ parent item fields prefixed with item_).
 */
function gawdee_variant_by_ref(int|string $ref, bool $includeInactive = false): ?array
{
    gawdee_ensure_items_migrated();
    if (!gawdee_items_tables_ready()) {
        return null;
    }
    $refStr = trim((string) $ref);
    if ($refStr === '') {
        return null;
    }
    $db = gawdee_db();
    $activeSql = $includeInactive ? '' : ' AND v.is_active = 1 AND i.is_active = 1';
    $queries = [];
    if (ctype_digit($refStr)) {
        $queries[] = ['SELECT v.*, i.slug AS item_slug, i.is_active AS item_is_active FROM item_variants v JOIN items i ON i.id = v.item_id WHERE v.id = ?' . $activeSql . ' LIMIT 1', [$refStr]];
    }
    $queries[] = ['SELECT v.*, i.slug AS item_slug, i.is_active AS item_is_active FROM item_variants v JOIN items i ON i.id = v.item_id WHERE v.slug = ?' . $activeSql . ' LIMIT 1', [$refStr]];
    $queries[] = ['SELECT v.*, i.slug AS item_slug, i.is_active AS item_is_active FROM item_variants v JOIN items i ON i.id = v.item_id WHERE v.legacy_product_id = ?' . $activeSql . ' LIMIT 1', [$refStr]];
    $queries[] = ['SELECT v.*, i.slug AS item_slug, i.is_active AS item_is_active FROM item_variants v JOIN items i ON i.id = v.item_id WHERE v.sku = ?' . $activeSql . ' LIMIT 1', [$refStr]];
    foreach ($queries as [$sql, $params]) {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            if ($row) {
                $row['id'] = (int) $row['id'];
                $row['item_id'] = (int) $row['item_id'];
                $row['stock_quantity'] = (int) ($row['stock_quantity'] ?? 0);
                $row['mrp'] = (int) ($row['mrp'] ?? 0);
                $row['price'] = (int) ($row['price'] ?? 0);
                $row['discount'] = (float) ($row['discount'] ?? 0);
                $row['is_inclusive_tax'] = (int) ($row['is_inclusive_tax'] ?? 1);
                $row['is_active'] = (int) ($row['is_active'] ?? 1);
                try {
                    $row['images'] = gawdee_variant_images((int) $row['id'], $includeInactive);
                } catch (Throwable) {
                    $row['images'] = [];
                }
                return $row;
            }
        } catch (Throwable) {
            continue;
        }
    }
    return null;
}

/**
 * Compatibility catalogue: one row per VARIANT in the legacy product shape,
 * so existing storefront templates keep working while reading the new tables.
 *
 * @return array<int,array>
 */
function gawdee_catalog_rows(bool $includeInactive = false): array
{
    gawdee_ensure_items_migrated();
    if (!gawdee_items_tables_ready() || gawdee_items_count(true) === 0) {
        return [];
    }
    $db = gawdee_db();
    $sql = 'SELECT v.*, i.slug AS item_slug_col, i.name AS item_name, i.flavor AS item_flavor,'
        . ' i.description AS item_description, i.image AS item_image, i.hover_image AS item_hover,'
        . ' i.customer_review AS item_review, i.category AS item_category, i.category_key AS item_catkey,'
        . ' i.tag AS item_tag, i.accent AS item_accent, i.rating AS item_rating,'
        . ' i.review_count AS item_reviews, i.is_active AS item_active, i.created_at AS item_created'
        . ' FROM item_variants v JOIN items i ON i.id = v.item_id';
    $conds = [];
    if (!$includeInactive) {
        $conds[] = 'v.is_active = 1 AND i.is_active = 1';
    }
    if ($conds) {
        $sql .= ' WHERE ' . implode(' AND ', $conds);
    }
    $sql .= ' ORDER BY i.name, v.mrp, v.id';
    try {
        $rows = $db->query($sql)->fetchAll();
    } catch (Throwable) {
        return [];
    }
    $galleryByVariant = [];
    try {
        $galleryByVariant = gawdee_variant_images_batch(array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $rows), $includeInactive);
    } catch (Throwable) {
    }
    $out = [];
    foreach ($rows as $r) {
        $item = [
            'id' => (int) ($r['item_id'] ?? 0),
            'slug' => (string) ($r['item_slug_col'] ?? ''),
            'name' => (string) ($r['item_name'] ?? ''),
            'flavor' => (string) ($r['item_flavor'] ?? ''),
            'description' => (string) ($r['item_description'] ?? ''),
            'image' => (string) ($r['item_image'] ?? ''),
            'hover_image' => (string) ($r['item_hover'] ?? ''),
            'customer_review' => (string) ($r['item_review'] ?? ''),
            'category' => (string) ($r['item_category'] ?? ''),
            'category_key' => (string) ($r['item_catkey'] ?? ''),
            'tag' => (string) ($r['item_tag'] ?? ''),
            'accent' => (string) ($r['item_accent'] ?? '#0a7540'),
            'rating' => (float) ($r['item_rating'] ?? 0),
            'review_count' => (int) ($r['item_reviews'] ?? 0),
            'is_active' => (int) ($r['item_active'] ?? 1),
            'created_at' => (string) ($r['item_created'] ?? ''),
        ];
        $variant = [
            'id' => (int) ($r['id'] ?? 0),
            'item_id' => (int) ($r['item_id'] ?? 0),
            'variant_name' => (string) ($r['variant_name'] ?? ''),
            'slug' => (string) ($r['slug'] ?? ''),
            'sku' => (string) ($r['sku'] ?? ''),
            'stock_quantity' => (int) ($r['stock_quantity'] ?? 0),
            'mrp' => (int) ($r['mrp'] ?? 0),
            'discount' => (float) ($r['discount'] ?? 0),
            'price' => (int) ($r['price'] ?? 0),
            'is_inclusive_tax' => (int) ($r['is_inclusive_tax'] ?? 1),
            'image' => (string) ($r['image'] ?? ''),
            'legacy_product_id' => (string) ($r['legacy_product_id'] ?? ''),
            'is_active' => (int) ($r['is_active'] ?? 1),
            'created_at' => (string) ($r['created_at'] ?? ''),
            'updated_at' => (string) ($r['updated_at'] ?? ''),
            'images' => $galleryByVariant[(int) ($r['id'] ?? 0)] ?? [],
        ];
        $out[] = gawdee_map_variant_row($item, $variant);
    }
    return $out;
}

// ---------- Validation ----------

function gawdee_validate_item_fields(array $fields): array
{
    $name = trim((string) ($fields['name'] ?? ''));
    $slug = trim((string) ($fields['slug'] ?? ''));
    if ($slug === '' && $name !== '') {
        $slug = function_exists('gawdee_slug') ? gawdee_slug($name) : strtolower(preg_replace('/[^a-z0-9]+/', '-', $name) ?? 'item');
    }
    if ($name === '') {
        throw new RuntimeException('Item name is required.');
    }
    if ($slug === '') {
        throw new RuntimeException('Item slug could not be generated.');
    }
    if (!preg_match('/^[a-z0-9-]{2,191}$/', $slug)) {
        throw new RuntimeException('Item slug must be 2–191 chars of lowercase letters, numbers and hyphens.');
    }
    $accent = trim((string) ($fields['accent'] ?? '#0a7540'));
    if (!preg_match('/^#[0-9a-f]{6}$/i', $accent)) {
        $accent = '#0a7540';
    }
    return [
        'slug' => $slug,
        'name' => $name,
        'flavor' => mb_substr(trim((string) ($fields['flavor'] ?? '')), 0, 100),
        'description' => trim((string) ($fields['description'] ?? '')),
        'image' => mb_substr(trim((string) ($fields['image'] ?? '')), 0, 255),
        'hover_image' => mb_substr(trim((string) ($fields['hover_image'] ?? '')), 0, 255),
        'customer_review' => trim((string) ($fields['customer_review'] ?? '')),
        'category' => mb_substr(trim((string) ($fields['category'] ?? '')), 0, 100),
        'category_key' => mb_substr(trim((string) ($fields['category_key'] ?? '')), 0, 100),
        'tag' => mb_substr(trim((string) ($fields['tag'] ?? '')), 0, 100),
        'accent' => $accent,
        'is_active' => !empty($fields['is_active']) ? 1 : 0,
    ];
}

function gawdee_validate_variant_fields(array $fields, int $itemId, ?int $ignoreVariantId = null): array
{
    if ($itemId <= 0 || !gawdee_item_by_id($itemId)) {
        throw new RuntimeException('Valid ItemId is required for a variant.');
    }
    $variantName = trim((string) ($fields['variant_name'] ?? $fields['weight'] ?? ''));
    if ($variantName === '') {
        throw new RuntimeException('Variant name (e.g. 250g, 500ml, 1kg) is required.');
    }
    $variantName = mb_substr($variantName, 0, 100);
    $sku = trim((string) ($fields['sku'] ?? ''));
    if ($sku === '') {
        throw new RuntimeException('SKU is required for every variant.');
    }
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,98}$/', $sku)) {
        throw new RuntimeException('SKU must be 2–99 chars: letters, numbers, dot, underscore or hyphen.');
    }
    // SKU must be unique across variants (case-insensitive).
    $db = gawdee_db();
    $q = 'SELECT id FROM item_variants WHERE LOWER(sku) = LOWER(?)';
    $params = [$sku];
    if ($ignoreVariantId) {
        $q .= ' AND id != ?';
        $params[] = $ignoreVariantId;
    }
    $q .= ' LIMIT 1';
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    if ($stmt->fetchColumn()) {
        throw new RuntimeException('SKU "' . $sku . '" is already used by another variant.');
    }

    if (!isset($fields['stock_quantity']) && isset($fields['stock'])) {
        $fields['stock_quantity'] = $fields['stock'];
    }
    if (!is_numeric($fields['stock_quantity'] ?? null) || (int) ($fields['stock_quantity'] ?? 0) != ($fields['stock_quantity'] ?? 'x') && !ctype_digit((string) ($fields['stock_quantity'] ?? ''))) {
        // Allow numeric strings; reject non-numeric.
        if (!is_numeric($fields['stock_quantity'] ?? null)) {
            throw new RuntimeException('Stock quantity must be a number.');
        }
    }
    $stock = (int) ($fields['stock_quantity'] ?? 0);
    if ($stock < 0 || $stock > 1000000) {
        throw new RuntimeException('Stock quantity must be between 0 and 1000000.');
    }
    if (!isset($fields['mrp']) && isset($fields['original_price'])) {
        $fields['mrp'] = $fields['original_price'];
    }
    if (!is_numeric($fields['mrp'] ?? null)) {
        throw new RuntimeException('MRP must be a number.');
    }
    $mrp = (int) round((float) $fields['mrp']);
    if ($mrp < 0 || $mrp > 10000000) {
        throw new RuntimeException('MRP must be between 0 and 10000000.');
    }
    if (!isset($fields['discount']) && isset($fields['discount_percent'])) {
        $fields['discount'] = $fields['discount_percent'];
    }
    if (!is_numeric($fields['discount'] ?? 0)) {
        throw new RuntimeException('Discount must be a number (percent).');
    }
    $discount = round((float) ($fields['discount'] ?? 0), 2);
    if ($discount < 0 || $discount > 90) {
        throw new RuntimeException('Discount must be between 0 and 90 percent.');
    }
    $price = gawdee_variant_price($mrp, $discount);
    if ($mrp > 0 && $price > $mrp) {
        throw new RuntimeException('Discount produces an invalid price above MRP.');
    }
    if ($mrp === 0 && $price !== 0) {
        $price = 0;
    }

    $slug = trim((string) ($fields['slug'] ?? ''));
    if ($slug === '') {
        $item = gawdee_item_by_id($itemId);
        $base = (($item['slug'] ?? 'item') . '-' . strtolower((string) preg_replace('/[^a-z0-9]+/', '-', $variantName)));
        $slug = function_exists('gawdee_slug') ? gawdee_slug($base) : trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($base)), '-');
    }
    if (!preg_match('/^[a-z0-9-]{2,191}$/', $slug)) {
        throw new RuntimeException('Variant slug must be 2–191 chars of lowercase letters, numbers and hyphens.');
    }
    $chk = $db->prepare('SELECT id FROM item_variants WHERE slug = ?' . ($ignoreVariantId ? ' AND id != ?' : '') . ' LIMIT 1');
    $chk->execute($ignoreVariantId ? [$slug, $ignoreVariantId] : [$slug]);
    if ($chk->fetchColumn()) {
        throw new RuntimeException('Variant slug "' . $slug . '" is already used.');
    }

    // IsInclusiveTax defaults to TRUE.
    $isInclusive = 1;
    if (array_key_exists('is_inclusive_tax', $fields)) {
        $v = $fields['is_inclusive_tax'];
        $isInclusive = ($v === 0 || $v === '0' || $v === false || $v === 'false' || $v === 'no') ? 0 : 1;
        if (is_string($v) && trim($v) === '') {
            $isInclusive = 1;
        }
    } elseif (array_key_exists('is_inclusive', $fields)) {
        $isInclusive = !empty($fields['is_inclusive']) ? 1 : 0;
    }

    return [
        'variant_name' => $variantName,
        'slug' => $slug,
        'sku' => $sku,
        'stock_quantity' => $stock,
        'mrp' => $mrp,
        'discount' => $discount,
        'price' => $price,
        'is_inclusive_tax' => $isInclusive,
        'image' => mb_substr(trim((string) ($fields['image'] ?? '')), 0, 255),
        'is_active' => array_key_exists('is_active', $fields) ? (!empty($fields['is_active']) ? 1 : 0) : 1,
    ];
}

// ---------- Write helpers ----------

function gawdee_create_item(array $fields): int
{
    $clean = gawdee_validate_item_fields($fields);
    $db = gawdee_db();
    $chk = $db->prepare('SELECT id FROM items WHERE slug = ? LIMIT 1');
    $chk->execute([$clean['slug']]);
    if ($chk->fetchColumn()) {
        throw new RuntimeException('An item with this slug already exists.');
    }
    $stmt = $db->prepare('INSERT INTO items (slug, name, flavor, description, image, hover_image, customer_review, category, category_key, tag, accent, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$clean['slug'], $clean['name'], $clean['flavor'], $clean['description'], $clean['image'], $clean['hover_image'], $clean['customer_review'], $clean['category'], $clean['category_key'], $clean['tag'], $clean['accent'], $clean['is_active']]);
    return (int) $db->lastInsertId();
}

function gawdee_update_item(int $itemId, array $fields): void
{
    $existing = gawdee_item_by_id($itemId);
    if (!$existing) {
        throw new RuntimeException('Item not found.');
    }
    $merged = array_merge($existing, $fields);
    // Preserve image paths when not supplied.
    foreach (['image', 'hover_image'] as $k) {
        if (!array_key_exists($k, $fields)) {
            $merged[$k] = $existing[$k];
        }
    }
    $clean = gawdee_validate_item_fields($merged);
    $db = gawdee_db();
    $chk = $db->prepare('SELECT id FROM items WHERE slug = ? AND id != ? LIMIT 1');
    $chk->execute([$clean['slug'], $itemId]);
    if ($chk->fetchColumn()) {
        throw new RuntimeException('Another item already uses this slug.');
    }
    $stmt = $db->prepare('UPDATE items SET slug=?, name=?, flavor=?, description=?, image=?, hover_image=?, customer_review=?, category=?, category_key=?, tag=?, accent=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
    $stmt->execute([$clean['slug'], $clean['name'], $clean['flavor'], $clean['description'], $clean['image'], $clean['hover_image'], $clean['customer_review'], $clean['category'], $clean['category_key'], $clean['tag'], $clean['accent'], $clean['is_active'], $itemId]);
}

function gawdee_delete_item(int $itemId): void
{
    $db = gawdee_db();
    $stmt = $db->prepare('SELECT id FROM items WHERE id = ? LIMIT 1');
    $stmt->execute([$itemId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Item not found.');
    }
    // Collect variants first so legacy/mirror products rows can be retired.
    $variants = [];
    try {
        $vs = $db->prepare('SELECT id, legacy_product_id FROM item_variants WHERE item_id = ?');
        $vs->execute([$itemId]);
        $variants = $vs->fetchAll();
    } catch (Throwable) {
    }
    // Variants removed automatically via FK cascade; explicit delete for SQLite builds without FK enforcement.
    $db->prepare('DELETE FROM item_variants WHERE item_id = ?')->execute([$itemId]);
    $db->prepare('DELETE FROM items WHERE id = ?')->execute([$itemId]);
    foreach ($variants as $v) {
        $vid = (int) ($v['id'] ?? 0);
        $legacy = (string) ($v['legacy_product_id'] ?? '');
        gawdee_delete_variant_images($vid);
        try {
            // Remove the lightweight mirror for brand-new variants.
            $db->prepare("DELETE FROM products WHERE id = ? AND source_id = 'variant-mirror'")->execute([(string) $vid]);
            $db->prepare('DELETE FROM products WHERE id = ?')->execute([(string) $vid]);
        } catch (Throwable) {
        }
        if ($legacy !== '') {
            try {
                // Retire (don't hard-delete) legacy rows: old orders reference
                // them, and the incremental sync only imports active rows.
                $db->prepare('UPDATE products SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$legacy]);
            } catch (Throwable) {
            }
        }
    }
}

function gawdee_create_variant(int $itemId, array $fields): int
{
    $clean = gawdee_validate_variant_fields($fields, $itemId);
    $db = gawdee_db();
    $stmt = $db->prepare('INSERT INTO item_variants (item_id, variant_name, slug, sku, stock_quantity, mrp, discount, price, is_inclusive_tax, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$itemId, $clean['variant_name'], $clean['slug'], $clean['sku'], $clean['stock_quantity'], $clean['mrp'], $clean['discount'], $clean['price'], $clean['is_inclusive_tax'], $clean['image'], $clean['is_active']]);
    $newId = (int) $db->lastInsertId();
    gawdee_sync_variant_mirror($newId);
    return $newId;
}

function gawdee_update_variant(int $variantId, array $fields): void
{
    $db = gawdee_db();
    $stmt = $db->prepare('SELECT * FROM item_variants WHERE id = ? LIMIT 1');
    $stmt->execute([$variantId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        throw new RuntimeException('Variant not found.');
    }
    $itemId = (int) ($fields['item_id'] ?? $existing['item_id']);
    $merged = array_merge($existing, $fields);
    if (!array_key_exists('image', $fields)) {
        $merged['image'] = $existing['image'];
    }
    // Map legacy keys.
    if (isset($merged['weight']) && !isset($fields['variant_name'])) {
        $merged['variant_name'] = $merged['weight'];
    }
    if (isset($merged['stock']) && !isset($fields['stock_quantity'])) {
        $merged['stock_quantity'] = $merged['stock'];
    }
    if (isset($merged['original_price']) && !isset($fields['mrp'])) {
        $merged['mrp'] = $merged['original_price'];
    }
    $clean = gawdee_validate_variant_fields($merged, $itemId, $variantId);
    $upd = $db->prepare('UPDATE item_variants SET item_id=?, variant_name=?, slug=?, sku=?, stock_quantity=?, mrp=?, discount=?, price=?, is_inclusive_tax=?, image=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
    $upd->execute([$itemId, $clean['variant_name'], $clean['slug'], $clean['sku'], $clean['stock_quantity'], $clean['mrp'], $clean['discount'], $clean['price'], $clean['is_inclusive_tax'], $clean['image'], $clean['is_active'], $variantId]);
    gawdee_sync_variant_mirror($variantId);
}

function gawdee_delete_variant(int $variantId): void
{
    $db = gawdee_db();
    $stmt = $db->prepare('SELECT item_id, legacy_product_id FROM item_variants WHERE id = ? LIMIT 1');
    $stmt->execute([$variantId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('Variant not found.');
    }
    $itemId = (int) $row['item_id'];
    $legacy = (string) ($row['legacy_product_id'] ?? '');
    $count = $db->prepare('SELECT COUNT(*) FROM item_variants WHERE item_id = ?');
    $count->execute([$itemId]);
    if ((int) $count->fetchColumn() <= 1) {
        throw new RuntimeException('An item must keep at least one variant. Delete the item instead.');
    }
    $db->prepare('DELETE FROM item_variants WHERE id = ?')->execute([$variantId]);
    gawdee_delete_variant_images($variantId);
    try {
        $db->prepare("DELETE FROM products WHERE id = ? AND source_id = 'variant-mirror'")->execute([(string) $variantId]);
        $db->prepare('DELETE FROM products WHERE id = ?')->execute([(string) $variantId]);
    } catch (Throwable) {
    }
    if ($legacy !== '') {
        try {
            $db->prepare('UPDATE products SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$legacy]);
        } catch (Throwable) {
        }
    }
}

/**
 * Save an item + its full variant set in one transaction.
 * $variants is a list of arrays; each may contain `id` for existing rows.
 * Variants missing from the list are deleted (except the last remaining one).
 *
 * @return int item id
 */
function gawdee_save_item_with_variants(array $itemFields, array $variants, ?int $itemId = null): int
{
    if (!$variants) {
        throw new RuntimeException('Add at least one variant (e.g. 250g) before saving.');
    }
    $db = gawdee_db();
    $db->beginTransaction();
    try {
        if ($itemId) {
            gawdee_update_item($itemId, $itemFields);
        } else {
            $itemId = gawdee_create_item($itemFields);
        }
        $keptIds = [];
        foreach ($variants as $v) {
            $vid = !empty($v['id']) ? (int) $v['id'] : null;
            if ($vid) {
                $chk = $db->prepare('SELECT id FROM item_variants WHERE id = ? AND item_id = ? LIMIT 1');
                $chk->execute([$vid, $itemId]);
                if (!$chk->fetchColumn()) {
                    throw new RuntimeException('Variant #' . $vid . ' does not belong to this item.');
                }
                gawdee_update_variant($vid, array_merge($v, ['item_id' => $itemId]));
                $keptIds[] = $vid;
            } else {
                $keptIds[] = gawdee_create_variant($itemId, array_merge($v, ['item_id' => $itemId]));
            }
        }
        // Delete variants removed in the UI (retire their legacy/mirror rows too).
        $existing = $db->prepare('SELECT id, legacy_product_id FROM item_variants WHERE item_id = ?');
        $existing->execute([$itemId]);
        foreach ($existing->fetchAll() as $erow) {
            $eid = (int) ($erow['id'] ?? 0);
            $elegacy = (string) ($erow['legacy_product_id'] ?? '');
            if (!in_array($eid, $keptIds, true)) {
                // Keep at least one variant.
                $cnt = $db->prepare('SELECT COUNT(*) FROM item_variants WHERE item_id = ?');
                $cnt->execute([$itemId]);
                if ((int) $cnt->fetchColumn() <= 1) {
                    break;
                }
                $db->prepare('DELETE FROM item_variants WHERE id = ?')->execute([$eid]);
                gawdee_delete_variant_images($eid);
                try {
                    $db->prepare("DELETE FROM products WHERE id = ? AND source_id = 'variant-mirror'")->execute([(string) $eid]);
                    $db->prepare('DELETE FROM products WHERE id = ?')->execute([(string) $eid]);
                } catch (Throwable) {
                }
                if ($elegacy !== '') {
                    try {
                        $db->prepare('UPDATE products SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$elegacy]);
                    } catch (Throwable) {
                    }
                }
            }
        }
        $db->commit();
        return $itemId;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

// ---------- Inventory (variant-aware, legacy-safe) ----------

/** Resolve a catalogue product to its variant identity for stock operations. */
function gawdee_resolve_variant_identity(array $product): array
{
    $variantId = 0;
    if (isset($product['variant_id'])) {
        $variantId = (int) $product['variant_id'];
    } elseif (isset($product['id']) && ctype_digit((string) $product['id']) && !empty($product['item_id'])) {
        $variantId = (int) $product['id'];
    }
    return [
        'variant_id' => $variantId,
        'legacy_id' => (string) ($product['legacy_product_id'] ?? ''),
        'product_id' => (string) ($product['id'] ?? ''),
    ];
}

function gawdee_deduct_variant_stock(int|string $ref, int $qty): bool
{
    $qty = max(1, $qty);
    if (!gawdee_items_tables_ready()) {
        return false;
    }
    $variant = gawdee_variant_by_ref($ref, true);
    if (!$variant) {
        return false;
    }
    $db = gawdee_db();
    $stmt = $db->prepare('UPDATE item_variants SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?');
    $stmt->execute([$qty, (int) $variant['id'], $qty]);
    if ($stmt->rowCount() !== 1) {
        return false;
    }
    // Keep legacy products row in sync when it exists (old direct queries / FK).
    if (!empty($variant['legacy_product_id'])) {
        try {
            $db->prepare("UPDATE products SET stock = GREATEST(0, stock - ?), stock_status = CASE WHEN stock - ? <= 0 THEN 'out_of_stock' ELSE 'in_stock' END WHERE id = ?")->execute([$qty, $qty, (string) $variant['legacy_product_id']]);
        } catch (Throwable) {
        }
    }
    gawdee_sync_variant_mirror((int) $variant['id']);
    return true;
}

function gawdee_restore_variant_stock(int|string $ref, int $qty): void
{
    $qty = max(1, $qty);
    if (!gawdee_items_tables_ready()) {
        return;
    }
    $variant = gawdee_variant_by_ref($ref, true);
    if (!$variant) {
        return;
    }
    $db = gawdee_db();
    try {
        $db->prepare('UPDATE item_variants SET stock_quantity = stock_quantity + ? WHERE id = ?')->execute([$qty, (int) $variant['id']]);
    } catch (Throwable) {
        return;
    }
    if (!empty($variant['legacy_product_id'])) {
        try {
            $db->prepare("UPDATE products SET stock = stock + ?, stock_status='in_stock' WHERE id = ?")->execute([$qty, (string) $variant['legacy_product_id']]);
        } catch (Throwable) {
        }
    }
    gawdee_sync_variant_mirror((int) $variant['id']);
}

function gawdee_variant_stock(int|string $ref): ?int
{
    $variant = gawdee_variant_by_ref($ref, true);
    if (!$variant) {
        return null;
    }
    return max(0, (int) ($variant['stock_quantity'] ?? 0));
}

/**
 * Keep a lightweight mirror row in legacy `products` for brand-new variants
 * (no legacy_product_id) so FK-constrained tables (product_reviews) and any
 * residual direct `products` queries keep working. Migrated variants already
 * have a legacy row and need no mirror.
 */
function gawdee_sync_variant_mirror(int $variantId): void
{
    try {
        if (!gawdee_items_tables_ready()) {
            return;
        }
        $db = gawdee_db();
        $stmt = $db->prepare('SELECT v.*, i.name AS iname, i.slug AS islug, i.category AS icat, i.category_key AS ikey, i.tag AS itag, i.description AS idesc, i.image AS iimg, i.accent AS iaccent FROM item_variants v JOIN items i ON i.id = v.item_id WHERE v.id = ? LIMIT 1');
        $stmt->execute([$variantId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        if (!empty($row['legacy_product_id'])) {
            // Sync legacy row stock/price for consistency.
            try {
                $db->prepare("UPDATE products SET stock=?, price=?, original_price=?, stock_status=CASE WHEN ? > 0 THEN 'in_stock' ELSE 'out_of_stock' END WHERE id=?")->execute([(int) $row['stock_quantity'], (int) $row['price'], (int) $row['mrp'], (int) $row['stock_quantity'], (string) $row['legacy_product_id']]);
            } catch (Throwable) {
            }
            return;
        }
        $mirrorId = (string) $row['id'];
        $variantName = trim((string) ($row['variant_name'] ?? '')) ?: 'Standard';
        $fullName = trim((string) ($row['iname'] ?? '')) . ' ' . $variantName;
        $primaryImage = '';
        try {
            foreach (gawdee_variant_images((int) $row['id'], false) as $galleryRow) {
                $candidate = trim((string) ($galleryRow['image'] ?? ''));
                if ($candidate !== '') {
                    $primaryImage = $candidate;
                    break;
                }
            }
        } catch (Throwable) {
        }
        $image = $primaryImage !== '' ? $primaryImage : (trim((string) ($row['image'] ?? '')) !== '' ? (string) $row['image'] : (string) ($row['iimg'] ?? ''));
        $exists = $db->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
        $exists->execute([$mirrorId]);
        if ($exists->fetchColumn()) {
            $db->prepare("UPDATE products SET slug=?, name=?, full_name=?, category=?, category_key=?, tag=?, price=?, original_price=?, weight=?, image=?, description=?, accent=?, stock=?, stock_status=CASE WHEN ? > 0 THEN 'in_stock' ELSE 'out_of_stock' END, sku=?, is_active=?, source_id='variant-mirror', updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([(string) $row['slug'], (string) $row['iname'], $fullName, (string) $row['icat'], (string) $row['ikey'], (string) $row['itag'], (int) $row['price'], (int) $row['mrp'], $variantName, $image, (string) $row['idesc'], (string) $row['iaccent'], (int) $row['stock_quantity'], (int) $row['stock_quantity'], (string) $row['sku'], (int) $row['is_active'], $mirrorId]);
        } else {
            // Avoid slug collision with legacy rows.
            $slugChk = $db->prepare('SELECT id FROM products WHERE slug = ? LIMIT 1');
            $slugChk->execute([(string) $row['slug']]);
            if ($slugChk->fetchColumn()) {
                return;
            }
            $db->prepare("INSERT INTO products (id, slug, name, full_name, category, category_key, tag, price, original_price, weight, image, description, accent, stock, stock_status, sku, source_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'variant-mirror', ?)")->execute([$mirrorId, (string) $row['slug'], (string) $row['iname'], $fullName, (string) $row['icat'], (string) $row['ikey'], (string) $row['itag'], (int) $row['price'], (int) $row['mrp'], $variantName, $image, (string) $row['idesc'], (string) $row['iaccent'], (int) $row['stock_quantity'], ((int) $row['stock_quantity'] > 0 ? 'in_stock' : 'out_of_stock'), (string) $row['sku'], (int) $row['is_active']]);
        }
    } catch (Throwable) {
        // Mirror must never break catalogue writes.
    }
}

function gawdee_sync_item_mirrors(int $itemId): void
{
    try {
        foreach (gawdee_variants_for_item($itemId, true) as $v) {
            gawdee_sync_variant_mirror((int) $v['id']);
        }
    } catch (Throwable) {
    }
}

// ---------- Variant Images (one variant → many images) ----------

function gawdee_images_tables_ready(): bool
{
    try {
        $cols = gawdee_get_table_columns(gawdee_db(), 'item_images');
        return in_array('id', $cols, true) && in_array('variant_id', $cols, true);
    } catch (Throwable) {
        return false;
    }
}

function gawdee_normalize_image_row(array $r): array
{
    return [
        'id' => (int) ($r['id'] ?? 0),
        'variant_id' => (int) ($r['variant_id'] ?? 0),
        'image' => (string) ($r['image'] ?? ''),
        'sort_order' => (int) ($r['sort_order'] ?? 0),
        'is_active' => (int) ($r['is_active'] ?? 1),
        'created_at' => (string) ($r['created_at'] ?? ''),
        'updated_at' => (string) ($r['updated_at'] ?? ''),
    ];
}

/**
 * One-time backfill: every variant keeps working even before an admin adds
 * gallery images — seed one row from the legacy single-image column.
 */
function gawdee_migrate_variant_images(): int
{
    static $done = false;
    if ($done) {
        return 0;
    }
    $done = true;
    if (!gawdee_images_tables_ready() || !gawdee_items_tables_ready()) {
        return 0;
    }
    try {
        $db = gawdee_db();
        $variants = $db->query("SELECT id, image FROM item_variants WHERE image IS NOT NULL AND image != ''")->fetchAll();
        if (!$variants) {
            return 0;
        }
        $have = [];
        foreach ($db->query('SELECT DISTINCT variant_id FROM item_images')->fetchAll(PDO::FETCH_COLUMN) as $vid) {
            $have[(int) $vid] = true;
        }
        $ins = $db->prepare('INSERT INTO item_images (variant_id, image, sort_order, is_active) VALUES (?, ?, 0, 1)');
        $migrated = 0;
        foreach ($variants as $v) {
            $vid = (int) ($v['id'] ?? 0);
            $path = trim((string) ($v['image'] ?? ''));
            if ($vid <= 0 || $path === '' || isset($have[$vid])) {
                continue;
            }
            try {
                $ins->execute([$vid, mb_substr($path, 0, 255)]);
                $migrated++;
            } catch (Throwable) {
                continue;
            }
        }
        return $migrated;
    } catch (Throwable) {
        return 0;
    }
}

/** @return array<int,array> active (or all) images for one variant, display order. */
function gawdee_variant_images(int $variantId, bool $includeInactive = false): array
{
    if ($variantId <= 0 || !gawdee_images_tables_ready()) {
        return [];
    }
    gawdee_migrate_variant_images();
    try {
        $sql = 'SELECT * FROM item_images WHERE variant_id = ?' . ($includeInactive ? '' : ' AND is_active = 1') . ' ORDER BY sort_order, id';
        $stmt = gawdee_db()->prepare($sql);
        $stmt->execute([$variantId]);
        return array_map('gawdee_normalize_image_row', $stmt->fetchAll());
    } catch (Throwable) {
        return [];
    }
}

/**
 * Single-query batch loader to avoid N+1 in catalogue loops.
 *
 * @param array<int> $variantIds
 * @return array<int,array<int,array>> variant_id => image rows
 */
function gawdee_variant_images_batch(array $variantIds, bool $includeInactive = false): array
{
    $variantIds = array_values(array_unique(array_map('intval', $variantIds)));
    $variantIds = array_values(array_filter($variantIds, static fn(int $v): bool => $v > 0));
    $out = [];
    foreach ($variantIds as $vid) {
        $out[$vid] = [];
    }
    if (!$variantIds || !gawdee_images_tables_ready()) {
        return $out;
    }
    gawdee_migrate_variant_images();
    try {
        $placeholders = implode(',', array_fill(0, count($variantIds), '?'));
        $sql = "SELECT * FROM item_images WHERE variant_id IN ($placeholders)" . ($includeInactive ? '' : ' AND is_active = 1') . ' ORDER BY variant_id, sort_order, id';
        $stmt = gawdee_db()->prepare($sql);
        $stmt->execute($variantIds);
        foreach ($stmt->fetchAll() as $row) {
            $row = gawdee_normalize_image_row($row);
            $out[$row['variant_id']][] = $row;
        }
    } catch (Throwable) {
    }
    return $out;
}

/**
 * Primary display image for a variant: first active gallery image,
 * else the legacy single-image fallback chain.
 */
function gawdee_variant_primary_image(array $variant, array $item = [], ?array $images = null): string
{
    if ($images === null) {
        $images = [];
        if (!empty($variant['images']) && is_array($variant['images'])) {
            $images = $variant['images'];
        } elseif (!empty($variant['id'])) {
            $images = gawdee_variant_images((int) $variant['id'], false);
        }
    }
    foreach ($images as $img) {
        $path = trim((string) (is_array($img) ? ($img['image'] ?? '') : $img));
        if ($path !== '') {
            return $path;
        }
    }
    $fallback = trim((string) ($variant['image'] ?? ''));
    if ($fallback !== '') {
        return $fallback;
    }
    return trim((string) ($item['image'] ?? ''));
}

/** Insert one gallery row. Returns the new image id. */
function gawdee_add_variant_image(int $variantId, string $imagePath, int $sortOrder = -1, int $isActive = 1): int
{
    if (!gawdee_images_tables_ready()) {
        throw new RuntimeException('Image storage is not ready.');
    }
    $db = gawdee_db();
    $chk = $db->prepare('SELECT id FROM item_variants WHERE id = ? LIMIT 1');
    $chk->execute([$variantId]);
    if (!$chk->fetchColumn()) {
        throw new RuntimeException('Variant not found.');
    }
    $imagePath = trim($imagePath);
    if ($imagePath === '' || strlen($imagePath) > 255) {
        throw new RuntimeException('A valid image path is required.');
    }
    if ($sortOrder < 0) {
        try {
            $mx = $db->prepare('SELECT COALESCE(MAX(sort_order), -1) FROM item_images WHERE variant_id = ?');
            $mx->execute([$variantId]);
            $sortOrder = (int) $mx->fetchColumn() + 1;
        } catch (Throwable) {
            $sortOrder = 0;
        }
    }
    $stmt = $db->prepare('INSERT INTO item_images (variant_id, image, sort_order, is_active) VALUES (?, ?, ?, ?)');
    $stmt->execute([$variantId, $imagePath, max(0, $sortOrder), $isActive ? 1 : 0]);
    return (int) $db->lastInsertId();
}

function gawdee_update_variant_image(int $imageId, array $fields): void
{
    if (!gawdee_images_tables_ready()) {
        throw new RuntimeException('Image storage is not ready.');
    }
    $db = gawdee_db();
    $stmt = $db->prepare('SELECT * FROM item_images WHERE id = ? LIMIT 1');
    $stmt->execute([$imageId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        throw new RuntimeException('Image not found.');
    }
    $sortOrder = array_key_exists('sort_order', $fields) ? max(0, (int) $fields['sort_order']) : (int) ($existing['sort_order'] ?? 0);
    $isActive = array_key_exists('is_active', $fields) ? (!empty($fields['is_active']) ? 1 : 0) : (int) ($existing['is_active'] ?? 1);
    $image = trim((string) ($fields['image'] ?? $existing['image']));
    if ($image === '' || strlen($image) > 255) {
        throw new RuntimeException('A valid image path is required.');
    }
    $db->prepare('UPDATE item_images SET image=?, sort_order=?, is_active=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')
        ->execute([$image, $sortOrder, $isActive, $imageId]);
}

/**
 * Delete one gallery row and its physical file when safe.
 * Only files under assets/uploads/ are ever unlinked; seed assets in
 * assets/images/ and external URLs are left untouched.
 */
function gawdee_delete_variant_image(int $imageId, bool $deleteFile = true): void
{
    if (!gawdee_images_tables_ready()) {
        throw new RuntimeException('Image storage is not ready.');
    }
    $db = gawdee_db();
    $stmt = $db->prepare('SELECT * FROM item_images WHERE id = ? LIMIT 1');
    $stmt->execute([$imageId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('Image not found.');
    }
    $db->prepare('DELETE FROM item_images WHERE id = ?')->execute([$imageId]);
    if ($deleteFile) {
        gawdee_delete_image_file((string) ($row['image'] ?? ''));
    }
}

function gawdee_delete_image_file(string $path): void
{
    $path = trim($path);
    if ($path === '' || preg_match('#^https?://#i', $path)) {
        return;
    }
    $relative = ltrim(str_replace('\\', '/', $path), '/');
    if (!str_starts_with($relative, 'assets/uploads/') || str_contains($relative, '..')) {
        return;
    }
    $absolute = rtrim((string) GAWDEE_ROOT, '/\\') . '/' . $relative;
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

/**
 * Reconcile a variant's gallery against the admin's submitted order.
 * Only diffs are applied: rows absent from $keepIdsOrdered are deleted
 * (with files), survivors are re-sequenced. Never wipes + recreates.
 *
 * @param array<int> $keepIdsOrdered image ids in display order
 */
function gawdee_set_variant_images(int $variantId, array $keepIdsOrdered): void
{
    if (!gawdee_images_tables_ready()) {
        return;
    }
    $db = gawdee_db();
    $stmt = $db->prepare('SELECT * FROM item_images WHERE variant_id = ?');
    $stmt->execute([$variantId]);
    $existing = [];
    foreach ($stmt->fetchAll() as $row) {
        $existing[(int) ($row['id'] ?? 0)] = $row;
    }
    $keepIdsOrdered = array_values(array_unique(array_map('intval', $keepIdsOrdered)));
    $keepSet = array_fill_keys($keepIdsOrdered, true);
    foreach ($existing as $eid => $erow) {
        if (!isset($keepSet[$eid])) {
            try {
                gawdee_delete_variant_image($eid, true);
            } catch (Throwable) {
            }
        }
    }
    $position = 0;
    foreach ($keepIdsOrdered as $eid) {
        if (!isset($existing[$eid])) {
            continue;
        }
        try {
            $db->prepare('UPDATE item_images SET sort_order=?, updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$position, $eid]);
        } catch (Throwable) {
        }
        $position++;
    }
}

/**
 * Remove every gallery row (and safe files) for a variant.
 * FK cascade is the backstop; this keeps the uploads folder clean.
 */
function gawdee_delete_variant_images(int $variantId): void
{
    if (!gawdee_images_tables_ready()) {
        return;
    }
    try {
        $stmt = gawdee_db()->prepare('SELECT id FROM item_images WHERE variant_id = ?');
        $stmt->execute([$variantId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $eid) {
            try {
                gawdee_delete_variant_image((int) $eid, true);
            } catch (Throwable) {
            }
        }
    } catch (Throwable) {
    }
}

/**
 * Shared upload pipeline for variant gallery images.
 * Same validation as the existing admin media upload (type, 10MB, real
 * image check, random collision-proof filename); the DB row is written
 * only after the file lands on disk.
 */
function gawdee_save_variant_image_upload(int $variantId, string $fileField, string $folder = 'products'): int
{
    if (empty($_FILES[$fileField]['tmp_name']) || (int) ($_FILES[$fileField]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('No image was uploaded.');
    }
    if ((int) $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        if (in_array((int) $_FILES[$fileField]['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $maxLimit = ini_get('upload_max_filesize') ?: 'server limit';
            throw new RuntimeException("The image exceeds the server upload limit ({$maxLimit}). Use a smaller file.");
        }
        throw new RuntimeException('The image upload did not complete (error code ' . (int) $_FILES[$fileField]['error'] . ').');
    }
    if ((int) $_FILES[$fileField]['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('Images must be smaller than 10 MB.');
    }
    $mime = @(new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$fileField]['tmp_name']) ?: '';
    $ext = strtolower(pathinfo($_FILES[$fileField]['name'] ?? '', PATHINFO_EXTENSION));
    $types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $targetExt = $types[$mime] ?? null;
    if ($targetExt === null && ($mime === 'application/octet-stream' || $mime === '')) {
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $targetExt = $ext === 'jpeg' ? 'jpg' : $ext;
        }
    }
    if ($targetExt === null) {
        throw new RuntimeException('Upload a supported JPG, PNG, WebP or GIF image.');
    }
    if (@getimagesize($_FILES[$fileField]['tmp_name']) === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }
    $folderClean = preg_replace('/[^a-z0-9_-]/', '', strtolower($folder)) ?: 'products';
    $directory = rtrim((string) GAWDEE_ROOT, '/\\') . '/assets/uploads/' . $folderClean;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the media directory.');
    }
    $filename = $folderClean . '-' . bin2hex(random_bytes(9)) . '.' . $targetExt;
    $target = $directory . '/' . $filename;
    if (!move_uploaded_file($_FILES[$fileField]['tmp_name'], $target)) {
        throw new RuntimeException('Unable to store the uploaded image.');
    }
    $stored = 'assets/uploads/' . $folderClean . '/' . $filename;
    try {
        return gawdee_add_variant_image($variantId, $stored);
    } catch (Throwable $e) {
        @unlink($target);
        throw $e;
    }
}
