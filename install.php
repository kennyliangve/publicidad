<?php
/**
 * 一键安装 / 数据库迁移
 * 访问: https://你的域名/publicidad/install.php
 * 安装完成后建议删除此文件
 */
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/Migrator.php';

$config = require __DIR__ . '/api/config.php';
$step = $_GET['step'] ?? 'check';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>信息分类网 - 安装/迁移</title>
  <style>
    body { font-family: sans-serif; max-width: 640px; margin: 60px auto; padding: 20px; }
    .ok { color: green; } .err { color: red; } .warn { color: #e67e22; }
    .btn { display: inline-block; padding: 10px 24px; background: #F8D000; color: #000; font-weight: 600; text-decoration: none; border-radius: 6px; margin-top: 16px; border: 2px solid #000; cursor: pointer; font-size: 15px; }
    .info { background: #f5f5f5; padding: 12px; border-radius: 6px; font-size: 14px; margin: 12px 0; }
    table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 14px; }
    th, td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
    .badge-ok { color: green; } .badge-pending { color: #e67e22; }
  </style>
</head>
<body>
  <h1>信息分类网 - 数据库迁移</h1>
<?php
try {
    $db = Database::getConnection();
    $migrator = new Migrator($db);
    $status = $migrator->getStatus();

    if ($step === 'install') {
        $results = $migrator->runPending();
        $status = $migrator->getStatus();

        if ($results) {
            echo '<p class="ok">✅ 成功执行 ' . count($results) . ' 个迁移：</p><ul>';
            foreach ($results as $r) {
                echo '<li>' . htmlspecialchars($r['version'] . ' - ' . $r['description']) . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="ok">✅ 数据库已是最新版本，无需迁移。</p>';
        }
        echo '<p>测试账号：13800138000 / 123456</p>';
        echo '<p><a class="btn" href="./">进入网站</a></p>';
    }

    echo '<div class="info">';
    echo '<p>数据库：<strong>' . htmlspecialchars($config['db']['dbname']) . '</strong></p>';
    echo '<p>当前版本：<strong>' . htmlspecialchars($status['current_version'] ?? '无') . '</strong>';
    echo ' &nbsp; 已执行：' . $status['applied_count'] . ' &nbsp; 待执行：' . $status['pending_count'] . '</p>';
    echo '</div>';

    echo '<table><tr><th>版本</th><th>描述</th><th>状态</th></tr>';
    foreach ($status['migrations'] as $m) {
        $badge = $m['applied']
            ? '<span class="badge-ok">✓ 已执行</span>'
            : '<span class="badge-pending">待执行</span>';
        echo '<tr><td>' . htmlspecialchars($m['version']) . '</td>';
        echo '<td>' . htmlspecialchars($m['description']) . '</td>';
        echo '<td>' . $badge . '</td></tr>';
    }
    echo '</table>';

    if ($step !== 'install') {
        if ($status['pending_count'] > 0) {
            echo '<p class="warn">有 ' . $status['pending_count'] . ' 个迁移待执行。</p>';
            echo '<a class="btn" href="?step=install">执行迁移</a>';
        } else {
            echo '<p class="ok">所有迁移已是最新。</p>';
            echo '<a class="btn" href="./">进入网站</a>';
        }
    }

} catch (Exception $e) {
    echo '<p class="err">❌ 错误：' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>请检查 api/config.php 中的数据库配置。</p>';
}
?>
</body>
</html>
