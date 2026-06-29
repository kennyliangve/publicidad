<?php
return [
    'version'     => '010',
    'description' => '系统设置新增 site_logo',

    'up' => function (PDO $db) {
        $db->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES (?, ?, ?)'
        )->execute(['site_logo', '', '系统 Logo']);
    },
];
