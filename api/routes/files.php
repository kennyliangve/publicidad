<?php

require_once __DIR__ . '/../helpers.php';

/** GET /files/{filename} — 经 API 输出图片，URL 不含 uploads，避免广告拦截 */
function handleFiles(string $method, ?string $filename): void
{
    if ($method !== 'GET' || !$filename) {
        jsonError('Not found', 404);
    }

    $filename = basename($filename);
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
        jsonError('Not found', 404);
    }

    $config = require __DIR__ . '/../config.php';
    $file = $config['upload_path'] . $filename;

    if (!is_file($file)) {
        jsonError('Not found', 404);
    }

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $types = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
    ];

    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=31536000');
    readfile($file);
    exit;
}
