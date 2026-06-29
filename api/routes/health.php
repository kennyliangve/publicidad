<?php

require_once __DIR__ . '/../db.php';

function handleHealth(): void
{
    $config = require __DIR__ . '/../config.php';
    $checks = [];
    $healthy = true;

    // PHP 运行环境
    $checks['php'] = [
        'status'  => 'ok',
        'version' => PHP_VERSION,
    ];

    // 必需扩展
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    $missingExtensions = [];
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    if ($missingExtensions) {
        $healthy = false;
        $checks['extensions'] = [
            'status'  => 'error',
            'message' => '缺少扩展: ' . implode(', ', $missingExtensions),
        ];
    } else {
        $checks['extensions'] = ['status' => 'ok'];
    }

    // 数据库连接
    try {
        $db = Database::getConnection();
        $checks['database'] = [
            'status'  => 'ok',
            'host'    => $config['db']['host'],
            'name'    => $config['db']['dbname'],
        ];

        // 核心数据表检查
        $requiredTables = ['users', 'categories', 'posts'];
        $stmt = $db->query('SHOW TABLES');
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $missingTables = array_diff($requiredTables, $existingTables);

        if ($missingTables) {
            $healthy = false;
            $checks['database']['tables'] = [
                'status'  => 'error',
                'message' => '缺少数据表: ' . implode(', ', $missingTables),
            ];
        } else {
            $checks['database']['tables'] = ['status' => 'ok'];
        }

        // 迁移状态
        require_once __DIR__ . '/../Migrator.php';
        $migrator = new Migrator($db);
        $migrationStatus = $migrator->getStatus();
        if ($migrationStatus['pending_count'] > 0) {
            $healthy = false;
            $checks['migrations'] = [
                'status'  => 'warning',
                'message' => '有 ' . $migrationStatus['pending_count'] . ' 个待执行迁移',
                'pending' => $migrationStatus['pending'],
            ];
        } else {
            $checks['migrations'] = [
                'status'          => 'ok',
                'current_version' => $migrationStatus['current_version'],
                'applied_count'   => $migrationStatus['applied_count'],
            ];
        }
    } catch (PDOException $e) {
        $healthy = false;
        $checks['database'] = [
            'status'  => 'error',
            'message' => '数据库连接失败',
        ];
    }

    // 上传目录
    $uploadPath = $config['upload_path'];
    if (!is_dir($uploadPath)) {
        @mkdir($uploadPath, 0755, true);
    }
    if (!is_dir($uploadPath) || !is_writable($uploadPath)) {
        $healthy = false;
        $checks['uploads'] = [
            'status'  => 'error',
            'message' => '上传目录不可写',
            'path'    => $uploadPath,
        ];
    } else {
        $checks['uploads'] = [
            'status' => 'ok',
            'path'   => $uploadPath,
        ];
    }

    $response = [
        'status'    => $healthy ? 'healthy' : 'unhealthy',
        'timestamp' => date('c'),
        'service'   => 'publicidad-api',
        'version'   => '1.0',
        'checks'    => $checks,
    ];

    http_response_code($healthy ? 200 : 503);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
