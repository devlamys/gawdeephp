<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';

$db = gawdee_db();
$db->beginTransaction();
try {
    $db->prepare('INSERT INTO video_testimonials (name, role_location, quote, rating, video_type, external_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')->execute([
        'Homepage Render Customer', 'CMS preview', 'This temporary record verifies the complete video testimonial storefront section.', 5, 'external_video', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 999,
    ]);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    require __DIR__ . '/../index.php';
    $html = (string) ob_get_clean();

    $checks = [
        'homepage video testimonial section renders' => str_contains($html, 'gawdee-video-testimonials') && str_contains($html, 'Homepage Render Customer'),
        'external video uses safe embed URL' => str_contains($html, 'https://www.youtube.com/embed/dQw4w9WgXcQ'),
        'global typography variables render' => str_contains($html, '--site-body-font:') && str_contains($html, '--site-base-font-size:16px'),
    ];
    $failed = false;
    foreach ($checks as $label => $passed) {
        echo ($passed ? 'PASS' : 'FAIL') . '  ' . $label . PHP_EOL;
        $failed = $failed || !$passed;
    }
} finally {
    $db->rollBack();
}

exit($failed ? 1 : 0);
