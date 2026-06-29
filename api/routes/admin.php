<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../helpers/user.php';
require_once __DIR__ . '/../helpers/settings.php';
require_once __DIR__ . '/../helpers/regions.php';
require_once __DIR__ . '/../helpers/phone.php';
require_once __DIR__ . '/../helpers/vid.php';
require_once __DIR__ . '/../helpers/vip.php';
require_once __DIR__ . '/../helpers/post.php';

function handleAdmin(string $method, ?string $module, ?string $recordId): void
{
    $currentUser = requireStaff();
    $role = userRoleLevel((int)($currentUser['role'] ?? 0));

    if (!$module || !userCanAccessAdminModule($role, $module)) {
        jsonError('无权访问该模块', 403);
    }

    $db = Database::getConnection();

    switch ($module) {
        case 'dashboard':
            handleAdminDashboard($db, $currentUser);
            break;
        case 'categories':
            handleAdminCategories($db, $method, $recordId);
            break;
        case 'posts':
            handleAdminPosts($db, $method, $recordId);
            break;
        case 'users':
            handleAdminUsers($db, $method, $recordId, $currentUser);
            break;
        case 'settings':
            handleAdminSettings($db, $method);
            break;
        case 'vip-plans':
            handleAdminVipPlans($db, $method, $recordId);
            break;
        default:
            jsonError('Not found', 404);
    }
}

function handleAdminDashboard(PDO $db, array $currentUser): void
{
    $stats = [
        'users'      => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'posts'      => (int)$db->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
        'posts_active' => (int)$db->query('SELECT COUNT(*) FROM posts WHERE status = 1')->fetchColumn(),
        'posts_pending' => (int)$db->query('SELECT COUNT(*) FROM posts WHERE status = 2')->fetchColumn(),
        'categories' => (int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    ];

    $stmt = $db->query(
        "SELECT p.*, c.name AS category_name, u.username
         FROM posts p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN users u ON p.user_id = u.id
         ORDER BY p.created_at DESC LIMIT 8"
    );
    $recentPosts = formatPostsPublic($stmt->fetchAll());

    $recentUsers = [];
    if (userHasAdminAccess((int)($currentUser['role'] ?? 0))) {
        $stmt = $db->query(
            "SELECT * FROM users ORDER BY created_at DESC LIMIT 8"
        );
        $recentUsers = array_map('formatUserAdmin', $stmt->fetchAll());
    }

    jsonSuccess([
        'stats'        => $stats,
        'recent_posts' => $recentPosts,
        'recent_users' => $recentUsers,
    ]);
}

function handleAdminCategories(PDO $db, string $method, ?string $id): void
{
    if ($method === 'GET' && !$id) {
        $stmt = $db->query('SELECT * FROM categories ORDER BY parent_id, sort_order, id');
        jsonSuccess($stmt->fetchAll());
    }

    if ($method === 'POST' && !$id) {
        $body = getRequestBody();
        $name = trim($body['name'] ?? '');
        $slug = trim($body['slug'] ?? '');
        if (!$name || !$slug) jsonError('名称和标识不能为空');

        $stmt = $db->prepare(
            'INSERT INTO categories (parent_id, name, slug, icon, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int)($body['parent_id'] ?? 0),
            $name,
            $slug,
            $body['icon'] ?? null,
            (int)($body['sort_order'] ?? 0),
        ]);
        jsonSuccess(['id' => (int)$db->lastInsertId()], '创建成功');
    }

    if ($id && $method === 'PUT') {
        $body = getRequestBody();
        $stmt = $db->prepare('SELECT id FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) jsonError('分类不存在', 404);

        $stmt = $db->prepare(
            'UPDATE categories SET parent_id=?, name=?, slug=?, icon=?, sort_order=? WHERE id=?'
        );
        $stmt->execute([
            (int)($body['parent_id'] ?? 0),
            trim($body['name'] ?? ''),
            trim($body['slug'] ?? ''),
            $body['icon'] ?? null,
            (int)($body['sort_order'] ?? 0),
            $id,
        ]);
        jsonSuccess(null, '更新成功');
    }

    if ($id && $method === 'DELETE') {
        $stmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE category_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            jsonError('该分类下仍有信息，无法删除');
        }
        $stmt = $db->prepare('SELECT COUNT(*) FROM categories WHERE parent_id = ?');
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            jsonError('请先删除子分类');
        }
        $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
        jsonSuccess(null, '删除成功');
    }

    jsonError('Method not allowed', 405);
}

