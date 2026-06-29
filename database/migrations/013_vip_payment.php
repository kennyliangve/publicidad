<?php
return [
    'version'     => '013',
    'description' => 'VIP 升级支付记录与银行对接设置',

    'up' => function (PDO $db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS vip_payments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                reference VARCHAR(32) NOT NULL,
                reference_key VARCHAR(64) NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                payer_phone VARCHAR(20) NOT NULL,
                payer_id_type CHAR(1) NOT NULL DEFAULT 'V',
                payer_id_number VARCHAR(20) NOT NULL,
                payer_bank_code VARCHAR(10) NOT NULL,
                payment_date DATE NOT NULL,
                status TINYINT NOT NULL DEFAULT 0 COMMENT '0=待验证 1=成功 2=失败',
                bank_response TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                verified_at DATETIME NULL,
                UNIQUE KEY uk_reference_key (reference_key),
                INDEX idx_user (user_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $defaults = [
            ['vip_upgrade_enabled', '1', '开启 VIP 在线升级'],
            ['vip_plan_amount', '50.00', 'VIP 套餐金额(Bs)'],
            ['vip_plan_currency', 'VES', 'VIP 套餐货币'],
            ['vip_merchant_phone', '', '收款 Pago Móvil 手机号'],
            ['vip_merchant_rif', '', '收款 RIF'],
            ['vip_merchant_bank_code', '0102', '收款银行代码'],
            ['bank_api_mode', 'production', '银行 API 模式(production/sandbox)'],
            ['bank_api_endpoint', 'https://bdvconciliacion.banvenez.com:443/api/consulta/consultaMultiple', '银行 API 生产地址'],
            ['bank_api_endpoint_sandbox', 'https://bdvconciliacion.banvenez.com:443/api/consulta/consultaMultiple', '银行 API 沙盒地址'],
            ['bank_api_token', '', '银行 API Token / X-API-Key'],
            ['bank_auth_type', 'x_api_key', '认证方式(x_api_key/bearer)'],
        ];

        $stmt = $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        );
        foreach ($defaults as $row) {
            $stmt->execute($row);
        }
    },
];
