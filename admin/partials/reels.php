<?php

declare(strict_types=1);

$allMedia = gawdee_homepage_media(null, true);
$videoReels = array_values(array_filter($allMedia, static function (array $item): bool {
    return in_array($item['media_type'], ['video', 'external_video'], true);
}));

$reelEditId = (int) ($_GET['edit'] ?? 0);
$editReel = null;
foreach ($allMedia as $candidate) {
    if ((int) $candidate['id'] === $reelEditId) {
        $editReel = $candidate;
        break;
    }
}

$reel = $editReel ?? [
    'id' => 0,
    'section_key' => 'reels',
    'media_type' => 'video',
    'title' => '',
    'subtitle' => '',
    'file_path' => '',
    'poster_path' => '',
    'external_url' => '',
    'link_url' => '',
    'alt_text' => '',
    'product_slug' => '',
    'sort_order' => count($videoReels) * 10 + 10,
    'is_active' => 1,
    'is_featured_homepage' => 1,
];
?>

<div class="admin-section-title">
    <div>
        <h2>Video Reels Manager</h2>
        <p>Upload, preview and manage short video reels, posters, and product-linked video stories.</p>
    </div>
    <a class="admin-button admin-button--primary" href="?view=reels&edit=-1"><i class="ph ph-plus"></i> Upload new reel</a>
</div>

<?php if (isset($_GET['edit'])): ?>
<section class="admin-card cms-form-card">
    <div class="admin-card__header">
        <div>
            <h2><?= $editReel ? 'Edit Video Reel' : 'Upload New Video Reel' ?></h2>
            <p>Upload video file (.mp4, .mov, .webm) and assign related product &amp; homepage visibility.</p>
        </div>
        <a href="?view=reels" class="admin-action-icon"><i class="ph ph-x"></i></a>
    </div>
    <div class="admin-card__body">
        <form method="post" enctype="multipart/form-data" class="admin-form form-grid form-grid--3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
            <input type="hidden" name="action" value="save_homepage_media">
            <input type="hidden" name="redirect_view" value="reels">
            <input type="hidden" name="id" value="<?= (int) $reel['id'] ?>">
            <input type="hidden" name="existing_file_path" value="<?= htmlspecialchars($reel['file_path']) ?>">
            <input type="hidden" name="existing_poster_path" value="<?= htmlspecialchars($reel['poster_path']) ?>">
            <input type="hidden" name="section_key" value="reels">

            <label>
                <span>Media type</span>
                <select name="media_type">
                    <option value="video" <?= $reel['media_type'] === 'video' ? 'selected' : '' ?>>Uploaded video (.mp4, .mov, .webm)</option>
                    <option value="external_video" <?= $reel['media_type'] === 'external_video' ? 'selected' : '' ?>>External video URL (YouTube/Vimeo)</option>
                </select>
            </label>

            <label>
                <span>Reel Title</span>
                <input name="title" required value="<?= htmlspecialchars($reel['title']) ?>" placeholder="e.g. Traditional Bilona Method">
            </label>

            <label>
                <span>Sort order</span>
                <input type="number" name="sort_order" value="<?= (int) $reel['sort_order'] ?>">
            </label>

            <label class="form-span-2">
                <span>Subtitle / Description</span>
                <input name="subtitle" value="<?= htmlspecialchars($reel['subtitle']) ?>" placeholder="e.g. Crafted with care from nature to your plate">
            </label>

            <label>
                <span>Related product</span>
                <select name="product_slug">
                    <option value="">No related product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= htmlspecialchars($product['slug']) ?>" <?= $reel['product_slug'] === $product['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($product['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Upload video file</span>
                <input type="file" name="media_file" accept="video/mp4,video/webm,video/ogg,video/quicktime,video/x-m4v,video/3gpp">
                <small class="help-text"><?= htmlspecialchars($reel['file_path'] ?: 'No video uploaded yet') ?></small>
            </label>

            <label>
                <span>Video poster thumbnail</span>
                <input type="file" name="poster_file" accept="image/jpeg,image/png,image/webp">
                <small class="help-text"><?= htmlspecialchars($reel['poster_path'] ?: 'No poster uploaded yet') ?></small>
            </label>

            <label>
                <span>External video URL</span>
                <input name="external_url" value="<?= htmlspecialchars($reel['external_url']) ?>" placeholder="https://youtube.com/watch?v=...">
            </label>

            <label>
                <span>Click destination URL (optional)</span>
                <input name="link_url" value="<?= htmlspecialchars($reel['link_url']) ?>" placeholder="product.php?slug=...">
            </label>

            <label>
                <span>Accessible alt text</span>
                <input name="alt_text" value="<?= htmlspecialchars($reel['alt_text']) ?>">
            </label>

            <label class="form-switch">
                <input type="checkbox" name="is_active" <?= (!isset($reel['is_active']) || $reel['is_active']) ? 'checked' : '' ?>>
                <span>Published &amp; Active</span>
            </label>

            <label class="form-switch">
                <input type="checkbox" name="is_featured_homepage" <?= (!isset($reel['is_featured_homepage']) || $reel['is_featured_homepage']) ? 'checked' : '' ?>>
                <span>Feature on Homepage</span>
            </label>

            <div class="form-span-3 form-submit-row">
                <button class="admin-button admin-button--primary">Save Video Reel <i class="ph ph-check"></i></button>
            </div>
        </form>
    </div>
