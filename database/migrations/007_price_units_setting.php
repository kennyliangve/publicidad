<?php
return [
    'version'     => '007',
    'description' => '价格单位可配置（settings.price_units）',

    'up' => function (PDO $db) {
        $default = json_encode(['元', '元/月', '元/次', '元/天', '万元'], JSON_UNESCAPED_UNICODE);
        $stmt = $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            'price_units',
            $default,
            '价格单位列表(JSON数组)',
        ]);
    },
];
