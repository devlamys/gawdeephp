<?php

declare(strict_types=1);

$allItems = gawdee_items(true, true);
if (!$allItems && gawdee_items_count(true) === 0) {
    // Fallback to legacy catalogue grouping when migration has not run yet.
    $legacyProducts = gawdee_products(true);
    $groupedLegacy = [];
    foreach ($legacyProducts as $p) {
        $fKey = ($p['family_key'] ?? '') ?: ($p['id'] ?? '');
        $groupedLegacy[$fKey][] = $p;
    }
}
$categories = gawdee_categories(true);
if (!$categories) {
    $categories = [
        ['filter' => 'ghee', 'name' => 'Ghee'],
        ['filter' => 'honey', 'name' => 'Honey'],
        ['filter' => 'nutrition', 'name' => 'Mix Me'],
        ['filter' => 'sugar', 'name' => 'Sugar'],
        ['filter' => 'wellness', 'name' => 'Wellness'],
    ];
}

$editParam = trim((string) ($_GET['edit'] ?? ''));
$editItem = null;
if ($editParam !== '' && $editParam !== 'new') {
    if (ctype_digit($editParam)) {
        $editItem = gawdee_item_by_id((int) $editParam);
    }
    if (!$editItem) {
        $bySlug = gawdee_item_by_slug($editParam);
        if ($bySlug) {
            $editItem = $bySlug;
        }
    }
    if (!$editItem && function_exists('gawdee_variant_by_ref')) {
        $variant = gawdee_variant_by_ref($editParam, true);
        if ($variant) {
            $editItem = gawdee_item_by_id((int) ($variant['item_id'] ?? 0));
        }
    }
}

$editVariants = [];
if ($editItem) {
    $editVariants = gawdee_variants_for_item((int) $editItem['id'], true);
    foreach ($editVariants as &$ev) {
        $ev['gallery'] = ($ev['id'] ?? 0) > 0 ? gawdee_variant_images((int) $ev['id'], true) : [];
    }
    unset($ev);
    if (!$editVariants) {
        $editVariants = [[
            'id' => null, 'variant_name' => '', 'slug' => '', 'sku' => '',
            'stock_quantity' => 0, 'mrp' => 0, 'discount' => 0,
            'is_inclusive_tax' => 1, 'image' => '', 'is_active' => 1, 'gallery' => [],
        ]];
    }
} elseif ($editParam !== '') {
    // New item form.
    $editVariants = [[
        'id' => null, 'variant_name' => '500 g', 'slug' => '', 'sku' => '',
        'stock_quantity' => 50, 'mrp' => 0, 'discount' => 0,
        'is_inclusive_tax' => 1, 'image' => '', 'is_active' => 1, 'gallery' => [],
    ]];
}

$baseItem = $editItem ?? [
    'id' => null, 'slug' => '', 'name' => '', 'flavor' => '',
    'category' => '', 'category_key' => '', 'tag' => '',
    'accent' => '#073c2b', 'description' => '', 'image' => '',
    'hover_image' => '', 'customer_review' => '', 'is_active' => 1,
];
?>

<div class="admin-section-title">
    <div>
        <h2>Items &amp; Variants</h2>
        <p>One item (e.g. Gawdee Ghee) can have many variants (250g, 500g, 1kg) — each with its own SKU, stock, MRP, discount and tax flag.</p>
    </div>
    <a class="admin-button admin-button--primary" href="?view=products&edit=new"><i class="ph ph-plus"></i> Add new item</a>
</div>

