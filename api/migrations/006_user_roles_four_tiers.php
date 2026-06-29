<?php
return [
    'version'     => '006',
    'description' => '用户角色四级体系（普通/审核员/管理员/超级管理员）',

    'up' => function (PDO $db) {
        // 旧版 role=1 表示管理员，升级为 role=2
        $db->exec('UPDATE users SET role = 2 WHERE role = 1');

        // 确保至少有一名超级管理员
        $superCount = (int)$db->query('SELECT COUNT(*) FROM users WHERE role = 3')->fetchColumn();
        if ($superCount === 0) {
            $stmt = $db->query('SELECT id FROM users WHERE role = 2 ORDER BY id ASC LIMIT 1');
            $admin = $stmt->fetch();
            if ($admin) {
                $db->prepare('UPDATE users SET role = 3 WHERE id = ?')->execute([(int)$admin['id']]);
            } else {
                $db->exec('UPDATE users SET role = 3 ORDER BY id ASC LIMIT 1');
            }
        }

        // 更新字段注释
        try {
            $db->exec("ALTER TABLE users MODIFY COLUMN role TINYINT UNSIGNED DEFAULT 0 COMMENT '0=普通 1=审核员 2=管理员 3=超级管理员'");
        } catch (Throwable) {
            // 部分环境无 MODIFY 权限时忽略
        }
    },
];
