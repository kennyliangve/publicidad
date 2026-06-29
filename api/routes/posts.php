<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../helpers/settings.php';
require_once __DIR__ . '/../helpers/regions.php';
require_once __DIR__ . '/../helpers/phone.php';
require_once __DIR__ . '/../helpers/user.php';
require_once __DIR__ . '/../helpers/vid.php';
require_once __DIR__ . '/../helpers/post.php';

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
        $posts = formatPostsPublic($stmt->fetchAll());

        jsonSuccess([
            'list'  => $posts,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    // GET /posts/{vid} - 详情
    if ($method === 'GET' && $id && !$action) {
        $postId = resolvePostId($db, $id);
        if (!$postId) {
            jsonError('信息不存在', 404);
        }

        $stmt = $db->prepare(
            'SELECT p.*, c.name as category_name, c.slug as category_slug, u.username, u.phone as user_phone 
             FROM posts p 
             LEFT JOIN categories c ON p.category_id = c.id 
             LEFT JOIN users u ON p.user_id = u.id 
             WHERE p.id = ? AND p.status = 1'
        );
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            jsonError('信息不存在', 404);
        }

        $db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$postId]);
        $post['views'] = (int)$post['views'] + 1;

        jsonSuccess(formatPostPublic($post));
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
        $posts = formatPostsPublic($stmt->fetchAll());

        jsonSuccess(['list' => $posts, 'total' => $total, 'page' => $page]);
    }

    // POST /posts - 发布
    if ($method === 'POST' && !$id) {
        $userId = requireAuth();
        $user = findUserById($db, $userId);
        if (!$user) {
            jsonError('用户不存在', 404);
        }

        $body = getRequestBody();

        $title = trim($body['title'] ?? '');
        $content = trim($body['content'] ?? '');
        $categoryId = (int)($body['category_id'] ?? 0);
        $contactPhone = trim($body['contact_phone'] ?? '');

        if (!$title || !$content || !$categoryId || !$contactPhone) {
            jsonError('请填写必填项');
        }
        $contactPhone = assertValidVenezuelaPhone($contactPhone, '联系电话');

        $imagesList = isset($body['images']) && is_array($body['images']) ? $body['images'] : [];
        if ($imagesList && !userCanUploadPostImages((int)($user['role'] ?? 0), $user)) {
            jsonError('仅 VIP 及以上用户可上传图片', 403);
        }

        $images = $imagesList ? json_encode($imagesList) : '[]';
        $price = isset($body['price']) && $body['price'] !== '' ? (float)$body['price'] : null;
        $priceUnit = assertValidPriceUnit($db, $body['price_unit'] ?? null);
        [$province, $city] = assertValidRegion($db, $body['province'] ?? null, $body['city'] ?? null);
        $status = isPostReviewRequired($db) ? 2 : 1;

        $created = insertPostWithVid($db, [
            'user_id'       => $userId,
            'category_id'   => $categoryId,
            'title'         => $title,
            'content'       => $content,
            'price'         => $price,
            'price_unit'    => $priceUnit,
            'contact_name'  => $body['contact_name'] ?? null,
            'contact_phone' => $contactPhone,
            'province'      => $province,
            'city'          => $city,
            'district'      => $body['district'] ?? null,
            'address'       => $body['address'] ?? null,
            'images'        => $images,
            'status'        => $status,
        ]);

        $message = $status === 2 ? '提交成功，等待审核' : '发布成功';
        jsonSuccess(['id' => $created['public_id'], 'status' => $status], $message);
    }

    // DELETE /posts/{vid} - 删除
    if ($method === 'DELETE' && $id) {
        $userId = requireAuth();
        $postId = resolvePostId($db, $id);
        if (!$postId) {
            jsonError('信息不存在', 404);
        }

        $stmt = $db->prepare('SELECT user_id FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post) {
            jsonError('信息不存在', 404);
        }
        if ((int)$post['user_id'] !== $userId) {
            jsonError('无权操作', 403);
        }

        $db->prepare('UPDATE posts SET status = 0 WHERE id = ?')->execute([$postId]);
        jsonSuccess(null, '删除成功');
    }

    jsonError('Method not allowed', 405);
}
