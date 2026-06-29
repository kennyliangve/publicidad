<?php
/**
 * 迁移模板 - 复制此文件并重命名后修改
 *
 * 命名规则: 003_描述.php （数字递增，不可重复）
 * 上传后系统会自动检测并执行
 */
return [
    'version'     => '003',
    'description' => '在此填写本次变更说明',

    'up' => function (PDO $db) {
        // 示例：安全添加列
        // $stmt = $db->query("SHOW COLUMNS FROM posts LIKE 'new_column'");
        // if (!$stmt->fetch()) {
        //     $db->exec("ALTER TABLE posts ADD COLUMN new_column VARCHAR(100) DEFAULT NULL");
        // }

        // 示例：安全添加索引
        // $db->exec("CREATE INDEX IF NOT EXISTS idx_example ON posts (city)");

        // 示例：插入种子数据
        // $db->exec("INSERT IGNORE INTO categories (id, parent_id, name, slug, sort_order) VALUES (23, 0, '新分类', 'new', 9)");
    },
];
