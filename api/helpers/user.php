<?php
/**
 * 用户相关常量与工具函数
 */

// 角色：0=普通 4=VIP 1=审核员 2=管理员 3=超级管理员
const USER_ROLE_NORMAL    = 0;
const USER_ROLE_MODERATOR = 1;
const USER_ROLE_ADMIN     = 2;
const USER_ROLE_SUPER     = 3;
const USER_ROLE_VIP       = 4;

// 状态
const USER_STATUS_DISABLED = 0;
const USER_STATUS_ACTIVE   = 1;
const USER_STATUS_PENDING  = 2;

// 性别
const USER_GENDER_UNKNOWN = 0;
const USER_GENDER_MALE    = 1;
const USER_GENDER_FEMALE  = 2;

/** 规范化角色值（保留 VIP=4，员工角色 1-3） */
function userRoleLevel(int $role): int
{
    if ($role === USER_ROLE_VIP) {
        return USER_ROLE_VIP;
    }
    return max(USER_ROLE_NORMAL, min(USER_ROLE_SUPER, $role));
}

function userIsStaffRole(int $role): bool
{
    return $role >= USER_ROLE_MODERATOR && $role <= USER_ROLE_SUPER;
}

function userIsVip(int $role): bool
{
    return $role === USER_ROLE_VIP;
}

/** 发布信息时是否可上传图片（VIP 且未过期 + 员工） */
function userCanUploadPostImages(int $role, ?array $user = null): bool
{
    if (userIsStaffRole($role)) {
        return true;
    }
    if ($role !== USER_ROLE_VIP) {
        return false;
    }
    if ($user === null) {
        return true;
    }
    require_once __DIR__ . '/vip.php';
    return userHasActiveVip($user);
}

function userRoleLabel(int $role): string
{
    return match ($role) {
        USER_ROLE_VIP       => 'VIP用户',
        USER_ROLE_MODERATOR => '审核员',
        USER_ROLE_ADMIN     => '管理员',
        USER_ROLE_SUPER     => '超级管理员',
        default             => '普通用户',
    };
}

function userHasStaffAccess(int $role): bool
{
    return userIsStaffRole($role);
}

function userHasAdminAccess(int $role): bool
{
    return userRoleLevel($role) >= USER_ROLE_ADMIN;
}

function userHasSuperAccess(int $role): bool
{
    return userRoleLevel($role) >= USER_ROLE_SUPER;
}

/** 后台模块访问权限 */
function userCanAccessAdminModule(int $role, string $module): bool
{
    if (!userIsStaffRole($role)) {
        return false;
    }
    $level = userRoleLevel($role);
    return match ($module) {
        'dashboard', 'posts' => $level >= USER_ROLE_MODERATOR,
        'categories', 'users', 'regions', 'settings', 'vip-plans' => $level >= USER_ROLE_ADMIN,
        default => false,
    };
}

/** 是否可为目标用户分配指定角色 */
function userCanAssignRole(array $actor, int $targetRole): bool
{
    $actorLevel = userRoleLevel((int)($actor['role'] ?? 0));
    if (!userIsStaffRole($actorLevel) || $actorLevel < USER_ROLE_ADMIN) {
        return false;
    }

    if ($targetRole === USER_ROLE_VIP || $targetRole === USER_ROLE_NORMAL) {
        return true;
    }

    $targetRole = userRoleLevel($targetRole);
    if ($actorLevel === USER_ROLE_ADMIN && $targetRole >= USER_ROLE_SUPER) {
        return false;
    }
    return $targetRole <= $actorLevel;
}

/** 是否可编辑目标用户（角色/状态） */
function userCanModifyUser(array $actor, array $target): bool
{
    $actorLevel = userRoleLevel((int)($actor['role'] ?? 0));
    if ($actorLevel < USER_ROLE_ADMIN) {
        return false;
    }

    $targetRole = (int)($target['role'] ?? 0);
    if ($targetRole === USER_ROLE_VIP || $targetRole === USER_ROLE_NORMAL) {
        return true;
    }

    $targetLevel = userRoleLevel($targetRole);
    if ($targetLevel > $actorLevel) {
        return false;
    }
    return true;
}

