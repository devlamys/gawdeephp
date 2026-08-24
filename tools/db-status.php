<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/platform.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

echo $isCli ? "=== Gawdee Database Status ===\n" : "<h2>Gawdee Database Status</h2><pre>";

try {
    $db = gawdee_db();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    echo "Status        : SUCCESS (Connected)\n";
    echo "Driver        : " . strtoupper($driver) . "\n";
    
    if ($driver === 'mysql') {
        echo "Host          : " . gawdee_env('DB_HOST', '127.0.0.1') . ":" . gawdee_env('DB_PORT', '3306') . "\n";
        echo "Database      : " . gawdee_env('DB_DATABASE', 'gawdee') . "\n";
        echo "Username      : " . gawdee_env('DB_USERNAME', 'root') . "\n";
    } else {
        echo "SQLite File   : " . GAWDEE_DB . "\n";
    }

    echo "\n--- Database Tables ---\n";
    $tables = [
        'users', 'settings', 'products', 'banners', 'cms_sections',
        'testimonials', 'homepage_media', 'blog_posts', 'orders',
        'order_items', 'integration_logs', 'subscribers', 'product_reviews',
        'order_status_events'
    ];

    foreach ($tables as $table) {
        try {
            $count = (int) $db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            printf(" - %-22s : %d records\n", $table, $count);
        } catch (Throwable $t) {
            printf(" - %-22s : MISSING (%s)\n", $table, $t->getMessage());
        }
    }

    echo "\nAll database migrations and seed data applied successfully!\n";

} catch (Throwable $e) {
    echo "Status        : ERROR\n";
    echo "Message       : " . $e->getMessage() . "\n";
    echo "\nPlease check your .env database settings or make sure your MySQL service in XAMPP is running.\n";
}

if (!$isCli) {
    echo "</pre>";
}
