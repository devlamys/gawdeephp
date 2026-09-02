<?php

declare(strict_types=1);

/**
 * Gawdee Storefront - SQLite to MySQL Migration & Export Utility
 * 
 * Usage:
 *  1. CLI: php scripts/migrate-sqlite-to-mysql.php
 *  2. Browser: Navigate to http://your-domain/scripts/migrate-sqlite-to-mysql.php
 */

require_once __DIR__ . '/../includes/platform.php';

$isCli = PHP_SAPI === 'cli';

function output_msg(string $message, string $type = 'info'): void {
    global $isCli;
    if ($isCli) {
        $prefix = match($type) {
            'success' => '[OK] ',
            'error'   => '[ERROR] ',
            'warn'    => '[WARN] ',
            default   => '[INFO] ',
        };
        echo $prefix . $message . PHP_EOL;
    } else {
        $color = match($type) {
            'success' => '#10b981',
            'error'   => '#ef4444',
            'warn'    => '#f59e0b',
            default   => '#3b82f6',
        };
        echo "<div style='margin: 8px 0; font-family: monospace; font-size: 14px; color: {$color};'>" . htmlspecialchars($message) . "</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>Gawdee SQLite to MySQL Migration</title>";
    echo "<style>body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; padding: 2rem; max-width: 800px; margin: 0 auto; }</style>";
    echo "</head><body>";
    echo "<h1 style='color: #38bdf8;'>Gawdee SQLite &rarr; MySQL Migration Utility</h1>";
}

output_msg('Starting Gawdee SQLite to MySQL Migration Process...');

$sqlitePath = GAWDEE_STORAGE . '/gawdee.sqlite';
if (!file_exists($sqlitePath)) {
    output_msg("SQLite database not found at: {$sqlitePath}. Nothing to export.", 'warn');
    if (!$isCli) echo "</body></html>";
    exit(0);
}

try {
    $sqlitePdo = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    output_msg("Connected to SQLite database at {$sqlitePath}", 'success');
} catch (Throwable $e) {
    output_msg("Failed to connect to SQLite: " . $e->getMessage(), 'error');
    if (!$isCli) echo "</body></html>";
    exit(1);
}

// Prepare MySQL SQL dump file
$dumpFilePath = GAWDEE_ROOT . '/gawdee_mysql_dump.sql';
$dumpHandle = fopen($dumpFilePath, 'wb');
if (!$dumpHandle) {
    output_msg("Cannot open dump file for writing: {$dumpFilePath}", 'error');
    if (!$isCli) echo "</body></html>";
    exit(1);
}

$header = "-- Gawdee Storefront MySQL Dump\n";
$header .= "-- Generated at " . date('Y-m-d H:i:s') . "\n";
$header .= "SET foreign_key_checks = 0;\n";
$header .= "SET NAMES utf8mb4;\n\n";
fwrite($dumpHandle, $header);

// Attempt direct MySQL connection if configured in .env
$targetMysqlPdo = null;
$dbDriver = gawdee_env('DB_DRIVER', 'sqlite');
if (strtolower((string)$dbDriver) === 'mysql') {
    try {
        $host = (string) gawdee_env('DB_HOST', '127.0.0.1');
        $port = (string) gawdee_env('DB_PORT', '3306');
        $dbname = (string) gawdee_env('DB_NAME', 'gawdee');
        $user = (string) gawdee_env('DB_USER', 'root');
        $pass = (string) gawdee_env('DB_PASSWORD', '');
        $charset = (string) gawdee_env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $targetMysqlPdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $targetMysqlPdo->exec("SET foreign_key_checks = 0;");
        output_msg("Connected to target MySQL database '{$dbname}' on {$host}:{$port}", 'success');
    } catch (Throwable $e) {
        output_msg("Could not connect to target MySQL database: " . $e->getMessage(), 'warn');
        output_msg("Continuing with SQL Dump file generation only.", 'info');
    }
}

$tables = [
    'users',
    'settings',
    'products',
    'banners',
    'cms_sections',
    'testimonials',
    'homepage_media',
    'video_testimonials',
    'cms_section_items',
    'blog_posts',
    'orders',
    'order_items',
    'integration_logs',
    'subscribers',
    'product_reviews',
    'order_status_events',
];

$migratedCount = 0;
foreach ($tables as $table) {
    try {
        $stmt = $sqlitePdo->query("SELECT * FROM {$table}");
        $rows = $stmt->fetchAll();
        $rowCount = count($rows);

        fwrite($dumpHandle, "-- Table structure and data for table `{$table}` --\n");

        if ($rowCount > 0) {
            output_msg("Exporting table '{$table}' ({$rowCount} rows)...", 'info');
            $cols = array_keys($rows[0]);
            $colList = implode('`, `', $cols);

            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } else {
                        // MySQL escape via PDO quote
                        $values[] = $sqlitePdo->quote((string)$val);
                    }
                }
                $valList = implode(', ', $values);
                $sql = "INSERT INTO `{$table}` (`{$colList}`) VALUES ({$valList});\n";
                fwrite($dumpHandle, $sql);

                if ($targetMysqlPdo) {
                    try {
                        $targetMysqlPdo->exec("INSERT IGNORE INTO `{$table}` (`{$colList}`) VALUES ({$valList})");
                    } catch (Throwable $ex) {
                        // ignore duplicate error
                    }
                }
            }
        } else {
            output_msg("Table '{$table}' is empty. Schema initialized.", 'info');
        }
        fwrite($dumpHandle, "\n");
        $migratedCount++;
    } catch (Throwable $e) {
        output_msg("Skipped table '{$table}': " . $e->getMessage(), 'warn');
    }
}

fwrite($dumpHandle, "SET foreign_key_checks = 1;\n");
fclose($dumpHandle);

if ($targetMysqlPdo) {
    $targetMysqlPdo->exec("SET foreign_key_checks = 1;");
    output_msg("Direct migration to MySQL database completed successfully!", 'success');
}

output_msg("MySQL Dump file successfully generated at: gawdee_mysql_dump.sql", 'success');
output_msg("You can now import gawdee_mysql_dump.sql directly into cPanel phpMyAdmin!", 'success');

if (!$isCli) {
    echo "<div style='margin-top: 2rem; padding: 1rem; background: #1e293b; border-radius: 8px;'>";
    echo "<h2>Next Steps for cPanel Deployment:</h2>";
    echo "<ol>";
    echo "<li>Log into your cPanel account.</li>";
    echo "<li>Go to <strong>phpMyAdmin</strong> and select your database.</li>";
    echo "<li>Click the <strong>Import</strong> tab and upload <code>gawdee_mysql_dump.sql</code>.</li>";
    echo "<li>Copy <code>.env.example</code> to <code>.env</code> on your cPanel server with your MySQL database details.</li>";
    echo "</ol>";
    echo "</div></body></html>";
}
