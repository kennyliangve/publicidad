<?php
return [
    'version'     => '005',
    'description' => '系统设置表 settings',

    'up' => function (PDO $db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value TEXT,
                label VARCHAR(100) DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $defaults = [
            ['site_name', '同城信息', '网站名称'],
            ['site_description', '本地生活信息分类平台', '网站描述'],
            ['contact_email', 'admin@example.com', '联系邮箱'],
            ['contact_phone', '', '联系电话'],
            ['posts_per_page', '20', '每页显示条数'],
            ['require_post_review', '0', '发布需审核(0否1是)'],
            ['allow_register', '1', '允许注册(0否1是)'],
        ];

        $stmt = $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        );
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }
    },
];
