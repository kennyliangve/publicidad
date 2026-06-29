<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Migrator.php';
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';

corsHeaders();

require_once __DIR__ . '/helpers/vip.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'OPTIONS') {
    expireVipUsers(Database::getConnection());
}

$segments = parseApiUri() ? explode('/', parseApiUri()) : [];

$method = $_SERVER['REQUEST_METHOD'];
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

// 特殊路由: posts/my
if ($resource === 'posts' && ($segments[1] ?? '') === 'my') {
    $id = null;
    $action = 'my';
}

try {
    switch ($resource) {
        case 'categories':
            require __DIR__ . '/routes/categories.php';
            handleCategories($method, $id);
            break;

        case 'auth':
            require __DIR__ . '/routes/auth.php';
            handleAuth($method, $id);
            break;

        case 'posts':
            require __DIR__ . '/routes/posts.php';
            handlePosts($method, $id, $action);
            break;

        case 'upload':
            require __DIR__ . '/routes/upload.php';
            if (($segments[1] ?? '') === 'logo') {
                handleUploadLogo($method);
            } else {
                handleUpload($method);
            }
            break;

        case 'files':
            require __DIR__ . '/routes/files.php';
            handleFiles($method, $id);
            break;

        case 'health':
            require __DIR__ . '/routes/health.php';
            handleHealth();
            break;

        case 'migrate':
            require __DIR__ . '/routes/migrate.php';
            handleMigrate($method);
            break;

        case 'admin':
            require __DIR__ . '/routes/admin.php';
            handleAdmin($method, $id, $action);
            break;

        case 'settings':
            require __DIR__ . '/routes/settings.php';
            handleSettings($method, $id);
            break;

        case 'vip':
            require __DIR__ . '/routes/vip.php';
            handleVip($method, $id);
            break;

        case '':
            jsonSuccess(['name' => '信息分类网 API', 'version' => '1.0']);
            break;

        default:
            jsonError('Not found', 404);
    }
} catch (PDOException $e) {
    jsonError('数据库错误: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}
