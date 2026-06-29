<?php
return [
    'version'     => '008',
    'description' => '省/城市选择配置（regions，默认委内瑞拉）',

    'up' => function (PDO $db) {
        $defaults = require __DIR__ . '/../../api/data/default_regions.php';
        $json = json_encode($defaults, JSON_UNESCAPED_UNICODE);

        $stmt = $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            'regions',
            $json,
            '省/城市列表(JSON)',
        ]);
    },
];
