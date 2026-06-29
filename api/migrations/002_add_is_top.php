<?php
/**
 * 迁移示例：新增字段
 * 复制此文件并重命名为 003_xxx.php 即可添加新的数据库变更
 */
return [
    'version'     => '002',
    'description' => 'posts 表新增 is_top 置顶字段',

    'up' => function (PDO $db) {
        // 安全添加列：先检查列是否已存在
        $stmt = $db->query("SHOW COLUMNS FROM posts LIKE 'is_top'");
        if (!$stmt->fetch()) {
            $db->exec("
                ALTER TABLE posts
                ADD COLUMN is_top TINYINT DEFAULT 0 COMMENT '1=置顶' AFTER status
            ");
        }
    },
];
