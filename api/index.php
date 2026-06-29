<?php
/**
 * 网站统一入口（SPA 前端 + REST API）
 * 访问: /publicidad/ 或 /publicidad/api/index.php/...
 */
$root = dirname(__DIR__);
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$uri  = rtrim($uri, '/') ?: '/';

// REST API → api/router.php
if (preg_match('#/api(/|$)#', $uri) && !preg_match('#/api/install\.php$#', $uri)) {
    require __DIR__ . '/router.php';
    exit;
}

// 健康检查（兼容旧路径 /health.php）
if (preg_match('#/health(\.php)?$#', $uri)) {
    require_once __DIR__ . '/routes/health.php';
    handleHealth();
    exit;
}

// 静态资源 /assets/*
if (preg_match('#/assets/(.+)$#', $uri, $m)) {
    $file = $root . '/dist/assets/' . basename($m[1]);
    if (is_file($file)) {
        $types = [
            'js'    => 'application/javascript',
            'css'   => 'text/css',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'svg'   => 'image/svg+xml',
            'woff2' => 'font/woff2',
        ];
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000');
        readfile($file);
        exit;
    }
}

// 用户上传图片 uploads/
if (preg_match('#/(?:uploads|media)/(.+)$#', $uri, $m)) {
    $file = $root . '/uploads/' . basename($m[1]);
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
}

// 系统 Logo logo/
if (preg_match('#/logo/(.+)$#', $uri, $m)) {
    $file = $root . '/logo/' . basename($m[1]);
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000');
        readfile($file);
        exit;
    }
}

// SPA 前端（触发数据库自动迁移）
require_once __DIR__ . '/db.php';
Database::getConnection();

$indexHtml = $root . '/dist/index.html';
if (is_file($indexHtml)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($indexHtml);
    exit;
}

$config = require __DIR__ . '/config.php';
$base = rtrim($config['base_path'] ?? '/publicidad', '/');
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><body><h1>404 Not Found</h1><p>请确认 dist/ 目录已上传，或访问 <a href="' . htmlspecialchars($base) . '/api/install.php">api/install.php</a> 检查环境。</p></body></html>';