</section>
<?php endif; ?>

<div class="reel-card-grid">
    <?php if (empty($videoReels)): ?>
        <div class="empty-state form-span-3">
            <i class="ph ph-video-camera-slash"></i>
            <h3>No video reels uploaded</h3>
            <p>Click "Upload new reel" above to add short videos to the Gawdee storefront.</p>
        </div>
    <?php else: ?>
        <?php foreach ($videoReels as $item): 
            $prod = !empty($item['product_slug']) ? product_by_slug($products, (string)$item['product_slug']) : null;
            $videoSrc = $item['file_path'] ? '../' . ltrim($item['file_path'], '/') : $item['external_url'];
            $posterSrc = $item['poster_path'] ? '../' . ltrim($item['poster_path'], '/') : '';
            ?>
            <article class="reel-card-admin <?= $item['is_active'] ? '' : 'is-hidden' ?>">
                <div class="reel-card-admin__visual" 
                     data-admin-play-reel
                     data-video-src="<?= htmlspecialchars($videoSrc) ?>"
                     data-video-type="<?= htmlspecialchars($item['media_type']) ?>"
                     data-video-title="<?= htmlspecialchars($item['title'] ?: 'Gawdee Video Reel') ?>"
                     data-video-subtitle="<?= htmlspecialchars($item['subtitle']) ?>"
                     data-video-poster="<?= htmlspecialchars($posterSrc) ?>">

                    <div class="reel-card-admin__badges">
                        <span class="reel-badge reel-badge--type"><i class="ph ph-film-strip"></i> <?= htmlspecialchars(str_replace('_', ' ', $item['media_type'])) ?></span>
                        <?php if (!empty($item['is_featured_homepage'])): ?>
                            <span class="reel-badge reel-badge--featured"><i class="ph ph-sparkle"></i> Featured HP</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($posterSrc): ?>
                        <img src="<?= htmlspecialchars($posterSrc) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                    <?php elseif ($item['file_path']): ?>
                        <video src="<?= htmlspecialchars($videoSrc) ?>" preload="metadata"></video>
                    <?php else: ?>
                        <div style="color:#6d9773;"><i class="ph ph-video-camera" style="font-size:3rem;"></i></div>
                    <?php endif; ?>

                    <div class="reel-card-admin__play-overlay">
                        <div class="reel-card-admin__play-icon" title="Play Video Reel">
                            <i class="ph-fill ph-play"></i>
                        </div>
                    </div>
                </div>

                <div class="reel-card-admin__body">
                    <div class="reel-card-admin__meta">
                        <span>Order <?= $item['sort_order'] ?></span> · 
                        <span class="status-pill <?= $item['is_active'] ? '' : 'status-pill--pending' ?>">
                            <?= $item['is_active'] ? 'Published' : 'Hidden' ?>
                        </span>
                    </div>

                    <h3 class="reel-card-admin__title"><?= htmlspecialchars($item['title'] ?: 'Untitled Reel') ?></h3>
                    <p class="reel-card-admin__subtitle"><?= htmlspecialchars($item['subtitle'] ?: 'No subtitle provided.') ?></p>

                    <?php if ($prod): ?>
                        <div class="reel-card-admin__product-tag">
                            <i class="ph ph-package"></i> <?= htmlspecialchars($prod['name']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="reel-card-admin__actions">
                        <button type="button" class="admin-play-btn"
                                data-admin-play-reel
                                data-video-src="<?= htmlspecialchars($videoSrc) ?>"
                                data-video-type="<?= htmlspecialchars($item['media_type']) ?>"
                                data-video-title="<?= htmlspecialchars($item['title'] ?: 'Gawdee Video Reel') ?>"
                                data-video-subtitle="<?= htmlspecialchars($item['subtitle']) ?>"
                                data-video-poster="<?= htmlspecialchars($posterSrc) ?>">
                            <i class="ph-fill ph-play-circle"></i> Play Reel
                        </button>

                        <div style="display:flex;gap:6px;">
                            <a class="admin-action-icon" href="?view=reels&edit=<?= $item['id'] ?>" title="Edit reel"><i class="ph ph-pencil-simple"></i></a>
                            
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                <input type="hidden" name="action" value="toggle_homepage_media">
                                <input type="hidden" name="redirect_view" value="reels">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button class="admin-action-icon" title="Toggle visibility"><i class="ph <?= $item['is_active'] ? 'ph-eye-slash' : 'ph-eye' ?>"></i></button>
                            </form>

                            <form method="post" onsubmit="return confirm('Delete this video reel?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(gawdee_csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_homepage_media">
                                <input type="hidden" name="redirect_view" value="reels">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button class="admin-action-icon admin-action-icon--danger" title="Delete reel"><i class="ph ph-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Admin Reel Video Player Modal -->
<div class="admin-reel-modal-backdrop" id="adminReelModal" aria-hidden="true">
    <div class="admin-reel-modal-box">
        <div class="admin-reel-modal-header">
            <span class="admin-reel-modal-badge"><i class="ph-fill ph-play-circle"></i> Gawdee Video Reel</span>
            <button type="button" class="admin-reel-modal-close" id="closeAdminReelModal" aria-label="Close player"><i class="ph ph-x"></i></button>
        </div>
        <div class="admin-reel-modal-player-area" id="adminReelModalPlayer"></div>
        <div class="admin-reel-modal-info">
            <h3 id="adminReelModalTitle"></h3>
            <p id="adminReelModalSubtitle"></p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('adminReelModal');
    const closeBtn = document.getElementById('closeAdminReelModal');
    const playerArea = document.getElementById('adminReelModalPlayer');
    const titleEl = document.getElementById('adminReelModalTitle');
    const subtitleEl = document.getElementById('adminReelModalSubtitle');

    let currentVideo = null;

    const closePlayer = () => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (currentVideo) {
            currentVideo.pause();
            currentVideo.src = '';
        }
        if (playerArea) playerArea.innerHTML = '';
    };

    const openPlayer = (src, type, title, subtitle, poster) => {
        if (!modal || !playerArea) return;
        if (titleEl) titleEl.textContent = title || 'Gawdee Video Reel';
        if (subtitleEl) subtitleEl.textContent = subtitle || '';

        playerArea.innerHTML = '';

        if (type === 'external_video' && src.includes('youtube')) {
            const embedUrl = src.replace('watch?v=', 'embed/');
            playerArea.innerHTML = `<iframe src="${embedUrl}?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="width:100%;height:100%;"></iframe>`;
        } else {
            const vid = document.createElement('video');
            vid.src = src;
            if (poster) vid.poster = poster;
            vid.autoplay = true;
            vid.controls = true;
            vid.playsInline = true;
            vid.style.width = '100%';
            vid.style.height = '100%';
            vid.style.objectFit = 'cover';
            playerArea.appendChild(vid);
            currentVideo = vid;

            vid.play().catch(() => {
                vid.muted = true;
                vid.play();
            });
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-admin-play-reel]');
        if (btn) {
            e.preventDefault();
            const src = btn.dataset.videoSrc || '';
            const type = btn.dataset.videoType || 'video';
            const title = btn.dataset.videoTitle || '';
            const subtitle = btn.dataset.videoSubtitle || '';
            const poster = btn.dataset.videoPoster || '';
            openPlayer(src, type, title, subtitle, poster);
        }
    });

    closeBtn?.addEventListener('click', closePlayer);
    modal?.addEventListener('click', (e) => {
        if (e.target === modal) closePlayer();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal?.classList.contains('is-open')) {
            closePlayer();
        }
    });
});
</script>
