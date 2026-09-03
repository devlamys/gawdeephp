<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = __DIR__;
$envFile = $root . '/.env';
$envExample = $root . '/.env.example';
$storageDir = $root . '/storage';

$phpVersion = PHP_VERSION;
$pdoDrivers = extension_loaded('pdo') ? PDO::getAvailableDrivers() : [];
$hasMysql = in_array('mysql', $pdoDrivers, true);
$hasSqlite = in_array('sqlite', $pdoDrivers, true);

$envExists = file_exists($envFile);
$envReadable = $envExists && is_readable($envFile);

$dbStatus = 'Not tested';
$dbError = '';
$dbDriver = 'unknown';

if (file_exists($root . '/includes/platform.php')) {
    require_once $root . '/includes/platform.php';
    $dbDriver = gawdee_db_driver();
    try {
        $db = gawdee_db();
        $dbStatus = 'Connected successfully (' . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . ')';
    } catch (Throwable $e) {
        $dbStatus = 'Connection Failed';
        $dbError = $e->getMessage();
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Gawdee cPanel Deployment Diagnostic</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 2rem; }
        .container { max-width: 760px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
        h1 { margin-top: 0; font-size: 1.5rem; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.75rem; }
        .status-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .status-table th, .status-table td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        .status-table th { background: #f8fafc; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: #64748b; }
        .badge-pass { background: #dcfce7; color: #15803d; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .badge-fail { background: #fee2e2; color: #b91c1c; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .badge-warn { background: #fef3c7; color: #b45309; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; }
        .code-box { background: #0f172a; color: #f8fafc; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.9rem; overflow-x: auto; margin-top: 1rem; }
        .steps { background: #f8fafc; padding: 1rem 1.5rem; border-radius: 8px; border-left: 4px solid #0ea5e9; margin-top: 1.5rem; }
        .steps h3 { margin-top: 0; margin-bottom: 0.5rem; font-size: 1.1rem; }
        .steps ol { margin: 0; padding-left: 1.2rem; }
        .steps li { margin-bottom: 0.4rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>Gawdee Deployment Diagnostic Tool</h1>

    <table class="status-table">
        <thead>
            <tr>
                <th>Check Item</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>PHP Version</strong></td>
                <td>
                    <?php if (version_compare(PHP_VERSION, '8.1.0', '>=')): ?>
                        <span class="badge-pass">PASS</span>
                    <?php else: ?>
                        <span class="badge-fail">FAIL</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($phpVersion) ?> (Requires PHP 8.1+)</td>
            </tr>
            <tr>
                <td><strong>PDO MySQL Driver</strong></td>
                <td>
                    <?php if ($hasMysql): ?>
                        <span class="badge-pass">PASS</span>
                    <?php else: ?>
                        <span class="badge-fail">MISSING</span>
                    <?php endif; ?>
                </td>
                <td>Available drivers: <?= implode(', ', $pdoDrivers) ?: 'None' ?></td>
            </tr>
            <tr>
                <td><strong>.env File</strong></td>
                <td>
                    <?php if ($envExists): ?>
                        <span class="badge-pass">FOUND</span>
                    <?php else: ?>
                        <span class="badge-fail">NOT FOUND</span>
                    <?php endif; ?>
                </td>
                <td>Location: <code><?= htmlspecialchars($envFile) ?></code></td>
            </tr>
            <tr>
                <td><strong>Configured DB Driver</strong></td>
                <td><span class="badge-warn"><?= strtoupper($dbDriver) ?></span></td>
                <td>From <code>.env</code> file (or default 'sqlite')</td>
            </tr>
            <tr>
                <td><strong>Database Connection</strong></td>
                <td>
                    <?php if ($dbStatus === 'Connection Failed'): ?>
                        <span class="badge-fail">FAILED</span>
                    <?php elseif (str_contains($dbStatus, 'Connected')): ?>
                        <span class="badge-pass">OK</span>
                    <?php else: ?>
                        <span class="badge-warn"><?= htmlspecialchars($dbStatus) ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= htmlspecialchars($dbStatus) ?>
                    <?php if ($dbError): ?>
                        <br><strong style="color:#b91c1c;">Error:</strong> <?= htmlspecialchars($dbError) ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Storage Directory</strong></td>
                <td>
                    <?php if (is_dir($storageDir) && is_writable($storageDir)): ?>
                        <span class="badge-pass">WRITABLE</span>
                    <?php else: ?>
                        <span class="badge-fail">NOT WRITABLE</span>
                    <?php endif; ?>
                </td>
                <td>Location: <code><?= htmlspecialchars($storageDir) ?></code></td>
            </tr>
        </tbody>
    </table>

    <?php if (!$envExists || $dbStatus === 'Connection Failed'): ?>
        <div class="steps">
            <h3>How to fix HTTP 500 error in cPanel:</h3>
            <ol>
                <li>In cPanel File Manager, ensure hidden dotfiles are visible or create a file named <code>.env</code> inside <code>/public_html/gawdeenew/</code>.</li>
                <li>Set up a MySQL Database and Database User in cPanel -> <strong>MySQL Database Wizard</strong>.</li>
                <li>Add the cPanel MySQL details to <code>.env</code>:
                    <div class="code-box">APP_NAME="Gawdee Storefront"
APP_ENV=production
APP_DEBUG=true
APP_URL=https://mixmepowder.in/gawdeenew

DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mixmepowder_your_dbname
DB_USER=mixmepowder_your_dbuser
DB_PASSWORD=your_database_password
SESSION_SECRET=<?= bin2hex(random_bytes(16)) ?></div>
                </li>
                <li>Import <code>gawdee_mysql_dump.sql</code> into phpMyAdmin.</li>
            </ol>
        </div>
    <?php else: ?>
        <div class="steps" style="border-left-color: #16a34a;">
            <h3 style="color:#16a34a;">Everything looks ready!</h3>
            <p>You can open the homepage at <a href="index.php">index.php</a>.</p>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
