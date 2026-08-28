<?php
$sections = gawdee_sections();
$featuredStoriesCount = (int) gawdee_db()->query("SELECT COUNT(*) FROM blog_posts WHERE status='published' AND is_featured=1")->fetchColumn();
?>
<div class="admin-section-title">
    <div>
        <h2>Homepage content studio</h2>
        <p>Edit copy, calls to action, section artwork, and mobile artwork from one screen.</p>
    </div>
    <div class="admin-actions">
        <a class="admin-button admin-button--secondary" href="?view=banners_two"><i class="ph ph-film-strip"></i> Manage Hero Banners 2</a>
        <a class="admin-button admin-button--secondary" href="?view=media"><i class="ph ph-video-camera"></i> Manage cards &amp; videos</a>
        <a class="admin-button admin-button--secondary" href="../index.php" target="_blank"><i class="ph ph-arrow-square-out"></i> Preview homepage</a>
    </div>
</div>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <input type="hidden" name="action" value="save_cms">
    <div class="cms-editor-list">
        <?php $posIndex = 0; foreach ($sections as $key => $section): $posIndex++; ?>
            <?php $isStoriesBlocked = ($key === 'stories' && $featuredStoriesCount === 0); ?>
            <details class="cms-editor" data-section-key="<?= htmlspecialchars($key) ?>" <?= in_array($key, ['offer', 'reviews', 'stories', 'reels'], true) ? 'open' : '' ?>>
                <summary>
                    <span class="cms-card__key"><i class="ph ph-layout"></i></span>
                    <span><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></strong><small><?= htmlspecialchars($section['title'] ?: 'Untitled section') ?></small></span>
                    <span class="cms-order-badge" title="Display order position">Pos #<span class="js-pos-num"><?= $posIndex ?></span></span>
                    <div class="cms-reorder-actions">
                        <button type="button" class="cms-reorder-btn js-move-up" title="Move section up"><i class="ph ph-caret-up"></i></button>
                        <button type="button" class="cms-reorder-btn js-move-down" title="Move section down"><i class="ph ph-caret-down"></i></button>
                    </div>
                    <span class="status-pill <?= ($section['is_active'] && !$isStoriesBlocked) ? '' : 'status-pill--draft' ?>"><?= ($section['is_active'] && !$isStoriesBlocked) ? 'Visible' : 'Hidden' ?></span>
                    <i class="ph ph-caret-down"></i>
                </summary>
                <div class="cms-editor__body form-grid form-grid--3">
                    <label><span>Eyebrow</span><input name="eyebrow[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['eyebrow']) ?>"></label>
                    <label><span>Section title</span><input name="title[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['title']) ?>"></label>
                    <label><span>Sort order</span><input type="number" class="js-sort-input" name="sort_order[<?= htmlspecialchars($key) ?>]"
                            value="<?= (int) $section['sort_order'] ?>"></label>
                    <label class="form-span-3"><span>Subtitle</span><input name="subtitle[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['subtitle']) ?>"></label>
                    <label class="form-span-3"><span>Supporting content</span><textarea
                            name="body[<?= htmlspecialchars($key) ?>]"><?= htmlspecialchars($section['body']) ?></textarea></label>
                    <label><span>Button label</span><input name="button_label[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['button_label']) ?>"
                            placeholder="Explore collection"></label>
                    <label><span>Button destination</span><input
                            name="button_url[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['button_url']) ?>"
                            placeholder="#shop or products.php"></label>
                    <label><span>Coupon code</span><input name="coupon_code[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['coupon_code'] ?? ($key === 'offer' ? gawdee_setting('offer_code', 'FREEDOM10') : '')) ?>"
                            placeholder="e.g. FREEDOM10"></label>
                    <label class="form-span-3"><span>Video URL</span><input name="video_url[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['video_url']) ?>"
                            placeholder="Optional YouTube, Vimeo or uploaded video URL"></label>

                    <div class="cms-media-field">
                        <label><span>Desktop / primary image</span><input type="file"
                                name="section_image_<?= htmlspecialchars($key) ?>"
                                accept="image/jpeg,image/png,image/webp"></label>
                        <input type="hidden" name="image[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['image']) ?>">
                        <?php if ($section['image']): ?><img src="../<?= htmlspecialchars($section['image']) ?>" alt=""
                                loading="lazy"><small><?= htmlspecialchars($section['image']) ?></small><?php endif; ?>
                    </div>
                    <div class="cms-media-field">
                        <label><span>Mobile image</span><input type="file"
                                name="section_mobile_image_<?= htmlspecialchars($key) ?>"
                                accept="image/jpeg,image/png,image/webp"></label>
                        <input type="hidden" name="mobile_image[<?= htmlspecialchars($key) ?>]"
                            value="<?= htmlspecialchars($section['mobile_image']) ?>">
                        <?php if ($section['mobile_image']): ?><img
                                src="../<?= htmlspecialchars($section['mobile_image']) ?>" alt=""
                                loading="lazy"><small><?= htmlspecialchars($section['mobile_image']) ?></small><?php endif; ?>
                    </div>
                    <?php if ($isStoriesBlocked): ?>
                        <label class="form-switch form-switch--disabled" title="No featured published stories available. Mark 'Feature on homepage' on published articles under Stories &amp; Publishing first.">
                            <input type="checkbox" disabled name="active[stories]">
                            <span>Show this section <small class="text-danger" style="color:var(--gawdee-danger,#d9534f); display:block; margin-top:2px;"><i class="ph ph-warning-circle"></i> Blocked: No featured published stories available. Check "Feature on homepage" in Stories to enable.</small></span>
                        </label>
                    <?php else: ?>
                        <label class="form-switch"><input type="checkbox" name="active[<?= htmlspecialchars($key) ?>]"
                                <?= $section['is_active'] ? 'checked' : '' ?>><span>Show this section</span></label>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
    <div class="sticky-save-bar"><span><i class="ph ph-info"></i> Changes are published immediately.</span><button
            class="admin-button admin-button--primary">Save homepage content <i class="ph ph-check"></i></button></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.querySelector('.cms-editor-list');
    if (!list) return;

    function updatePositions() {
        const editors = list.querySelectorAll('.cms-editor');
        editors.forEach((editor, idx) => {
            const posNum = editor.querySelector('.js-pos-num');
            const sortInput = editor.querySelector('.js-sort-input');
            const newSortVal = (idx + 1) * 10;
            if (posNum) posNum.textContent = idx + 1;
            if (sortInput) sortInput.value = newSortVal;
        });
    }

    list.addEventListener('click', (e) => {
        const moveUpBtn = e.target.closest('.js-move-up');
        const moveDownBtn = e.target.closest('.js-move-down');
        if (!moveUpBtn && !moveDownBtn) return;

        e.preventDefault();
        e.stopPropagation();

        const editor = e.target.closest('.cms-editor');
        if (!editor) return;

        if (moveUpBtn) {
            const prev = editor.previousElementSibling;
            if (prev && prev.classList.contains('cms-editor')) {
                list.insertBefore(editor, prev);
                updatePositions();
            }
        } else if (moveDownBtn) {
            const next = editor.nextElementSibling;
            if (next && next.classList.contains('cms-editor')) {
                list.insertBefore(next, editor);
                updatePositions();
            }
        }
    });
});
</script>