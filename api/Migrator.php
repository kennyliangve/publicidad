<?php

class Migrator
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, ?string $migrationsPath = null)
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath ?? dirname(__DIR__) . '/database/migrations';
    }

    /** 确保迁移记录表存在 */
    public function ensureMigrationTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS _migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                version VARCHAR(50) NOT NULL UNIQUE,
                description VARCHAR(255) NOT NULL DEFAULT '',
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /** 获取已执行的迁移版本 */
    public function getAppliedVersions(): array
    {
        $this->ensureMigrationTable();
        $stmt = $this->db->query('SELECT version FROM _migrations ORDER BY version');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** 获取所有迁移文件 */
    public function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        $files = glob($this->migrationsPath . '/*.php');
        $files = array_filter($files, function ($f) {
            return strpos(basename($f), '_') !== 0;
        });
        sort($files);
        return $files;
    }

    /** 执行所有待运行的迁移，返回执行结果 */
    public function runPending(): array
    {
        $this->ensureMigrationTable();
        $applied = $this->getAppliedVersions();
        $results = [];

        foreach ($this->getMigrationFiles() as $file) {
            $migration = require $file;
            $version = $migration['version'] ?? basename($file, '.php');

            if (in_array($version, $applied, true)) {
                continue;
            }

            $description = $migration['description'] ?? '';
            $this->db->beginTransaction();
            try {
                ($migration['up'])($this->db);

                $stmt = $this->db->prepare(
                    'INSERT INTO _migrations (version, description) VALUES (?, ?)'
                );
                $stmt->execute([$version, $description]);

                $this->db->commit();
                $results[] = [
                    'version'     => $version,
                    'description' => $description,
                    'status'      => 'applied',
                ];
            } catch (Exception $e) {
                $this->db->rollBack();
                throw new RuntimeException(
                    "迁移 {$version} 失败: " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        return $results;
    }

    /** 获取迁移状态概览 */
    public function getStatus(): array
    {
        $applied = $this->getAppliedVersions();
        $all = [];
        $pending = [];

        foreach ($this->getMigrationFiles() as $file) {
            $migration = require $file;
            $version = $migration['version'] ?? basename($file, '.php');
            $item = [
                'version'     => $version,
                'description' => $migration['description'] ?? '',
                'applied'     => in_array($version, $applied, true),
            ];
            $all[] = $item;
            if (!$item['applied']) {
                $pending[] = $version;
            }
        }

        return [
            'current_version' => $applied ? end($applied) : null,
            'applied_count'   => count($applied),
            'pending_count'   => count($pending),
            'pending'         => $pending,
            'migrations'      => $all,
        ];
    }
}

/** 自动执行待运行迁移（每次请求仅执行一次） */
function autoMigrate(): void
{
    static $done = false;
    if ($done) return;

    $config = require __DIR__ . '/config.php';
    if (empty($config['auto_migrate'])) return;

    $done = true;
    try {
        $db = Database::getConnection();
        $migrator = new Migrator($db);
        $migrator->runPending();
    } catch (Exception $e) {
        error_log('[Migrator] ' . $e->getMessage());
    }
}
