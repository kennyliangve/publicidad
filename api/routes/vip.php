<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../helpers/user.php';
require_once __DIR__ . '/../helpers/vip.php';

function handleVip(string $method, ?string $action): void
{
    $db = Database::getConnection();

    if ($action === 'plan' && $method === 'GET') {
        jsonSuccess(getVipPlanPublic($db));
        return;
    }

    if ($action === 'verify' && $method === 'POST') {
        $user = requireAuthUser();
        $body = getRequestBody();
        $result = processVipPaymentVerification($db, $user, $body);
        jsonSuccess($result, $result['message'] ?? '升级成功');
        return;
    }

    jsonError('Not found', 404);
}
