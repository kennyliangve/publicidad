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
