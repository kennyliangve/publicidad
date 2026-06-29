<?php
return [
    'version'     => '014',
    'description' => 'VIP 多套餐与到期时间',

    'up' => function (PDO $db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS vip_plans (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                duration_days INT UNSIGNED NOT NULL DEFAULT 30 COMMENT '有效天数',
                sort_order INT NOT NULL DEFAULT 0,
                enabled TINYINT NOT NULL DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_enabled_sort (enabled, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $col = $db->query("SHOW COLUMNS FROM users LIKE 'vip_expires_at'")->fetch();
        if (!$col) {
            $db->exec("ALTER TABLE users ADD COLUMN vip_expires_at DATETIME NULL COMMENT 'VIP到期时间' AFTER role");
        }

        $col = $db->query("SHOW COLUMNS FROM vip_payments LIKE 'plan_id'")->fetch();
        if (!$col) {
            $db->exec("ALTER TABLE vip_payments ADD COLUMN plan_id INT UNSIGNED NULL AFTER user_id");
        }

        $count = (int)$db->query('SELECT COUNT(*) FROM vip_plans')->fetchColumn();
        if ($count === 0) {
            $amount = 50.00;
            try {
                $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'vip_plan_amount'");
                $stmt->execute();
                $row = $stmt->fetch();
                if ($row && (float)$row['setting_value'] > 0) {
                    $amount = (float)$row['setting_value'];
                }
            } catch (Throwable) {
            }

            $stmt = $db->prepare(
                'INSERT INTO vip_plans (name, amount, duration_days, sort_order, enabled) VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute(['VIP 月卡', $amount, 30, 1]);
            $stmt->execute(['VIP 季卡', round($amount * 2.5, 2), 90, 2]);
        }
    },
];
