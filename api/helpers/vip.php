<?php

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/phone.php';
require_once __DIR__ . '/bcv.php';

const VIP_PAYMENT_VERIFIED = 1;
const VIP_PAYMENT_FAILED   = 2;
const BDV_CONSULTA_SUCCESS_CODE = 1000;
const BDV_DEFAULT_ENDPOINT = 'https://bdvconciliacion.banvenez.com:443/api/consulta/consultaMultiple';

function vipPlansTableExists(PDO $db): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        $db->query('SELECT 1 FROM vip_plans LIMIT 1');
        $exists = true;
    } catch (Throwable) {
        $exists = false;
    }
    return $exists;
}

function usersHasVipExpiresColumn(PDO $db): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'vip_expires_at'");
        $has = (bool)$stmt->fetch();
    } catch (Throwable) {
        $has = false;
    }
    return $has;
}

/** 将已过期的 VIP 降级为普通用户，并取消其全部置顶 */
function expireVipUsers(PDO $db, ?int $userId = null): int
{
    require_once __DIR__ . '/post.php';

    $count = 0;

    if (usersHasVipExpiresColumn($db)) {
        $findSql = 'SELECT id FROM users WHERE role = ? AND vip_expires_at IS NOT NULL AND vip_expires_at < NOW()';
        $findParams = [USER_ROLE_VIP];
        if ($userId !== null) {
            $findSql .= ' AND id = ?';
            $findParams[] = $userId;
        }

        $stmt = $db->prepare($findSql);
        $stmt->execute($findParams);
        $expiredIds = array_column($stmt->fetchAll(), 'id');

        foreach ($expiredIds as $expiredId) {
            clearUserPinnedPosts($db, (int)$expiredId);
        }

        if ($expiredIds) {
            $sql = 'UPDATE users SET role = ?, vip_expires_at = NULL
                    WHERE role = ? AND vip_expires_at IS NOT NULL AND vip_expires_at < NOW()';
            $params = [USER_ROLE_NORMAL, USER_ROLE_VIP];

            if ($userId !== null) {
                $sql .= ' AND id = ?';
                $params[] = $userId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->rowCount();
        }
    }

    clearNormalUserPinnedPosts($db);

    return $count;
}

function userHasActiveVip(array $user): bool
{
    if ((int)($user['role'] ?? 0) !== USER_ROLE_VIP) {
        return false;
    }
    $expires = $user['vip_expires_at'] ?? null;
    if ($expires === null || $expires === '') {
        return true;
    }
    return strtotime((string)$expires) >= time();
}

function vipPlanUsesAmountUsd(PDO $db): bool
{
    static $uses = null;
    if ($uses !== null) {
        return $uses;
    }
    try {
        $uses = (bool)$db->query("SHOW COLUMNS FROM vip_plans LIKE 'amount_usd'")->fetch();
    } catch (Throwable) {
        $uses = false;
    }
    return $uses;
}

function vipPlanAmountUsd(array $plan): float
{
    if (isset($plan['amount_usd'])) {
        return (float)$plan['amount_usd'];
    }
    return (float)($plan['amount'] ?? 0);
}

function formatVipPlanPublic(PDO $db, array $plan): array
{
    $days = max(1, (int)($plan['duration_days'] ?? 30));
    $usd = vipPlanAmountUsd($plan);
    $bcv = getBcvUsdRate($db);
    $ves = convertUsdToVes($usd, (float)$bcv['rate']);

    return [
        'id'             => (int)$plan['id'],
        'name'           => (string)$plan['name'],
        'amount_usd'     => round($usd, 2),
        'amount_ves'     => $ves,
        'amount'         => $ves,
        'duration_days'  => $days,
        'duration_label' => vipDurationLabel($days),
        'sort_order'     => (int)($plan['sort_order'] ?? 0),
        'enabled'        => (int)($plan['enabled'] ?? 1),
    ];
}

function vipDurationLabel(int $days): string
{
    if ($days % 365 === 0 && $days >= 365) {
        $years = (int)($days / 365);
        return $years . ' 年';
    }
    if ($days % 30 === 0 && $days >= 30) {
        $months = (int)($days / 30);
        return $months . ' 个月';
    }
    return $days . ' 天';
}

function getAllVipPlans(PDO $db, bool $enabledOnly = false): array
{
    if (!vipPlansTableExists($db)) {
        return [];
    }

    $sql = 'SELECT * FROM vip_plans';
    if ($enabledOnly) {
        $sql .= ' WHERE enabled = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';

    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll() ?: [];
    return array_map(static fn(array $row) => formatVipPlanPublic($db, $row), $rows);
}

function findVipPlanById(PDO $db, int $planId, bool $enabledOnly = false): ?array
{
    if (!vipPlansTableExists($db) || $planId <= 0) {
        return null;
    }

    $sql = 'SELECT * FROM vip_plans WHERE id = ?';
    if ($enabledOnly) {
        $sql .= ' AND enabled = 1';
    }

    $stmt = $db->prepare($sql);
    $stmt->execute([$planId]);
    $row = $stmt->fetch();
    return $row ? formatVipPlanPublic($db, $row) : null;
}

function getVipPlanRawById(PDO $db, int $planId): ?array
{
    if (!vipPlansTableExists($db) || $planId <= 0) {
        return null;
    }
    $stmt = $db->prepare('SELECT * FROM vip_plans WHERE id = ?');
    $stmt->execute([$planId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** 前台 VIP 升级信息（多套餐，不含密钥） */
function getVipPlanPublic(PDO $db): array
{
    $enabled = getSetting($db, 'vip_upgrade_enabled', '1') !== '0';
    $plans = getAllVipPlans($db, true);
    $hasPlans = count($plans) > 0;
    $bcv = getBcvUsdRate($db);

    return [
        'enabled'        => $enabled && $hasPlans,
        'currency'       => (string)getSetting($db, 'vip_plan_currency', 'USD'),
        'pricing_currency' => 'USD',
        'payment_currency' => 'VES',
        'bcv'            => formatBcvRatePublic($bcv),
        'merchant_phone' => (string)getSetting($db, 'vip_merchant_phone', ''),
        'merchant_rif'   => (string)getSetting($db, 'vip_merchant_rif', ''),
        'merchant_bank'  => (string)getSetting($db, 'vip_merchant_bank_code', '0102'),
        'plans'          => $plans,
        'benefits'       => [
            '发布信息时可上传图片',
            '最多置顶 5 条信息',
            '信息展示更直观，提高曝光',
        ],
    ];
}

function getVipBankConfig(PDO $db): array
{
    $mode = getSetting($db, 'bank_api_mode', 'production');
    $endpoint = $mode === 'sandbox'
        ? (string)getSetting($db, 'bank_api_endpoint_sandbox', '')
        : (string)getSetting($db, 'bank_api_endpoint', '');

    if (trim($endpoint) === '') {
        $endpoint = BDV_DEFAULT_ENDPOINT;
    }

    return [
        'mode'           => $mode,
        'endpoint'       => trim($endpoint),
        'token'          => trim((string)getSetting($db, 'bank_api_token', '')),
        'auth_type'      => (string)getSetting($db, 'bank_auth_type', 'x_api_key'),
        'merchant_phone' => (string)getSetting($db, 'vip_merchant_phone', ''),
        'merchant_rif'   => (string)getSetting($db, 'vip_merchant_rif', ''),
        'merchant_bank'  => (string)getSetting($db, 'vip_merchant_bank_code', '0102'),
    ];
}

function normalizeVipPlanInput(array $body, ?array $existing = null): array
{
    $name = trim((string)($body['name'] ?? ($existing['name'] ?? '')));
    if ($name === '') {
        jsonError('请填写套餐名称');
    }
    if (mb_strlen($name) > 100) {
        jsonError('套餐名称过长');
    }

    $amountUsd = isset($body['amount_usd'])
        ? (float)$body['amount_usd']
        : (isset($body['amount']) ? (float)$body['amount'] : (float)vipPlanAmountUsd($existing ?? []));
    if ($amountUsd <= 0) {
        jsonError('套餐金额 (USD) 必须大于 0');
    }

    $durationDays = isset($body['duration_days'])
        ? (int)$body['duration_days']
        : (int)($existing['duration_days'] ?? 0);
    if ($durationDays < 1 || $durationDays > 3650) {
        jsonError('有效天数须在 1～3650 之间');
    }

    $sortOrder = isset($body['sort_order'])
        ? (int)$body['sort_order']
        : (int)($existing['sort_order'] ?? 0);

    $enabled = array_key_exists('enabled', $body)
        ? ((int)(bool)$body['enabled'])
        : (int)($existing['enabled'] ?? 1);

    return [
        'name'          => $name,
        'amount_usd'    => round($amountUsd, 2),
        'duration_days' => $durationDays,
        'sort_order'    => $sortOrder,
        'enabled'       => $enabled ? 1 : 0,
    ];
}

function createVipPlan(PDO $db, array $body): array
{
    if (!vipPlansTableExists($db)) {
        jsonError('请先执行数据库迁移 014', 503);
    }

    $data = normalizeVipPlanInput($body);
    $amountColumn = vipPlanUsesAmountUsd($db) ? 'amount_usd' : 'amount';
    $stmt = $db->prepare(
        "INSERT INTO vip_plans (name, {$amountColumn}, duration_days, sort_order, enabled) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $data['name'],
        $data['amount_usd'],
        $data['duration_days'],
        $data['sort_order'],
        $data['enabled'],
    ]);

    $plan = findVipPlanById($db, (int)$db->lastInsertId());
    return $plan ?? $data;
}

function updateVipPlan(PDO $db, int $planId, array $body): array
{
    $existing = getVipPlanRawById($db, $planId);
    if (!$existing) {
        jsonError('套餐不存在', 404);
    }

    $data = normalizeVipPlanInput($body, $existing);
    $amountColumn = vipPlanUsesAmountUsd($db) ? 'amount_usd' : 'amount';
    $stmt = $db->prepare(
        "UPDATE vip_plans SET name = ?, {$amountColumn} = ?, duration_days = ?, sort_order = ?, enabled = ? WHERE id = ?"
    );
    $stmt->execute([
        $data['name'],
        $data['amount_usd'],
        $data['duration_days'],
        $data['sort_order'],
        $data['enabled'],
        $planId,
    ]);

    return findVipPlanById($db, $planId) ?? $data;
}

function deleteVipPlan(PDO $db, int $planId): void
{
    $existing = getVipPlanRawById($db, $planId);
    if (!$existing) {
        jsonError('套餐不存在', 404);
    }

    $enabledCount = (int)$db->query('SELECT COUNT(*) FROM vip_plans WHERE enabled = 1')->fetchColumn();
    if ((int)$existing['enabled'] === 1 && $enabledCount <= 1) {
        jsonError('至少保留一个启用的套餐');
    }

    $db->prepare('DELETE FROM vip_plans WHERE id = ?')->execute([$planId]);
}

function calculateVipExpiresAt(PDO $db, int $userId, int $durationDays): string
{
    $base = new DateTime('now');
    if (usersHasVipExpiresColumn($db)) {
        $stmt = $db->prepare('SELECT vip_expires_at, role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row && (int)$row['role'] === USER_ROLE_VIP && !empty($row['vip_expires_at'])) {
            $current = new DateTime($row['vip_expires_at']);
            if ($current > $base) {
                $base = $current;
            }
        }
    }
    $base->modify('+' . max(1, $durationDays) . ' days');
    return $base->format('Y-m-d H:i:s');
}

function applyVipSubscription(PDO $db, int $userId, int $durationDays): void
{
    $expiresAt = calculateVipExpiresAt($db, $userId, $durationDays);
    if (usersHasVipExpiresColumn($db)) {
        $db->prepare('UPDATE users SET role = ?, vip_expires_at = ? WHERE id = ?')
            ->execute([USER_ROLE_VIP, $expiresAt, $userId]);
    } else {
        $db->prepare('UPDATE users SET role = ? WHERE id = ?')
            ->execute([USER_ROLE_VIP, $userId]);
    }
}

function normalizePayerPhone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (str_starts_with($digits, '58') && strlen($digits) === 12) {
        return '0' . substr($digits, 2);
    }
    if (strlen($digits) === 10 && ($digits[0] === '4' || $digits[0] === '2')) {
        return '0' . $digits;
    }
    return $digits;
}

function normalizeTelefonoCliente(string $phone): string
{
    return normalizePayerPhone($phone);
}

function normalizePaymentReference(string $reference): string
{
    $digits = preg_replace('/\D/', '', trim($reference));
    if (strlen($digits) < 6) {
        jsonError('参考号至少需要 6 位数字，请填写银行凭证上的最后 6 位');
    }
    return substr($digits, -6);
}

function buildVipReferenceKey(int $planId, string $reference, string $bankCode, string $paymentDate, float $amount): string
{
    $ref = normalizePaymentReference($reference);
    return hash('sha256', $planId . '|' . $bankCode . '|' . $ref . '|' . $paymentDate . '|' . number_format($amount, 2, '.', ''));
}

function normalizePaymentDateTime(string $date): DateTime
{
    $date = trim($date);
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    if ($dt && $dt->format('Y-m-d') === $date) {
        return $dt;
    }
    $dt = DateTime::createFromFormat('d/m/Y', $date);
    if ($dt) {
        return $dt;
    }
    jsonError('付款日期格式无效');
}

function normalizePaymentDateSql(string $date): string
{
    return normalizePaymentDateTime($date)->format('Y-m-d');
}

function normalizeBankCode(string $code, string $default = '0102'): string
{
    $digits = preg_replace('/\D/', '', trim($code));
    if ($digits === '') {
        $digits = preg_replace('/\D/', '', $default);
    }
    return str_pad($digits, 4, '0', STR_PAD_LEFT);
}

function amountsMatch(float $expected, float $actual): bool
{
    return abs($expected - $actual) < 0.02;
}

function buildBdvConsultaPayload(array $payment): array
{
    return [
        'fechaPago'       => normalizePaymentDateSql($payment['payment_date']),
        'bancoOrigen'     => normalizeBankCode($payment['payer_bank_code']),
        'telefonoCliente' => normalizeTelefonoCliente($payment['payer_phone']),
    ];
}

function referenceMatchesResult(string $apiReference, string $userReference): bool
{
    $apiRef = preg_replace('/\D/', '', $apiReference);
    $userRef = normalizePaymentReference($userReference);
    if ($apiRef === '') {
        return false;
    }
    return substr($apiRef, -6) === $userRef;
}

function findMatchingBdvPayment(array $response, string $userReference, float $expectedAmount): ?array
{
    $results = $response['resultados'] ?? null;
    if (!is_array($results)) {
        return null;
    }

    foreach ($results as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (!referenceMatchesResult((string)($row['referencia'] ?? ''), $userReference)) {
            continue;
        }
        if (!amountsMatch($expectedAmount, (float)($row['monto'] ?? 0))) {
            continue;
        }
        return $row;
    }

    return null;
}

function callBdvConsultaMultiple(PDO $db, array $payment): array
{
    $config = getVipBankConfig($db);

    if ($config['token'] === '') {
        jsonError('银行 API Token 未配置，请联系管理员', 503);
    }

    $payload = buildBdvConsultaPayload($payment);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    if ($config['auth_type'] === 'bearer') {
        $headers[] = 'Authorization: Bearer ' . $config['token'];
    } else {
        $headers[] = 'X-API-Key: ' . $config['token'];
    }

    $ch = curl_init($config['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [
            'ok'      => false,
            'message' => '银行接口连接失败: ' . ($curlError ?: 'unknown'),
            'http'    => $httpCode,
            'payload' => null,
            'request' => $payload,
            'matched' => null,
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $snippet = trim(substr($raw, 0, 200));
        return [
            'ok'      => false,
            'message' => $snippet !== '' ? $snippet : '银行返回格式无效',
            'http'    => $httpCode,
            'payload' => ['raw' => $raw],
            'request' => $payload,
            'matched' => null,
        ];
    }

    $code = (int)($decoded['code'] ?? 0);
    $matched = findMatchingBdvPayment($decoded, $payment['reference'], (float)$payment['amount']);
    $ok = $code === BDV_CONSULTA_SUCCESS_CODE && $matched !== null;

    $message = extractBankVerifyMessage($decoded, $ok);
    if ($code === BDV_CONSULTA_SUCCESS_CODE && !$matched) {
        $message = '未找到匹配的付款记录，请核对参考号、金额、付款日期、银行和手机号';
    }

    return [
        'ok'      => $ok,
        'message' => $message,
        'http'    => $httpCode,
        'payload' => $decoded,
        'request' => $payload,
        'matched' => $matched,
    ];
}

function extractBankVerifyMessage(array $response, bool $ok): string
{
    $message = trim((string)($response['message'] ?? ''));
    if ($message !== '') {
        return $message;
    }

    return $ok ? 'Consulta exitosa' : '银行未确认该笔付款';
}

function isReferenceAlreadyUsed(PDO $db, string $referenceKey): bool
{
    $stmt = $db->prepare('SELECT id FROM vip_payments WHERE reference_key = ? LIMIT 1');
    $stmt->execute([$referenceKey]);
    return (bool)$stmt->fetch();
}

function recordVipPayment(PDO $db, array $data): int
{
    $hasPlanId = false;
    try {
        $hasPlanId = (bool)$db->query("SHOW COLUMNS FROM vip_payments LIKE 'plan_id'")->fetch();
    } catch (Throwable) {
    }

    if ($hasPlanId) {
        $stmt = $db->prepare(
            'INSERT INTO vip_payments (user_id, plan_id, reference, reference_key, amount, payer_phone, payer_id_type,
             payer_id_number, payer_bank_code, payment_date, status, bank_response, verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['plan_id'] ?? null,
            $data['reference'],
            $data['reference_key'],
            $data['amount'],
            $data['payer_phone'],
            $data['payer_id_type'],
            $data['payer_id_number'],
            $data['payer_bank_code'],
            $data['payment_date'],
            $data['status'],
            $data['bank_response'],
            $data['verified_at'],
        ]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO vip_payments (user_id, reference, reference_key, amount, payer_phone, payer_id_type,
             payer_id_number, payer_bank_code, payment_date, status, bank_response, verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['reference'],
            $data['reference_key'],
            $data['amount'],
            $data['payer_phone'],
            $data['payer_id_type'],
            $data['payer_id_number'],
            $data['payer_bank_code'],
            $data['payment_date'],
            $data['status'],
            $data['bank_response'],
            $data['verified_at'],
        ]);
    }

    return (int)$db->lastInsertId();
}

function processVipPaymentVerification(PDO $db, array $user, array $body): array
{
    expireVipUsers($db, (int)$user['id']);
    $user = findUserById($db, (int)$user['id']) ?? $user;

    if (userIsStaffRole((int)($user['role'] ?? 0))) {
        jsonError('员工账号无需通过付款升级');
    }

    $planInfo = getVipPlanPublic($db);
    if (!$planInfo['enabled']) {
        jsonError('VIP 在线升级暂未开放', 403);
    }

    $planId = (int)($body['plan_id'] ?? 0);
    $plan = findVipPlanById($db, $planId, true);
    if (!$plan) {
        jsonError('请选择有效的 VIP 套餐');
    }

    $referenceRaw = trim($body['reference'] ?? '');
    $payerPhone = trim($body['payer_phone'] ?? '');
    $payerBank = trim($body['payer_bank_code'] ?? '');
    $paymentDate = trim($body['payment_date'] ?? '');
    $amountUsd = (float)$plan['amount_usd'];
    $amountVes = (float)$plan['amount_ves'];
    $durationDays = (int)$plan['duration_days'];

    if (!$referenceRaw || !$payerPhone || !$payerBank || !$paymentDate) {
        jsonError('请填写完整的付款信息');
    }

    $reference = normalizePaymentReference($referenceRaw);
    $paymentDateSql = normalizePaymentDateSql($paymentDate);

    $referenceKey = buildVipReferenceKey($planId, $reference, $payerBank, $paymentDateSql, $amountVes);
    if (isReferenceAlreadyUsed($db, $referenceKey)) {
        jsonError('该付款参考号已被使用，请勿重复提交');
    }

    $payment = [
        'reference'       => $reference,
        'payer_phone'     => $payerPhone,
        'payer_bank_code' => $payerBank,
        'payment_date'    => $paymentDate,
        'amount'          => $amountVes,
    ];

    $bankResult = callBdvConsultaMultiple($db, $payment);

    if (!$bankResult['ok']) {
        jsonError($bankResult['message'] ?: '付款验证失败，请核对信息后重试');
    }

    $isRenewal = userHasActiveVip($user);

    $db->beginTransaction();
    try {
        applyVipSubscription($db, (int)$user['id'], $durationDays);

        recordVipPayment($db, [
            'user_id'          => (int)$user['id'],
            'plan_id'          => $planId,
            'reference'        => $reference,
            'reference_key'    => $referenceKey,
            'amount'           => $amountVes,
            'payer_phone'      => normalizeTelefonoCliente($payerPhone),
            'payer_id_type'    => 'V',
            'payer_id_number'  => '',
            'payer_bank_code'  => normalizeBankCode($payerBank),
            'payment_date'     => $paymentDateSql,
            'status'           => VIP_PAYMENT_VERIFIED,
            'bank_response'    => json_encode([
                'response'   => $bankResult['payload'] ?? [],
                'matched'    => $bankResult['matched'] ?? null,
                'plan_id'    => $planId,
                'amount_usd' => $amountUsd,
                'amount_ves' => $amountVes,
                'bcv_rate'   => getBcvUsdRate($db)['rate'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'verified_at'      => date('Y-m-d H:i:s'),
        ]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    $updated = findUserById($db, (int)$user['id']);

    return [
        'user'         => formatUserPublic($updated),
        'vip_expires_at' => $updated['vip_expires_at'] ?? null,
        'message'      => $isRenewal
            ? '续费成功，VIP 有效期已延长'
            : '付款验证成功，已升级为 VIP 用户',
    ];
}
