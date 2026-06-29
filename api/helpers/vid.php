<?php
/**
 * 对外公开 ID（VID）：32 位十六进制随机字符串，隐藏自增 id
 */

function isValidVid(string $value): bool
{
    return (bool)preg_match('/^[a-f0-9]{32}$/i', $value);
}

function tableHasVidColumn(PDO $db, string $table): bool
{
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM {$table} LIKE 'vid'");
        $cache[$table] = (bool)$stmt->fetch();
    } catch (Throwable) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

/** 生成唯一 VID */
function generateUniqueVid(PDO $db, string $table): string
{
    if (!in_array($table, ['users', 'posts'], true)) {
        throw new InvalidArgumentException('不支持的表');
    }

    for ($i = 0; $i < 12; $i++) {
        $vid = bin2hex(random_bytes(16));
        $stmt = $db->prepare("SELECT id FROM {$table} WHERE vid = ? LIMIT 1");
        $stmt->execute([$vid]);
        if (!$stmt->fetch()) {
            return $vid;
        }
    }

    throw new RuntimeException('无法生成唯一 VID');
}

function resolveUserId(PDO $db, string $identifier): ?int
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    if (tableHasVidColumn($db, 'users') && isValidVid($identifier)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE vid = ? LIMIT 1');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    if (ctype_digit($identifier)) {
        $stmt = $db->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$identifier]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    return null;
}

function resolvePostId(PDO $db, string $identifier): ?int
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    if (tableHasVidColumn($db, 'posts') && isValidVid($identifier)) {
        $stmt = $db->prepare('SELECT id FROM posts WHERE vid = ? LIMIT 1');
        $stmt->execute([$identifier]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    if (ctype_digit($identifier)) {
        $stmt = $db->prepare('SELECT id FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$identifier]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    return null;
}

function findUserByVid(PDO $db, string $vid): ?array
{
    if (!isValidVid($vid) || !tableHasVidColumn($db, 'users')) {
        return null;
    }
    $stmt = $db->prepare('SELECT * FROM users WHERE vid = ? LIMIT 1');
    $stmt->execute([$vid]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function findPostByVid(PDO $db, string $vid): ?array
{
    if (!isValidVid($vid) || !tableHasVidColumn($db, 'posts')) {
        return null;
    }
    $stmt = $db->prepare('SELECT * FROM posts WHERE vid = ? LIMIT 1');
    $stmt->execute([$vid]);
    $post = $stmt->fetch();
    return $post ?: null;
}

/** 对外响应：用 vid 替换 id，移除内部字段 */
function exposePublicId(array $row, string $entity = 'generic'): array
{
    if (!empty($row['vid'])) {
        $row['id'] = $row['vid'];
    }
    unset($row['vid']);

    if ($entity === 'post') {
        unset($row['user_id']);
    }

    return $row;
}

function backfillTableVids(PDO $db, string $table): void
{
    if (!in_array($table, ['users', 'posts'], true)) {
        return;
    }

    $stmt = $db->query("SELECT id FROM {$table} WHERE vid IS NULL OR vid = ''");
    while ($row = $stmt->fetch()) {
        $vid = generateUniqueVid($db, $table);
        $update = $db->prepare("UPDATE {$table} SET vid = ? WHERE id = ?");
        $update->execute([$vid, (int)$row['id']]);
    }
}
