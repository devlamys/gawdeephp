<?php

$categories = gawdee_categories(true);
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editCategory = null;
if ($editId !== null && $editId > 0) {
    $editCategory = gawdee_category_by_id($editId);
}

$dbProductCategories = gawdee_db()->query("SELECT DISTINCT category, category_key FROM products WHERE category_key != '' ORDER BY category ASC")->fetchAll();
$categoryOptions = [
    'all' => 'All Products',
    'ghee' => 'Ghee',
    'honey' => 'Honey',
    'wellness' => 'Drops',
    'nutrition' => 'Mix Me',
    'sugar' => 'Sugar',
];

// Dynamically add all previously created category cards from categories table
$savedCategories = gawdee_db()->query("SELECT DISTINCT name, filter FROM categories WHERE filter != '' ORDER BY name ASC")->fetchAll();
foreach ($savedCategories as $savedCat) {
    $filterKey = trim((string) $savedCat['filter']);
    $nameVal = trim((string) $savedCat['name']);
    if ($filterKey !== '') {
        $categoryOptions[$filterKey] = $nameVal !== '' ? $nameVal : $filterKey;
    }
}

// Dynamically add product category keys
foreach ($dbProductCategories as $dbCat) {
    $key = trim((string) $dbCat['category_key']);
    $catName = trim((string) $dbCat['category']);
    if ($key !== '' && !isset($categoryOptions[$key])) {
        $categoryOptions[$key] = $catName !== '' ? $catName : $key;
    }
}
?>
<script>
    const gawdeeCategoryMap = <?= json_encode($categoryOptions) ?>;
    function gawdeeSyncCategoryName(input) {
        const val = input.value.trim();
        const nameInp = document.getElementById('category_name_input');
        if (nameInp && (nameInp.value.trim() === '' || <?= ($editId === null || $editId <= 0) ? 'true' : 'false' ?>)) {
            if (gawdeeCategoryMap[val]) {
                nameInp.value = gawdeeCategoryMap[val];
            }
        }
    }
</script>

<div class="admin-section-title">
    <div>
        <h2>Shop by category manager</h2>
        <p>Upload dynamic category images, set filter links, Phosphor icons, display order, and control homepage
            visibility.</p>
    </div>
    <div class="admin-actions">
        <a class="admin-button admin-button--primary" href="?view=categories&edit=-1"><i class="ph ph-plus"></i> Add
            category card</a>
    </div>
</div>

<?php if ($editId !== null): ?>
    <?php
    $cat = $editCategory ?? [
        'id' => 0,
        'name' => '',
        'filter' => '',
        'image' => '',
        'icon' => '',
        'sort_order' => (count($categories) + 1) * 10,
        'is_active' => 1,
    ];
    ?>
    <section class="admin-card mb-4">
        <div class="admin-card__header">
            <div>
                <h2><?= $cat['id'] > 0 ? 'Edit category card' : 'New category card' ?></h2>
                <p>Upload a thumbnail image (recommended size ~200x200px PNG/JPG/WebP) or provide an icon class like
                    <code>ph-gift</code>.
                </p>
            </div>
            <a href="?view=categories" class="admin-action-icon" title="Close editor"><i class="ph ph-x"></i></a>
        </div>
        <form method="post" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($cat['image']) ?>">

            <div class="form-grid form-grid--3">
                <label>
                    <span>Category Name *</span>
                    <input id="category_name_input" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
                </label>
                <label>
                    <span>Filter Slug / Link *</span>
                    <input list="category_filter_list" id="filter_input" name="filter"
                        value="<?= htmlspecialchars($cat['filter']) ?>" required placeholder="Type or select filter slug.."
                        onchange="gawdeeSyncCategoryName(this)">
                    <datalist id="category_filter_list">
                        <?php foreach ($categoryOptions as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                    <small style="color: #666; display: block; margin-top: 4px;">Click arrow to choose from suggestions or
                        type any custom filter slug.</small>
                </label>
                <label>
                    <span>Sort Order</span>
                    <input type="number" name="sort_order" value="<?= (int) $cat['sort_order'] ?>">
                </label>

                <div class="cms-media-field form-span-2">
                    <label>
                        <span>Category Thumbnail Image</span>
                        <input type="file" name="category_image" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <?php if (!empty($cat['image'])): ?>
                        <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                            <img src="../<?= htmlspecialchars($cat['image']) ?>" alt=""
                                style="width: 50px; height: 50px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd; background: #fafafa;">
                            <small><?= htmlspecialchars($cat['image']) ?></small>
                        </div>
                    <?php endif; ?>
                </div>

                <label>
                    <span>Icon Class (Optional fallback)</span>
                    <input name="icon" value="<?= htmlspecialchars($cat['icon']) ?>" placeholder="e.g. ph-gift, ph-leaf">
                </label>

                <label class="form-switch form-span-3">
                    <input type="checkbox" name="is_active" <?= $cat['is_active'] ? 'checked' : '' ?>>
                    <span>Show on homepage "Shop by category" section</span>
                </label>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 12px;">
                <button class="admin-button admin-button--primary"><i class="ph ph-check"></i> Save Category Card</button>
                <a href="?view=categories" class="admin-button admin-button--ghost">Cancel</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Filter Link</th>
                    <th>Visual</th>
                    <th>Sort Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($categories): ?>
                    <?php foreach ($categories as $item): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                            </td>
                            <td>
                                <code>products.php?category=<?= htmlspecialchars($item['filter']) ?></code>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../<?= htmlspecialchars($item['image']) ?>" alt=""
                                            style="width: 36px; height: 36px; object-fit: contain; border-radius: 6px; border: 1px solid #eee; background: #fff;">
                                    <?php elseif (!empty($item['icon'])): ?>
                                        <i class="ph <?= htmlspecialchars($item['icon']) ?>" style="font-size: 24px;"></i>
                                    <?php else: ?>
                                        <i class="ph ph-squares-four" style="font-size: 24px; color: #999;"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?= (int) $item['sort_order'] ?>
                            </td>
                            <td>
                                <span class="status-pill <?= $item['is_active'] ? '' : 'status-pill--draft' ?>">
                                    <?= $item['is_active'] ? 'Visible' : 'Hidden' ?>
                                </span>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <a class="admin-action-icon" href="?view=categories&edit=<?= (int) $item['id'] ?>"
                                        title="Edit category">
                                        <i class="ph ph-pencil-simple"></i>
                                    </a>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle_category">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="admin-action-icon" title="Toggle visibility">
                                            <i class="ph <?= $item['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i>
                                        </button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('Delete this category card?')">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete_category">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="admin-action-icon admin-action-icon--danger" title="Delete category">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 24px;">No categories found. Click "Add category
                            card" to create one.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>