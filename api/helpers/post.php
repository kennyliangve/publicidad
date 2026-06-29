<?php

require_once __DIR__ . '/vid.php';
require_once __DIR__ . '/user.php';

const VIP_PIN_POST_LIMIT = 5;

function postsHasIsTopColumn(PDO $db): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM posts LIKE 'is_top'");
        $has = (bool)$stmt->fetch();
    } catch (Throwable) {
        $has = false;
    }
    return $has;
}

function postsPublicOrderSql(PDO $db): string
{
    if (postsHasIsTopColumn($db)) {
        return 'p.is_top DESC, p.created_at DESC';
    }
    return 'p.created_at DESC';
}

function userCanPinPosts(array $user): bool
{
    return userCanUploadPostImages((int)($user['role'] ?? USER_ROLE_NORMAL), $user);
}

function countUserPinnedPosts(PDO $db, int $userId): int
{
    if (!postsHasIsTopColumn($db)) {
        return 0;
    }
    $stmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ? AND is_top = 1 AND status != 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function clearUserPinnedPosts(PDO $db, int $userId): void
{
    if (!postsHasIsTopColumn($db)) {
        return;
    }
    $db->prepare('UPDATE posts SET is_top = 0 WHERE user_id = ?')->execute([$userId]);
}

/** 清除普通用户残留的置顶（如 VIP 过期降级后） */
function clearNormalUserPinnedPosts(PDO $db): void
{
    if (!postsHasIsTopColumn($db)) {
        return;
    }
    $db->prepare(
        'UPDATE posts p
         INNER JOIN users u ON u.id = p.user_id
         SET p.is_top = 0
         WHERE p.is_top = 1 AND u.role = ?'
    )->execute([USER_ROLE_NORMAL]);
}

function getUserPinPostMeta(PDO $db, array $user, ?int $userId = null): array
{
    $uid = $userId ?? (int)$user['id'];
    $canPin = userCanPinPosts($user);
    $count = $canPin ? countUserPinnedPosts($db, $uid) : 0;

    return [
        'can_pin'   => $canPin,
        'pin_limit' => VIP_PIN_POST_LIMIT,
        'pin_count' => $count,
    ];
}

/** 按对外 ID 查找信息（不限归属） */
function findPostByPublicId(PDO $db, string $publicId): ?array
{
    $publicId = trim($publicId);
    if ($publicId === '') {
        return null;
    }

    if (tableHasVidColumn($db, 'posts') && isValidVid($publicId)) {
        $stmt = $db->prepare('SELECT * FROM posts WHERE vid = ? LIMIT 1');
        $stmt->execute([$publicId]);
        $post = $stmt->fetch();
        if ($post) {
            return $post;
        }
    }

    if (ctype_digit($publicId)) {
        $stmt = $db->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$publicId]);
        $post = $stmt->fetch();
        if ($post) {
            return $post;
        }
    }

    return null;
}

