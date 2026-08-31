<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';
$posts = gawdee_db()->query("SELECT * FROM blog_posts WHERE status='published' ORDER BY is_featured DESC, COALESCE(published_at, created_at) DESC")->fetchAll();
$pageTitle = 'Gawdee Journal — Food, tradition and wellness';
$pageDescription = 'Thoughtful articles about traditional food, ingredient knowledge and everyday family wellness.';
$bodyClass = 'blog-index-page';
require __DIR__ . '/includes/header.php';
?>

<section class="journal-hero reveal reveal--scale">
    <div class="container">
        <span class="eyebrow"><i class="ph ph-book-open"></i> Gawdee Journal</span>
        <h1>Stories for a more<br><em>thoughtful table.</em></h1>
        <p>Practical ideas, ingredient knowledge and time-honoured food traditions for modern family life.</p>
    </div>
</section>

<section class="journal-shell section">
    <div class="container">
        <?php if ($posts): ?>
            <div class="journal-grid">
                <?php foreach ($posts as $index => $post): ?>
                    <article class="journal-card reveal" data-delay="<?= ($index % 3) * 60 ?>">
                        <a class="journal-card__visual" href="blog-post?slug=<?= rawurlencode($post['slug']) ?>">
                            <?php if ($post['featured_image']): ?>
                                <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="journal-card__placeholder"><i class="ph ph-leaf"></i></div>
                            <?php endif; ?>
                            <span class="journal-card__badge"><?= htmlspecialchars(($post['category'] ?: 'Wellness') . ' · ' . ($post['source'] === 'ai' ? 'AI-assisted' : 'Editorial')) ?></span>
                        </a>
                        <div class="journal-card__body">
                            <div class="journal-card__meta">
                                <i class="ph ph-calendar-blank"></i>
                                <span><?= htmlspecialchars(date('d M Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></span>
                                <span>·</span>
                                <span><?= htmlspecialchars($post['author'] ?: 'Gawdee Editorial') ?></span>
                            </div>
                            <h2><a href="blog-post?slug=<?= rawurlencode($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a></h2>
                            <p><?= htmlspecialchars($post['excerpt']) ?></p>
                            <a class="journal-card__link" href="blog-post?slug=<?= rawurlencode($post['slug']) ?>">Read Story <i class="ph ph-arrow-right"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="journal-empty reveal">
                <i class="ph ph-article"></i>
                <h2>Fresh stories are on the way</h2>
                <p>The Gawdee journal is being prepared with care by our editorial team.</p>
                <a class="button button--primary" href="index">Return to Shop</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
