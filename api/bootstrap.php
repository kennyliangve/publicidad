<?php

function getConfig(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function getBasePath(): string
{
    return rtrim(getConfig()['base_path'] ?? '', '/');
}

function getApiPrefix(): string
{
    return getBasePath() . '/api';
}

function parseApiUri(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

    // 去掉 /api/index.php 形式
    $uri = preg_replace('#/api/index\.php#', '/api', $uri);

    $prefix = preg_quote(getApiPrefix(), '#');
    $uri = preg_replace('#^' . $prefix . '#', '', $uri);

    return trim($uri, '/');
}

/** 站点对外 Origin（用于上传返回完整图片 URL） */
function getPublicOrigin(): string
{
    static $origin = null;
    if ($origin !== null) {
        return $origin;
    }

    $config = getConfig();
    if (!empty($config['public_origin'])) {
        $origin = rtrim((string)$config['public_origin'], '/');
        return $origin;
    }

    if (php_sapi_name() === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        $origin = '';
        return $origin;
    }

    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $origin = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    return $origin;
}
