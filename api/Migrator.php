<?php

class Migrator
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, ?string $migrationsPath = null)
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath ?? self::resolveMigrationsPath();
    }

    /** 解析迁移目录（优先 api/migrations，兼容 database/migrations） */
    public static function resolveMigrationsPath(): string
    {
        $candidates = [
            __DIR__ . '/migrations',
            dirname(__DIR__) . '/database/migrations',
        ];
        foreach ($candidates as $path) {
            if (self::countMigrationFiles($path) > 0) {
                return $path;
            }
        }
        return __DIR__ . '/migrations';
    }

    private static function countMigrationFiles(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }
        $files = glob($path . '/*.php') ?: [];
        return count(array_filter($files, fn($f) => strpos(basename($f), '_') !== 0));
    }

    public function getMigrationsPath(): string
    {
        return $this->migrationsPath;
    }

    /** 检测数据库结构是否缺少关键字段 */
    public function detectSchemaIssues(): array
    {
        $issues = [];

        if (!$this->tableExists('users')) {
            $issues[] = '缺少 users 表，请执行迁移 001';
            return $issues;
        }

        $requiredUserColumns = [
            'role'          => '004',
            'email'         => '003',
            'last_login_at' => '004',
        ];
        foreach ($requiredUserColumns as $col => $ver) {
            if (!$this->columnExists('users', $col)) {
                $issues[] = "users 表缺少 {$col} 字段（需迁移 {$ver}）";
            }
        }

        if (!$this->tableExists('settings')) {
            $issues[] = '缺少 settings 表（需迁移 005）';
        }

        return $issues;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetch();
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE " . $this->db->quote($column));
        return (bool)$stmt->fetch();
    }

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

    public function getAppliedVersions(): array
    {
        $this->ensureMigrationTable();
        $stmt = $this->db->query('SELECT version FROM _migrations ORDER BY version');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        $files = array_filter($files, function ($f) {
            return strpos(basename($f), '_') !== 0;
        });
        sort($files);
        return array_values($files);
    }

    public static function migrationsDirMtime(?string $path = null): int
    {
        $path = $path ?? self::resolveMigrationsPath();
        if (!is_dir($path)) {
            return 0;
        }
        $mtime = 0;
        foreach (glob($path . '/*.php') ?: [] as $file) {
            if (strpos(basename($file), '_') === 0) {
                continue;
            }
            $mtime = max($mtime, (int)filemtime($file));
        }
        return $mtime;
    }

    public function runPending(): array
    {
        $files = $this->getMigrationFiles();
        if (!$files) {
            throw new RuntimeException(
                '未找到迁移文件。请确认已上传 api/migrations/ 目录（含 001~005 等 .php 文件）'
            );
        }

        $this->ensureMigrationTable();
        $applied = $this->getAppliedVersions();
        $results = [];

        foreach ($files as $file) {
            $migration = require $file;
            $version = $migration['version'] ?? basename($file, '.php');

            if (in_array($version, $applied, true)) {
                continue;
            }

            $description = $migration['description'] ?? '';

            try {
                ($migration['up'])($this->db);

                $stmt = $this->db->prepare(
                    'INSERT INTO _migrations (version, description) VALUES (?, ?)'
                );
                $stmt->execute([$version, $description]);

                $results[] = [
                    'version'     => $version,
                    'description' => $description,
                    'status'      => 'applied',
                ];
            } catch (Exception $e) {
                throw new RuntimeException(
                    "迁移 {$version} 失败: " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        return $results;
    }

    public function getStatus(): array
    {
        $applied = $this->getAppliedVersions();
        $all = [];
        $pending = [];
        $files = $this->getMigrationFiles();

        foreach ($files as $file) {
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

        $schemaIssues = $this->detectSchemaIssues();

        return [
            'migrations_path'  => $this->migrationsPath,
            'migration_files'  => count($files),
            'current_version'  => $applied ? end($applied) : null,
            'applied_count'    => count($applied),
            'pending_count'    => count($pending),
            'pending'          => $pending,
            'migrations'       => $all,
            'schema_issues'    => $schemaIssues,
            'needs_migration'  => count($pending) > 0 || count($schemaIssues) > 0,
        ];
    }
}

function autoMigrate(?PDO $db = null): array
{
    static $done = false;
    if ($done) {
        return [];
    }

    $config = require __DIR__ . '/config.php';
    if (empty($config['auto_migrate'])) {
        return [];
    }

    $done = true;
    $cacheKey = md5(($config['db']['dbname'] ?? '') . ($config['db']['host'] ?? ''));
    $cacheFile = sys_get_temp_dir() . '/publicidad_migrate_' . $cacheKey . '.json';
    $dirMtime = Migrator::migrationsDirMtime();

    if (is_file($cacheFile)) {
        $cache = json_decode((string)file_get_contents($cacheFile), true);
        if (
            is_array($cache)
            && ($cache['pending'] ?? 1) === 0
            && empty($cache['schema_issues'] ?? [])
            && ($cache['dir_mtime'] ?? 0) >= $dirMtime
            && ($cache['migration_files'] ?? 0) > 0
            && (time() - ($cache['checked_at'] ?? 0)) < 300
        ) {
            return [];
        }
    }

    try {
        if ($db === null) {
            $db = Database::getConnection();
        }

        $migrator = new Migrator($db);
        if ($migrator->getMigrationFiles() === []) {
            error_log('[Migrator] 未找到迁移文件: ' . $migrator->getMigrationsPath());
            return [];
        }

        $results = $migrator->runPending();
        $status = $migrator->getStatus();

        file_put_contents($cacheFile, json_encode([
            'pending'          => $status['pending_count'],
            'schema_issues'    => $status['schema_issues'],
            'dir_mtime'        => $dirMtime,
            'migration_files'  => $status['migration_files'],
            'checked_at'       => time(),
            'applied'          => array_column($results, 'version'),
        ]));

        if ($results) {
            error_log('[Migrator] 自动执行迁移: ' . implode(', ', array_column($results, 'version')));
        }

        return $results;
    } catch (Exception $e) {
        @unlink($cacheFile);
        error_log('[Migrator] ' . $e->getMessage());
        return [];
    }
}

function clearMigrateCache(): void
{
    $config = require __DIR__ . '/config.php';
    $cacheKey = md5(($config['db']['dbname'] ?? '') . ($config['db']['host'] ?? ''));
    $cacheFile = sys_get_temp_dir() . '/publicidad_migrate_' . $cacheKey . '.json';
    @unlink($cacheFile);
}
