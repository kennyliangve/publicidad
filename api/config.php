<?php
/**
 * 数据库及系统配置
 *
 * base_path 说明：
 *   ''            = 网站部署在域名根目录（如 https://example.com/）
 *   '/publicidad' = 部署在子目录（如 https://example.com/publicidad/）
 */
$config = [
    'base_path'   => '/publicidad',
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'dbname'   => 'vecinoco_1688',
        'username' => 'vecinoco_1688',
        'password' => 'Kenny88.',
        'charset'  => 'utf8mb4',
    ],
    'jwt_secret'  => 'publicidad_secret_key_change_in_production',
    'upload_path' => __DIR__ . '/../uploads/',
    'logo_path'   => __DIR__ . '/../logo/',
    'cors_origin' => '*',
    'auto_migrate'  => true,   // 连接数据库时自动执行 api/migrations/ 中的新迁移
    'migrate_secret' => 'pub_migrate_2026',  // POST /api/migrate?key=xxx 手动触发
];

// 自动检测部署路径（当 base_path 为空时）
if ($config['base_path'] === '' && php_sapi_name() !== 'cli') {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = realpath(dirname(__DIR__));
    if ($docRoot && $appRoot && strpos($appRoot, $docRoot) === 0) {
        $detected = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        $config['base_path'] = $detected ?: '';
    }
}

$config['upload_url'] = rtrim($config['base_path'], '/') . '/uploads/';
$config['logo_url'] = rtrim($config['base_path'], '/') . '/logo/';

return $config;
