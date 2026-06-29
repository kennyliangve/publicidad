-- ============================================================
-- phpMyAdmin 手动升级脚本（users 表缺少 role 等字段时使用）
-- 在 phpMyAdmin 中选择数据库 vecinoco_1688 → SQL → 粘贴执行
-- 执行后访问：/publicidad/api/install.php 确认迁移状态
-- ============================================================

-- 003: 邮箱
ALTER TABLE users ADD COLUMN email VARCHAR(100) DEFAULT NULL AFTER phone;
ALTER TABLE users ADD UNIQUE INDEX uk_email (email);

-- 004: 用户扩展字段（逐条执行，若某列已存在会报错，可跳过该行）
ALTER TABLE users ADD COLUMN role TINYINT UNSIGNED DEFAULT 0 COMMENT '0=普通 4=VIP 1=审核员 2=管理员 3=超级管理员' AFTER avatar;
ALTER TABLE users ADD COLUMN status TINYINT DEFAULT 1 COMMENT '0=禁用 1=正常 2=待审核' AFTER role;
ALTER TABLE users ADD COLUMN gender TINYINT DEFAULT 0 COMMENT '0=保密 1=男 2=女' AFTER status;
ALTER TABLE users ADD COLUMN real_name VARCHAR(50) DEFAULT NULL AFTER gender;
ALTER TABLE users ADD COLUMN bio VARCHAR(255) DEFAULT NULL AFTER real_name;
ALTER TABLE users ADD COLUMN province VARCHAR(50) DEFAULT NULL AFTER bio;
ALTER TABLE users ADD COLUMN city VARCHAR(50) DEFAULT NULL AFTER province;
ALTER TABLE users ADD COLUMN district VARCHAR(50) DEFAULT NULL AFTER city;
ALTER TABLE users ADD COLUMN register_ip VARCHAR(45) DEFAULT NULL AFTER district;
ALTER TABLE users ADD COLUMN register_source VARCHAR(20) DEFAULT 'web' AFTER register_ip;
ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL AFTER register_source;
ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) DEFAULT NULL AFTER last_login_at;
ALTER TABLE users ADD COLUMN login_count INT UNSIGNED DEFAULT 0 AFTER last_login_ip;

-- 将当前账号设为超级管理员（按你的手机号修改；2=管理员，3=超级管理员）
UPDATE users SET role = 3 WHERE phone = '04129992217';

-- 005: 系统设置表
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    label VARCHAR(100) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES
('site_name', '同城信息', '网站名称'),
('site_description', '本地生活信息分类平台', '网站描述'),
('contact_email', 'admin@example.com', '联系邮箱'),
('contact_phone', '', '联系电话'),
('posts_per_page', '20', '每页显示条数'),
('require_post_review', '0', '发布需审核(0否1是)'),
('allow_register', '1', '允许注册(0否1是)'),
('price_units', '["元","元/月","元/次","元/天","万元"]', '价格单位列表(JSON数组)'),
('regions', '', '省/城市列表(JSON，见 migration 008)');

-- 记录迁移版本（避免 PHP 重复执行）
CREATE TABLE IF NOT EXISTS _migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL DEFAULT '',
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO _migrations (version, description) VALUES
('001', 'initial'),
('002', 'is_top'),
('003', 'email'),
('004', 'user fields'),
('005', 'settings'),
('006', 'user roles four tiers'),
('007', 'price units'),
('008', 'regions'),
('009', 'supply demand category'),
('010', 'site logo');

-- 010: 系统 Logo 设置
INSERT IGNORE INTO settings (setting_key, setting_value, label) VALUES
('site_logo', '', '系统 Logo');

-- 009: 货品供求分类
INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order) VALUES
(23, 0, '货品供求', 'supply-demand', '🛒', 9);

INSERT IGNORE INTO categories (id, parent_id, name, slug, icon, sort_order) VALUES
(24, 23, '供应出售', 'supply', NULL, 1),
(25, 23, '求购需求', 'demand', NULL, 2),
(26, 23, '批发团购', 'wholesale', NULL, 3),
(27, 23, '农产品', 'farm-products', NULL, 4),
(28, 23, '设备机械', 'equipment', NULL, 5);

-- 012: 对外公开随机 ID（执行后请访问 /publicidad/api/install.php?step=install 完成回填）
-- ALTER TABLE users ADD COLUMN vid VARCHAR(32) NULL COMMENT '对外公开随机ID' AFTER id;
-- ALTER TABLE posts ADD COLUMN vid VARCHAR(32) NULL COMMENT '对外公开随机ID' AFTER id;
-- 推荐直接运行 install.php 迁移 012，会自动生成 vid 并加唯一索引
