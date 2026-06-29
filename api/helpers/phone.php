<?php

/** 委内瑞拉电话标准格式：0412-0000000（11 位数字，固话 02XX / 手机 04XX） */
const VENEZUELA_PHONE_PATTERN = '/^0[24]\d{2}-\d{7}$/';

/**
 * 规范化为 0412-0000000，无效返回 null
 */
function normalizeVenezuelaPhone(string $input): ?string
{
    $input = trim($input);
    if ($input === '') {
        return null;
    }

    $compact = preg_replace('/\s+/', '', $input);

    if (preg_match('/^\+?58/', $compact)) {
        $digits = preg_replace('/\D/', '', $compact);
        if (str_starts_with($digits, '58')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10 && ($digits[0] === '4' || $digits[0] === '2')) {
            $digits = '0' . $digits;
        }
    } else {
        $digits = preg_replace('/\D/', '', $compact);
    }

    if (strlen($digits) !== 11 || !preg_match('/^0[24]\d{9}$/', $digits)) {
        return null;
    }

    return substr($digits, 0, 4) . '-' . substr($digits, 4);
}

function isValidVenezuelaPhone(string $input): bool
{
    return normalizeVenezuelaPhone($input) !== null;
}

/** 校验并返回规范格式，失败 jsonError */
function assertValidVenezuelaPhone(string $input, string $fieldLabel = '电话号码'): string
{
    $normalized = normalizeVenezuelaPhone($input);
    if ($normalized === null) {
        jsonError("{$fieldLabel}格式不正确，请使用委内瑞拉格式，如：0412-0000000");
    }
    return $normalized;
}

/** 登录账号：邮箱原样返回，手机号规范化 */
function normalizeLoginAccount(string $account): string
{
    $account = trim($account);
    if ($account === '') {
        return '';
    }
    if (str_contains($account, '@')) {
        return strtolower($account);
    }
    return assertValidVenezuelaPhone($account, '手机号');
}

/** 可选电话（空则 null） */
function normalizeOptionalVenezuelaPhone(?string $input, string $fieldLabel = '电话号码'): ?string
{
    $input = trim((string)$input);
    if ($input === '') {
        return null;
    }
    return assertValidVenezuelaPhone($input, $fieldLabel);
}
