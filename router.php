<?php

/**
 * Router for PHP built-in server (local only). Mirrors .htaccess CMS catch-all.
 * Usage: php -S localhost:8080 router.php
 */

declare(strict_types=1);

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$path = trim($uri, '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

if ($uri !== '/' && is_dir($file) && is_file(rtrim($file, '/\\') . DIRECTORY_SEPARATOR . 'index.php')) {
    require rtrim($file, '/\\') . DIRECTORY_SEPARATOR . 'index.php';
    return true;
}

if ($path === '' || $path === 'home') {
    require __DIR__ . '/index.php';
    return true;
}

if (preg_match('#^proxy/([A-Za-z0-9_-]+)/?$#', $path, $m)) {
    $proxy = __DIR__ . '/proxy/' . $m[1] . '.php';
    if (is_file($proxy)) {
        require $proxy;
        return true;
    }
}

if (preg_match('#^blog/blog/([A-Za-z0-9_-]+)/?$#i', $path, $m)) {
    header('Location: /blog/' . $m[1], true, 301);
    return true;
}

if (preg_match('#^blog/([A-Za-z0-9_-]+)/?$#i', $path, $m)) {
    $_GET['cms_path'] = 'blog/' . $m[1];
    require __DIR__ . '/cms-page.php';
    return true;
}

if (preg_match('#^view/([A-Za-z0-9_-]+)/?$#i', $path, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/detail-view.php';
    return true;
}

$phpFile = __DIR__ . '/' . $path . '.php';
if ($path !== '' && is_file($phpFile)) {
    require $phpFile;
    return true;
}

if ($path !== '' && preg_match('#^[A-Za-z0-9_-]+$#', $path)) {
    $_GET['cms_path'] = $path;
    require __DIR__ . '/cms-page.php';
    return true;
}

http_response_code(404);
if (is_file(__DIR__ . '/404.php')) {
    require __DIR__ . '/404.php';
} else {
    echo '404 Not Found';
}

return true;
