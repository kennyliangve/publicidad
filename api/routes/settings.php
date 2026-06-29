<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../helpers/settings.php';

function handleSettings(string $method, ?string $action): void
{
    if ($method !== 'GET') {
        jsonError('Method not allowed', 405);
    }

    $db = Database::getConnection();

    if ($action === 'price-units') {
        jsonSuccess(['units' => getPriceUnits($db)]);
        return;
    }

    if ($action === 'regions') {
        require_once __DIR__ . '/../helpers/regions.php';
        jsonSuccess(['regions' => getRegions($db)]);
        return;
    }

    if ($action === 'public') {
        require_once __DIR__ . '/../helpers/vip.php';
        jsonSuccess(getPublicSettings($db));
        return;
    }

    jsonError('Not found', 404);
}
