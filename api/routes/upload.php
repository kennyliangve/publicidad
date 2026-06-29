<?php



require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../helpers/user.php';
require_once __DIR__ . '/../bootstrap.php';



/** 校验并保存图片到指定目录 */

function saveUploadedImage(array $file, string $dir, string $urlBase, int $maxBytes, string $prefix = ''): array

{

    if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {

        jsonError('上传失败');

    }



    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime = finfo_file($finfo, $file['tmp_name']);

    finfo_close($finfo);



    if (!in_array($mime, $allowed, true)) {

        jsonError('仅支持 JPG/PNG/GIF/WEBP 格式');

    }



    if (($file['size'] ?? 0) > $maxBytes) {

        $mb = (int)ceil($maxBytes / (1024 * 1024));

        jsonError("文件不能超过 {$mb}MB");

    }



    if (!is_dir($dir)) {

        mkdir($dir, 0755, true);

    }



    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';

    $filename = $prefix . date('Ymd') . '_' . uniqid() . '.' . $ext;

    $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;



    if (!move_uploaded_file($file['tmp_name'], $dest)) {

        jsonError('保存文件失败');

    }



    $url = rtrim($urlBase, '/') . '/' . $filename;

    $payload = ['url' => $url];

    $origin = getPublicOrigin();

    if ($origin !== '') {

        $payload['full_url'] = $origin . $url;

    }



    return $payload;

}



function handleUpload(string $method): void

{

    if ($method !== 'POST') {

        jsonError('Method not allowed', 405);

    }



    $user = requireAuthUser();

    if (!userCanUploadPostImages((int)($user['role'] ?? 0), $user)) {

        jsonError('仅 VIP 及以上用户可上传图片', 403);

    }



    if (!isset($_FILES['file'])) {

        jsonError('上传失败');

    }



    $config = require __DIR__ . '/../config.php';

    $payload = saveUploadedImage(

        $_FILES['file'],

        $config['upload_path'],

        rtrim($config['upload_url'], '/'),

        5 * 1024 * 1024

    );



    jsonSuccess($payload);

}



function handleUploadLogo(string $method): void

{

    if ($method !== 'POST') {

        jsonError('Method not allowed', 405);

    }



    requireAuth();



    if (!isset($_FILES['file'])) {

        jsonError('上传失败');

    }



    $config = require __DIR__ . '/../config.php';

    $payload = saveUploadedImage(

        $_FILES['file'],

        $config['logo_path'],

        rtrim($config['logo_url'], '/'),

        2 * 1024 * 1024,

        'logo_'

    );



    jsonSuccess($payload);

}


