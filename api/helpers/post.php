<?php

require_once __DIR__ . '/vid.php';

function normalizePostRow(array $post): array
{
    $post['images'] = json_decode($post['images'] ?? '[]', true) ?: [];
    $post['price'] = $post['price'] !== null ? (float)$post['price'] : null;
    return $post;
}

function formatPostPublic(array $post): array
{
    return exposePublicId(normalizePostRow($post), 'post');
}

function formatPostsPublic(array $posts): array
{
    return array_map('formatPostPublic', $posts);
}

function insertPostWithVid(PDO $db, array $data): array
{
    $hasVid = tableHasVidColumn($db, 'posts');
    $vid = $hasVid ? generateUniqueVid($db, 'posts') : null;

    if ($hasVid) {
        $stmt = $db->prepare(
            'INSERT INTO posts (vid, user_id, category_id, title, content, price, price_unit,
             contact_name, contact_phone, province, city, district, address, images, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $vid,
            $data['user_id'],
            $data['category_id'],
            $data['title'],
            $data['content'],
            $data['price'],
            $data['price_unit'],
            $data['contact_name'],
            $data['contact_phone'],
            $data['province'],
            $data['city'],
            $data['district'],
            $data['address'],
            $data['images'],
            $data['status'],
        ]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO posts (user_id, category_id, title, content, price, price_unit,
             contact_name, contact_phone, province, city, district, address, images, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['user_id'],
            $data['category_id'],
            $data['title'],
            $data['content'],
            $data['price'],
            $data['price_unit'],
            $data['contact_name'],
            $data['contact_phone'],
            $data['province'],
            $data['city'],
            $data['district'],
            $data['address'],
            $data['images'],
            $data['status'],
        ]);
    }

    $internalId = (int)$db->lastInsertId();
    $publicId = $vid ?? (string)$internalId;

    return ['internal_id' => $internalId, 'public_id' => $publicId];
}
