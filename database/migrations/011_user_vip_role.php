<?php
return [
    'version'     => '011',
    'description' => '用户角色新增 VIP 级别（role=4）',

    'up' => function (PDO $db) {
        try {
            $db->exec(
                "ALTER TABLE users MODIFY COLUMN role TINYINT UNSIGNED DEFAULT 0 COMMENT '0=普通 4=VIP 1=审核员 2=管理员 3=超级管理员'"
            );
        } catch (Throwable) {
            // 部分环境 COMMENT 语法差异，忽略
        }
    },
];
