<?php

$sections = gawdee_sections();
?>
<div class="admin-section-title">
    <div>
        <h2>Homepage content studio</h2>
        <p>Edit copy, calls to action, section artwork, mobile artwork and video links from one screen.</p>
    </div>
    <div class="admin-actions">
        <a class="admin-button admin-button--secondary" href="?view=media"><i class="ph ph-video-camera"></i> Manage cards & videos</a>
        <a class="admin-button admin-button--secondary" href="../index.php" target="_blank"><i class="ph ph-arrow-square-out"></i> Preview homepage</a>
    </div>
</div>

<form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <input type="hidden" name="action" value="save_cms">
    <div class="cms-editor-list">
        <?php foreach ($sections as $key => $section): ?>
            <details class="cms-editor" <?= in_array($key, ['offer', 'reviews', 'stories', 'reels'], true) ? 'open' : '' ?>>
                <summary>
                    <span class="cms-card__key"><i class="ph ph-layout"></i></span>
                    <span><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></strong><small><?= htmlspecialchars($section['title'] ?: 'Untitled section') ?></small></span>
                    <span class="status-pill <?= $section['is_active'] ? '' : 'status-pill--draft' ?>"><?= $section['is_active'] ? 'Visible' : 'Hidden' ?></span>
                    <i class="ph ph-caret-down"></i>
                </summary>
                <div class="cms-editor__body form-grid form-grid--3">
                    <label><span>Eyebrow</span><input name="eyebrow[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['eyebrow']) ?>"></label>
                    <label><span>Section title</span><input name="title[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['title']) ?>"></label>
                    <label><span>Sort order</span><input type="number" name="sort_order[<?= htmlspecialchars($key) ?>]" value="<?= (int) $section['sort_order'] ?>"></label>
                    <label class="form-span-3"><span>Subtitle</span><input name="subtitle[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['subtitle']) ?>"></label>
                    <label class="form-span-3"><span>Supporting content</span><textarea name="body[<?= htmlspecialchars($key) ?>]"><?= htmlspecialchars($section['body']) ?></textarea></label>
                    <label><span>Button label</span><input name="button_label[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['button_label']) ?>" placeholder="Explore collection"></label>
                    <label class="form-span-2"><span>Button destination</span><input name="button_url[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['button_url']) ?>" placeholder="#shop or products.php"></label>
                    <label class="form-span-3"><span>Video URL</span><input name="video_url[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['video_url']) ?>" placeholder="Optional YouTube, Vimeo or uploaded video URL"></label>

                    <div class="cms-media-field">
                        <label><span>Desktop / primary image</span><input type="file" name="section_image_<?= htmlspecialchars($key) ?>" accept="image/jpeg,image/png,image/webp"></label>
                        <input type="hidden" name="image[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['image']) ?>">
                        <?php if ($section['image']): ?><img src="../<?= htmlspecialchars($section['image']) ?>" alt="" loading="lazy"><small><?= htmlspecialchars($section['image']) ?></small><?php endif; ?>
                    </div>
                    <div class="cms-media-field">
                        <label><span>Mobile image</span><input type="file" name="section_mobile_image_<?= htmlspecialchars($key) ?>" accept="image/jpeg,image/png,image/webp"></label>
                        <input type="hidden" name="mobile_image[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($section['mobile_image']) ?>">
                        <?php if ($section['mobile_image']): ?><img src="../<?= htmlspecialchars($section['mobile_image']) ?>" alt="" loading="lazy"><small><?= htmlspecialchars($section['mobile_image']) ?></small><?php endif; ?>
                    </div>
                    <label class="form-switch"><input type="checkbox" name="active[<?= htmlspecialchars($key) ?>]" <?= $section['is_active'] ? 'checked' : '' ?>><span>Show this section</span></label>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
    <div class="sticky-save-bar"><span><i class="ph ph-info"></i> Changes are published immediately.</span><button class="admin-button admin-button--primary">Save homepage content <i class="ph ph-check"></i></button></div>
</form>
