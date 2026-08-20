<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/data.php';

$db = gawdee_db();
$failures = [];
$check = static function (bool $condition, string $label) use (&$failures): void {
    echo ($condition ? 'PASS' : 'FAIL') . '  ' . $label . PHP_EOL;
    if (!$condition) {
        $failures[] = $label;
    }
};

$db->beginTransaction();
try {
    $db->prepare('INSERT INTO testimonials (name, initials, product_name, product_slug, quote, rating, theme, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)')->execute([
        'CMS QA Customer', 'CQ', 'A2 Ghee', 'gawdee-gir-cow-a2-ghee-500-ml', 'A temporary testimonial used to verify the complete CMS workflow.', 5, 'ghee', 999,
    ]);
    $testimonialId = (int) $db->lastInsertId();
    $testimonial = array_values(array_filter(gawdee_testimonials(), static fn (array $row): bool => $row['id'] === $testimonialId));
    $check(count($testimonial) === 1 && $testimonial[0]['rating'] === 5, 'testimonial create and public query');

    $db->prepare('INSERT INTO homepage_media (section_key, media_type, title, file_path, product_slug, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)')->execute([
        'reels', 'image', 'CMS QA Media', 'assets/images/products/ghee-500.webp', 'gawdee-gir-cow-a2-ghee-500-ml', 999,
    ]);
    $mediaId = (int) $db->lastInsertId();
    $media = array_values(array_filter(gawdee_homepage_media('reels'), static fn (array $row): bool => $row['id'] === $mediaId));
    $check(count($media) === 1 && $media[0]['media_type'] === 'image', 'homepage media create and public query');

    $slug = 'cms-qa-post-' . bin2hex(random_bytes(4));
    $db->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, status, source, featured_image, category, author, is_featured, published_at) VALUES (?, ?, ?, ?, 'published', 'manual', ?, ?, ?, 1, CURRENT_TIMESTAMP)")->execute([
        'CMS QA Post', $slug, 'Temporary CMS test article.', '<h2>Safe story</h2><p>Verified content.</p>', 'assets/images/blogs/small-daily-improvements-v1.webp', 'Testing', 'QA editor',
    ]);
    $postStatement = $db->prepare("SELECT * FROM blog_posts WHERE slug=? AND status='published'");
    $postStatement->execute([$slug]);
    $post = $postStatement->fetch();
    $check((bool) $post && $post['featured_image'] !== '' && (int) $post['is_featured'] === 1, 'blog image, author and featured publishing');

    $original = gawdee_section('reviews');
    $db->prepare('UPDATE cms_sections SET title=?, button_label=?, button_url=? WHERE section_key=?')->execute(['CMS QA Reviews', 'Read stories', '#reviews', 'reviews']);
    $updated = gawdee_section('reviews');
    $check($updated['title'] === 'CMS QA Reviews' && $updated['button_label'] === 'Read stories', 'homepage section content and CTA update');
} finally {
    $db->rollBack();
}

exit($failures ? 1 : 0);
