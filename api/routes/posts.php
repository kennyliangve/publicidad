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

    // GET /posts/my - 我的发布（必须在 GET /posts 公开列表之前，否则 id=null 会误匹配全站列表）
    if ($method === 'GET' && ($action === 'my' || $id === 'my')) {
        $user = requireAuthUser();
        $userId = getAuthUserId();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
        $countStmt->execute([$userId]);
        $total = (int)$countStmt->fetchColumn();

        $order = postsPublicOrderSql($db);
        $stmt = $db->prepare(
            "SELECT p.*, c.name as category_name, c.slug as category_slug FROM posts p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE p.user_id = ? ORDER BY $order LIMIT $limit OFFSET $offset"
        );
        $stmt->execute([$userId]);
        $posts = formatPostsPublic($stmt->fetchAll());

        jsonSuccess(array_merge(
            ['list' => $posts, 'total' => $total, 'page' => $page],
            getUserPinPostMeta($db, $user, $userId)
        ));
    }

    // GET /posts - 公开列表
    if ($method === 'GET' && !$id && $action !== 'my') {
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
                ORDER BY " . postsPublicOrderSql($db) . "
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

    // GET /posts/{vid}/edit - 本人编辑（含待审核/已下架展示态）
    if ($method === 'GET' && $id && $action === 'edit') {
        $userId = getAuthUserId();
        $post = findOwnedPostByPublicId($db, $id, $userId);
        if (!$post) {
            $exists = findPostByPublicId($db, $id) !== null;
            jsonError($exists ? '无权操作' : '信息不存在', $exists ? 403 : 404);
        }
        if ((int)$post['status'] === 0) {
            jsonError('已删除的信息无法编辑');
        }

        $stmt = $db->prepare(
            'SELECT p.*, c.name as category_name, c.slug as category_slug FROM posts p
             LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.user_id = ?'
        );
        $stmt->execute([(int)$post['id'], $userId]);
        $row = $stmt->fetch();
        jsonSuccess(formatPostPublic($row ?: $post));
    }

    // PUT /posts/{vid} - 编辑本人信息
    if ($method === 'PUT' && $id && !$action) {
        $user = requireAuthUser();
        $userId = getAuthUserId();

        $post = findOwnedPostByPublicId($db, $id, $userId);
        if (!$post) {
            $exists = findPostByPublicId($db, $id) !== null;
            jsonError($exists ? '无权操作' : '信息不存在', $exists ? 403 : 404);
        }

        $body = getRequestBody();
        $parsed = parsePostBody($db, $body, $user);
        $updated = updateOwnedPost($db, $user, $userId, $post, $parsed);

        $reviewRequired = isPostReviewRequired($db);
        $wasActive = (int)$post['status'] === 1;
        $message = ($reviewRequired && $wasActive) ? '已保存，等待审核' : '保存成功';

        jsonSuccess(['post' => $updated, 'id' => $updated['id']], $message);
    }

    // PUT /posts/{vid}/pin - 置顶/取消置顶
    if ($method === 'PUT' && $id && $action === 'pin') {
        $user = requireAuthUser();
        $userId = getAuthUserId();

        $post = findOwnedPostByPublicId($db, $id, $userId);
        if (!$post) {
            $exists = findPostByPublicId($db, $id) !== null;
            jsonError($exists ? '无权操作' : '信息不存在', $exists ? 403 : 404);
        }

        $body = getRequestBody();
        $pinned = array_key_exists('is_top', $body) ? (bool)(int)$body['is_top'] : true;

        $updated = setPostPinned($db, $user, $userId, $post, $pinned);
        jsonSuccess(array_merge(
            ['post' => $updated],
            getUserPinPostMeta($db, $user, $userId)
        ), $pinned ? '已置顶' : '已取消置顶');
    }

    // POST /posts - 发布
    if ($method === 'POST' && !$id) {
        $userId = requireAuth();
        $user = findUserById($db, $userId);
        if (!$user) {
            jsonError('用户不存在', 404);
        }

        $body = getRequestBody();
        $parsed = parsePostBody($db, $body, $user);

        $images = $parsed['images_list'] ? json_encode($parsed['images_list']) : '[]';
        $status = isPostReviewRequired($db) ? 2 : 1;

        $created = insertPostWithVid($db, [
            'user_id'        => $userId,
            'category_id'    => $parsed['category_id'],
            'title'          => $parsed['title'],
            'content'        => $parsed['content'],
            'price'          => $parsed['price'],
            'price_unit'     => $parsed['price_unit'],
            'contact_name'   => $parsed['contact_name'],
            'contact_phone'  => $parsed['contact_phone'],
            'contact_wechat' => $parsed['contact_wechat'],
            'province'       => $parsed['province'],
            'city'           => $parsed['city'],
            'district'       => $parsed['district'],
            'address'        => $parsed['address'],
            'images'         => $images,
            'status'         => $status,
        ]);

        $message = $status === 2 ? '提交成功，等待审核' : '发布成功';
        jsonSuccess(['id' => $created['public_id'], 'status' => $status], $message);
    }

    // DELETE /posts/{vid} - 删除
    if ($method === 'DELETE' && $id) {
        $userId = getAuthUserId();
        $post = findOwnedPostByPublicId($db, $id, $userId);
        if (!$post) {
            $exists = findPostByPublicId($db, $id) !== null;
            jsonError($exists ? '无权操作' : '信息不存在', $exists ? 403 : 404);
        }

        $db->prepare('UPDATE posts SET status = 0 WHERE id = ? AND user_id = ?')->execute([(int)$post['id'], $userId]);
        jsonSuccess(null, '删除成功');
    }

    jsonError('Method not allowed', 405);
}
