<?php
return [
    'version'     => '003',
    'description' => 'users 表新增 email 邮箱字段',

    'up' => function (PDO $db) {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'email'");
        if (!$stmt->fetch()) {
            $db->exec("
                ALTER TABLE users
                ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER phone,
                ADD UNIQUE INDEX uk_email (email)
            ");
        }
    },
];
