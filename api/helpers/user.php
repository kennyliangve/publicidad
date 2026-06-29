<?php
/**
 * 用户相关常量与工具函数
 */

// 角色
const USER_ROLE_NORMAL = 0;
const USER_ROLE_ADMIN  = 1;

// 状态
const USER_STATUS_DISABLED = 0;
const USER_STATUS_ACTIVE   = 1;
const USER_STATUS_PENDING  = 2;

// 性别
const USER_GENDER_UNKNOWN = 0;
const USER_GENDER_MALE    = 1;
const USER_GENDER_FEMALE  = 2;

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
    return [
        'id'            => (int)$user['id'],
        'username'      => $user['username'],
        'phone'         => $user['phone'],
        'email'         => $user['email'] ?? null,
        'avatar'        => $user['avatar'] ?? null,
        'role'          => (int)($user['role'] ?? USER_ROLE_NORMAL),
        'role_label'    => ((int)($user['role'] ?? 0) === USER_ROLE_ADMIN) ? '管理员' : '普通用户',
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

/** 记录登录信息 */
function recordUserLogin(PDO $db, int $userId): void
{
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

/** 用户可编辑字段白名单 */
function getUserEditableFields(): array
{
    return ['username', 'avatar', 'gender', 'real_name', 'bio', 'province', 'city', 'district'];
}

/** 要求管理员权限，返回当前管理员用户 */
function requireAdmin(): array
{
    $userId = requireAuth();
    $db = Database::getConnection();
    $user = findUserById($db, $userId);
    if (!$user) {
        jsonError('用户不存在', 404);
    }
    assertUserActive($user);
    if ((int)($user['role'] ?? 0) !== USER_ROLE_ADMIN) {
        jsonError('无管理员权限', 403);
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
