<?php
return [
    'version'     => '004',
    'description' => '扩展 users 表管理字段（角色、状态、资料、登录统计）',

    'up' => function (PDO $db) {
        $columns = [
            'role' => "ADD COLUMN role TINYINT UNSIGNED DEFAULT 0 COMMENT '0=普通用户 1=管理员' AFTER avatar",
            'status' => "ADD COLUMN status TINYINT DEFAULT 1 COMMENT '0=禁用 1=正常 2=待审核' AFTER role",
            'gender' => "ADD COLUMN gender TINYINT DEFAULT 0 COMMENT '0=保密 1=男 2=女' AFTER status",
            'real_name' => "ADD COLUMN real_name VARCHAR(50) DEFAULT NULL COMMENT '真实姓名' AFTER gender",
            'bio' => "ADD COLUMN bio VARCHAR(255) DEFAULT NULL COMMENT '个人简介' AFTER real_name",
            'province' => "ADD COLUMN province VARCHAR(50) DEFAULT NULL AFTER bio",
            'city' => "ADD COLUMN city VARCHAR(50) DEFAULT NULL AFTER province",
            'district' => "ADD COLUMN district VARCHAR(50) DEFAULT NULL AFTER city",
            'register_ip' => "ADD COLUMN register_ip VARCHAR(45) DEFAULT NULL AFTER district",
            'register_source' => "ADD COLUMN register_source VARCHAR(20) DEFAULT 'web' COMMENT '注册来源' AFTER register_ip",
            'last_login_at' => "ADD COLUMN last_login_at DATETIME DEFAULT NULL AFTER register_source",
            'last_login_ip' => "ADD COLUMN last_login_ip VARCHAR(45) DEFAULT NULL AFTER last_login_at",
            'login_count' => "ADD COLUMN login_count INT UNSIGNED DEFAULT 0 AFTER last_login_ip",
        ];

        foreach ($columns as $name => $sql) {
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE '$name'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE users $sql");
            }
        }

        // 测试账号设为管理员
        $db->exec("UPDATE users SET role = 1 WHERE phone = '13800138000' AND role = 0");
        // 若无任何管理员，将首个用户设为管理员
        $adminCount = (int)$db->query('SELECT COUNT(*) FROM users WHERE role = 1')->fetchColumn();
        if ($adminCount === 0) {
            $db->exec('UPDATE users SET role = 1 ORDER BY id ASC LIMIT 1');
        }
    },
];
