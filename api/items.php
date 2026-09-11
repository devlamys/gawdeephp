<?php

declare(strict_types=1);

/**
 * Gawdee Items + Variants JSON API.
 *
 * Public (GET, no auth):
 *   GET ?action=list                        -> all active items with variants
 *   GET ?action=get&id=12                    -> single item with variants (id or slug)
 *   GET ?action=get&slug=mixme-choco         -> same via slug
 *   GET ?action=variants&item_id=12          -> variants for an item
 *   GET ?action=variant&id=5                 -> single variant (id, slug, sku, legacy id) + images[]
 *   GET ?action=images&variant_id=5          -> images for one variant
 *
 * Admin (POST JSON, requires admin session + csrf_token):
 *   {action:create_item, item:{...}, variants:[{...}]}
 *   {action:update_item, item_id:12, item:{...}, variants:[{...}]}
 *   {action:delete_item, item_id:12}
 *   {action:create_variant, item_id:12, variant:{...}}
 *   {action:update_variant, variant_id:5, variant:{...}}
 *   {action:delete_variant, variant_id:5}
 *   multipart {action:create_image, variant_id:5, image_file:<upload>}
 *   {action:update_image, image_id:3, sort_order:0, is_active:1}
 *   {action:delete_image, image_id:3}
 */

require_once __DIR__ . '/../includes/platform.php';

function items_api_public_item(array $item, bool $withVariants = true): array
{
    $out = [
        'id' => (int) ($item['id'] ?? 0),
        'slug' => (string) ($item['slug'] ?? ''),
        'name' => (string) ($item['name'] ?? ''),
        'flavor' => (string) ($item['flavor'] ?? ''),
        'description' => (string) ($item['description'] ?? ''),
        'image' => (string) ($item['image'] ?? ''),
        'hover_image' => (string) ($item['hover_image'] ?? ''),
        'customer_review' => (string) ($item['customer_review'] ?? ''),
        'category' => (string) ($item['category'] ?? ''),
        'category_key' => (string) ($item['category_key'] ?? ''),
        'tag' => (string) ($item['tag'] ?? ''),
        'accent' => (string) ($item['accent'] ?? '#0a7540'),
        'rating' => (float) ($item['rating'] ?? 0),
        'review_count' => (int) ($item['review_count'] ?? 0),
        'is_active' => (int) ($item['is_active'] ?? 1),
    ];
    if ($withVariants) {
        $variants = [];
        try {
            $variants = gawdee_variants_for_item((int) ($item['id'] ?? 0), false);
        } catch (Throwable) {
            $variants = [];
        }
        $out['variants'] = array_map('items_api_public_variant', $variants);
        $prices = array_map(static fn(array $v): int => (int) ($v['price'] ?? 0), $variants);
        $out['price_min'] = $prices ? min($prices) : 0;
        $out['price_max'] = $prices ? max($prices) : 0;
        $out['total_stock'] = array_sum(array_map(static fn(array $v): int => (int) ($v['stock_quantity'] ?? 0), $variants));
    }
    return $out;
}

