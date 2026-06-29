# 信息分类网系统

类似 58 同城的本地生活信息分类平台，支持招聘、黄页、租房、二手车等多种分类。

## 技术栈

- **前端**: Vue 3 + Vite + Vue Router + Pinia
- **后端**: PHP 7.4+ (RESTful API)
- **数据库**: MySQL 5.7+

## 功能特性

- 8 大一级分类 + 二级子分类（招聘、生活服务、租房、二手房、二手车、二手物品、宠物、交友）
- 信息发布 / 浏览 / 搜索 / 删除
- 用户注册登录
- 图片上传
- 响应式设计，自适应 PC 端和移动端
- 移动端底部导航栏

## 快速部署（phpstudy）

### 1. 导入数据库

打开 phpstudy 的 MySQL 管理工具（或 Navicat），执行：

```
database/init.sql
```

### 2. 修改数据库配置

编辑 `api/config.php`，根据你的 phpstudy 环境修改数据库连接信息：

```php
'username' => 'root',
'password' => 'root',  // 改为你的 MySQL 密码
```

### 3. 构建前端

```bash
cd frontend
npm install
npm run build
```

构建产物会自动输出到 `dist/` 目录。

### 4. 配置 Apache

确保 phpstudy 中 Apache 已开启 `mod_rewrite` 模块。

项目根目录的 `.htaccess` 已配置好 SPA 路由和 API 转发。

### 5. 访问

浏览器打开：`http://localhost/publicidad/`

## 开发模式

```bash
# 终端 1：前端开发服务器（带 API 代理）
cd frontend
npm install
npm run dev

# 终端 2：确保 phpstudy 的 Apache + MySQL 已启动
```

开发地址：`http://localhost:5173/publicidad/`

## 测试账号

| 手机号 | 密码 |
|--------|------|
| 13800138000 | 123456 |

## 目录结构

```
publicidad/
├── api/                # PHP 后端 API
│   ├── index.php       # 路由入口
│   ├── config.php      # 配置文件
│   ├── db.php          # 数据库连接
│   ├── helpers.php     # 工具函数
│   └── routes/         # API 路由
├── database/
│   └── init.sql        # 数据库初始化
├── frontend/           # Vue 前端源码
│   └── src/
│       ├── views/      # 页面
│       ├── components/ # 组件
│       ├── api/        # API 封装
│       └── stores/     # 状态管理
├── dist/               # 前端构建产物
├── uploads/            # 上传文件目录
└── .htaccess           # Apache 路由配置
```

## API 接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /api/categories | 获取分类列表 |
| GET | /api/posts | 信息列表（支持 category_id, keyword, city 筛选） |
| GET | /api/posts/{id} | 信息详情 |
| POST | /api/posts | 发布信息（需登录） |
| DELETE | /api/posts/{id} | 删除信息（需登录） |
| GET | /api/posts/my | 我的发布（需登录） |
| POST | /api/auth/register | 注册 |
| POST | /api/auth/login | 登录 |
| POST | /api/upload | 上传图片（需登录） |
