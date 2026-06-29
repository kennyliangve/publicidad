-- ============================================================
-- 信息分类网 - 数据库初始化（已弃用，请使用迁移系统）
-- ============================================================
-- 推荐方式：
--   1. 访问 install.php 一键迁移
--   2. 或上传代码后 API 会自动执行迁移（auto_migrate = true）
--
-- 手动添加数据库变更：
--   在 database/migrations/ 目录新建文件，如 003_xxx.php
--   上传后访问任意 API 或 install.php 即可自动更新线上数据库
-- ============================================================

-- 以下内容与 001_initial_schema.php 相同，仅供 phpMyAdmin 手动导入参考

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED DEFAULT 0,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    icon VARCHAR(20) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    status TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    INDEX idx_user (user_id),
    INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
