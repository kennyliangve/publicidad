<?php

/** 获取默认地区（委内瑞拉） */
function getDefaultRegions(): array
{
    static $defaults = null;
    if ($defaults === null) {
        $defaults = require __DIR__ . '/../data/default_regions.php';
    }
    return $defaults;
}

/** 从 settings 读取地区树 */
function getRegions(PDO $db): array
{
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'regions'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && ($row['setting_value'] ?? '') !== '') {
            $regions = parseRegionsValue($row['setting_value']);
            if ($regions) {
                return $regions;
            }
        }
    } catch (Throwable) {
    }

    return getDefaultRegions();
}

/** 解析地区 JSON */
function parseRegionsValue(mixed $input): array
{
    if (is_string($input)) {
        $text = trim($input);
        if ($text === '') {
            return [];
        }
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            return [];
        }
        $input = $decoded;
    }

    if (!is_array($input)) {
        return [];
    }

    return normalizeRegions($input);
}

/** 清洗地区数据 */
function normalizeRegions(array $regions): array
{
    $result = [];
    $seenProvinces = [];

    foreach ($regions as $item) {
        if (!is_array($item)) {
            continue;
        }
        $province = trim((string)($item['province'] ?? ''));
        if ($province === '' || mb_strlen($province) > 80 || isset($seenProvinces[$province])) {
            continue;
        }

        $citiesRaw = $item['cities'] ?? [];
        if (!is_array($citiesRaw)) {
            $citiesRaw = preg_split('/[\n,，;；]+/', (string)$citiesRaw) ?: [];
        }

        $cities = [];
        foreach ($citiesRaw as $city) {
            $city = trim((string)$city);
            if ($city === '' || mb_strlen($city) > 80) {
                continue;
            }
            if (!in_array($city, $cities, true)) {
                $cities[] = $city;
            }
        }

        if (!$cities) {
            continue;
        }

        $seenProvinces[$province] = true;
        $result[] = [
            'province' => $province,
            'cities'   => $cities,
        ];
    }

    return $result;
}

/** 校验发帖/资料中的省与城市 */
function assertValidRegion(PDO $db, ?string $province, ?string $city): array
{
    $province = trim((string)$province);
    $city = trim((string)$city);

    if ($province === '' && $city === '') {
        return [null, null];
    }

    if ($province === '' || $city === '') {
        jsonError('请选择完整的省份和城市');
    }

    $regions = getRegions($db);
    foreach ($regions as $region) {
        if ($region['province'] !== $province) {
            continue;
        }
        if (in_array($city, $region['cities'], true)) {
            return [$province, $city];
        }
        jsonError('所选城市不属于该省份');
    }

    jsonError('所选省份无效');
}

/** 序列化保存到 settings */
function encodeRegions(array $regions): string
{
    $normalized = normalizeRegions($regions);
    if (!$normalized) {
        $normalized = getDefaultRegions();
    }
    return json_encode($normalized, JSON_UNESCAPED_UNICODE);
}

/** 扁平化城市列表（搜索筛选用） */
function flattenRegionCities(array $regions): array
{
    $list = [];
    foreach ($regions as $region) {
        foreach ($region['cities'] as $city) {
            $list[] = [
                'province' => $region['province'],
                'city'     => $city,
            ];
        }
    }
    return $list;
}
