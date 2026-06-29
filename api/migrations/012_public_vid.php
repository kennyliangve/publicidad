<?php
return [
    'version'     => '012',
    'description' => 'users/posts 新增 vid 随机公开 ID',

    'up' => function (PDO $db) {
        require_once __DIR__ . '/../helpers/vid.php';

        foreach (['users', 'posts'] as $table) {
            try {
                $db->exec("ALTER TABLE {$table} ADD COLUMN vid VARCHAR(32) NULL COMMENT '对外公开随机ID' AFTER id");
            } catch (Throwable) {
                // 已存在则跳过
            }
        }

        backfillTableVids($db, 'users');
        backfillTableVids($db, 'posts');

        foreach (['users', 'posts'] as $table) {
            try {
                $db->exec("ALTER TABLE {$table} MODIFY COLUMN vid VARCHAR(32) NOT NULL");
            } catch (Throwable) {
                // ignore
            }
            try {
                $db->exec("ALTER TABLE {$table} ADD UNIQUE INDEX uk_{$table}_vid (vid)");
            } catch (Throwable) {
                // ignore
            }
        }
    },
];