function handleAdminPosts(PDO $db, string $method, ?string $id): void
{
    if ($method === 'GET' && !$id) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? '';
        $keyword = trim($_GET['keyword'] ?? '');

        $where = ['1=1'];
        $params = [];
        if ($status !== '') {
            $where[] = 'p.status = ?';
            $params[] = (int)$status;
        }
        if ($keyword) {
            $where[] = '(p.title LIKE ? OR p.content LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        $whereStr = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM posts p WHERE $whereStr");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT p.*, c.name AS category_name, u.username
                FROM posts p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users u ON p.user_id = u.id
                WHERE $whereStr
                ORDER BY p.created_at DESC
                LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $list = formatPostsPublic($stmt->fetchAll());

        jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    if ($id && $method === 'PUT') {
        $body = getRequestBody();
        $postId = resolvePostId($db, (string)$id);
        if (!$postId) {
            jsonError('信息不存在', 404);
        }

        $stmt = $db->prepare('SELECT id FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        if (!$stmt->fetch()) {
            jsonError('信息不存在', 404);
        }

        if (isset($body['status'])) {
            $status = (int)$body['status'];
            if (!in_array($status, [0, 1, 2], true)) {
                jsonError('状态无效');
            }
            $db->prepare('UPDATE posts SET status = ? WHERE id = ?')->execute([$status, $postId]);
        }
        jsonSuccess(null, '更新成功');
    }

    if ($id && $method === 'DELETE') {
        $postId = resolvePostId($db, (string)$id);
        if (!$postId) {
            jsonError('信息不存在', 404);
        }
        $db->prepare('UPDATE posts SET status = 0 WHERE id = ?')->execute([$postId]);
        jsonSuccess(null, '已下架');
    }

    jsonError('Method not allowed', 405);
}

function handleAdminUsers(PDO $db, string $method, ?string $id, array $currentUser): void
{
    if ($method === 'GET' && !$id) {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $keyword = trim($_GET['keyword'] ?? '');

        $where = '1=1';
        $params = [];
        if ($keyword) {
            $where = '(username LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $params = ["%$keyword%", "%$keyword%", "%$keyword%"];
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT * FROM users WHERE $where ORDER BY id DESC LIMIT $limit OFFSET $offset"
        );
        $stmt->execute($params);
        $list = array_map('formatUserAdmin', $stmt->fetchAll());

        jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    if ($id && $method === 'PUT') {
        $body = getRequestBody();
        $targetUserId = resolveUserId($db, (string)$id);
        if (!$targetUserId) {
            jsonError('用户不存在', 404);
        }

        $user = findUserById($db, $targetUserId);
        if (!$user) {
            jsonError('用户不存在', 404);
        }

        if (!userCanModifyUser($currentUser, $user)) {
            jsonError('无权修改该用户', 403);
        }

        if (isset($body['role'])) {
            $newRole = (int)$body['role'];
            if ($newRole !== USER_ROLE_VIP) {
                $newRole = userRoleLevel($newRole);
            }
            if (!userCanAssignRole($currentUser, $newRole)) {
                jsonError('无权分配该角色', 403);
            }
            if ($targetUserId === (int)$currentUser['id']) {
                $actorStaff = userIsStaffRole((int)($currentUser['role'] ?? 0));
                $newStaff = userIsStaffRole($newRole);
                if ($actorStaff && (!$newStaff || userRoleLevel($newRole) < userRoleLevel((int)$currentUser['role']))) {
                    jsonError('不能降低自己的权限级别');
                }
            }
        }

        if ($targetUserId === (int)$currentUser['id'] && isset($body['status']) && (int)$body['status'] === USER_STATUS_DISABLED) {
            jsonError('不能禁用自己');
        }

        $updates = [];
        $params = [];
        if (isset($body['role'])) {
            $updates[] = 'role = ?';
            $roleVal = (int)$body['role'];
            $params[] = $roleVal === USER_ROLE_VIP ? USER_ROLE_VIP : userRoleLevel($roleVal);
            if (usersHasVipExpiresColumn($db)) {
                if ($roleVal === USER_ROLE_VIP) {
                    // 手动设为 VIP 时不自动写入到期时间（永久 VIP）
                } else {
                    $updates[] = 'vip_expires_at = NULL';
                }
            }
        }
        if (isset($body['status'])) {
            $status = (int)$body['status'];
            if (!in_array($status, [USER_STATUS_DISABLED, USER_STATUS_ACTIVE, USER_STATUS_PENDING], true)) {
                jsonError('状态无效');
            }
            $updates[] = 'status = ?';
            $params[] = $status;
        }
        if (!$updates) {
            jsonError('没有可更新字段');
        }

        $params[] = $targetUserId;
        $db->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
        jsonSuccess(formatUserAdmin(findUserById($db, $targetUserId)), '更新成功');
    }

    jsonError('Method not allowed', 405);
}

function handleAdminSettings(PDO $db, string $method): void
{
    if ($method === 'GET') {
        $stmt = $db->query('SELECT setting_key, setting_value, label, updated_at FROM settings ORDER BY id');
        $rows = $stmt->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'label' => $row['label'],
                'updated_at' => $row['updated_at'],
            ];
        }
        if (isset($settings['bank_api_token'])) {
            $configured = trim((string)($settings['bank_api_token']['value'] ?? '')) !== '';
            $settings['bank_api_token']['value'] = '';
            $settings['bank_api_token']['configured'] = $configured;
        }
        jsonSuccess($settings);
    }

    if ($method === 'PUT') {
        $body = getRequestBody();
        if (!$body) jsonError('请提供设置项');

        if (isset($body['price_units'])) {
            $units = parsePriceUnitsValue($body['price_units']);
            if (!$units) {
                jsonError('至少保留一个价格单位');
            }
            $body['price_units'] = encodePriceUnits($units);
        }

        if (isset($body['regions'])) {
            $regions = parseRegionsValue($body['regions']);
            if (!$regions) {
                jsonError('至少保留一个省份及城市');
            }
            $body['regions'] = encodeRegions($regions);
        }

        $stmt = $db->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
        foreach ($body as $key => $value) {
            if (!is_string($key) || $key === '') continue;
            if ($key === 'bank_api_token' && trim((string)$value) === '') {
                continue;
            }
            if ($key === 'contact_phone') {
                $value = normalizeOptionalVenezuelaPhone((string)$value, '联系电话') ?? '';
            }
            $stmt->execute([(string)$value, $key]);
        }
        handleAdminSettings($db, 'GET');
        return;
    }

    jsonError('Method not allowed', 405);
}

function handleAdminVipPlans(PDO $db, string $method, ?string $id): void
{
    if ($method === 'GET' && !$id) {
        jsonSuccess([
            'list' => getAllVipPlans($db),
            'bcv'  => formatBcvRatePublic(getBcvUsdRate($db)),
        ]);
        return;
    }

    if ($method === 'POST' && !$id) {
        $body = getRequestBody();
        $plan = createVipPlan($db, $body);
        jsonSuccess($plan, '套餐已创建');
        return;
    }

    if ($id && $method === 'PUT') {
        $body = getRequestBody();
        $plan = updateVipPlan($db, (int)$id, $body);
        jsonSuccess($plan, '套餐已更新');
        return;
    }

    if ($id && $method === 'DELETE') {
        deleteVipPlan($db, (int)$id);
        jsonSuccess(null, '套餐已删除');
        return;
    }

    jsonError('Method not allowed', 405);
}
