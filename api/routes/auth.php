<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../helpers/user.php';

function handleAuth(string $method, ?string $action): void
{
    $db = Database::getConnection();
    $body = getRequestBody();

    switch ($action) {
        case 'register':
            if ($method !== 'POST') jsonError('Method not allowed', 405);

            $username = trim($body['username'] ?? '');
            $phone = trim($body['phone'] ?? '');
            $email = strtolower(trim($body['email'] ?? ''));
            $password = $body['password'] ?? '';

            if (!$username || !$phone || !$email || !$password) {
                jsonError('请填写完整信息');
            }
            if (strlen($password) < 6) {
                jsonError('密码至少6位');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonError('邮箱格式不正确');
            }

            $stmt = $db->prepare('SELECT id FROM users WHERE phone = ?');
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                jsonError('该手机号已注册');
            }

            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                jsonError('该邮箱已注册');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $registerIp = getClientIp();
            $registerSource = trim($body['register_source'] ?? 'web') ?: 'web';

            $stmt = $db->prepare(
                'INSERT INTO users (username, phone, email, password, register_ip, register_source, status, role) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $username, $phone, $email, $hash,
                $registerIp, $registerSource,
                USER_STATUS_ACTIVE, USER_ROLE_NORMAL,
            ]);
            $userId = (int)$db->lastInsertId();

            $user = findUserById($db, $userId);
            recordUserLogin($db, $userId);
            $user = findUserById($db, $userId);

            jsonSuccess([
                'token' => generateToken($userId),
                'user'  => formatUserPublic($user),
            ], '注册成功');
            break;

        case 'login':
            if ($method !== 'POST') jsonError('Method not allowed', 405);

            $account = trim($body['account'] ?? $body['phone'] ?? '');
            $password = $body['password'] ?? '';

            if (!$account || !$password) {
                jsonError('请填写账号和密码');
            }

            if (strpos($account, '@') !== false) {
                $account = strtolower($account);
                $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
            } else {
                $stmt = $db->prepare('SELECT * FROM users WHERE phone = ?');
            }
            $stmt->execute([$account]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                jsonError('账号或密码错误');
            }

            assertUserActive($user);
            recordUserLogin($db, (int)$user['id']);
            $user = findUserById($db, (int)$user['id']);

            jsonSuccess([
                'token' => generateToken((int)$user['id']),
                'user'  => formatUserPublic($user),
            ], '登录成功');
            break;

        case 'profile':
            if ($method === 'GET') {
                $userId = requireAuth();
                $user = findUserById($db, $userId);
                if (!$user) jsonError('用户不存在', 404);
                assertUserActive($user);
                jsonSuccess(formatUserPublic($user));
            }

            if ($method === 'PUT') {
                $userId = requireAuth();
                $user = findUserById($db, $userId);
                if (!$user) jsonError('用户不存在', 404);
                assertUserActive($user);

                $allowed = getUserEditableFields();
                $updates = [];
                $params = [];

                foreach ($allowed as $field) {
                    if (!array_key_exists($field, $body)) continue;
                    $value = $body[$field];

                    if ($field === 'username') {
                        $value = trim((string)$value);
                        if ($value === '') jsonError('昵称不能为空');
                        if (mb_strlen($value) > 50) jsonError('昵称过长');
                    } elseif ($field === 'gender') {
                        $value = (int)$value;
                        if (!in_array($value, [USER_GENDER_UNKNOWN, USER_GENDER_MALE, USER_GENDER_FEMALE], true)) {
                            jsonError('性别参数无效');
                        }
                    } elseif (in_array($field, ['real_name', 'bio', 'province', 'city', 'district', 'avatar'], true)) {
                        $value = trim((string)$value) ?: null;
                    }

                    $updates[] = "$field = ?";
                    $params[] = $value;
                }

                if (!$updates) {
                    jsonError('没有可更新的字段');
                }

                $params[] = $userId;
                $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
                $db->prepare($sql)->execute($params);

                $user = findUserById($db, $userId);
                jsonSuccess(formatUserPublic($user), '资料已更新');
            }

            jsonError('Method not allowed', 405);
            break;

        default:
            jsonError('Not found', 404);
    }
}
