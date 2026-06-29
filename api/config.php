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
    'cors_origin' => '*',
    'auto_migrate'  => true,                          // API 请求时自动执行待运行迁移
    'migrate_secret' => 'pub_migrate_2026',           // 手动触发迁移的密钥
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

return $config;
