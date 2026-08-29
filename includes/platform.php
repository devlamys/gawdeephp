<?php

declare(strict_types=1);

const GAWDEE_ROOT = __DIR__ . '/..';
const GAWDEE_STORAGE = GAWDEE_ROOT . '/storage';
const GAWDEE_DB = GAWDEE_STORAGE . '/gawdee.sqlite';

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_name('gawdee_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function gawdee_load_env(string $path = GAWDEE_ROOT . '/.env'): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

function gawdee_env(string $key, ?string $default = null): ?string
{
    gawdee_load_env();

    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return (string) $val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }
    return $default;
}

class GawdeePDO extends PDO
{
    private string $driverName;

    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->driverName = (string) $this->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getDriverName(): string
    {
        return $this->driverName;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return parent::prepare($this->adaptSql($query), $options);
    }

    public function exec(string $statement): int|false
    {
        return parent::exec($this->adaptSql($statement));
    }

    public function query(string $statement, ?int $mode = null, mixed ...$args): PDOStatement|false
    {
        $adapted = $this->adaptSql($statement);
        if ($mode !== null) {
            return parent::query($adapted, $mode, ...$args);
        }
        return parent::query($adapted);
    }

    public function adaptSql(string $sql): string
    {
        if ($this->driverName !== 'mysql') {
            return $sql;
        }

        // Convert SQLite 'INSERT OR IGNORE INTO' -> MySQL 'INSERT IGNORE INTO'
        $sql = preg_replace('/INSERT\s+OR\s+IGNORE\s+INTO/i', 'INSERT IGNORE INTO', $sql);

        // Convert SQLite 'ON CONFLICT(...) DO UPDATE SET' -> MySQL 'ON DUPLICATE KEY UPDATE'
        $sql = preg_replace('/ON\s+CONFLICT\([^)]+\)\s+DO\s+UPDATE\s+SET/i', 'ON DUPLICATE KEY UPDATE', $sql);

        // Convert SQLite 'excluded.column' -> MySQL 'VALUES(column)'
        $sql = preg_replace('/excluded\.([a-zA-Z0-9_]+)/i', 'VALUES($1)', $sql);

        return $sql;
    }
}

function gawdee_db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    gawdee_load_env();
    $driver = strtolower((string) gawdee_env('DB_DRIVER', gawdee_env('DB_CONNECTION', 'sqlite')));

    if ($driver === 'mysql') {
        $host = (string) gawdee_env('DB_HOST', '127.0.0.1');
        $port = (string) gawdee_env('DB_PORT', '3306');
        $dbname = (string) gawdee_env('DB_DATABASE', 'gawdee');
        $username = (string) gawdee_env('DB_USERNAME', 'root');
        $password = (string) gawdee_env('DB_PASSWORD', '');

        try {
            $initDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $initPdo = new PDO($initDsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $initPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $initPdo = null;
        } catch (Throwable $e) {
            // Fall back to direct connection if DB creation statement isn't allowed or DB already exists
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new GawdeePDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } else {
        if (!is_dir(GAWDEE_STORAGE)) {
            mkdir(GAWDEE_STORAGE, 0750, true);
        }
        $sqlitePath = (string) gawdee_env('DB_SQLITE_PATH', GAWDEE_DB);
        if (!str_starts_with($sqlitePath, '/') && !preg_match('/^[a-zA-Z]:[\\\\\/]/', $sqlitePath)) {
            $sqlitePath = GAWDEE_ROOT . '/' . ltrim($sqlitePath, '/\\');
        }

        $pdo = new GawdeePDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    }

    gawdee_migrate($pdo);

    return $pdo;
}

function gawdee_get_table_columns(PDO $db, string $table): array
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $stmt = $db->prepare("SHOW COLUMNS FROM `{$table}`");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    }
    $stmt = $db->query("PRAGMA table_info(`{$table}`)");
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
}

function gawdee_ensure_column(PDO $db, string $table, string $column, string $definition): void
{
    $existing = gawdee_get_table_columns($db, $table);
    if (in_array($column, $existing, true)) {
        return;
    }

    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $definition = preg_replace('/TEXT\s+NOT\s+NULL\s+DEFAULT\s+\'\'/i', "VARCHAR(255) NOT NULL DEFAULT ''", $definition);
        $definition = preg_replace('/REAL\s+NOT\s+NULL\s+DEFAULT\s+0/i', "DOUBLE NOT NULL DEFAULT 0", $definition);
        $definition = preg_replace('/INTEGER/i', "INT", $definition);
    }

    $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}

function gawdee_create_index(PDO $db, string $table, string $indexName, string $columnsSql, bool $isUnique = false, string $sqliteWhere = ''): void
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'mysql') {
        $check = $db->prepare("SHOW INDEX FROM `{$table}` WHERE Key_name = ?");
        $check->execute([$indexName]);
        if (!$check->fetch()) {
            $uniqueSql = $isUnique ? 'UNIQUE' : '';
            $db->exec("CREATE {$uniqueSql} INDEX `{$indexName}` ON `{$table}` ({$columnsSql})");
        }
    } else {
        $uniqueSql = $isUnique ? 'UNIQUE' : '';
        $whereSql = $sqliteWhere !== '' ? " WHERE {$sqliteWhere}" : '';
        $db->exec("CREATE {$uniqueSql} INDEX IF NOT EXISTS `{$indexName}` ON `{$table}` ({$columnsSql}){$whereSql}");
    }
}