/** 按对外 ID + 用户 ID 查找本人信息（与「我的发布」一致） */
function findOwnedPostByPublicId(PDO $db, string $publicId, int $userId): ?array
{
    $publicId = trim($publicId);
    if ($publicId === '') {
        return null;
    }

    if (tableHasVidColumn($db, 'posts') && isValidVid($publicId)) {
        $stmt = $db->prepare('SELECT * FROM posts WHERE vid = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$publicId, $userId]);
        $post = $stmt->fetch();
        if ($post) {
            return $post;
        }
    }

    if (ctype_digit($publicId)) {
        $stmt = $db->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([(int)$publicId, $userId]);
        $post = $stmt->fetch();
        if ($post) {
            return $post;
        }
    }

    return null;
}

function setPostPinned(PDO $db, array $user, int $userId, array $post, bool $pinned): array
{
    if (!postsHasIsTopColumn($db)) {
        jsonError('置顶功能未启用，请先执行数据库迁移', 503);
    }

    if (!userCanPinPosts($user)) {
        jsonError('普通用户无法置顶信息，请升级 VIP 或联系管理员', 403);
    }

    $postId = (int)$post['id'];
    if ((int)$post['user_id'] !== $userId) {
        jsonError('无权操作', 403);
    }
    if ((int)$post['status'] === 0) {
        jsonError('已删除的信息无法置顶');
    }

    $currentlyPinned = (int)($post['is_top'] ?? 0) === 1;

    if ($pinned && !$currentlyPinned) {
        if ((int)$post['status'] !== 1) {
            jsonError('仅展示中的信息可置顶');
        }
        if (countUserPinnedPosts($db, $userId) >= VIP_PIN_POST_LIMIT) {
            jsonError('最多置顶 ' . VIP_PIN_POST_LIMIT . ' 条信息，请先取消其他置顶');
        }
    }

    $db->prepare('UPDATE posts SET is_top = ? WHERE id = ? AND user_id = ?')->execute([$pinned ? 1 : 0, $postId, $userId]);

    $stmt = $db->prepare(
        'SELECT p.*, c.name as category_name, c.slug as category_slug FROM posts p
         LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.user_id = ?'
    );
    $stmt->execute([$postId, $userId]);
    $updated = $stmt->fetch();

    return formatPostPublic($updated ?: $post);
}

/** 解析并校验发布/编辑表单 */
function parsePostBody(PDO $db, array $body, array $user): array
{
    require_once __DIR__ . '/settings.php';
    require_once __DIR__ . '/regions.php';
    require_once __DIR__ . '/phone.php';

    $title = trim($body['title'] ?? '');
    $content = trim($body['content'] ?? '');
    $categoryId = (int)($body['category_id'] ?? 0);
    $contactPhone = trim($body['contact_phone'] ?? '');
    $contactWechat = trim($body['contact_wechat'] ?? '');

    if (!$title || !$content || !$categoryId) {
        jsonError('请填写必填项');
    }
    if ($contactPhone === '' && $contactWechat === '') {
        jsonError('请填写联系电话或微信号');
    }
    if ($contactPhone !== '') {
        $contactPhone = assertValidVenezuelaPhone($contactPhone, '联系电话');
    }
    if ($contactWechat !== '' && mb_strlen($contactWechat) > 50) {
        jsonError('微信号过长');
    }

    $imagesList = isset($body['images']) && is_array($body['images']) ? $body['images'] : [];
    if ($imagesList && !userCanUploadPostImages((int)($user['role'] ?? 0), $user)) {
        jsonError('仅 VIP 及以上用户可上传图片', 403);
    }

    $priceUnit = assertValidPriceUnit($db, $body['price_unit'] ?? null);
    [$province, $city] = assertValidRegion($db, $body['province'] ?? null, $body['city'] ?? null);

    return [
        'category_id'    => $categoryId,
        'title'          => $title,
        'content'        => $content,
        'price'          => isset($body['price']) && $body['price'] !== '' ? (float)$body['price'] : null,
        'price_unit'     => $priceUnit,
        'contact_name'   => trim($body['contact_name'] ?? '') ?: null,
        'contact_phone'  => $contactPhone,
        'contact_wechat' => $contactWechat !== '' ? $contactWechat : null,
        'province'       => $province,
        'city'           => $city,
        'district'       => trim($body['district'] ?? '') ?: null,
        'address'        => trim($body['address'] ?? '') ?: null,
        'images_list'    => $imagesList,
    ];
}

function resolvePostImagesJson(array $post, array $imagesList, array $user): string
{
    $canUpload = userCanUploadPostImages((int)($user['role'] ?? 0), $user);
    if (!$canUpload) {
        if ($imagesList) {
            jsonError('仅 VIP 及以上用户可上传图片', 403);
        }
        $existing = $post['images'] ?? '[]';
        return is_string($existing) ? $existing : json_encode($existing ?: []);
    }
    return $imagesList ? json_encode($imagesList) : '[]';
}

function updateOwnedPost(PDO $db, array $user, int $userId, array $post, array $parsed): array
{
    if ((int)$post['status'] === 0) {
        jsonError('已删除的信息无法编辑');
    }

    require_once __DIR__ . '/settings.php';

    $postId = (int)$post['id'];
    $currentStatus = (int)$post['status'];
    $reviewRequired = isPostReviewRequired($db);
    $newStatus = $currentStatus;
    $clearTop = false;

    if ($reviewRequired && $currentStatus === 1) {
        $newStatus = 2;
        $clearTop = true;
    }

    $images = resolvePostImagesJson($post, $parsed['images_list'], $user);
    $hasWechat = postsHasContactWechatColumn($db);
    $hasTop = postsHasIsTopColumn($db);

    $sets = [
        'category_id = ?',
        'title = ?',
        'content = ?',
        'price = ?',
        'price_unit = ?',
        'contact_name = ?',
        'contact_phone = ?',
    ];
    $params = [
        $parsed['category_id'],
        $parsed['title'],
        $parsed['content'],
        $parsed['price'],
        $parsed['price_unit'],
        $parsed['contact_name'],
        $parsed['contact_phone'],
    ];

    if ($hasWechat) {
        $sets[] = 'contact_wechat = ?';
        $params[] = $parsed['contact_wechat'];
    }

    $sets = array_merge($sets, [
        'province = ?',
        'city = ?',
        'district = ?',
        'address = ?',
        'images = ?',
        'status = ?',
    ]);
    $params = array_merge($params, [
        $parsed['province'],
        $parsed['city'],
        $parsed['district'],
        $parsed['address'],
        $images,
        $newStatus,
    ]);

    if ($hasTop && $clearTop) {
        $sets[] = 'is_top = 0';
    }

    $params[] = $postId;
    $params[] = $userId;

    $sql = 'UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id = ? AND user_id = ?';
    $db->prepare($sql)->execute($params);

    $stmt = $db->prepare(
        'SELECT p.*, c.name as category_name, c.slug as category_slug FROM posts p
         LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ? AND p.user_id = ?'
    );
    $stmt->execute([$postId, $userId]);
    $updated = $stmt->fetch();

    return formatPostPublic($updated ?: $post);
}

function normalizePostRow(array $post): array
{
    $post['images'] = json_decode($post['images'] ?? '[]', true) ?: [];
    $post['price'] = $post['price'] !== null ? (float)$post['price'] : null;
    if (array_key_exists('is_top', $post)) {
        $post['is_top'] = (int)$post['is_top'];
    }
    return $post;
}

function formatPostPublic(array $post): array
{
    return exposePublicId(normalizePostRow($post), 'post');
}

function formatPostsPublic(array $posts): array
{
    return array_map('formatPostPublic', $posts);
}

function postsHasContactWechatColumn(PDO $db): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM posts LIKE 'contact_wechat'");
        $has = (bool)$stmt->fetch();
    } catch (Throwable) {
        $has = false;
    }
    return $has;
}

