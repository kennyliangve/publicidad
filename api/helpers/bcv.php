<?php

require_once __DIR__ . '/settings.php';

const BCV_RATE_SETTING_KEY = 'bcv_usd_rate';
const BCV_RATE_DATE_SETTING_KEY = 'bcv_usd_rate_date';
const BCV_RATE_EFFECTIVE_SETTING_KEY = 'bcv_usd_rate_effective_date';
const BCV_RATE_UPDATED_SETTING_KEY = 'bcv_usd_rate_updated_at';
const BCV_RATE_MANUAL_SETTING_KEY = 'bcv_usd_rate_manual';
const BCV_RATE_SOURCE_SETTING_KEY = 'bcv_usd_rate_source';

/** 委内瑞拉当地今天 (Y-m-d) */
function getVenezuelaTodayDate(): string
{
    $tz = new DateTimeZone('America/Caracas');
    return (new DateTime('now', $tz))->format('Y-m-d');
}

function saveSettingValue(PDO $db, string $key, string $value): void
{
    $stmt = $db->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?');
    $stmt->execute([$value, $key]);
}

function getBcvManualRate(PDO $db): ?float
{
    $manual = trim((string)getSetting($db, BCV_RATE_MANUAL_SETTING_KEY, ''));
    if ($manual === '') {
        return null;
    }
    $rate = (float)$manual;
    return $rate > 0 ? $rate : null;
}

function getCachedBcvRate(PDO $db): ?array
{
    $rate = (float)getSetting($db, BCV_RATE_SETTING_KEY, '0');
    if ($rate <= 0) {
        return null;
    }

    return [
        'rate'           => $rate,
        'date'           => (string)getSetting($db, BCV_RATE_DATE_SETTING_KEY, ''),
        'effective_date' => (string)getSetting($db, BCV_RATE_EFFECTIVE_SETTING_KEY, ''),
        'updated_at'     => (string)getSetting($db, BCV_RATE_UPDATED_SETTING_KEY, ''),
        'source'         => (string)getSetting($db, BCV_RATE_SOURCE_SETTING_KEY, 'cache'),
        'cached'         => true,
    ];
}

function cacheBcvRate(PDO $db, array $data): void
{
    $today = getVenezuelaTodayDate();
    saveSettingValue($db, BCV_RATE_SETTING_KEY, number_format((float)$data['rate'], 6, '.', ''));
    saveSettingValue($db, BCV_RATE_DATE_SETTING_KEY, $today);
    saveSettingValue($db, BCV_RATE_EFFECTIVE_SETTING_KEY, (string)($data['effective_date'] ?? $today));
    saveSettingValue($db, BCV_RATE_UPDATED_SETTING_KEY, date('Y-m-d H:i:s'));
    saveSettingValue($db, BCV_RATE_SOURCE_SETTING_KEY, (string)($data['source'] ?? 'bcv'));
}

function parseBcvTodayResponse(array $decoded): ?array
{
    if (empty($decoded['USD'])) {
        return null;
    }

    $rate = (float)$decoded['USD'];
    if ($rate <= 0) {
        return null;
    }

    return [
        'rate'           => $rate,
        'effective_date' => (string)($decoded['effective_date'] ?? $decoded['date'] ?? getVenezuelaTodayDate()),
        'source'         => 'bcv.today',
    ];
}

function parseBcvExchangeRatesResponse(array $decoded): ?array
{
    $value = $decoded['data']['dolar']['value'] ?? null;
    if ($value === null) {
        return null;
    }

    $rate = (float)str_replace(',', '.', (string)$value);
    if ($rate <= 0) {
        return null;
    }

    return [
        'rate'           => $rate,
        'effective_date' => getVenezuelaTodayDate(),
        'source'         => 'bcv-exchange-rates',
    ];
}

function fetchRemoteBcvUsdRate(): ?array
{
    $sources = [
        [
            'url'    => 'https://bcv.today/api/v1/rate.json',
            'parser' => 'parseBcvTodayResponse',
        ],
        [
            'url'    => 'https://bcv-exchange-rates.vercel.app/get_bcv_exchange_rates',
            'parser' => 'parseBcvExchangeRatesResponse',
        ],
    ];

    foreach ($sources as $source) {
        $ch = curl_init($source['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        if ($raw === false || trim($raw) === '') {
            continue;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            continue;
        }

        $parser = $source['parser'];
        $parsed = $parser($decoded);
        if ($parsed !== null) {
            return $parsed;
        }
    }

    return null;
}

/** 获取 BCV 美元官方汇率（VES / 1 USD） */
function getBcvUsdRate(PDO $db, bool $forceRefresh = false): array
{
    $today = getVenezuelaTodayDate();
    $manual = getBcvManualRate($db);
    if ($manual !== null) {
        return [
            'rate'           => $manual,
            'date'           => $today,
            'effective_date' => $today,
            'updated_at'     => date('Y-m-d H:i:s'),
            'source'         => 'manual',
            'cached'         => false,
            'currency'       => 'USD',
            'quote'          => 'VES',
        ];
    }

    $cached = getCachedBcvRate($db);
    if (!$forceRefresh && $cached && ($cached['date'] ?? '') === $today) {
        return array_merge($cached, [
            'currency' => 'USD',
            'quote'    => 'VES',
        ]);
    }

    $remote = fetchRemoteBcvUsdRate();
    if ($remote !== null) {
        cacheBcvRate($db, $remote);
        return [
            'rate'           => (float)$remote['rate'],
            'date'           => $today,
            'effective_date' => (string)$remote['effective_date'],
            'updated_at'     => date('Y-m-d H:i:s'),
            'source'         => (string)$remote['source'],
            'cached'         => false,
            'currency'       => 'USD',
            'quote'          => 'VES',
        ];
    }

    if ($cached) {
        return array_merge($cached, [
            'currency' => 'USD',
            'quote'    => 'VES',
            'stale'    => ($cached['date'] ?? '') !== $today,
        ]);
    }

    jsonError('无法获取 BCV 官方汇率，请稍后再试或在后台设置手动汇率', 503);
}

function convertUsdToVes(float $usd, float $rate): float
{
    if ($usd <= 0 || $rate <= 0) {
        return 0.0;
    }
    return round($usd * $rate, 2);
}

function formatBcvRatePublic(array $rate): array
{
    return [
        'rate'           => (float)$rate['rate'],
        'date'           => (string)($rate['date'] ?? ''),
        'effective_date' => (string)($rate['effective_date'] ?? ''),
        'updated_at'     => (string)($rate['updated_at'] ?? ''),
        'source'         => (string)($rate['source'] ?? ''),
        'currency'       => 'USD',
        'quote'          => 'VES',
        'stale'          => !empty($rate['stale']),
    ];
}
