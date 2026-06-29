<?php
return [
    'version'     => '007',
    'description' => '价格单位可配置（settings.price_units）',

    'up' => function (PDO $db) {
        require_once __DIR__ . '/../helpers/settings.php';

        $stmt = $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            'price_units',
            encodePriceUnits(DEFAULT_PRICE_UNITS),
            '价格单位列表(JSON数组)',
        ]);
    },
];
