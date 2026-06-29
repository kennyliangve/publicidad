<?php

require_once __DIR__ . '/../helpers.php';

function handleUpload(string $method): void
{
    if ($method !== 'POST') {
        jsonError('Method not allowed', 405);
    }

    requireAuth();

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonError('上传失败');
    }

    $file = $_FILES['file'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        jsonError('仅支持 JPG/PNG/GIF/WEBP 格式');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        jsonError('文件不能超过 5MB');
    }

    $config = require __DIR__ . '/../config.php';
    $uploadDir = $config['upload_path'];
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = date('Ymd') . '_' . uniqid() . '.' . $ext;
    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonError('保存文件失败');
    }

    jsonSuccess(['url' => $config['upload_url'] . $filename]);
}
