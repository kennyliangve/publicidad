<?php
return [
    'version'     => '008',
    'description' => '省/城市选择配置（regions，默认委内瑞拉）',

    'up' => function (PDO $db) {
        require_once __DIR__ . '/../helpers/regions.php';

        $stmt = $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            'regions',
            encodeRegions(getDefaultRegions()),
            '省/城市列表(JSON)',
        ]);
    },
];