<?php if ($editParam !== ''): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header">
        <div>
            <h2><?= $editItem ? 'Item Details #' . (int) $editItem['id'] . ' — ' . htmlspecialchars((string) $editItem['name']) : 'Create Item with Variants' ?></h2>
            <p>Product information is stored once on the item; pricing and stock live on each variant.</p>
        </div>
        <a href="?view=products" class="admin-action-icon" aria-label="Close editor"><i class="ph ph-x"></i></a>
    </div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form" data-item-form>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_item">
            <?php if ($editItem): ?>
                <input type="hidden" name="item_id" value="<?= (int) $editItem['id'] ?>">
            <?php endif; ?>

            <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #e1e7e2;">
                <h3 style="margin: 0 0 14px 0; font-size: 1rem; color: #073c2b;"><i class="ph ph-package"></i> Product Information</h3>
                <div class="form-grid form-grid--3">
                    <label>
                        <span>Name *</span>
                        <input name="name" required value="<?= htmlspecialchars((string) ($baseItem['name'] ?? '')) ?>" placeholder="e.g. A2 Gir Cow Ghee" maxlength="255">
                    </label>
                    <label>
                        <span>Flavor</span>
                        <input name="flavor" value="<?= htmlspecialchars((string) ($baseItem['flavor'] ?? '')) ?>" placeholder="e.g. Choco, Elaichi, Classic" maxlength="100">
                    </label>
                    <label>
                        <span>Item Slug</span>
                        <input name="slug" value="<?= htmlspecialchars((string) ($baseItem['slug'] ?? '')) ?>" placeholder="auto-generated if empty" maxlength="191">
                    </label>
                    <label>
                        <span>Category</span>
                        <select name="category_key" id="adminCategorySelect">
                            <option value="">— Select —</option>
                            <?php foreach ($categories as $cat):
                                $catKey = (string) ($cat['filter'] ?? $cat['category_key'] ?? $cat['slug'] ?? $cat['id'] ?? '');
                                $catName = (string) ($cat['name'] ?? $catKey);
                                if ($catKey === '' || $catKey === 'all') {
                                    continue;
                                }
                            ?>
                                <option value="<?= htmlspecialchars($catKey) ?>" <?= ((string) ($baseItem['category_key'] ?? '') === $catKey) ? 'selected' : '' ?> data-cat-name="<?= htmlspecialchars($catName) ?>">
                                    <?= htmlspecialchars($catName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="category" id="adminCategoryNameInput" value="<?= htmlspecialchars((string) ($baseItem['category'] ?? '')) ?>">
                    </label>
                    <label>
                        <span>Tag / Badge</span>
                        <input name="tag" value="<?= htmlspecialchars((string) ($baseItem['tag'] ?? '')) ?>" placeholder="e.g. Bilona method" maxlength="100">
                    </label>
                    <label>
                        <span>Accent Colour</span>
                        <input type="color" name="accent" value="<?= htmlspecialchars((string) ($baseItem['accent'] ?? '#073c2b') ?: '#073c2b') ?>" style="height: 42px; padding: 4px;">
                    </label>
                    <label class="form-span-2">
                        <span>Description</span>
                        <textarea name="description" rows="3" placeholder="Product story, sourcing and benefits..."><?= htmlspecialchars((string) ($baseItem['description'] ?? '')) ?></textarea>
                    </label>
                    <label class="form-span-3">
                        <span>Customer Review (featured quote shown on the product page)</span>
                        <textarea name="customer_review" rows="2" placeholder="e.g. The aroma opens beautifully in a hot tadka... — Rohan Mehta"><?= htmlspecialchars((string) ($baseItem['customer_review'] ?? '')) ?></textarea>
                    </label>
                    <div>
                        <span style="display:block;font-size:.68rem;font-weight:700;margin-bottom:6px;">Product Image *</span>
                        <?php if (!empty($baseItem['image'])): ?>
                            <img src="../<?= htmlspecialchars((string) $baseItem['image']) ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:10px;border:1px solid #e2e8e3;background:#f6f8f6;margin-bottom:8px;">
                            <small class="help-text" style="display:block;margin-bottom:6px;"><?= htmlspecialchars((string) $baseItem['image']) ?></small>
                        <?php endif; ?>
                        <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars((string) ($baseItem['image'] ?? '')) ?>">
                    </div>
                    <div>
                        <span style="display:block;font-size:.68rem;font-weight:700;margin-bottom:6px;">Hover Image (revealed on card hover)</span>
                        <?php if (!empty($baseItem['hover_image'])): ?>
                            <img src="../<?= htmlspecialchars((string) $baseItem['hover_image']) ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:10px;border:1px solid #e2e8e3;background:#f6f8f6;margin-bottom:8px;">
                            <small class="help-text" style="display:block;margin-bottom:6px;"><?= htmlspecialchars((string) $baseItem['hover_image']) ?></small>
                        <?php endif; ?>
                        <input type="file" name="hover_image_file" accept="image/jpeg,image/png,image/webp">
                        <input type="hidden" name="existing_hover_image" value="<?= htmlspecialchars((string) ($baseItem['hover_image'] ?? '')) ?>">
                    </div>
                    <label class="form-switch">
                        <input type="checkbox" name="is_active" <?= (!isset($baseItem['is_active']) || !empty($baseItem['is_active'])) ? 'checked' : '' ?>>
                        <span>Visible on storefront</span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap:12px; margin-bottom: 16px; flex-wrap:wrap;">
                    <div>
                        <h3 style="margin: 0; font-size: 1rem; color: #073c2b;"><i class="ph ph-sliders-horizontal"></i> Variants</h3>
                        <p class="help-text" style="margin: 2px 0 0 0;">Each variant needs a name, unique SKU, numeric stock, numeric MRP and discount %. Selling price is computed as MRP − discount. Tax-inclusive defaults to yes.</p>
                    </div>
                    <button type="button" class="admin-button admin-button--secondary" id="addVariantRowBtn">
                        <i class="ph ph-plus-circle"></i> Add Variant
                    </button>
                </div>

                <div id="variantRepeaterContainer" style="display: grid; gap: 16px;">
                    <?php foreach ($editVariants as $idx => $v): ?>
                        <?php
                        $vId = isset($v['id']) && $v['id'] !== null && $v['id'] !== '' ? (int) $v['id'] : null;
                        $vMrp = (int) ($v['mrp'] ?? 0);
                        $vDiscount = (float) ($v['discount'] ?? 0);
                        $vPrice = function_exists('gawdee_variant_price') ? gawdee_variant_price($vMrp, $vDiscount) : $vMrp;
                        ?>
                        <div class="admin-card variant-repeater-row" data-variant-row style="padding: 16px; background: #fafcfb; border: 1px solid #dce4de; border-radius: 14px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <strong style="font-size: 0.82rem; color: #073c2b;"><i class="ph ph-tag"></i> <span data-variant-title>Variant #<?= $idx + 1 ?></span></strong>
                                <span style="display:flex;gap:8px;align-items:center;">
                                    <span class="help-text" data-variant-price-preview>₹<?= number_format($vPrice) ?> selling</span>
                                    <button type="button" class="admin-action-icon admin-action-icon--danger remove-variant-btn" title="Remove variant before saving"><i class="ph ph-trash"></i></button>
                                </span>
                            </div>

                            <input type="hidden" name="variants[<?= $idx ?>][id]" value="<?= $vId ? (int) $vId : '' ?>">
                            <input type="hidden" name="variants[<?= $idx ?>][existing_image]" value="<?= htmlspecialchars((string) ($v['image'] ?? '')) ?>">

                            <div class="form-grid form-grid--3" style="gap: 12px;">
                                <label>
                                    <span>Variant Name *</span>
                                    <input name="variants[<?= $idx ?>][variant_name]" required value="<?= htmlspecialchars((string) ($v['variant_name'] ?? '')) ?>" placeholder="e.g. 250g, 500ml, 1kg" maxlength="100" data-variant-name>
                                </label>
                                <label>
                                    <span>SKU *</span>
                                    <input name="variants[<?= $idx ?>][sku]" required value="<?= htmlspecialchars((string) ($v['sku'] ?? '')) ?>" placeholder="e.g. GWD-GHEE-500" maxlength="99">
                                </label>
                                <label>
                                    <span>Variant Slug</span>
                                    <input name="variants[<?= $idx ?>][slug]" value="<?= htmlspecialchars((string) ($v['slug'] ?? '')) ?>" placeholder="auto-generated if empty" maxlength="191">
                                </label>
                                <label>
                                    <span>Stock Quantity *</span>
                                    <input type="number" min="0" max="1000000" step="1" name="variants[<?= $idx ?>][stock_quantity]" required value="<?= (int) ($v['stock_quantity'] ?? 0) ?>">
                                </label>
                                <label>
                                    <span>MRP (₹) *</span>
                                    <input type="number" min="0" max="10000000" step="1" name="variants[<?= $idx ?>][mrp]" required value="<?= (int) ($v['mrp'] ?? 0) ?>" data-variant-mrp>
                                </label>
                                <label>
                                    <span>Discount (%) *</span>
                                    <input type="number" min="0" max="90" step="0.01" name="variants[<?= $idx ?>][discount]" required value="<?= htmlspecialchars((string) ($v['discount'] ?? 0)) ?>" data-variant-discount>
                                </label>
                                <label class="form-switch">
                                    <input type="checkbox" name="variants[<?= $idx ?>][is_inclusive_tax]" value="1" <?= (!isset($v['is_inclusive_tax']) || !empty($v['is_inclusive_tax'])) ? 'checked' : '' ?>>
                                    <span>Inclusive of all taxes</span>
                                </label>
                                <label class="form-switch">
                                    <input type="checkbox" name="variants[<?= $idx ?>][is_active]" value="1" <?= (!isset($v['is_active']) || !empty($v['is_active'])) ? 'checked' : '' ?>>
                                    <span>Visible on storefront</span>
                                </label>
                            </div>

                            <div class="variant-images" data-image-list style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cfd8d1;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
                                    <strong style="font-size:.78rem;color:#073c2b;"><i class="ph ph-images"></i> Images <span class="help-text" data-image-count><?= count($v['gallery'] ?? []) ?> saved</span></strong>
                                    <button type="button" class="admin-button admin-button--secondary add-image-btn" style="padding:8px 14px;font-size:.72rem;"><i class="ph ph-plus-circle"></i> Add Image</button>
                                </div>
                                <p class="help-text" style="margin:0 0 10px;">First image is the primary display image. Use ↑ ↓ to reorder; removing a row deletes the image on save.</p>
                                <div data-image-rows style="display:grid;gap:10px;">
                                    <?php foreach (($v['gallery'] ?? []) as $imgIdx => $gImg): ?>
                                        <div class="variant-image-row" data-image-row style="display:flex;gap:10px;align-items:center;padding:10px;border:1px solid #e2e9e4;border-radius:12px;background:#fff;">
                                            <img src="../<?= htmlspecialchars((string) ($gImg['image'] ?? '')) ?>" alt="" loading="lazy" data-image-preview style="width:56px;height:56px;object-fit:cover;border-radius:10px;border:1px solid #e2e8e3;background:#f3f6f3;flex-shrink:0;">
                                            <input type="hidden" name="images[<?= $idx ?>][<?= $imgIdx ?>][id]" value="<?= (int) ($gImg['id'] ?? 0) ?>">
                                            <div style="flex:1;min-width:0;">
                                                <small class="help-text" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars((string) ($gImg['image'] ?? '')) ?></small>
                                                <label style="display:flex;gap:8px;align-items:center;margin-top:6px;font-size:.68rem;font-weight:700;color:#4e5e55;">Replace <input type="file" name="variant_images_<?= $idx ?>_<?= $imgIdx ?>" data-image-file accept="image/jpeg,image/png,image/webp,image/gif" style="font-weight:400;"></label>
                                            </div>
                                            <span style="display:flex;gap:4px;">
                                                <button type="button" class="admin-action-icon image-up-btn" title="Move up" aria-label="Move image up"><i class="ph ph-arrow-up"></i></button>
                                                <button type="button" class="admin-action-icon image-down-btn" title="Move down" aria-label="Move image down"><i class="ph ph-arrow-down"></i></button>
                                                <button type="button" class="admin-action-icon admin-action-icon--danger remove-image-btn" title="Remove image" aria-label="Remove image"><i class="ph ph-trash"></i></button>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-submit-row" style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="admin-button admin-button--primary" style="padding: 12px 24px; font-size: 0.85rem;">
                    <?= $editItem ? 'Update Item & Variants' : 'Create Item & Variants' ?> <i class="ph ph-check"></i>
                </button>
                <a href="?view=products" class="admin-button admin-button--ghost">Cancel</a>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<section class="admin-card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Variants</th>
                    <th>Price Range</th>
                    <th>Total Stock</th>
                    <th>Visibility</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($allItems): ?>
                    <?php foreach ($allItems as $item):
                        $variants = $item['variants'] ?? [];
                        $prices = array_map(static fn($vv): int => (int) ($vv['price'] ?? 0), $variants);
                        $minPrice = $prices ? min($prices) : 0;
                        $maxPrice = $prices ? max($prices) : 0;
                        $priceLabel = ($minPrice === $maxPrice) ? '₹' . number_format($minPrice) : '₹' . number_format($minPrice) . ' – ₹' . number_format($maxPrice);
                        $totalStock = array_sum(array_map(static fn($vv): int => (int) ($vv['stock_quantity'] ?? 0), $variants));
                        $activeCount = count(array_filter($variants, static fn($vv) => !empty($vv['is_active'])));
                    ?>
                        <tr>
                            <td>
                                <div class="admin-table__product">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../<?= htmlspecialchars((string) $item['image']) ?>" alt="" loading="lazy">
                                    <?php else: ?>
                                        <span style="width:44px;height:44px;border-radius:10px;background:#eef3ee;display:grid;place-items:center;"><i class="ph ph-package"></i></span>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars((string) $item['name']) ?><?= !empty($item['flavor']) ? ' — ' . htmlspecialchars((string) $item['flavor']) : '' ?></strong>
                                        <span>#<?= (int) $item['id'] ?> · <?= htmlspecialchars((string) ($item['tag'] ?: 'Gawdee Essential')) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars((string) ($item['category'] ?: $item['category_key'])) ?></td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 320px;">
                                    <?php foreach ($variants as $vItem): ?>
                                        <span title="SKU <?= htmlspecialchars((string) $vItem['sku']) ?> · MRP ₹<?= number_format((int) $vItem['mrp']) ?> · <?= (float) $vItem['discount'] ?>% off · <?= !empty($vItem['is_inclusive_tax']) ? 'incl. tax' : 'excl. tax' ?>" style="padding: 3px 8px; border-radius: 8px; background: <?= !empty($vItem['is_active']) ? '#e8f4ed' : '#f1f1f1' ?>; color: <?= !empty($vItem['is_active']) ? '#087345' : '#888' ?>; font-size: 0.62rem; font-weight: 700;">
                                            <?= htmlspecialchars((string) $vItem['variant_name']) ?> (₹<?= number_format((int) $vItem['price']) ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><strong><?= $priceLabel ?></strong></td>
                            <td><?= $totalStock ?> units</td>
                            <td>
                                <span class="status-pill <?= ($activeCount > 0 && !empty($item['is_active'])) ? '' : 'status-pill--draft' ?>">
                                    <?= (!empty($item['is_active']) ? 'Active' : 'Hidden') . ' (' . $activeCount . '/' . count($variants) . ')' ?>
                                </span>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <a class="admin-action-icon" href="?view=products&edit=<?= (int) $item['id'] ?>" title="View / edit item & variants">
                                        <i class="ph ph-pencil-simple"></i>
                                    </a>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle_item">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="admin-action-icon" type="submit" title="Toggle visibility">
                                            <i class="ph <?= !empty($item['is_active']) ? 'ph-eye-slash' : 'ph-eye' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Delete this item and all its <?= count($variants) ?> variant(s)? This cannot be undone.')" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete_item">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="admin-action-icon admin-action-icon--danger" type="submit" title="Delete item">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php elseif (!empty($groupedLegacy)): ?>
                    <?php foreach ($groupedLegacy as $fKey => $items):
                        $mainItem = $items[0];
                    ?>
                        <tr>
                            <td colspan="7" style="padding:12px;">Legacy catalogue group <strong><?= htmlspecialchars((string) $mainItem['name']) ?></strong> — open <a href="?view=products&edit=<?= rawurlencode((string) $mainItem['id']) ?>">edit</a> to migrate it into the new Item/Variant structure.</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="padding:24px;text-align:center;">No items yet. Click “Add new item”.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const catSelect = document.getElementById('adminCategorySelect');
    const catInput = document.getElementById('adminCategoryNameInput');
    const syncCat = () => {
        if (!catSelect || !catInput) return;
        const opt = catSelect.options[catSelect.selectedIndex];
        if (opt) catInput.value = opt.dataset.catName || opt.text;
    };
    catSelect?.addEventListener('change', syncCat);

    const repeater = document.getElementById('variantRepeaterContainer');
    const addBtn = document.getElementById('addVariantRowBtn');
    let variantCount = repeater ? repeater.querySelectorAll('[data-variant-row]').length : 0;

    const moneyFmt = (n) => '₹' + Number(n || 0).toLocaleString('en-IN', {maximumFractionDigits: 0});
    const refreshPreview = (row) => {
        const mrp = parseFloat(row.querySelector('[data-variant-mrp]')?.value || '0');
        const disc = parseFloat(row.querySelector('[data-variant-discount]')?.value || '0');
        const price = Math.max(0, Math.round(mrp * (1 - Math.min(90, Math.max(0, disc)) / 100)));
        const prev = row.querySelector('[data-variant-price-preview]');
        if (prev) prev.textContent = moneyFmt(price) + ' selling';
    };

    repeater?.addEventListener('input', (e) => {
        const row = e.target.closest('[data-variant-row]');
        if (row) refreshPreview(row);
    });
    repeater?.querySelectorAll('[data-variant-row]').forEach(refreshPreview);

    const renumberImages = (variantRow, i) => {
        variantRow.querySelectorAll('[data-image-row]').forEach((imgRow, k) => {
            imgRow.querySelectorAll('input[type="hidden"]').forEach((inp) => {
                const name = inp.getAttribute('name');
                if (!name) return;
                inp.setAttribute('name', name.replace(/images\[\d+\]\[\d+\]/, `images[${i}][${k}]`));
            });
            const file = imgRow.querySelector('input[type="file"][data-image-file]');
            if (file) file.setAttribute('name', `variant_images_${i}_${k}`);
        });
        const counter = variantRow.querySelector('[data-image-count]');
        if (counter) counter.textContent = variantRow.querySelectorAll('[data-image-row]').length + ' saved';
    };

    const renumber = () => {
        repeater?.querySelectorAll('[data-variant-row]').forEach((row, i) => {
            const title = row.querySelector('[data-variant-title]');
            if (title) title.textContent = 'Variant #' + (i + 1);
            row.querySelectorAll('input[type="hidden"], input:not([type="file"]):not([type="checkbox"])').forEach((inp) => {
                const name = inp.getAttribute('name');
                if (!name) return;
                inp.setAttribute('name', name.replace(/variants\[\d+\]/, 'variants[' + i + ']'));
            });
            row.querySelectorAll('input[type="checkbox"]').forEach((inp) => {
                const name = inp.getAttribute('name');
                if (!name) return;
                inp.setAttribute('name', name.replace(/variants\[\d+\]/, 'variants[' + i + ']'));
            });
            renumberImages(row, i);
        });
        variantCount = repeater ? repeater.querySelectorAll('[data-variant-row]').length : 0;
    };
    repeater?.querySelectorAll('[data-variant-row]').forEach((row, i) => renumberImages(row, i));

    addBtn?.addEventListener('click', () => {
        if (!repeater) return;
        const index = variantCount;
        variantCount++;
        const row = document.createElement('div');
        row.className = 'admin-card variant-repeater-row';
        row.setAttribute('data-variant-row', '');
        row.style.cssText = 'padding: 16px; background: #fafcfb; border: 1px solid #dce4de; border-radius: 14px;';
        row.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <strong style="font-size: 0.82rem; color: #073c2b;"><i class="ph ph-tag"></i> <span data-variant-title>Variant #${index + 1}</span></strong>
                <span style="display:flex;gap:8px;align-items:center;">
                    <span class="help-text" data-variant-price-preview>₹0 selling</span>
                    <button type="button" class="admin-action-icon admin-action-icon--danger remove-variant-btn" title="Remove variant before saving"><i class="ph ph-trash"></i></button>
                </span>
            </div>
            <input type="hidden" name="variants[${index}][id]" value="">
            <input type="hidden" name="variants[${index}][existing_image]" value="">
            <div class="form-grid form-grid--3" style="gap: 12px;">
                <label><span>Variant Name *</span><input name="variants[${index}][variant_name]" required placeholder="e.g. 250g, 500ml, 1kg" maxlength="100" data-variant-name></label>
                <label><span>SKU *</span><input name="variants[${index}][sku]" required placeholder="e.g. GWD-ITEM-250" maxlength="99"></label>
                <label><span>Variant Slug</span><input name="variants[${index}][slug]" placeholder="auto-generated if empty" maxlength="191"></label>
                <label><span>Stock Quantity *</span><input type="number" min="0" max="1000000" step="1" name="variants[${index}][stock_quantity]" required value="50"></label>
                <label><span>MRP (₹) *</span><input type="number" min="0" max="10000000" step="1" name="variants[${index}][mrp]" required value="0" data-variant-mrp></label>
                <label><span>Discount (%) *</span><input type="number" min="0" max="90" step="0.01" name="variants[${index}][discount]" required value="0" data-variant-discount></label>
                <label class="form-switch"><input type="checkbox" name="variants[${index}][is_inclusive_tax]" value="1" checked><span>Inclusive of all taxes</span></label>
                <label class="form-switch"><input type="checkbox" name="variants[${index}][is_active]" value="1" checked><span>Visible on storefront</span></label>
            </div>
            <div class="variant-images" data-image-list style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #cfd8d1;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px;flex-wrap:wrap;">
                    <strong style="font-size:.78rem;color:#073c2b;"><i class="ph ph-images"></i> Images <span class="help-text" data-image-count>0 saved</span></strong>
                    <button type="button" class="admin-button admin-button--secondary add-image-btn" style="padding:8px 14px;font-size:.72rem;"><i class="ph ph-plus-circle"></i> Add Image</button>
                </div>
                <p class="help-text" style="margin:0 0 10px;">First image is the primary display image. Use ↑ ↓ to reorder; removing a row deletes the image on save.</p>
                <div data-image-rows style="display:grid;gap:10px;"></div>
            </div>`;
        repeater.appendChild(row);
        renumber();
    });

    const newImageRow = (i, k) => {
        const row = document.createElement('div');
        row.className = 'variant-image-row';
        row.setAttribute('data-image-row', '');
        row.style.cssText = 'display:flex;gap:10px;align-items:center;padding:10px;border:1px solid #e2e9e4;border-radius:12px;background:#fff;';
        row.innerHTML = `
            <img src="" alt="New image preview" data-image-preview style="width:56px;height:56px;object-fit:cover;border-radius:10px;border:1px dashed #b9c6bd;background:#f3f6f3;flex-shrink:0;" hidden>
            <input type="hidden" name="images[${i}][${k}][id]" value="">
            <div style="flex:1;min-width:0;">
                <label style="display:flex;gap:8px;align-items:center;font-size:.72rem;font-weight:700;color:#4e5e55;">Upload <input type="file" name="variant_images_${i}_${k}" data-image-file accept="image/jpeg,image/png,image/webp,image/gif" style="font-weight:400;" required></label>
            </div>
            <span style="display:flex;gap:4px;">
                <button type="button" class="admin-action-icon image-up-btn" title="Move up" aria-label="Move image up"><i class="ph ph-arrow-up"></i></button>
                <button type="button" class="admin-action-icon image-down-btn" title="Move down" aria-label="Move image down"><i class="ph ph-arrow-down"></i></button>
                <button type="button" class="admin-action-icon admin-action-icon--danger remove-image-btn" title="Remove image" aria-label="Remove image"><i class="ph ph-trash"></i></button>
            </span>`;
        return row;
    };

    document.addEventListener('click', (e) => {
        const addImg = e.target.closest('.add-image-btn');
        if (addImg) {
            const variantRow = addImg.closest('[data-variant-row]');
            const list = variantRow?.querySelector('[data-image-rows]');
            if (!variantRow || !list) return;
            const i = [...repeater.querySelectorAll('[data-variant-row]')].indexOf(variantRow);
            const k = list.querySelectorAll('[data-image-row]').length;
            list.appendChild(newImageRow(Math.max(0, i), k));
            renumberImages(variantRow, Math.max(0, i));
            list.lastElementChild?.querySelector('input[type="file"]')?.focus();
            return;
        }
        const rmImg = e.target.closest('.remove-image-btn');
        if (rmImg) {
            const variantRow = rmImg.closest('[data-variant-row]');
            rmImg.closest('[data-image-row]')?.remove();
            if (variantRow) {
                const i = [...repeater.querySelectorAll('[data-variant-row]')].indexOf(variantRow);
                renumberImages(variantRow, Math.max(0, i));
            }
            return;
        }
        const moveBtn = e.target.closest('.image-up-btn, .image-down-btn');
        if (moveBtn) {
            const variantRow = moveBtn.closest('[data-variant-row]');
            const imgRow = moveBtn.closest('[data-image-row]');
            if (!variantRow || !imgRow) return;
            const isUp = moveBtn.classList.contains('image-up-btn');
            const sibling = isUp ? imgRow.previousElementSibling : imgRow.nextElementSibling;
            if (sibling) {
                if (isUp) sibling.before(imgRow);
                else sibling.after(imgRow);
                const i = [...repeater.querySelectorAll('[data-variant-row]')].indexOf(variantRow);
                renumberImages(variantRow, Math.max(0, i));
            }
            return;
        }
    });

    // Instant preview for freshly chosen files.
    document.addEventListener('change', (e) => {
        const file = e.target.closest('input[type="file"][data-image-file]');
        if (!file || !file.files || !file.files[0]) return;
        const imgRow = file.closest('[data-image-row]');
        const preview = imgRow?.querySelector('[data-image-preview]');
        if (!preview) return;
        const reader = new FileReader();
        reader.onload = () => {
            preview.src = reader.result;
            preview.hidden = false;
        };
        reader.readAsDataURL(file.files[0]);
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-variant-btn');
        if (!btn || !repeater) return;
        const rows = repeater.querySelectorAll('[data-variant-row]');
        if (rows.length <= 1) {
            alert('An item must keep at least one variant.');
            return;
        }
        btn.closest('[data-variant-row]')?.remove();
        renumber();
    });

    // Guard against invalid discount entry before submit.
    document.querySelector('[data-item-form]')?.addEventListener('submit', (e) => {
        let ok = true;
        repeater?.querySelectorAll('[data-variant-row]').forEach((row) => {
            const mrp = parseFloat(row.querySelector('[data-variant-mrp]')?.value || '0');
            const disc = parseFloat(row.querySelector('[data-variant-discount]')?.value || '0');
            if (!(mrp >= 0) || !(disc >= 0 && disc <= 90)) ok = false;
        });
        if (!ok) {
            e.preventDefault();
            alert('Check variant MRP (≥ 0) and discount (0–90%).');
        }
    });
});
</script>
