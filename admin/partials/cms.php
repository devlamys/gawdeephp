<?php

$sections = gawdee_sections();
?>
<div class="admin-section-title">
    <div>
        <h2>Homepage content studio</h2>
        <p>Edit copy, calls to action, section artwork, mobile artwork and hero scrub video from one screen.</p>
    </div>
    <div class="admin-actions">
        <a class="admin-button admin-button--secondary" href="?view=media"><i class="ph ph-video-camera"></i> Manage cards &amp; videos</a>
        <a class="admin-button admin-button--secondary" href="../index.php" target="_blank"><i class="ph ph-arrow-square-out"></i> Preview homepage</a>
    </div>
</div>

<form method="post" enctype="multipart/form-data" class="admin-form" style="margin-bottom: 32px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
    <input type="hidden" name="action" value="save_hero_scrub">

    <details class="cms-editor" open>
        <summary>
            <span class="cms-card__key"><i class="ph ph-film-strip"></i></span>
            <span><strong>Hero Scrub Video Experience</strong><small>Interactive ~5s scroll-controlled video hero</small></span>
            <span class="status-pill <?= gawdee_setting('hero_scrub_enabled', '1') === '1' ? '' : 'status-pill--draft' ?>"><?= gawdee_setting('hero_scrub_enabled', '1') === '1' ? 'Visible' : 'Hidden' ?></span>
            <i class="ph ph-caret-down"></i>
        </summary>
        <div class="cms-editor__body form-grid form-grid--3">
            <label class="form-span-2"><span>Hero Title / Headline</span><input name="hero_scrub_title" value="<?= htmlspecialchars(gawdee_setting('hero_scrub_title', 'PURE FOOD. BETTER EVERYDAY.')) ?>"></label>
            <label class="form-span-3"><span>Hero Subtitle</span><input name="hero_scrub_subtitle" value="<?= htmlspecialchars(gawdee_setting('hero_scrub_subtitle', 'Thoughtfully sourced natural goodness for everyday wellness.')) ?>"></label>

            <div class="cms-media-field form-span-2">
                <label><span>Scroll-Scrub Video File (.mp4 / .webm, approx 5 sec, max 60 MB)</span><input type="file" name="hero_scrub_video" accept="video/mp4,video/webm"></label>
                <input type="hidden" name="existing_hero_scrub_video" value="<?= htmlspecialchars(gawdee_setting('hero_scrub_video', '')) ?>">
                <?php if (gawdee_setting('hero_scrub_video')): ?>
                    <video src="../<?= htmlspecialchars(gawdee_setting('hero_scrub_video')) ?>" controls muted style="max-height: 120px; border-radius: 8px; margin-top: 8px;"></video>
                    <small><?= htmlspecialchars(gawdee_setting('hero_scrub_video')) ?></small>
                <?php else: ?>
                    <small style="color: #888;">No custom scrub video uploaded yet. Add an MP4 video to replace poster preview.</small>
                <?php endif; ?>
            </div>

            <div class="cms-media-field">
                <label><span>Video Poster / Fallback Image</span><input type="file" name="hero_scrub_poster" accept="image/jpeg,image/png,image/webp"></label>
                <input type="hidden" name="existing_hero_scrub_poster" value="<?= htmlspecialchars(gawdee_setting('hero_scrub_poster', 'assets/images/hero-slide-independence-v5.webp')) ?>">
                <?php if (gawdee_setting('hero_scrub_poster')): ?>
                    <img src="../<?= htmlspecialchars(gawdee_setting('hero_scrub_poster')) ?>" alt="" loading="lazy" style="max-height: 100px; border-radius: 8px; margin-top: 8px;">
                <?php endif; ?>
            </div>

            <label class="form-switch form-span-3">
                <input type="checkbox" name="hero_scrub_enabled" value="1" <?= gawdee_setting('hero_scrub_enabled', '1') === '1' ? 'checked' : '' ?>>
                <span>Enable 5-Second Scroll-Scrub Hero Video</span>
            </label>

            <div class="form-span-3" style="margin-top: 12px;">
                <button type="submit" class="admin-button admin-button--primary">Save Hero Scrub Settings <i class="ph ph-check"></i></button>
            </div>
        </div>
    </details>
</form>

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
