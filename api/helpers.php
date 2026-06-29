<?php

function jsonResponse($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonSuccess($data = null, string $message = 'success'): void
{
    jsonResponse(['code' => 0, 'message' => $message, 'data' => $data]);
}

function jsonError(string $message, int $code = 400): void
{
    jsonResponse(['code' => $code, 'message' => $message, 'data' => null], $code);
}

function getRequestBody(): array
{
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

function generateToken(int $userId): string
{
    $config = require __DIR__ . '/config.php';
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'exp'     => time() + 86400 * 7,
    ]));
    $signature = hash_hmac('sha256', $payload, $config['jwt_secret']);
    return $payload . '.' . $signature;
}

function verifyToken(?string $token): ?int
{
    if (!$token) return null;
    $config = require __DIR__ . '/config.php';
    $parts = explode('.', $token);
    if (count($parts) !== 2) return null;

    [$payload, $signature] = $parts;
    $expected = hash_hmac('sha256', $payload, $config['jwt_secret']);
    if (!hash_equals($expected, $signature)) return null;

    $data = json_decode(base64_decode($payload), true);
    if (!$data || ($data['exp'] ?? 0) < time()) return null;

    return (int)$data['user_id'];
}

function requireAuth(): int
{
    $userId = verifyToken(getBearerToken());
    if (!$userId) {
        jsonError('请先登录', 401);
    }
    return $userId;
}

function corsHeaders(): void
{
    $config = require __DIR__ . '/config.php';
    header('Access-Control-Allow-Origin: ' . $config['cors_origin']);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