function items_api_public_variant(array $v): array
{
    $mrp = (int) ($v['mrp'] ?? 0);
    $price = (int) ($v['price'] ?? $mrp);
    $images = [];
    try {
        if (isset($v['images']) && is_array($v['images'])) {
            $images = array_values($v['images']);
        } elseif (!empty($v['id'])) {
            $images = gawdee_variant_images((int) $v['id'], false);
        }
    } catch (Throwable) {
        $images = [];
    }
    $primary = '';
    foreach ($images as $imgRow) {
        $candidate = trim((string) (is_array($imgRow) ? ($imgRow['image'] ?? '') : $imgRow));
        if ($candidate !== '') {
            $primary = $candidate;
            break;
        }
    }
    if ($primary === '') {
        $primary = trim((string) ($v['image'] ?? ''));
    }
    return [
        'id' => (int) ($v['id'] ?? 0),
        'item_id' => (int) ($v['item_id'] ?? 0),
        'variant_name' => (string) ($v['variant_name'] ?? ''),
        'slug' => (string) ($v['slug'] ?? ''),
        'sku' => (string) ($v['sku'] ?? ''),
        'stock_quantity' => (int) ($v['stock_quantity'] ?? 0),
        'in_stock' => ((int) ($v['stock_quantity'] ?? 0)) > 0,
        'mrp' => $mrp,
        'discount' => (float) ($v['discount'] ?? 0),
        'price' => $price,
        'price_formatted' => function_exists('money') ? money($price) : ('₹' . number_format($price)),
        'mrp_formatted' => function_exists('money') ? money($mrp) : ('₹' . number_format($mrp)),
        'is_inclusive_tax' => !empty($v['is_inclusive_tax']) ? true : false,
        'image' => (string) ($v['image'] ?? ''),
        'primary_image' => $primary,
        'images' => array_values(array_map(static function ($imgRow): array {
            $imgRow = is_array($imgRow) ? $imgRow : ['image' => (string) $imgRow];
            return [
                'id' => (int) ($imgRow['id'] ?? 0),
                'image' => (string) ($imgRow['image'] ?? ''),
                'sort_order' => (int) ($imgRow['sort_order'] ?? $imgRow['sortOrder'] ?? 0),
                'is_active' => !empty($imgRow['is_active'] ?? $imgRow['isActive'] ?? true),
            ];
        }, $images)),
        'is_active' => (int) ($v['is_active'] ?? 1),
    ];
}

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($method === 'GET') {
        $action = trim((string) ($_GET['action'] ?? 'list'));
        if ($action === '' || $action === 'list') {
            $items = gawdee_items(false, true);
            gawdee_json_response(['ok' => true, 'items' => array_map(static fn(array $i): array => items_api_public_item($i, true), $items), 'count' => count($items)]);
        }
        if ($action === 'get') {
            $ref = trim((string) ($_GET['id'] ?? $_GET['slug'] ?? $_GET['item_id'] ?? ''));
            if ($ref === '') {
                gawdee_json_response(['ok' => false, 'message' => 'Provide id or slug.'], 422);
            }
            $item = ctype_digit($ref) ? gawdee_item_by_id((int) $ref) : gawdee_item_by_slug($ref);
            if (!$item) {
                $withVariants = gawdee_item_with_variants($ref, false);
                if ($withVariants) {
                    $item = $withVariants;
                }
            }
            if (!$item) {
                gawdee_json_response(['ok' => false, 'message' => 'Item not found.'], 404);
            }
            if (empty($item['variants'])) {
                $item['variants'] = gawdee_variants_for_item((int) $item['id'], false);
            }
            // Attach full variant payloads.
            $full = items_api_public_item($item, false);
            $full['variants'] = array_map('items_api_public_variant', $item['variants']);
            $prices = array_map(static fn(array $v): int => (int) ($v['price'] ?? 0), $item['variants']);
            $full['price_min'] = $prices ? min($prices) : 0;
            $full['price_max'] = $prices ? max($prices) : 0;
            $full['total_stock'] = array_sum(array_map(static fn(array $v): int => (int) ($v['stock_quantity'] ?? 0), $item['variants']));
            gawdee_json_response(['ok' => true, 'item' => $full]);
        }
        if ($action === 'variants') {
            $itemRef = trim((string) ($_GET['item_id'] ?? $_GET['id'] ?? $_GET['slug'] ?? ''));
            if ($itemRef === '') {
                gawdee_json_response(['ok' => false, 'message' => 'Provide item_id.'], 422);
            }
            $item = ctype_digit($itemRef) ? gawdee_item_by_id((int) $itemRef) : gawdee_item_by_slug($itemRef);
            if (!$item) {
                gawdee_json_response(['ok' => false, 'message' => 'Item not found.'], 404);
            }
            $variants = gawdee_variants_for_item((int) $item['id'], false);
            gawdee_json_response(['ok' => true, 'item_id' => (int) $item['id'], 'variants' => array_map('items_api_public_variant', $variants), 'count' => count($variants)]);
        }
        if ($action === 'variant') {
            $ref = trim((string) ($_GET['id'] ?? $_GET['slug'] ?? $_GET['sku'] ?? ''));
            if ($ref === '') {
                gawdee_json_response(['ok' => false, 'message' => 'Provide variant id, slug or sku.'], 422);
            }
            $variant = gawdee_variant_by_ref($ref, false);
            if (!$variant) {
                gawdee_json_response(['ok' => false, 'message' => 'Variant not found.'], 404);
            }
            gawdee_json_response(['ok' => true, 'variant' => items_api_public_variant($variant)]);
        }
        if ($action === 'images') {
            $ref = trim((string) ($_GET['variant_id'] ?? $_GET['id'] ?? $_GET['slug'] ?? $_GET['sku'] ?? ''));
            if ($ref === '') {
                gawdee_json_response(['ok' => false, 'message' => 'Provide variant_id.'], 422);
            }
            $variant = gawdee_variant_by_ref($ref, false);
            if (!$variant) {
                gawdee_json_response(['ok' => false, 'message' => 'Variant not found.'], 404);
            }
            gawdee_json_response(['ok' => true, 'variant_id' => (int) $variant['id'], 'images' => items_api_public_variant($variant)['images']]);
        }
        gawdee_json_response(['ok' => false, 'message' => 'Unknown action. Use list, get, variants, variant or images.'], 404);
    }

    if ($method !== 'POST') {
        gawdee_json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
    }

    $payload = gawdee_request_json();
    if (!$payload) {
        $payload = $_POST;
    }
    gawdee_verify_csrf($payload['csrf_token'] ?? null);
    $admin = gawdee_admin();
    if (!$admin) {
        gawdee_json_response(['ok' => false, 'message' => 'Admin sign-in required.'], 401);
    }
    $action = trim((string) ($payload['action'] ?? ''));

    if ($action === 'create_item') {
        $item = is_array($payload['item'] ?? null) ? $payload['item'] : [];
        $variants = is_array($payload['variants'] ?? null) ? array_values($payload['variants']) : [];
        $id = gawdee_save_item_with_variants($item, $variants, null);
        gawdee_sync_item_mirrors($id);
        $created = gawdee_item_with_variants($id, true);
        gawdee_json_response(['ok' => true, 'message' => 'Item created.', 'item_id' => $id, 'item' => $created ? items_api_public_item(array_merge($created, ['variants' => []]), false) : null]);
    }
    if ($action === 'update_item') {
        $itemId = (int) ($payload['item_id'] ?? $payload['id'] ?? 0);
        if ($itemId <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid item_id is required.'], 422);
        }
        $item = is_array($payload['item'] ?? null) ? $payload['item'] : [];
        $variants = array_key_exists('variants', $payload) && is_array($payload['variants']) ? array_values($payload['variants']) : null;
        if ($variants === null) {
            gawdee_update_item($itemId, $item);
        } else {
            gawdee_save_item_with_variants($item, $variants, $itemId);
        }
        gawdee_sync_item_mirrors($itemId);
        gawdee_json_response(['ok' => true, 'message' => 'Item updated.', 'item_id' => $itemId]);
    }
    if ($action === 'delete_item') {
        $itemId = (int) ($payload['item_id'] ?? $payload['id'] ?? 0);
        if ($itemId <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid item_id is required.'], 422);
        }
        gawdee_delete_item($itemId);
        gawdee_json_response(['ok' => true, 'message' => 'Item and its variants deleted.', 'item_id' => $itemId]);
    }
    if ($action === 'create_variant') {
        $itemId = (int) ($payload['item_id'] ?? 0);
        $variant = is_array($payload['variant'] ?? null) ? $payload['variant'] : [];
        if ($itemId <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid item_id is required.'], 422);
        }
        $vid = gawdee_create_variant($itemId, $variant);
        $created = gawdee_variant_by_ref($vid, true);
        gawdee_json_response(['ok' => true, 'message' => 'Variant created.', 'variant_id' => $vid, 'variant' => $created ? items_api_public_variant($created) : null]);
    }
    if ($action === 'update_variant') {
        $vid = (int) ($payload['variant_id'] ?? $payload['id'] ?? 0);
        $variant = is_array($payload['variant'] ?? null) ? $payload['variant'] : $payload;
        unset($variant['action'], $variant['csrf_token'], $variant['variant_id'], $variant['id']);
        if ($vid <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid variant_id is required.'], 422);
        }
        gawdee_update_variant($vid, $variant);
        $updated = gawdee_variant_by_ref($vid, true);
        gawdee_json_response(['ok' => true, 'message' => 'Variant updated.', 'variant_id' => $vid, 'variant' => $updated ? items_api_public_variant($updated) : null]);
    }
    if ($action === 'delete_variant') {
        $vid = (int) ($payload['variant_id'] ?? $payload['id'] ?? 0);
        if ($vid <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid variant_id is required.'], 422);
        }
        gawdee_delete_variant($vid);
        gawdee_json_response(['ok' => true, 'message' => 'Variant deleted.', 'variant_id' => $vid]);
    }
    if ($action === 'create_image') {
        $variantId = (int) ($payload['variant_id'] ?? $payload['variantId'] ?? 0);
        if ($variantId <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid variant_id is required.'], 422);
        }
        $field = 'image_file';
        if (empty($_FILES[$field]['tmp_name']) && !empty($_FILES['image']['tmp_name'])) {
            $field = 'image';
        }
        try {
            $imageId = gawdee_save_variant_image_upload($variantId, $field, 'products');
        } catch (Throwable $uploadError) {
            gawdee_json_response(['ok' => false, 'message' => $uploadError->getMessage()], 422);
        }
        gawdee_json_response(['ok' => true, 'message' => 'Variant image added.', 'variant_id' => $variantId, 'image_id' => $imageId]);
    }
    if ($action === 'update_image') {
        $imageId = (int) ($payload['image_id'] ?? $payload['imageId'] ?? $payload['id'] ?? 0);
        if ($imageId <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid image_id is required.'], 422);
        }
        $fields = [];
        foreach (['sort_order' => 'sort_order', 'sortOrder' => 'sort_order', 'is_active' => 'is_active', 'isActive' => 'is_active'] as $inKey => $outKey) {
            if (array_key_exists($inKey, $payload)) {
                $fields[$outKey] = $payload[$inKey];
            }
        }
        if (!$fields) {
            gawdee_json_response(['ok' => false, 'message' => 'Nothing to update. Provide sort_order or is_active.'], 422);
        }
        gawdee_update_variant_image($imageId, $fields);
        gawdee_json_response(['ok' => true, 'message' => 'Variant image updated.', 'image_id' => $imageId]);
    }
    if ($action === 'delete_image') {
        $imageId = (int) ($payload['image_id'] ?? $payload['imageId'] ?? $payload['id'] ?? 0);
        if ($imageId <= 0) {
            gawdee_json_response(['ok' => false, 'message' => 'Valid image_id is required.'], 422);
        }
        gawdee_delete_variant_image($imageId, true);
        gawdee_json_response(['ok' => true, 'message' => 'Variant image deleted.', 'image_id' => $imageId]);
    }

    gawdee_json_response(['ok' => false, 'message' => 'Unknown action.'], 404);
} catch (Throwable $e) {
    $code = 422;
    $msg = $e->getMessage();
    if (str_contains($msg, 'sign-in') || str_contains($msg, 'session expired')) {
        $code = 401;
    }
    try {
        gawdee_json_response(['ok' => false, 'message' => $msg], $code);
    } catch (Throwable) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $msg]);
        exit;
    }
}
