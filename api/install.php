<?php
/**
 * 一键安装 / 数据库迁移
 * 访问: https://你的域名/publicidad/api/install.php
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Migrator.php';

$config = require __DIR__ . '/config.php';
$step = $_GET['step'] ?? 'check';
$siteHome = rtrim($config['base_path'] ?? '', '/') . '/';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <title>信息分类网 - 安装/迁移</title>
  <style>
    body { font-family: sans-serif; max-width: 720px; margin: 60px auto; padding: 20px; }
    .ok { color: green; } .err { color: red; } .warn { color: #e67e22; }
    .btn { display: inline-block; padding: 10px 24px; background: #F8D000; color: #000; font-weight: 600; text-decoration: none; border-radius: 6px; margin-top: 16px; border: 2px solid #000; cursor: pointer; font-size: 15px; }
    .info { background: #f5f5f5; padding: 12px; border-radius: 6px; font-size: 14px; margin: 12px 0; }
    .issue-box { background: #fff3e0; border: 1px solid #ffb74d; padding: 12px; border-radius: 6px; margin: 12px 0; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 14px; }
    th, td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
    .badge-ok { color: green; } .badge-pending { color: #e67e22; }
    code { background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
  </style>
</head>
<body>
  <h1>信息分类网 - 数据库迁移</h1>
<?php
try {
    clearMigrateCache();
    $db = Database::getConnection();
    $migrator = new Migrator($db);
    $status = $migrator->getStatus();

    if ($step === 'install') {
        try {
            $results = $migrator->runPending();
            clearMigrateCache();
            $status = $migrator->getStatus();

            if ($results) {
                echo '<p class="ok">✅ 成功执行 ' . count($results) . ' 个迁移：</p><ul>';
                foreach ($results as $r) {
                    echo '<li>' . htmlspecialchars($r['version'] . ' - ' . $r['description']) . '</li>';
                }
                echo '</ul>';
            } elseif ($status['needs_migration']) {
                echo '<p class="err">❌ 迁移未完全成功，请查看下方结构问题。</p>';
            } else {
                echo '<p class="ok">✅ 数据库结构已是最新。</p>';
            }
        } catch (Exception $e) {
            echo '<p class="err">❌ 迁移失败：' . htmlspecialchars($e->getMessage()) . '</p>';
            $status = $migrator->getStatus();
        }
        echo '<p><a class="btn" href="' . htmlspecialchars($siteHome) . '">进入网站</a>';
        echo ' <a class="btn" href="?step=check" style="background:#fff">重新检测</a></p>';
    }

    echo '<div class="info">';
    echo '<p>数据库：<strong>' . htmlspecialchars($config['db']['dbname']) . '</strong></p>';
    echo '<p>迁移目录：<code>' . htmlspecialchars($status['migrations_path']) . '</code></p>';
    echo '<p>迁移文件数：<strong>' . (int)$status['migration_files'] . '</strong>';
    echo ' &nbsp; 当前版本：<strong>' . htmlspecialchars($status['current_version'] ?? '无') . '</strong>';
    echo ' &nbsp; 已执行：<strong>' . (int)$status['applied_count'] . '</strong>';
    echo ' &nbsp; 待执行：<strong>' . (int)$status['pending_count'] . '</strong></p>';
    echo '</div>';

    if ((int)$status['migration_files'] === 0) {
        echo '<div class="issue-box"><strong>⚠ 未找到迁移文件</strong><br>';
        echo '请上传 <code>api/migrations/</code> 目录（001~005 等 .php 文件）到服务器。</div>';
    }

    if (!empty($status['schema_issues'])) {
        echo '<div class="issue-box"><strong>⚠ 数据库结构问题</strong><ul>';
        foreach ($status['schema_issues'] as $issue) {
            echo '<li>' . htmlspecialchars($issue) . '</li>';
        }
        echo '</ul></div>';
    }

    if ($status['migrations']) {
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
    } else {
        echo '<p class="warn">迁移列表为空，无法自动升级数据库。</p>';
    }

    if ($step !== 'install') {
        if ($status['needs_migration']) {
            echo '<p class="warn">检测到数据库需要升级（待执行迁移或缺少字段）。</p>';
            echo '<a class="btn" href="?step=install">立即执行迁移</a>';
        } elseif ((int)$status['migration_files'] > 0) {
            echo '<p class="ok">✅ 数据库结构正常，所有迁移已执行。</p>';
            echo '<a class="btn" href="' . htmlspecialchars($siteHome) . '">进入网站</a>';
        }
    }

} catch (Exception $e) {
    echo '<p class="err">❌ 错误：' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>请检查 api/config.php 中的数据库配置。</p>';
}
?>
</body>
</html>
