<?php

const DEFAULT_PRICE_UNITS = ['元', '元/月', '元/次', '元/天', '万元'];

/** 从 settings 读取价格单位列表 */
function getPriceUnits(PDO $db): array
{
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'price_units'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && ($row['setting_value'] ?? '') !== '') {
            $units = parsePriceUnitsValue($row['setting_value']);
            if ($units) {
                return $units;
            }
        }
    } catch (Throwable) {
    }

    return DEFAULT_PRICE_UNITS;
}

/** 解析价格单位（JSON 数组或逗号/换行分隔文本） */
function parsePriceUnitsValue(mixed $input): array
{
    if (is_array($input)) {
        return normalizePriceUnits($input);
    }

    $text = trim((string)$input);
    if ($text === '') {
        return [];
    }

    if ($text[0] === '[') {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return normalizePriceUnits($decoded);
        }
    }

    $parts = preg_split('/[\n,，;；]+/', $text) ?: [];
    return normalizePriceUnits($parts);
}

/** 清洗并去重价格单位 */
function normalizePriceUnits(array $units): array
{
    $result = [];
    foreach ($units as $unit) {
        $unit = trim((string)$unit);
        if ($unit === '' || mb_strlen($unit) > 20) {
            continue;
        }
        if (!in_array($unit, $result, true)) {
            $result[] = $unit;
        }
    }
    return $result;
}

/** 校验发帖使用的单位是否合法 */
function assertValidPriceUnit(PDO $db, ?string $unit): string
{
    $unit = trim((string)$unit);
    $allowed = getPriceUnits($db);

    if ($unit === '') {
        return $allowed[0] ?? '元';
    }

    if (!in_array($unit, $allowed, true)) {
        jsonError('价格单位无效，请从列表中选择');
    }

    return $unit;
}

/** 序列化保存到 settings */
function encodePriceUnits(array $units): string
{
    $normalized = normalizePriceUnits($units);
    if (!$normalized) {
        $normalized = DEFAULT_PRICE_UNITS;
    }
    return json_encode($normalized, JSON_UNESCAPED_UNICODE);
}

/** 读取单个设置项 */
function getSetting(PDO $db, string $key, mixed $default = null): mixed
{
    try {
        $stmt = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row && ($row['setting_value'] ?? '') !== '') {
            return $row['setting_value'];
        }
    } catch (Throwable) {
    }
    return $default;
}

/** 前台公开设置（不含敏感项） */
function getPublicSettings(PDO $db): array
{
    return [
        'site_name'           => (string)getSetting($db, 'site_name', '同城信息'),
        'site_description'    => (string)getSetting($db, 'site_description', ''),
        'site_logo'           => (string)getSetting($db, 'site_logo', ''),
        'contact_email'       => (string)getSetting($db, 'contact_email', ''),
        'contact_phone'       => (string)getSetting($db, 'contact_phone', ''),
        'posts_per_page'      => max(5, min(50, (int)getSetting($db, 'posts_per_page', 20))),
        'allow_register'      => getSetting($db, 'allow_register', '1') !== '0',
        'require_post_review' => getSetting($db, 'require_post_review', '0') === '1',
        'vip_upgrade'         => getVipPlanPublic($db),
    ];
}

function isRegisterAllowed(PDO $db): bool
{
    return getSetting($db, 'allow_register', '1') !== '0';
}

function isPostReviewRequired(PDO $db): bool
{
    return getSetting($db, 'require_post_review', '0') === '1';
}
