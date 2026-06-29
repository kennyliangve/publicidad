<?php
return [
    'version'     => '015',
    'description' => 'VIP 套餐金额改为 USD，BCV 汇率设置',

    'up' => function (PDO $db) {
        require_once __DIR__ . '/../helpers/bcv.php';

        $hasUsd = (bool)$db->query("SHOW COLUMNS FROM vip_plans LIKE 'amount_usd'")->fetch();
        $hasAmount = (bool)$db->query("SHOW COLUMNS FROM vip_plans LIKE 'amount'")->fetch();

        if (!$hasUsd && $hasAmount) {
            $fallbackRate = 50.0;
            $remote = fetchRemoteBcvUsdRate();
            if ($remote && (float)$remote['rate'] > 0) {
                $fallbackRate = (float)$remote['rate'];
            }

            $maxAmount = (float)$db->query('SELECT COALESCE(MAX(amount), 0) FROM vip_plans')->fetchColumn();

            $db->exec('ALTER TABLE vip_plans ADD COLUMN amount_usd DECIMAL(12,2) NULL AFTER name');
            if ($maxAmount > 0 && $maxAmount < 20) {
                $db->exec('UPDATE vip_plans SET amount_usd = amount');
            } else {
                $db->exec('UPDATE vip_plans SET amount_usd = ROUND(amount / ' . $fallbackRate . ', 2)');
            }
            $db->exec('ALTER TABLE vip_plans DROP COLUMN amount');
            $db->exec("ALTER TABLE vip_plans MODIFY amount_usd DECIMAL(12,2) NOT NULL COMMENT 'USD'");
        } elseif (!$hasUsd && !$hasAmount) {
            $db->exec("ALTER TABLE vip_plans ADD COLUMN amount_usd DECIMAL(12,2) NOT NULL DEFAULT 1.00 COMMENT 'USD' AFTER name");
        }

        $defaults = [
            [BCV_RATE_SETTING_KEY, '', 'BCV 美元汇率缓存 (VES/USD)'],
            [BCV_RATE_DATE_SETTING_KEY, '', 'BCV 汇率缓存日期'],
            [BCV_RATE_EFFECTIVE_SETTING_KEY, '', 'BCV 汇率生效日期'],
            [BCV_RATE_UPDATED_SETTING_KEY, '', 'BCV 汇率更新时间'],
            [BCV_RATE_MANUAL_SETTING_KEY, '', 'BCV 手动汇率 (留空则自动拉取)'],
            [BCV_RATE_SOURCE_SETTING_KEY, '', 'BCV 汇率来源'],
            ['vip_plan_currency', 'USD', 'VIP 套餐定价货币'],
        ];

        $stmt = $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        );
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }

        $db->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ? AND setting_value IN (\'VES\', \'Bs\', \'\')')
            ->execute(['USD', 'vip_plan_currency']);
    },
];
