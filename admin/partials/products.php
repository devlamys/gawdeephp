<?php

declare(strict_types=1);

$allProducts = gawdee_products(true);
$categories = gawdee_categories();
$editId = (string) ($_GET['edit'] ?? '');

$editProduct = null;
if ($editId !== '') {
    foreach ($allProducts as $candidate) {
        if ($candidate['id'] === $editId) {
            $editProduct = $candidate;
            break;
        }
    }
}

// Find family variants if editing an existing product
$familyVariants = [];
if ($editProduct) {
    $targetFamily = $editProduct['family_key'] ?? '';
    if ($targetFamily !== '') {
        $familyVariants = array_values(array_filter(
            $allProducts,
            static fn(array $p): bool => ($p['family_key'] ?? '') === $targetFamily
        ));
    }
    if (empty($familyVariants)) {
        $familyVariants = [$editProduct];
    }
} else {
    // Default initial variant row for new product form
    $familyVariants = [
        [
            'id' => '',
            'slug' => '',
            'full_name' => '',
            'weight' => '500 ml',
            'price' => 590,
            'original_price' => 690,
            'stock' => 50,
            'image' => '',
            'is_active' => 1,
        ]
    ];
}

$baseProduct = $editProduct ?? [
    'id' => '',
    'name' => '',
    'category' => 'A2 Ghee',
    'category_key' => 'ghee',
    'tag' => '100% Pure Organic',
    'accent' => '#073c2b',
    'description' => 'Pure traditional food made with care for modern families.',
];
?>

<div class="admin-section-title">
    <div>
        <h2>Product Catalogue</h2>
        <p>Manage single products, weight &amp; price variants, image uploads, and storefront visibility.</p>
    </div>
    <a class="admin-button admin-button--primary" href="?view=products&edit=new"><i class="ph ph-plus"></i> Add new product</a>