function insertPostWithVid(PDO $db, array $data): array
{
    $hasVid = tableHasVidColumn($db, 'posts');
    $hasWechat = postsHasContactWechatColumn($db);
    $vid = $hasVid ? generateUniqueVid($db, 'posts') : null;

    if ($hasVid) {
        $wechatSql = $hasWechat ? ', contact_wechat' : '';
        $wechatVal = $hasWechat ? ', ?' : '';
        $stmt = $db->prepare(
            "INSERT INTO posts (vid, user_id, category_id, title, content, price, price_unit,
             contact_name, contact_phone{$wechatSql}, province, city, district, address, images, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?{$wechatVal}, ?, ?, ?, ?, ?, ?)"
        );
        $params = [
            $vid,
            $data['user_id'],
            $data['category_id'],
            $data['title'],
            $data['content'],
            $data['price'],
            $data['price_unit'],
            $data['contact_name'],
            $data['contact_phone'],
        ];
        if ($hasWechat) {
            $params[] = $data['contact_wechat'] ?? null;
        }
        $params = array_merge($params, [
            $data['province'],
            $data['city'],
            $data['district'],
            $data['address'],
            $data['images'],
            $data['status'],
        ]);
        $stmt->execute($params);
    } else {
        $wechatSql = $hasWechat ? ', contact_wechat' : '';
        $wechatVal = $hasWechat ? ', ?' : '';
        $stmt = $db->prepare(
            "INSERT INTO posts (user_id, category_id, title, content, price, price_unit,
             contact_name, contact_phone{$wechatSql}, province, city, district, address, images, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?{$wechatVal}, ?, ?, ?, ?, ?, ?)"
        );
        $params = [
            $data['user_id'],
            $data['category_id'],
            $data['title'],
            $data['content'],
            $data['price'],
            $data['price_unit'],
            $data['contact_name'],
            $data['contact_phone'],
        ];
        if ($hasWechat) {
            $params[] = $data['contact_wechat'] ?? null;
        }
        $params = array_merge($params, [
            $data['province'],
            $data['city'],
            $data['district'],
            $data['address'],
            $data['images'],
            $data['status'],
        ]);
        $stmt->execute($params);
    }

    $internalId = (int)$db->lastInsertId();
    $publicId = $vid ?? (string)$internalId;

    return ['internal_id' => $internalId, 'public_id' => $publicId];
}
