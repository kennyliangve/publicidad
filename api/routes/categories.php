<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

function handleCategories(string $method, ?string $id): void
{
    $db = Database::getConnection();

    if ($method === 'GET') {
        if ($id) {
            $stmt = $db->prepare('SELECT * FROM categories WHERE id = ?');
            $stmt->execute([$id]);
            $cat = $stmt->fetch();
            if (!$cat) jsonError('分类不存在', 404);

            $stmt = $db->prepare('SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order');
            $stmt->execute([$id]);
            $cat['children'] = $stmt->fetchAll();
            jsonSuccess($cat);
        }

        // 获取所有一级分类及子分类
        $stmt = $db->query('SELECT * FROM categories WHERE parent_id = 0 ORDER BY sort_order');
        $parents = $stmt->fetchAll();

        foreach ($parents as &$parent) {
            $stmt = $db->prepare('SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order');
            $stmt->execute([$parent['id']]);
            $parent['children'] = $stmt->fetchAll();
        }
        jsonSuccess($parents);
    }

    jsonError('Method not allowed', 405);
}