</div>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header">
        <div>
            <h2><?= $editProduct ? 'Edit Product & Variants' : 'Create Product with Variant Sizes' ?></h2>
            <p>Fill product details and add multiple pack sizes/weights (Price, MRP, Stock, Image upload).</p>
        </div>
        <a href="?view=products" class="admin-action-icon"><i class="ph ph-x"></i></a>
    </div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_product">

            <div style="margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #e1e7e2;">
                <h3 style="margin: 0 0 14px 0; font-size: 1rem; color: #073c2b;"><i class="ph ph-package"></i> Base Product Information</h3>
                <div class="form-grid form-grid--3">
                    <label>
                        <span>Product Base Name</span>
                        <input name="name" required value="<?= htmlspecialchars($baseProduct['name']) ?>" placeholder="e.g. Gir Cow A2 Ghee">
                    </label>

                    <label>
                        <span>Category</span>
                        <select name="category_key" id="adminCategorySelect">
                            <?php foreach ($categories as $cat): 
                                $catKey = (string) ($cat['slug'] ?? $cat['key'] ?? $cat['id'] ?? '');
                                $catName = (string) ($cat['name'] ?? '');
                            ?>
                                <option value="<?= htmlspecialchars($catKey) ?>" <?= ($baseProduct['category_key'] ?? '') === $catKey ? 'selected' : '' ?> data-cat-name="<?= htmlspecialchars($catName) ?>">
                                    <?= htmlspecialchars($catName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="category" id="adminCategoryNameInput" value="<?= htmlspecialchars((string) ($baseProduct['category'] ?? '')) ?>">
                    </label>

                    <label>
                        <span>Tag / Badge</span>
                        <input name="tag" value="<?= htmlspecialchars($baseProduct['tag']) ?>" placeholder="e.g. 100% Pure Organic">
                    </label>

                    <label>
                        <span>Accent Colour</span>
                        <input type="color" name="accent" value="<?= htmlspecialchars($baseProduct['accent'] ?: '#073c2b') ?>" style="height: 42px; padding: 4px;">
                    </label>

                    <label class="form-span-2">
                        <span>Description</span>
                        <textarea name="description" placeholder="Product story and benefits..."><?= htmlspecialchars($baseProduct['description']) ?></textarea>
                    </label>
                </div>
            </div>

            <!-- Dynamic Variant Repeater Section -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0; font-size: 1rem; color: #073c2b;"><i class="ph ph-sliders-horizontal"></i> Product Pack Sizes &amp; Variants</h3>
                        <p class="help-text" style="margin: 2px 0 0 0;">Add multiple weight/volume variants (e.g. 250g, 500 ml, 1 Litre) with individual price &amp; image uploads.</p>
                    </div>
                    <button type="button" class="admin-button admin-button--secondary" id="addVariantRowBtn">
                        <i class="ph ph-plus-circle"></i> Add Variant Size
                    </button>
                </div>

                <div id="variantRepeaterContainer" style="display: grid; gap: 16px;">
                    <?php foreach ($familyVariants as $idx => $v): ?>
                        <div class="admin-card variant-repeater-row" style="padding: 16px; background: #fafcfb; border: 1px solid #dce4de; border-radius: 14px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <strong style="font-size: 0.82rem; color: #073c2b;"><i class="ph ph-tag"></i> Variant #<?= $idx + 1 ?></strong>
                                <?php if (count($familyVariants) > 1): ?>
                                    <button type="button" class="admin-action-icon admin-action-icon--danger remove-variant-btn" title="Remove size"><i class="ph ph-trash"></i></button>
                                <?php endif; ?>
                            </div>

                            <input type="hidden" name="variants[<?= $idx ?>][id]" value="<?= htmlspecialchars($v['id'] ?? '') ?>">
                            <input type="hidden" name="variants[<?= $idx ?>][existing_image]" value="<?= htmlspecialchars($v['image'] ?? '') ?>">

                            <div class="form-grid form-grid--3" style="gap: 12px;">
                                <label>
                                    <span>Weight / Volume Size</span>
                                    <input name="variants[<?= $idx ?>][weight]" required value="<?= htmlspecialchars($v['weight'] ?? '') ?>" placeholder="e.g. 500 ml or 1 Litre">
                                </label>

                                <label>
                                    <span>Full Product Name</span>
                                    <input name="variants[<?= $idx ?>][full_name]" value="<?= htmlspecialchars($v['full_name'] ?? '') ?>" placeholder="e.g. Gawdee Gir Cow A2 Ghee 500 ml">
                                </label>

                                <label>
                                    <span>Product ID / Slug</span>
                                    <input name="variants[<?= $idx ?>][slug]" value="<?= htmlspecialchars($v['slug'] ?? '') ?>" placeholder="auto-generated if empty">
                                </label>

                                <label>
                                    <span>Selling Price (₹)</span>
                                    <input type="number" min="0" name="variants[<?= $idx ?>][price]" required value="<?= (int) ($v['price'] ?? 0) ?>">
                                </label>

                                <label>
                                    <span>MRP / Original Price (₹)</span>
                                    <input type="number" min="0" name="variants[<?= $idx ?>][original_price]" required value="<?= (int) ($v['original_price'] ?? 0) ?>">
                                </label>

                                <label>
                                    <span>Stock Quantity</span>
                                    <input type="number" min="0" name="variants[<?= $idx ?>][stock]" value="<?= (int) ($v['stock'] ?? 50) ?>">
                                </label>

                                <label class="form-span-2">
                                    <span>Upload Image File</span>
                                    <input type="file" name="variant_image_<?= $idx ?>" accept="image/jpeg,image/png,image/webp">
                                    <small class="help-text"><?= htmlspecialchars($v['image'] ?? 'No image uploaded yet') ?></small>
                                </label>

                                <label class="form-switch">
                                    <input type="checkbox" name="variants[<?= $idx ?>][is_active]" <?= (!isset($v['is_active']) || $v['is_active']) ? 'checked' : '' ?>>
                                    <span>Visible on Storefront</span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-submit-row">
                <button type="submit" class="admin-button admin-button--primary" style="padding: 12px 24px; font-size: 0.85rem;">
                    Save Product &amp; Variants <i class="ph ph-check"></i>
                </button>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<!-- Products Table -->
<section class="admin-card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Pack Sizes / Variants</th>
                    <th>Price Range</th>
                    <th>Total Stock</th>
                    <th>Visibility</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Group products by family for clean admin overview
                $groupedCatalogue = [];
                foreach ($allProducts as $p) {
                    $fKey = ($p['family_key'] ?? '') ?: ($p['id'] ?? '');
                    $groupedCatalogue[$fKey][] = $p;
                }

                foreach ($groupedCatalogue as $fKey => $items):
                    $mainItem = $items[0];
                    $prices = array_column($items, 'price');
                    $minPrice = min($prices);
                    $maxPrice = max($prices);
                    $priceLabel = ($minPrice === $maxPrice) ? '₹' . number_format($minPrice) : '₹' . number_format($minPrice) . ' – ₹' . number_format($maxPrice);
                    $totalStock = array_sum(array_column($items, 'stock'));
                    $isAnyActive = array_filter($items, static fn($i) => !empty($i['is_active']));
                ?>
                    <tr>
                        <td>
                            <div class="admin-table__product">
                                <img src="../<?= htmlspecialchars($mainItem['image']) ?>" alt="">
                                <div>
                                    <strong><?= htmlspecialchars($mainItem['name']) ?></strong>
                                    <span><?= htmlspecialchars($mainItem['tag'] ?: 'Gawdee Essential') ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($mainItem['category']) ?></td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                <?php foreach ($items as $vItem): ?>
                                    <span style="padding: 3px 8px; border-radius: 8px; background: #e8f4ed; color: #087345; font-size: 0.62rem; font-weight: 700;">
                                        <?= htmlspecialchars($vItem['weight']) ?> (₹<?= number_format((int)$vItem['price']) ?>)
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td><strong><?= $priceLabel ?></strong></td>
                        <td><?= $totalStock ?> units</td>
                        <td>
                            <span class="status-pill <?= $isAnyActive ? '' : 'status-pill--draft' ?>">
                                <?= $isAnyActive ? 'Active (' . count($isAnyActive) . ')' : 'Hidden' ?>
                            </span>
                        </td>
                        <td>
                            <div class="admin-actions">
                                <a class="admin-action-icon" href="?view=products&edit=<?= rawurlencode($mainItem['id']) ?>" title="Edit product & variants">
                                    <i class="ph ph-pencil-simple"></i>
                                </a>

                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="toggle_product">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($mainItem['id']) ?>">
                                    <button class="admin-action-icon" type="submit" title="Toggle visibility">
                                        <i class="ph <?= $mainItem['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i>
                                    </button>
                                </form>

                                <form method="post" onsubmit="return confirm('Delete this product and all its size variants?')" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($mainItem['id']) ?>">
                                    <button class="admin-action-icon admin-action-icon--danger" type="submit" title="Delete product">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const catSelect = document.getElementById('adminCategorySelect');
    const catInput = document.getElementById('adminCategoryNameInput');

    catSelect?.addEventListener('change', () => {
        const selectedOpt = catSelect.options[catSelect.selectedIndex];
        if (selectedOpt && catInput) {
            catInput.value = selectedOpt.dataset.catName || selectedOpt.text;
        }
    });

    const repeaterContainer = document.getElementById('variantRepeaterContainer');
    const addBtn = document.getElementById('addVariantRowBtn');

    let variantCount = repeaterContainer ? repeaterContainer.children.length : 1;

    addBtn?.addEventListener('click', () => {
        if (!repeaterContainer) return;
        const index = variantCount;
        variantCount++;

        const newRow = document.createElement('div');
        newRow.className = 'admin-card variant-repeater-row';
        newRow.style.cssText = 'padding: 16px; background: #fafcfb; border: 1px solid #dce4de; border-radius: 14px;';

        newRow.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                <strong style="font-size: 0.82rem; color: #073c2b;"><i class="ph ph-tag"></i> Variant #${variantCount}</strong>
                <button type="button" class="admin-action-icon admin-action-icon--danger remove-variant-btn" title="Remove size"><i class="ph ph-trash"></i></button>
            </div>

            <input type="hidden" name="variants[${index}][id]" value="">
            <input type="hidden" name="variants[${index}][existing_image]" value="">

            <div class="form-grid form-grid--3" style="gap: 12px;">
                <label>
                    <span>Weight / Volume Size</span>
                    <input name="variants[${index}][weight]" required placeholder="e.g. 1 Litre or 250g">
                </label>

                <label>
                    <span>Full Product Name</span>
                    <input name="variants[${index}][full_name]" placeholder="auto-generated if empty">
                </label>

                <label>
                    <span>Product ID / Slug</span>
                    <input name="variants[${index}][slug]" placeholder="auto-generated if empty">
                </label>

                <label>
                    <span>Selling Price (₹)</span>
                    <input type="number" min="0" name="variants[${index}][price]" required value="0">
                </label>

                <label>
                    <span>MRP / Original Price (₹)</span>
                    <input type="number" min="0" name="variants[${index}][original_price]" required value="0">
                </label>

                <label>
                    <span>Stock Quantity</span>
                    <input type="number" min="0" name="variants[${index}][stock]" value="50">
                </label>

                <label class="form-span-2">
                    <span>Upload Image File</span>
                    <input type="file" name="variant_image_${index}" accept="image/jpeg,image/png,image/webp">
                </label>

                <label class="form-switch">
                    <input type="checkbox" name="variants[${index}][is_active]" checked>
                    <span>Visible on Storefront</span>
                </label>
            </div>
        `;

        repeaterContainer.appendChild(newRow);
    });

    document.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.remove-variant-btn');
        if (removeBtn && repeaterContainer) {
            const rows = repeaterContainer.querySelectorAll('.variant-repeater-row');
            if (rows.length > 1) {
                removeBtn.closest('.variant-repeater-row')?.remove();
            } else {
                alert('A product must have at least one variant size.');
            }
        }
    });
});
</script>
