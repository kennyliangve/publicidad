<?php
/**
 * 网站入口（兼容无 mod_rewrite 的虚拟主机）
 */
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$uri    = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// API 请求 → 转交 api/index.php
if (preg_match('#/api(/|$)#', $uri)) {
    require __DIR__ . '/api/index.php';
    exit;
}

// 健康检查
if (preg_match('#/health(\.php)?$#', $uri)) {
    require __DIR__ . '/health.php';
    exit;
}

// 安装向导
if (preg_match('#/install\.php$#', $uri)) {
    require __DIR__ . '/install.php';
    exit;
}

// 静态资源 /assets/*
if (preg_match('#/assets/(.+)$#', $uri, $m)) {
    $file = __DIR__ . '/dist/assets/' . basename($m[1]);
    if (is_file($file)) {
        $types = [
            'js'  => 'application/javascript',
            'css' => 'text/css',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'woff2' => 'font/woff2',
        ];
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000');
        readfile($file);
        exit;
    }
}

// uploads 目录
if (preg_match('#/uploads/(.+)$#', $uri, $m)) {
    $file = __DIR__ . '/uploads/' . basename($m[1]);
    if (is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
}

// SPA 前端页面
$indexHtml = __DIR__ . '/dist/index.html';
if (is_file($indexHtml)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($indexHtml);
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><body><h1>404 Not Found</h1><p>请确认 dist/ 目录已上传，或访问 <a href="health.php">health.php</a> 检查环境。</p></body></html>';
