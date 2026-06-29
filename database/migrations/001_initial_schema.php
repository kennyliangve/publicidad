<?php
/**
 * 迁移 001：初始数据表 + 种子数据
 */
return [
    'version'     => '001',
    'description' => '初始数据表和种子数据',

    'up' => function (PDO $db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                phone VARCHAR(20) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                avatar VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                parent_id INT UNSIGNED DEFAULT 0,
                name VARCHAR(50) NOT NULL,
                slug VARCHAR(50) NOT NULL,
                icon VARCHAR(20) DEFAULT NULL,
                sort_order INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS posts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                title VARCHAR(200) NOT NULL,
                content TEXT NOT NULL,
                price DECIMAL(12,2) DEFAULT NULL,
                price_unit VARCHAR(20) DEFAULT '元',
                contact_name VARCHAR(50) DEFAULT NULL,
                contact_phone VARCHAR(20) NOT NULL,
                province VARCHAR(50) DEFAULT NULL,
                city VARCHAR(50) DEFAULT NULL,
                district VARCHAR(50) DEFAULT NULL,
                address VARCHAR(255) DEFAULT NULL,
                images TEXT DEFAULT NULL,
                views INT UNSIGNED DEFAULT 0,
                status TINYINT DEFAULT 1 COMMENT '1=正常 0=下架 2=待审核',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_category (category_id),
                INDEX idx_user (user_id),
                INDEX idx_status_created (status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 一级分类
        $db->exec("
            INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order) VALUES
            (1, 0, '招聘求职', 'jobs', '💼', 1),
            (2, 0, '生活服务', 'services', '🏪', 2),
            (3, 0, '租房', 'rent', '🏠', 3),
            (4, 0, '二手房', 'house', '🏡', 4),
            (5, 0, '二手车', 'car', '🚗', 5),
            (6, 0, '二手物品', 'goods', '📦', 6),
            (7, 0, '宠物', 'pet', '🐾', 7),
            (8, 0, '本地交友', 'social', '👥', 8)
        ");

        // 二级分类
        $db->exec("
            INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order) VALUES
            (9,  1, '全职招聘', 'fulltime', NULL, 1),
            (10, 1, '兼职招聘', 'parttime', NULL, 2),
            (11, 1, '求职简历', 'resume', NULL, 3),
            (12, 2, '家政服务', 'housekeeping', NULL, 1),
            (13, 2, '维修安装', 'repair', NULL, 2),
            (14, 2, '搬家物流', 'moving', NULL, 3),
            (15, 2, '教育培训', 'education', NULL, 4),
            (16, 2, '婚庆摄影', 'wedding', NULL, 5),
            (17, 3, '整租', 'whole-rent', NULL, 1),
            (18, 3, '合租', 'share-rent', NULL, 2),
            (19, 3, '短租', 'short-rent', NULL, 3),
            (20, 5, '轿车', 'sedan', NULL, 1),
            (21, 5, 'SUV', 'suv', NULL, 2),
            (22, 5, '货车', 'truck', NULL, 3)
        ");

        // 测试用户，密码 123456
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            'INSERT IGNORE INTO users (id, username, phone, password) VALUES (1, ?, ?, ?)'
        );
        $stmt->execute(['测试用户', '13800138000', $hash]);

        // 示例帖子
        $db->exec("
            INSERT IGNORE INTO posts (id, user_id, category_id, title, content, price, price_unit, contact_name, contact_phone, city, district, address, images, views) VALUES
            (1, 1, 9,  '急招前端开发工程师', '公司位于市中心，五险一金，双休。要求：熟悉Vue/React，2年以上经验。', 15000, '元/月', '张经理', '13800138000', '北京', '朝阳区', '国贸CBD', '[]', 128),
            (2, 1, 12, '专业家政保洁服务', '提供日常保洁、深度清洁、开荒保洁等服务，价格优惠，口碑好。', 80, '元/次', '李阿姨', '13800138001', '北京', '海淀区', '中关村', '[]', 56),
            (3, 1, 17, '精装两室一厅整租', '地铁口500米，南北通透，家具家电齐全，拎包入住。', 4500, '元/月', '王先生', '13800138002', '北京', '朝阳区', '望京', '[]', 234),
            (4, 1, 20, '2019款大众朗逸 1.4T', '个人一手车，无事故，全程4S保养，里程5万公里。', 85000, '元', '赵先生', '13800138003', '北京', '丰台区', '花乡二手车市场', '[]', 89),
            (5, 1, 6,  '九成新 iPhone 14 Pro', '256G 深空黑，无划痕，配件齐全，当面交易。', 5200, '元', '小刘', '13800138004', '北京', '西城区', '西单', '[]', 45)
        ");
    },
];
