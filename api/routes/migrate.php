<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../Migrator.php';
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers.php';

function handleMigrate(string $method): void
{
    $config = getConfig();

    if ($method === 'GET') {
        $db = Database::getConnection();
        $migrator = new Migrator($db);
        jsonSuccess($migrator->getStatus());
    }

    if ($method === 'POST') {
        // 手动触发需提供密钥
        $key = $_GET['key'] ?? '';
        if (empty($config['migrate_secret']) || !hash_equals($config['migrate_secret'], $key)) {
            jsonError('无效的迁移密钥', 403);
        }

        $db = Database::getConnection();
        $migrator = new Migrator($db);
        $results = $migrator->runPending();

        jsonSuccess([
            'applied' => $results,
            'status'  => $migrator->getStatus(),
        ], count($results) ? '迁移完成' : '已是最新版本');
    }

    jsonError('Method not allowed', 405);
}
