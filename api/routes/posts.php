<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

function handlePosts(string $method, ?string $id, ?string $action): void
{
    $db = Database::getConnection();

    // GET /posts - 列表
    if ($method === 'GET' && !$id) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $categoryId = $_GET['category_id'] ?? null;
        $keyword = trim($_GET['keyword'] ?? '');
        $city = trim($_GET['city'] ?? '');

        $where = ['p.status = 1'];
        $params = [];

        if ($categoryId) {
            // 包含子分类
            $stmt = $db->prepare('SELECT id FROM categories WHERE id = ? OR parent_id = ?');
            $stmt->execute([$categoryId, $categoryId]);
            $catIds = array_column($stmt->fetchAll(), 'id');
            if ($catIds) {
                $placeholders = implode(',', array_fill(0, count($catIds), '?'));
                $where[] = "p.category_id IN ($placeholders)";
                $params = array_merge($params, $catIds);
            }
        }

        if ($keyword) {
            $where[] = '(p.title LIKE ? OR p.content LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }

        if ($city) {
            $where[] = 'p.city LIKE ?';
            $params[] = "%$city%";
        }

        $whereStr = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM posts p WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, u.username 
                FROM posts p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN users u ON p.user_id = u.id 
                WHERE $whereStr 
                ORDER BY p.created_at DESC 
                LIMIT $limit OFFSET $offset";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $post['images'] = json_decode($post['images'] ?? '[]', true) ?: [];
            $post['price'] = $post['price'] !== null ? (float)$post['price'] : null;
        }

        jsonSuccess([
            'list'  => $posts,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    // GET /posts/{id} - 详情
    if ($method === 'GET' && $id && !$action) {
        $stmt = $db->prepare(
            'SELECT p.*, c.name as category_name, c.slug as category_slug, u.username, u.phone as user_phone 
             FROM posts p 
             LEFT JOIN categories c ON p.category_id = c.id 
             LEFT JOIN users u ON p.user_id = u.id 
             WHERE p.id = ? AND p.status = 1'
        );
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if (!$post) jsonError('信息不存在', 404);

        // 增加浏览量
        $db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$id]);
        $post['views'] = (int)$post['views'] + 1;
        $post['images'] = json_decode($post['images'] ?? '[]', true) ?: [];
        $post['price'] = $post['price'] !== null ? (float)$post['price'] : null;

        jsonSuccess($post);
    }

    // GET /posts/my - 我的发布
    if ($method === 'GET' && $action === 'my') {
        $userId = requireAuth();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT p.*, c.name as category_name, c.slug as category_slug FROM posts p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute([$userId]);
        $posts = $stmt->fetchAll();

        foreach ($posts as &$post) {
            $post['images'] = json_decode($post['images'] ?? '[]', true) ?: [];
        }

        jsonSuccess(['list' => $posts, 'total' => $total, 'page' => $page]);
    }

    // POST /posts - 发布
    if ($method === 'POST' && !$id) {
        $userId = requireAuth();
        $body = getRequestBody();

        $title = trim($body['title'] ?? '');
        $content = trim($body['content'] ?? '');
        $categoryId = (int)($body['category_id'] ?? 0);
        $contactPhone = trim($body['contact_phone'] ?? '');

        if (!$title || !$content || !$categoryId || !$contactPhone) {
            jsonError('请填写必填项');
        }

        $stmt = $db->prepare(
            'INSERT INTO posts (user_id, category_id, title, content, price, price_unit, 
             contact_name, contact_phone, province, city, district, address, images) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $images = isset($body['images']) ? json_encode($body['images']) : '[]';
        $price = isset($body['price']) && $body['price'] !== '' ? (float)$body['price'] : null;

        $stmt->execute([
            $userId,
            $categoryId,
            $title,
            $content,
            $price,
            $body['price_unit'] ?? '元',
            $body['contact_name'] ?? null,
            $contactPhone,
            $body['province'] ?? null,
            $body['city'] ?? null,
            $body['district'] ?? null,
            $body['address'] ?? null,
            $images,
        ]);

        jsonSuccess(['id' => (int)$db->lastInsertId()], '发布成功');
    }

    // DELETE /posts/{id} - 删除
    if ($method === 'DELETE' && $id) {
        $userId = requireAuth();
        $stmt = $db->prepare('SELECT user_id FROM posts WHERE id = ?');
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if (!$post) jsonError('信息不存在', 404);
        if ((int)$post['user_id'] !== $userId) jsonError('无权操作', 403);

        $db->prepare('UPDATE posts SET status = 0 WHERE id = ?')->execute([$id]);
        jsonSuccess(null, '删除成功');
    }

    jsonError('Method not allowed', 405);
}
