<?php
return [
    'version'     => '016',
    'description' => 'posts 表新增 contact_wechat 微信号',

    'up' => function (PDO $db) {
        $stmt = $db->query("SHOW COLUMNS FROM posts LIKE 'contact_wechat'");
        if (!$stmt->fetch()) {
            $db->exec("
                ALTER TABLE posts
                ADD COLUMN contact_wechat VARCHAR(50) DEFAULT NULL COMMENT '微信号' AFTER contact_phone
            ");
        }
    },
];
