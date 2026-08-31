<?php

declare(strict_types=1);

$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Block access to private directories and hidden files
if (str_starts_with($uri, '/storage/') || str_starts_with($uri, '/includes/') || preg_match('#/(?:\.|\.git)#', $uri)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    return true;
}

// Redirect public .php requests to clean extensionless URLs (301 Permanent Redirect)
if (str_ends_with($uri, '.php') && !str_starts_with($uri, '/api/') && !str_starts_with($uri, '/cron/')) {
    $cleanUri = substr($uri, 0, -4);
    if ($cleanUri === '/index') {
        $cleanUri = '/';
    }
    $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    header('Location: ' . $cleanUri . $queryString, true, 301);
    return true;
}

$path = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);

// Serve static assets directly (CSS, JS, images, favicons, site.webmanifest, llm.text, etc.)
if ($uri !== '/' && is_file($path)) {
    return false;
}

if ($uri === '/' || $uri === '/index') {
    require __DIR__ . '/index.php';
    return true;
}

if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    return true;
}

// Route extensionless URLs to their corresponding .php file
$phpFile = $path . '.php';
if (is_file($phpFile)) {
    require $phpFile;
    return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo 'Not found';
return true;