/** 获取客户端 IP */
function getClientIp(): string
{
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/** 对外暴露的用户字段（不含敏感信息） */
function formatUserPublic(array $user): array
{
    require_once __DIR__ . '/vid.php';

    $role = (int)($user['role'] ?? USER_ROLE_NORMAL);
    if ($role !== USER_ROLE_VIP) {
        $role = userRoleLevel($role);
    }

    $publicId = !empty($user['vid']) ? $user['vid'] : (int)$user['id'];

    return [
        'id'            => $publicId,
        'username'      => $user['username'],
        'phone'         => $user['phone'],
        'email'         => $user['email'] ?? null,
        'avatar'        => $user['avatar'] ?? null,
        'role'          => $role,
        'role_label'    => userRoleLabel($role),
        'status'        => (int)($user['status'] ?? USER_STATUS_ACTIVE),
        'status_label'  => userStatusLabel((int)($user['status'] ?? USER_STATUS_ACTIVE)),
        'gender'        => (int)($user['gender'] ?? USER_GENDER_UNKNOWN),
        'gender_label'  => userGenderLabel((int)($user['gender'] ?? USER_GENDER_UNKNOWN)),
        'real_name'     => $user['real_name'] ?? null,
        'bio'           => $user['bio'] ?? null,
        'province'      => $user['province'] ?? null,
        'city'          => $user['city'] ?? null,
        'district'      => $user['district'] ?? null,
        'register_source' => $user['register_source'] ?? 'web',
        'login_count'   => (int)($user['login_count'] ?? 0),
        'last_login_at' => $user['last_login_at'] ?? null,
        'created_at'    => $user['created_at'] ?? null,
        'updated_at'    => $user['updated_at'] ?? null,
        'vip_expires_at' => $user['vip_expires_at'] ?? null,
    ];
}

function userStatusLabel(int $status): string
{
    return match ($status) {
        USER_STATUS_DISABLED => '已禁用',
        USER_STATUS_PENDING  => '待审核',
        default              => '正常',
    };
}

function userGenderLabel(int $gender): string
{
    return match ($gender) {
        USER_GENDER_MALE   => '男',
        USER_GENDER_FEMALE => '女',
        default            => '保密',
    };
}

/** 校验用户是否可登录/操作 */
function assertUserActive(array $user): void
{
    $status = (int)($user['status'] ?? USER_STATUS_ACTIVE);
    if ($status === USER_STATUS_DISABLED) {
        jsonError('账号已被禁用，请联系管理员', 403);
    }
    if ($status === USER_STATUS_PENDING) {
        jsonError('账号审核中，请稍后再试', 403);
    }
}

/** 是否已执行用户扩展字段迁移（004） */
function usersHasLoginColumns(PDO $db): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'last_login_at'");
        $has = (bool)$stmt->fetch();
    } catch (Throwable) {
        $has = false;
    }
    return $has;
}

/** 记录登录信息 */
function recordUserLogin(PDO $db, int $userId): void
{
    if (!usersHasLoginColumns($db)) {
        return;
    }
    $ip = getClientIp();
    $stmt = $db->prepare(
        'UPDATE users SET last_login_at = NOW(), last_login_ip = ?, login_count = login_count + 1 WHERE id = ?'
    );
    $stmt->execute([$ip, $userId]);
}

/** 根据 ID 获取用户 */
function findUserById(PDO $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/** 按登录账号（邮箱或手机号）查找用户，兼容带/不带连字符及旧版号码 */
function findUserByLoginAccount(PDO $db, string $account): ?array
{
    require_once __DIR__ . '/phone.php';

    $account = trim($account);
    if ($account === '') {
        return null;
    }

    if (str_contains($account, '@')) {
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower($account)]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    $normalized = normalizeVenezuelaPhone($account);
    if ($normalized !== null) {
        $compact = str_replace('-', '', $normalized);
        $stmt = $db->prepare(
            'SELECT * FROM users WHERE phone = ? OR phone = ? OR REPLACE(phone, "-", "") = ? LIMIT 1'
        );
        $stmt->execute([$normalized, $compact, $compact]);
        $user = $stmt->fetch();
        if ($user) {
            return $user;
        }
    }

    $digits = preg_replace('/\D/', '', $account);
    $candidates = array_unique(array_filter([$account, $digits]));
    foreach ($candidates as $phone) {
        $stmt = $db->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        if ($user) {
            return $user;
        }
    }

    return null;
}

/** 用户可编辑字段白名单 */
function getUserEditableFields(): array
{
    return ['username', 'avatar', 'gender', 'real_name', 'bio', 'province', 'city', 'district'];
}

/** 要求已登录且账号正常 */
function requireAuthUser(): array
{
    $userId = requireAuth();
    $db = Database::getConnection();
    require_once __DIR__ . '/vip.php';
    expireVipUsers($db, $userId);
    $user = findUserById($db, $userId);
    if (!$user) {
        jsonError('用户不存在', 404);
    }
    assertUserActive($user);
    return $user;
}

/** 要求审核员及以上（可进后台） */
function requireStaff(): array
{
    $user = requireAuthUser();
    if (!userHasStaffAccess((int)($user['role'] ?? 0))) {
        jsonError('无后台访问权限', 403);
    }
    return $user;
}

/** 要求管理员及以上 */
function requireAdmin(): array
{
    $user = requireAuthUser();
    if (!userHasAdminAccess((int)($user['role'] ?? 0))) {
        jsonError('无管理员权限', 403);
    }
    return $user;
}

/** 要求超级管理员 */
function requireSuperAdmin(): array
{
    $user = requireAuthUser();
    if (!userHasSuperAccess((int)($user['role'] ?? 0))) {
        jsonError('无超级管理员权限', 403);
    }
    return $user;
}

/** 管理端用户列表字段 */
function formatUserAdmin(array $user): array
{
    $data = formatUserPublic($user);
    $data['register_ip'] = $user['register_ip'] ?? null;
    $data['last_login_ip'] = $user['last_login_ip'] ?? null;
    return $data;
}
