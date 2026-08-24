<?php

declare(strict_types=1);

require __DIR__ . '/includes/data.php';
$statement = gawdee_db()->prepare("SELECT * FROM blog_posts WHERE slug=? AND status='published'");
$statement->execute([trim((string) ($_GET['slug'] ?? ''))]);
$post = $statement->fetch();
if (!$post) {
    http_response_code(404);
    $pageTitle = 'Story not found — Gawdee';
} else {
    $pageTitle = $post['title'] . ' — Gawdee Journal';
    $pageDescription = $post['meta_description'] ?: $post['excerpt'];
}
$bodyClass = 'blog-post-page';
require __DIR__ . '/includes/header.php';
?>
<?php if ($post): ?>
<article class="journal-article section">
    <div class="container container--narrow">
        <header class="journal-article__header reveal">
            <a class="journal-article__back" href="blog.php"><i class="ph ph-arrow-left"></i> Back to Journal</a>
            <span class="eyebrow"><i class="ph ph-tag"></i> <?= htmlspecialchars($post['category'] ?: 'Gawdee Journal') ?></span>
            <h1><?= htmlspecialchars($post['title']) ?></h1>
            <?php if ($post['excerpt']): ?><p class="journal-article__excerpt"><?= htmlspecialchars($post['excerpt']) ?></p><?php endif; ?>
            <div class="journal-article__meta">
                <span><i class="ph ph-calendar-blank"></i> <?= htmlspecialchars(date('d F Y', strtotime($post['published_at'] ?: $post['created_at']))) ?></span>
                <span>·</span>
                <span><i class="ph ph-user"></i> <?= htmlspecialchars($post['author'] ?: 'Gawdee Editorial') ?></span>
            </div>
        </header>
        <?php if ($post['featured_image']): ?>
            <figure class="journal-article__cover reveal reveal--scale">
                <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
            </figure>
        <?php endif; ?>
        <div class="journal-article__body reveal">
            <?= $post['content'] ?>
        </div>
        <footer class="journal-article__footer reveal">
            <div>
                <span class="eyebrow"><i class="ph ph-plant"></i> Mindful Food</span>
                <h3>Thoughtful choices start with good information.</h3>
            </div>
            <a class="button button--primary" href="index.php#shop">Explore Gawdee Products <i class="ph ph-arrow-right"></i></a>
        </footer>
    </div>
</article>
<?php else: ?>
<section class="journal-hero section text-center">
    <div class="container">
        <span class="eyebrow">404</span>
        <h1>Story not found.</h1>
        <p>The article may have moved or is still being prepared.</p>
        <a class="button button--primary" href="blog.php">Back to Journal</a>
    </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