function gawdee_migrate(PDO $db): void
{
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'admin',
    phone VARCHAR(50) NOT NULL DEFAULT '',
    address1 TEXT,
    address2 TEXT,
    city VARCHAR(100) NOT NULL DEFAULT '',
    state VARCHAR(100) NOT NULL DEFAULT '',
    pincode VARCHAR(20) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(191) PRIMARY KEY,
    setting_value TEXT,
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(191) PRIMARY KEY,
    slug VARCHAR(191) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    category_key VARCHAR(100) NOT NULL,
    tag VARCHAR(100) NOT NULL DEFAULT '',
    price INT NOT NULL,
    original_price INT NOT NULL,
    weight VARCHAR(50) NOT NULL DEFAULT '',
    image VARCHAR(255) NOT NULL DEFAULT '',
    description TEXT,
    accent VARCHAR(20) NOT NULL DEFAULT '#0a7540',
    stock INT NOT NULL DEFAULT 100,
    stock_status VARCHAR(50) NOT NULL DEFAULT 'in_stock',
    sku VARCHAR(100) NOT NULL DEFAULT '',
    source_id VARCHAR(100) NOT NULL DEFAULT '',
    source_url VARCHAR(255) NOT NULL DEFAULT '',
    rating DOUBLE NOT NULL DEFAULT 0,
    review_count INT NOT NULL DEFAULT 0,
    gallery_json TEXT,
    details_json TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    desktop_image VARCHAR(255) NOT NULL,
    mobile_image VARCHAR(255) NOT NULL DEFAULT '',
    link_url VARCHAR(255) NOT NULL DEFAULT '#shop',
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS hero_banners_two (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    headline VARCHAR(255) NOT NULL DEFAULT '',
    eyebrow VARCHAR(255) NOT NULL DEFAULT '',
    subtitle VARCHAR(255) NOT NULL DEFAULT '',
    desktop_video VARCHAR(255) NOT NULL DEFAULT '',
    mobile_video VARCHAR(255) NOT NULL DEFAULT '',
    duration INT NOT NULL DEFAULT 1,
    link_url VARCHAR(255) NOT NULL DEFAULT '#shop',
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cms_sections (
    section_key VARCHAR(191) PRIMARY KEY,
    eyebrow VARCHAR(255) NOT NULL DEFAULT '',
    title VARCHAR(255) NOT NULL DEFAULT '',
    subtitle VARCHAR(255) NOT NULL DEFAULT '',
    body TEXT,
    image VARCHAR(255) NOT NULL DEFAULT '',
    mobile_image VARCHAR(255) NOT NULL DEFAULT '',
    video_url VARCHAR(255) NOT NULL DEFAULT '',
    button_label VARCHAR(100) NOT NULL DEFAULT '',
    button_url VARCHAR(255) NOT NULL DEFAULT '',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    initials VARCHAR(10) NOT NULL DEFAULT '',
    avatar VARCHAR(255) NOT NULL DEFAULT '',
    product_name VARCHAR(255) NOT NULL DEFAULT '',
    product_slug VARCHAR(191) NOT NULL DEFAULT '',
    quote TEXT NOT NULL,
    rating INT NOT NULL DEFAULT 5,
    theme VARCHAR(50) NOT NULL DEFAULT 'ghee',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    filter VARCHAR(100) NOT NULL DEFAULT 'all',
    image VARCHAR(255) NOT NULL DEFAULT '',
    icon VARCHAR(100) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS homepage_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(100) NOT NULL DEFAULT 'reels',
    media_type VARCHAR(50) NOT NULL DEFAULT 'image',
    title VARCHAR(255) NOT NULL DEFAULT '',
    subtitle VARCHAR(255) NOT NULL DEFAULT '',
    file_path VARCHAR(255) NOT NULL DEFAULT '',
    poster_path VARCHAR(255) NOT NULL DEFAULT '',
    external_url VARCHAR(255) NOT NULL DEFAULT '',
    link_url VARCHAR(255) NOT NULL DEFAULT '',
    alt_text VARCHAR(255) NOT NULL DEFAULT '',
    product_slug VARCHAR(191) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    source VARCHAR(50) NOT NULL DEFAULT 'manual',
    ai_provider VARCHAR(50) NOT NULL DEFAULT '',
    meta_description TEXT,
    featured_image VARCHAR(255) NOT NULL DEFAULT '',
    category VARCHAR(100) NOT NULL DEFAULT 'Wellness',
    author VARCHAR(100) NOT NULL DEFAULT 'Gawdee editorial',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    order_number VARCHAR(191) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL DEFAULT 'razorpay',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    shipment_status VARCHAR(50) NOT NULL DEFAULT 'not_created',
    currency VARCHAR(10) NOT NULL DEFAULT 'INR',
    subtotal INT NOT NULL,
    shipping INT NOT NULL DEFAULT 0,
    discount INT NOT NULL DEFAULT 0,
    total INT NOT NULL,
    coupon_code VARCHAR(50) NOT NULL DEFAULT '',
    checkout_token VARCHAR(191) NOT NULL DEFAULT '',
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address1 TEXT NOT NULL,
    address2 TEXT,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    pincode VARCHAR(20) NOT NULL DEFAULT '',
    notes TEXT,
    razorpay_order_id VARCHAR(100) NOT NULL DEFAULT '',
    razorpay_payment_id VARCHAR(100) NOT NULL DEFAULT '',
    razorpay_signature VARCHAR(255) NOT NULL DEFAULT '',
    dtdc_reference VARCHAR(100) NOT NULL DEFAULT '',
    dtdc_tracking_url VARCHAR(255) NOT NULL DEFAULT '',
    fulfillment_mode VARCHAR(50) NOT NULL DEFAULT 'manual',
    courier_name VARCHAR(100) NOT NULL DEFAULT '',
    tracking_number VARCHAR(100) NOT NULL DEFAULT '',
    tracking_url VARCHAR(255) NOT NULL DEFAULT '',
    inventory_status VARCHAR(50) NOT NULL DEFAULT 'not_deducted',
    admin_note TEXT,
    payment_error TEXT,
    paid_at DATETIME NULL,
    fulfilled_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id VARCHAR(191) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price INT NOT NULL,
    image VARCHAR(255) NOT NULL DEFAULT '',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS integration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL,
    reference VARCHAR(100) NOT NULL DEFAULT '',
    message TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(191) NOT NULL,
    rating INT NOT NULL,
    review TEXT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(191) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'approved',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_status_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
    } else {
        $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    phone TEXT NOT NULL DEFAULT '',
    address1 TEXT NOT NULL DEFAULT '',
    address2 TEXT NOT NULL DEFAULT '',
    city TEXT NOT NULL DEFAULT '',
    state TEXT NOT NULL DEFAULT '',
    pincode TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT NOT NULL DEFAULT '',
    is_secret INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id TEXT PRIMARY KEY,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    full_name TEXT NOT NULL,
    category TEXT NOT NULL,
    category_key TEXT NOT NULL,
    tag TEXT NOT NULL DEFAULT '',
    price INTEGER NOT NULL,
    original_price INTEGER NOT NULL,
    weight TEXT NOT NULL DEFAULT '',
    image TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    accent TEXT NOT NULL DEFAULT '#0a7540',
    stock INTEGER NOT NULL DEFAULT 100,
    stock_status TEXT NOT NULL DEFAULT 'in_stock',
    sku TEXT NOT NULL DEFAULT '',
    source_id TEXT NOT NULL DEFAULT '',
    source_url TEXT NOT NULL DEFAULT '',
    rating REAL NOT NULL DEFAULT 0,
    review_count INTEGER NOT NULL DEFAULT 0,
    gallery_json TEXT NOT NULL DEFAULT '[]',
    details_json TEXT NOT NULL DEFAULT '{}',
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    desktop_image TEXT NOT NULL,
    mobile_image TEXT NOT NULL DEFAULT '',
    link_url TEXT NOT NULL DEFAULT '#shop',
    alt_text TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hero_banners_two (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    headline TEXT NOT NULL DEFAULT '',
    eyebrow TEXT NOT NULL DEFAULT '',
    subtitle TEXT NOT NULL DEFAULT '',
    desktop_video TEXT NOT NULL DEFAULT '',
    mobile_video TEXT NOT NULL DEFAULT '',
    duration INTEGER NOT NULL DEFAULT 1,
    link_url TEXT NOT NULL DEFAULT '#shop',
    alt_text TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cms_sections (
    section_key TEXT PRIMARY KEY,
    eyebrow TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL DEFAULT '',
    subtitle TEXT NOT NULL DEFAULT '',
    body TEXT NOT NULL DEFAULT '',
    image TEXT NOT NULL DEFAULT '',
    mobile_image TEXT NOT NULL DEFAULT '',
    video_url TEXT NOT NULL DEFAULT '',
    button_label TEXT NOT NULL DEFAULT '',
    button_url TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    initials TEXT NOT NULL DEFAULT '',
    avatar TEXT NOT NULL DEFAULT '',
    product_name TEXT NOT NULL DEFAULT '',
    product_slug TEXT NOT NULL DEFAULT '',
    quote TEXT NOT NULL,
    rating INTEGER NOT NULL DEFAULT 5 CHECK (rating BETWEEN 1 AND 5),
    theme TEXT NOT NULL DEFAULT 'ghee',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS homepage_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    section_key TEXT NOT NULL DEFAULT 'reels',
    media_type TEXT NOT NULL DEFAULT 'image',
    title TEXT NOT NULL DEFAULT '',
    subtitle TEXT NOT NULL DEFAULT '',
    file_path TEXT NOT NULL DEFAULT '',
    poster_path TEXT NOT NULL DEFAULT '',
    external_url TEXT NOT NULL DEFAULT '',
    link_url TEXT NOT NULL DEFAULT '',
    alt_text TEXT NOT NULL DEFAULT '',
    product_slug TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    filter TEXT NOT NULL DEFAULT 'all',
    image TEXT NOT NULL DEFAULT '',
    icon TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blog_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    excerpt TEXT NOT NULL DEFAULT '',
    content TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    source TEXT NOT NULL DEFAULT 'manual',
    ai_provider TEXT NOT NULL DEFAULT '',
    meta_description TEXT NOT NULL DEFAULT '',
    featured_image TEXT NOT NULL DEFAULT '',
    category TEXT NOT NULL DEFAULT 'Wellness',
    author TEXT NOT NULL DEFAULT 'Gawdee editorial',
    is_featured INTEGER NOT NULL DEFAULT 0,
    published_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    order_number TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'pending',
    payment_method TEXT NOT NULL DEFAULT 'razorpay',
    payment_status TEXT NOT NULL DEFAULT 'pending',
    shipment_status TEXT NOT NULL DEFAULT 'not_created',
    currency TEXT NOT NULL DEFAULT 'INR',
    subtotal INTEGER NOT NULL,
    shipping INTEGER NOT NULL DEFAULT 0,
    discount INTEGER NOT NULL DEFAULT 0,
    total INTEGER NOT NULL,
    coupon_code TEXT NOT NULL DEFAULT '',
    checkout_token TEXT NOT NULL DEFAULT '',
    customer_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    address1 TEXT NOT NULL,
    address2 TEXT NOT NULL DEFAULT '',
    city TEXT NOT NULL,
    state TEXT NOT NULL,
    pincode TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '',
    razorpay_order_id TEXT NOT NULL DEFAULT '',
    razorpay_payment_id TEXT NOT NULL DEFAULT '',
    razorpay_signature TEXT NOT NULL DEFAULT '',
    dtdc_reference TEXT NOT NULL DEFAULT '',
    dtdc_tracking_url TEXT NOT NULL DEFAULT '',
    fulfillment_mode TEXT NOT NULL DEFAULT 'manual',
    courier_name TEXT NOT NULL DEFAULT '',
    tracking_number TEXT NOT NULL DEFAULT '',
    tracking_url TEXT NOT NULL DEFAULT '',
    inventory_status TEXT NOT NULL DEFAULT 'not_deducted',
    admin_note TEXT NOT NULL DEFAULT '',
    payment_error TEXT NOT NULL DEFAULT '',
    paid_at TEXT,
    fulfilled_at TEXT,
    cancelled_at TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id TEXT NOT NULL,
    product_name TEXT NOT NULL,
    quantity INTEGER NOT NULL,
    unit_price INTEGER NOT NULL,
    image TEXT NOT NULL DEFAULT '',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS integration_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    integration TEXT NOT NULL,
    action TEXT NOT NULL,
    status TEXT NOT NULL,
    reference TEXT NOT NULL DEFAULT '',
    message TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS product_reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id TEXT NOT NULL,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT NOT NULL,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'approved',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_status_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    status TEXT NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
SQL);
    }

    foreach ([
        'phone' => "TEXT NOT NULL DEFAULT ''",
        'address1' => "TEXT NOT NULL DEFAULT ''",
        'address2' => "TEXT NOT NULL DEFAULT ''",
        'city' => "TEXT NOT NULL DEFAULT ''",
        'state' => "TEXT NOT NULL DEFAULT ''",
        'pincode' => "TEXT NOT NULL DEFAULT ''",
        'updated_at' => "TEXT NOT NULL DEFAULT ''",
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'users', $column, $definition);
    }
    foreach ([
        'stock_status' => "TEXT NOT NULL DEFAULT 'in_stock'",
        'sku' => "TEXT NOT NULL DEFAULT ''",
        'source_id' => "TEXT NOT NULL DEFAULT ''",
        'source_url' => "TEXT NOT NULL DEFAULT ''",
        'rating' => 'REAL NOT NULL DEFAULT 0',
        'review_count' => 'INTEGER NOT NULL DEFAULT 0',
        'gallery_json' => "TEXT NOT NULL DEFAULT '[]'",
        'details_json' => "TEXT NOT NULL DEFAULT '{}'",
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'products', $column, $definition);
    }
    foreach ([
        'image' => "TEXT NOT NULL DEFAULT ''",
        'mobile_image' => "TEXT NOT NULL DEFAULT ''",
        'video_url' => "TEXT NOT NULL DEFAULT ''",
        'button_label' => "TEXT NOT NULL DEFAULT ''",
        'button_url' => "TEXT NOT NULL DEFAULT ''",
        'coupon_code' => "TEXT NOT NULL DEFAULT ''",
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'cms_sections', $column, $definition);
    }
    foreach ([
        'featured_image' => "TEXT NOT NULL DEFAULT ''",
        'category' => "TEXT NOT NULL DEFAULT 'Wellness'",
        'author' => "TEXT NOT NULL DEFAULT 'Gawdee editorial'",
        'is_featured' => 'INTEGER NOT NULL DEFAULT 0',
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'blog_posts', $column, $definition);
    }
    foreach ([
        'eyebrow' => "TEXT NOT NULL DEFAULT ''",
        'subtitle' => "TEXT NOT NULL DEFAULT ''",
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'hero_banners_two', $column, $definition);
    }
    gawdee_ensure_column($db, 'orders', 'user_id', 'INTEGER');
    foreach ([
        'discount' => 'INTEGER NOT NULL DEFAULT 0',
        'coupon_code' => "TEXT NOT NULL DEFAULT ''",
        'checkout_token' => "TEXT NOT NULL DEFAULT ''",
        'fulfillment_mode' => "TEXT NOT NULL DEFAULT 'manual'",
        'courier_name' => "TEXT NOT NULL DEFAULT ''",
        'tracking_number' => "TEXT NOT NULL DEFAULT ''",
        'tracking_url' => "TEXT NOT NULL DEFAULT ''",
        'inventory_status' => "TEXT NOT NULL DEFAULT 'not_deducted'",
        'admin_note' => "TEXT NOT NULL DEFAULT ''",
        'payment_error' => "TEXT NOT NULL DEFAULT ''",
        'paid_at' => 'TEXT',
        'fulfilled_at' => 'TEXT',
        'cancelled_at' => 'TEXT',
    ] as $column => $definition) {
        gawdee_ensure_column($db, 'orders', $column, $definition);
    }

    gawdee_create_index($db, 'products', 'idx_products_category_key', 'category_key');
    gawdee_create_index($db, 'testimonials', 'idx_testimonials_active', 'is_active, sort_order, id');
    gawdee_create_index($db, 'categories', 'idx_categories_active', 'is_active, sort_order, id');
    gawdee_create_index($db, 'homepage_media', 'idx_homepage_media_section', 'section_key, is_active, sort_order, id');
    gawdee_create_index($db, 'blog_posts', 'idx_blog_posts_status', 'status, published_at, id');
    gawdee_create_index($db, 'orders', 'idx_orders_user_id', 'user_id');
    gawdee_create_index($db, 'orders', 'idx_orders_checkout_token', 'checkout_token', true, "checkout_token != ''");
    gawdee_create_index($db, 'orders', 'idx_orders_workflow', 'status, payment_status, shipment_status, id');
    gawdee_create_index($db, 'order_status_events', 'idx_order_status_events_order_id', 'order_id, id');

    if ($driver === 'sqlite') {
        $db->exec('PRAGMA optimize');
    }

    gawdee_seed_defaults($db);
}

function gawdee_seed_defaults(PDO $db): void
{
    $defaults = [
        'store_name' => 'Gawdee',
        'store_email' => 'info@gawdee.com',
        'store_phone' => '+91 70552 07030',
        'currency' => 'INR',
        'free_shipping_threshold' => '999',
        'shipping_fee' => '99',
        'cod_enabled' => '1',
        'dtdc_enabled' => '0',
        'offer_code' => 'FREEDOM10',
        'offer_percent' => '10',
        'offer_popup_enabled' => '1',
        'offer_popup_image' => 'assets/images/independence-offer-popup-v1.webp',
        'offer_popup_title' => 'Independence Day Special',
        'offer_popup_text' => 'Use code %code% at checkout',
        'offer_popup_link' => '#shop',
        'offer_popup_btn_text' => 'Shop offer',
        'offer_popup_delay_ms' => '850',
        'ai_provider' => 'groq',
        'groq_model' => 'llama-3.3-70b-versatile',
        'openai_model' => 'gpt-5.6-luna',
        'ai_chat_enabled' => '1',
        'ai_auto_blog_enabled' => '0',
        'ai_blog_frequency_days' => '7',
        'ai_blog_topics' => 'traditional Indian foods, ingredient transparency, family wellness, mindful nutrition',
        'ai_last_blog_at' => '',
        'razorpay_key_id' => '',
        'dtdc_booking_endpoint' => '',
        'dtdc_tracking_endpoint' => '',
        'dtdc_customer_code' => '',
        'dtdc_service_type' => 'EXPRESS',
        'dtdc_pickup_pincode' => '',
    ];

    $insert = $db->prepare('INSERT OR IGNORE INTO settings (setting_key, setting_value, is_secret) VALUES (?, ?, 0)');
    foreach ($defaults as $key => $value) {
        $insert->execute([$key, $value]);
    }

    $sections = [
        ['hero', 'Featured collection', 'Pure food. Beautifully made.', 'Explore seasonal offers and family wellness favourites.', '', 1, 10],
        ['shop', 'Everyday favourites', 'Bestsellers', 'Handpicked products for everyday family routines.', '', 1, 20],
        ['categories', 'Browse the pantry', 'Shop by category', 'Find the right products for your daily rituals.', '', 1, 30],
        ['offer', 'Independence Day offer', 'Flat 10% OFF', 'On all products. Use code FREEDOM10 at checkout.', 'Celebrate with better everyday wellness.', 1, 40],
        ['combos', 'Thoughtful bundles', 'Healthy combos. Greater savings.', 'Pairs designed to make everyday wellness simpler.', '', 1, 50],
        ['about', 'Rooted in purity', 'Inspired by nature', 'A wholesome journey from earth to plate.', 'We bring pure A2 Gir Cow Ghee, natural honey, grain foods and wellness products made with care, authenticity and village-inspired goodness.', 1, 60],
        ['why', 'The Gawdee difference', 'Why choose Gawdee', 'Purity, tradition and nutrition for a healthier lifestyle.', '', 1, 70],
        ['reviews', 'Customer stories', 'Loved by families who choose purity daily', 'Real words from customers who value authentic taste and thoughtful quality.', '', 1, 80],
        ['stories', 'Gawdee journal', 'Stories for a more thoughtful table', 'Ideas, traditions and ingredient knowledge for everyday wellness.', '', 1, 90],
        ['reels', 'Made with care', 'From nature to your plate', 'A closer look at the products and people behind Gawdee.', '', 1, 100],
        ['newsletter', 'Stay close to goodness', 'Be the first to know!', 'Subscribe for special offers, health tips and updates.', '', 1, 110],
    ];
    $insertSection = $db->prepare('INSERT OR IGNORE INTO cms_sections (section_key, eyebrow, title, subtitle, body, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($sections as $section) {
        $insertSection->execute($section);
    }
    $db->exec("UPDATE cms_sections SET image='assets/images/independence-day-offer-banner-v1.png', mobile_image='assets/images/independence-day-offer-banner-mobile-v1.png', button_label='Shop offer', button_url='#shop' WHERE section_key='offer' AND image=''");
    $db->exec("UPDATE cms_sections SET image='assets/images/blogs/blog-tree-laptop-reference-v1.png', button_label='View more', button_url='blog.php' WHERE section_key='stories' AND image=''");
    $db->exec("UPDATE cms_sections SET button_label='View all products', button_url='products.php' WHERE section_key='shop' AND button_label=''");
    $db->exec("UPDATE cms_sections SET button_label='Subscribe', button_url='#' WHERE section_key='newsletter' AND button_label=''");

    if ((int) $db->query('SELECT COUNT(*) FROM banners')->fetchColumn() === 0) {
        $insertBanner = $db->prepare('INSERT INTO banners (title, desktop_image, mobile_image, link_url, alt_text, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
        $insertBanner->execute(['Independence Day wellness offer', 'assets/images/hero-slide-independence-v5.webp', 'assets/images/hero-slide-independence-mobile-v5.webp', '#shop', 'Happy Independence Day. Flat 10% off with code FREEDOM10. Featuring exact Gawdee A2 Gir Cow Ghee, Burra Sugar and MixMe Choco packs.', 10]);
        $insertBanner->execute(['Traditional A2 Ghee', 'assets/images/hero-slide-ghee-v5.webp', 'assets/images/hero-slide-ghee-mobile-v5.webp', 'product.php?slug=gawdee-gir-cow-a2-ghee-500-ml', 'Freedom to choose pure tradition with Gawdee Bilona-crafted A2 Gir Cow Ghee.', 20]);
        $insertBanner->execute(['MixMe daily nutrition', 'assets/images/hero-slide-mixme-v5.webp', 'assets/images/hero-slide-mixme-mobile-v5.webp', 'product.php?slug=gawdee-mixme-choco-500-g', 'Celebrate everyday wellness with Gawdee MixMe Choco.', 30]);
    }

    if (gawdee_setting('hero_banners_two_seeded', '0') !== '1') {
        if ((int) $db->query('SELECT COUNT(*) FROM hero_banners_two')->fetchColumn() === 0) {
            $insertBannerTwo = $db->prepare('INSERT INTO hero_banners_two (title, headline, desktop_video, mobile_video, duration, link_url, alt_text, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $insertBannerTwo->execute(['Pure Food Harvest', 'PURE FOOD. BETTER EVERYDAY.', 'assets/uploads/hero/hero-38c236d09817bc8426.mp4', 'assets/uploads/hero/hero-38c236d09817bc8426.mp4', 1, '#shop', 'Pure Food Better Everyday', 10]);
            $insertBannerTwo->execute(['Organic Goodness', 'ORGANIC GOODNESS. PURE HARVEST.', 'assets/uploads/hero/hero-a3f9a6cc6ea5b3c2eb.mp4', 'assets/uploads/hero/hero-a3f9a6cc6ea5b3c2eb.mp4', 1, '#shop', 'Organic Goodness Pure Harvest', 20]);
            $insertBannerTwo->execute(['Soulful Wellness', 'SOULFUL WELLNESS. NATURALLY CRAFTED.', 'assets/uploads/hero/hero-38c236d09817bc8426.mp4', 'assets/uploads/hero/hero-38c236d09817bc8426.mp4', 1, '#shop', 'Soulful Wellness Naturally Crafted', 30]);
            $insertBannerTwo->execute(['Handpicked Ingredients', 'HANDPICKED INGREDIENTS. HONEST TASTE.', 'assets/uploads/hero/hero-a3f9a6cc6ea5b3c2eb.mp4', 'assets/uploads/hero/hero-a3f9a6cc6ea5b3c2eb.mp4', 1, '#shop', 'Handpicked Ingredients Honest Taste', 40]);
            $insertBannerTwo->execute(['Traditional Recipes', 'TRADITIONAL RECIPES. MODERN LIVING.', 'assets/uploads/hero/hero-38c236d09817bc8426.mp4', 'assets/uploads/hero/hero-a3f9a6cc6ea5b3c2eb.mp4', 1, '#shop', 'Traditional Recipes Modern Living', 50]);
        }
        gawdee_set_setting('hero_banners_two_seeded', '1');
    }

    if ((int) $db->query('SELECT COUNT(*) FROM testimonials')->fetchColumn() === 0) {
        $insertTestimonial = $db->prepare('INSERT INTO testimonials (name, initials, avatar, product_name, product_slug, quote, rating, theme, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertTestimonial->execute(['Neha Shah', 'NS', 'assets/images/testimonials/neha-shah.png', 'Raw Forest Honey', 'gawdee-raw-wild-forest-honey-650-g', 'What I love most is that the honey tastes naturally rich without feeling overly processed or artificially sweet.', 5, 'honey', 10]);
        $insertTestimonial->execute(['Pooja Desai', 'PD', 'assets/images/testimonials/pooja-desai.png', 'A2 Gir Cow Ghee', 'gawdee-gir-cow-a2-ghee-500-ml', 'The aroma feels beautifully traditional, and it has become a trusted part of our family meals.', 5, 'ghee', 20]);
        $insertTestimonial->execute(['Ritu Sharma', 'RS', '', 'MixMe Choco', 'gawdee-mixme-choco-500-g', 'A simple way to add better everyday nutrition. My children genuinely enjoy the flavour.', 5, 'mixme', 30]);
        $insertTestimonial->execute(['Ananya Rao', 'AR', '', 'Moringa Powder', 'gawdee-moringa-powder-300-g', 'Clean, convenient and easy to add to my routine. The ingredient story gives me confidence.', 5, 'moringa', 40]);
    }

    if ((int) $db->query('SELECT COUNT(*) FROM homepage_media')->fetchColumn() === 0) {
        $insertMedia = $db->prepare('INSERT INTO homepage_media (section_key, media_type, title, subtitle, file_path, link_url, alt_text, product_slug, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertMedia->execute(['reels', 'image', 'Pure A2 Ghee', 'Bilona-crafted everyday goodness', 'assets/images/products/ghee-500.webp', 'product.php?slug=gawdee-gir-cow-a2-ghee-500-ml', 'Gawdee A2 Gir Cow Ghee', 'gawdee-gir-cow-a2-ghee-500-ml', 10]);
        $insertMedia->execute(['reels', 'image', 'Raw Forest Honey', 'Naturally rich and thoughtfully sourced', 'assets/images/products/forest-honey.webp', 'product.php?slug=gawdee-raw-wild-forest-honey-650-g', 'Gawdee Raw Forest Honey', 'gawdee-raw-wild-forest-honey-650-g', 20]);
        $insertMedia->execute(['reels', 'image', 'MixMe Choco', 'Family nutrition made delicious', 'assets/images/products/mixme-choco.webp', 'product.php?slug=gawdee-mixme-choco-500-g', 'Gawdee MixMe Choco', 'gawdee-mixme-choco-500-g', 30]);
    }
}

function gawdee_seed_products(array $seedProducts): void
{
    $db = gawdee_db();
    $insert = $db->prepare(<<<'SQL'
INSERT OR IGNORE INTO products
(id, slug, name, full_name, category, category_key, tag, price, original_price, weight, image, description, accent)
VALUES (:id, :slug, :name, :full_name, :category, :category_key, :tag, :price, :original_price, :weight, :image, :description, :accent)
SQL);
    foreach ($seedProducts as $product) {
        $insert->execute($product);
    }
}

function gawdee_products(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM products' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY created_at, name';
    $rows = gawdee_db()->query($sql)->fetchAll();
    return array_map(static function (array $row): array {
        $row['price'] = (int) $row['price'];
        $row['original_price'] = (int) $row['original_price'];
        $row['stock'] = (int) $row['stock'];
        $row['rating'] = (float) ($row['rating'] ?? 0);
        $row['review_count'] = (int) ($row['review_count'] ?? 0);
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, $rows);
}

function gawdee_product_by_id(string $id): ?array
{
    $statement = gawdee_db()->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
    $statement->execute([$id]);
    $row = $statement->fetch();
    if (!$row) {
        return null;
    }
    $row['price'] = (int) $row['price'];
    $row['original_price'] = (int) $row['original_price'];
    $row['stock'] = (int) ($row['stock'] ?? 0);
    $row['rating'] = (float) ($row['rating'] ?? 0);
    $row['review_count'] = (int) ($row['review_count'] ?? 0);
    return $row;
}

function gawdee_product_reviews(string $productId): array
{
    $statement = gawdee_db()->prepare("SELECT id, product_id, rating, review, name, created_at FROM product_reviews WHERE product_id = ? AND status = 'approved' ORDER BY id DESC");
    $statement->execute([$productId]);
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['rating'] = (int) $row['rating'];
        return $row;
    }, $statement->fetchAll());
}

function gawdee_setting(string $key, string $default = ''): string
{
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $statement = gawdee_db()->prepare('SELECT setting_value, is_secret FROM settings WHERE setting_key = ?');
    $statement->execute([$key]);
    $row = $statement->fetch();
    if (!$row) {
        return $default;
    }
    $value = (int) $row['is_secret'] === 1 ? gawdee_decrypt((string) $row['setting_value']) : (string) $row['setting_value'];
    return $cache[$key] = $value;
}

function gawdee_set_setting(string $key, string $value, bool $secret = false): void
{
    $stored = $secret ? gawdee_encrypt($value) : $value;
    $statement = gawdee_db()->prepare(<<<'SQL'
INSERT INTO settings (setting_key, setting_value, is_secret, updated_at)
VALUES (?, ?, ?, CURRENT_TIMESTAMP)
ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, is_secret = excluded.is_secret, updated_at = CURRENT_TIMESTAMP
SQL);
    $statement->execute([$key, $stored, $secret ? 1 : 0]);
}

function gawdee_secret_key(): string
{
    $environmentKey = getenv('GAWDEE_APP_KEY');
    if (is_string($environmentKey) && $environmentKey !== '') {
        return hash('sha256', $environmentKey, true);
    }
    $path = GAWDEE_STORAGE . '/.app_key';
    if (!is_file($path)) {
        file_put_contents($path, base64_encode(random_bytes(32)), LOCK_EX);
        @chmod($path, 0600);
    }
    $decoded = base64_decode(trim((string) file_get_contents($path)), true);
    if ($decoded === false || strlen($decoded) < 32) {
        throw new RuntimeException('Application encryption key is invalid.');
    }
    return $decoded;
}

function gawdee_encrypt(string $plainText): string
{
    if ($plainText === '') {
        return '';
    }
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plainText, 'aes-256-gcm', gawdee_secret_key(), OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipher === false) {
        throw new RuntimeException('Unable to encrypt the setting.');
    }
    return base64_encode(json_encode([
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
        'data' => base64_encode($cipher),
    ], JSON_THROW_ON_ERROR));
}

function gawdee_decrypt(string $payload): string
{
    if ($payload === '') {
        return '';
    }
    try {
        $decoded = json_decode((string) base64_decode($payload, true), true, 512, JSON_THROW_ON_ERROR);
        $plain = openssl_decrypt(
            (string) base64_decode((string) $decoded['data'], true),
            'aes-256-gcm',
            gawdee_secret_key(),
            OPENSSL_RAW_DATA,
            (string) base64_decode((string) $decoded['iv'], true),
            (string) base64_decode((string) $decoded['tag'], true)
        );
        return $plain === false ? '' : $plain;
    } catch (Throwable) {
        return '';
    }
}

function gawdee_sections(): array
{
    $rows = gawdee_db()->query('SELECT * FROM cms_sections ORDER BY sort_order, section_key')->fetchAll();
    $sections = [];
    foreach ($rows as $row) {
        $row['is_active'] = (int) $row['is_active'];
        $sections[$row['section_key']] = $row;
    }
    return $sections;
}

function gawdee_section(string $key): array
{
    $sections = gawdee_sections();
    return $sections[$key] ?? ['section_key' => $key, 'eyebrow' => '', 'title' => '', 'subtitle' => '', 'body' => '', 'image' => '', 'mobile_image' => '', 'video_url' => '', 'button_label' => '', 'button_url' => '', 'coupon_code' => '', 'is_active' => 1, 'sort_order' => 0];
}

function gawdee_banners(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM banners' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY sort_order, id';
    return gawdee_db()->query($sql)->fetchAll();
}

function gawdee_hero_banners_two(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM hero_banners_two' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY sort_order, id';
    return gawdee_db()->query($sql)->fetchAll();
}

function gawdee_testimonials(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM testimonials' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY sort_order, id';
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['rating'] = min(5, max(1, (int) $row['rating']));
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, gawdee_db()->query($sql)->fetchAll());
}

function gawdee_categories(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM categories' . ($includeInactive ? '' : ' WHERE is_active = 1') . ' ORDER BY sort_order, id';
    $rows = gawdee_db()->query($sql)->fetchAll();
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, $rows);
}

function gawdee_category_by_id(int $id): ?array
{
    $stmt = gawdee_db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['id'] = (int) $row['id'];
    $row['sort_order'] = (int) $row['sort_order'];
    $row['is_active'] = (int) $row['is_active'];
    return $row;
}

function gawdee_homepage_media(?string $sectionKey = null, bool $includeInactive = false): array
{
    $conditions = [];
    $values = [];
    if ($sectionKey !== null) {
        $conditions[] = 'section_key = ?';
        $values[] = $sectionKey;
    }
    if (!$includeInactive) {
        $conditions[] = 'is_active = 1';
    }
    $sql = 'SELECT * FROM homepage_media' . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY section_key, sort_order, id';
    $statement = gawdee_db()->prepare($sql);
    $statement->execute($values);
    return array_map(static function (array $row): array {
        $row['id'] = (int) $row['id'];
        $row['sort_order'] = (int) $row['sort_order'];
        $row['is_active'] = (int) $row['is_active'];
        return $row;
    }, $statement->fetchAll());
}

function gawdee_has_admin(): bool
{
    return (int) gawdee_db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
}

function gawdee_admin(): ?array
{
    if (empty($_SESSION['admin_user_id'])) {
        return null;
    }
    $statement = gawdee_db()->prepare('SELECT id, name, email, role, last_login_at FROM users WHERE id = ?');
    $statement->execute([(int) $_SESSION['admin_user_id']]);
    return $statement->fetch() ?: null;
}

function gawdee_require_admin(): array
{
    $admin = gawdee_admin();
    if (!$admin) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

function gawdee_customer(): ?array
{
    if (empty($_SESSION['customer_user_id'])) {
        return null;
    }
    $statement = gawdee_db()->prepare("SELECT id, name, email, role, phone, address1, address2, city, state, pincode, created_at, last_login_at FROM users WHERE id = ? AND role = 'customer'");
    $statement->execute([(int) $_SESSION['customer_user_id']]);
    return $statement->fetch() ?: null;
}

function gawdee_require_customer(string $returnTo = 'account.php'): array
{
    $customer = gawdee_customer();
    if ($customer) {
        return $customer;
    }
    header('Location: login.php?return=' . rawurlencode(gawdee_safe_return_path($returnTo)));
    exit;
}

function gawdee_safe_return_path(string $path, string $fallback = 'account.php'): string
{
    $path = trim($path);
    if ($path === '' || str_contains($path, '://') || str_starts_with($path, '//') || str_contains($path, "\r") || str_contains($path, "\n")) {
        return $fallback;
    }
    return ltrim($path, '/');
}

function gawdee_customer_orders(int $userId): array
{
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function gawdee_customer_order(int $userId, string $orderNumber): ?array
{
    $statement = gawdee_db()->prepare('SELECT * FROM orders WHERE user_id = ? AND order_number = ?');
    $statement->execute([$userId, $orderNumber]);
    $order = $statement->fetch();
    if (!$order) {
        return null;
    }
    $items = gawdee_db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
    $items->execute([(int) $order['id']]);
    $events = gawdee_db()->prepare('SELECT * FROM order_status_events WHERE order_id = ? ORDER BY id');
    $events->execute([(int) $order['id']]);
    $order['items'] = $items->fetchAll();
    $order['events'] = $events->fetchAll();
    return $order;
}

function gawdee_record_order_event(int $orderId, string $status, string $title, string $description = ''): void
{
    $statement = gawdee_db()->prepare('INSERT INTO order_status_events (order_id, status, title, description) VALUES (?, ?, ?, ?)');
    $statement->execute([$orderId, $status, $title, $description]);
}

function gawdee_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return (string) $_SESSION['csrf_token'];
}

function gawdee_verify_csrf(?string $token): void
{
    if (!$token || !hash_equals(gawdee_csrf_token(), $token)) {
        throw new RuntimeException('Your session expired. Refresh the page and try again.');
    }
}

function gawdee_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-') ?: 'post-' . date('Ymd-His');
}

function gawdee_json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function gawdee_request_json(): array
{
    $payload = json_decode((string) file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}

function gawdee_log_integration(string $integration, string $action, string $status, string $message = '', string $reference = ''): void
{
    $statement = gawdee_db()->prepare('INSERT INTO integration_logs (integration, action, status, reference, message) VALUES (?, ?, ?, ?, ?)');
    $statement->execute([$integration, $action, $status, $reference, mb_substr($message, 0, 1500)]);
}

function gawdee_mark_order_paid(int $orderId, string $paymentId = '', string $signature = ''): void
{
    $db = gawdee_db();
    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT payment_status, inventory_status FROM orders WHERE id = ?');
        $statement->execute([$orderId]);
        $order = $statement->fetch();
        if (!$order) {
            throw new RuntimeException('Order not found.');
        }
        if ($order['payment_status'] !== 'paid') {
            if ($order['inventory_status'] !== 'reserved' && $order['inventory_status'] !== 'deducted') {
                $items = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
                $items->execute([$orderId]);
                $orderItems = $items->fetchAll();
                foreach ($orderItems as $item) {
                    $stock = $db->prepare('SELECT stock FROM products WHERE id = ?');
                    $stock->execute([$item['product_id']]);
                    if ((int) $stock->fetchColumn() < (int) $item['quantity']) {
                        $db->prepare("UPDATE orders SET payment_status='paid', status='on_hold', payment_error='Payment received, but stock needs manual review.', paid_at=CURRENT_TIMESTAMP, cancelled_at=NULL, razorpay_payment_id=CASE WHEN ?='' THEN razorpay_payment_id ELSE ? END, razorpay_signature=CASE WHEN ?='' THEN razorpay_signature ELSE ? END, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                            ->execute([$paymentId, $paymentId, $signature, $signature, $orderId]);
                        gawdee_record_order_event($orderId, 'on_hold', 'Payment received — stock review needed', 'Payment is secure, but fulfilment needs an inventory check by the store team.');
                        $db->commit();
                        return;
                    }
                }
                $reduce = $db->prepare("UPDATE products SET stock = stock - ?, stock_status = CASE WHEN stock - ? <= 0 THEN 'out_of_stock' ELSE 'in_stock' END WHERE id = ?");
                foreach ($orderItems as $item) {
                    $reduce->execute([(int) $item['quantity'], (int) $item['quantity'], $item['product_id']]);
                }
            }
            $update = $db->prepare("UPDATE orders SET payment_status='paid', status='processing', shipment_status='awaiting_fulfillment', inventory_status='deducted', payment_error='', paid_at=CURRENT_TIMESTAMP, cancelled_at=NULL, razorpay_payment_id=CASE WHEN ?='' THEN razorpay_payment_id ELSE ? END, razorpay_signature=CASE WHEN ?='' THEN razorpay_signature ELSE ? END, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $update->execute([$paymentId, $paymentId, $signature, $signature, $orderId]);
            gawdee_record_order_event($orderId, 'processing', 'Payment confirmed', 'Secure online payment was verified and the order moved to processing.');
        } elseif ($paymentId !== '' || $signature !== '') {
            $update = $db->prepare("UPDATE orders SET razorpay_payment_id=CASE WHEN ?='' THEN razorpay_payment_id ELSE ? END, razorpay_signature=CASE WHEN ?='' THEN razorpay_signature ELSE ? END, updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $update->execute([$paymentId, $paymentId, $signature, $signature, $orderId]);
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function gawdee_base_url(string $path = ''): string
{
    $envUrl = gawdee_env('APP_URL');
    if (!empty($envUrl)) {
        $baseUrl = rtrim($envUrl, '/');
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptName));
        $dir = preg_replace('#/admin$#', '', $dir);
        $basePath = ($dir === '/' || $dir === '.') ? '' : $dir;
        $baseUrl = $scheme . '://' . $host . $basePath;
    }
    return $path !== '' ? $baseUrl . '/' . ltrim($path, '/') : $baseUrl;
}

gawdee_db();
