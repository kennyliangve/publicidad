<?php
return [
    'version'     => '009',
    'description' => '新增一级分类「货品供求」及子分类',

    'up' => function (PDO $db) {
        $db->exec("
            INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order) VALUES
            (23, 0, '货品供求', 'supply-demand', '🛒', 9)
        ");

        $db->exec("
            INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order) VALUES
            (24, 23, '供应出售', 'supply', NULL, 1),
            (25, 23, '求购需求', 'demand', NULL, 2),
            (26, 23, '批发团购', 'wholesale', NULL, 3),
            (27, 23, '农产品', 'farm-products', NULL, 4),
            (28, 23, '设备机械', 'equipment', NULL, 5)
        ");
    },
];
