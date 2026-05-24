<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

class Migrator
{
    private PDO $pdo;
    private string $migrationsDir;
    private string $seedsDir;

    public function __construct(PDO $pdo, ?string $migrationsDir = null, ?string $seedsDir = null)
    {
        $this->pdo = $pdo;
        $this->migrationsDir = $migrationsDir ?: STREAMHUB_BASE . '/database/migrations';
        $this->seedsDir      = $seedsDir      ?: STREAMHUB_BASE . '/database/seeds';
    }

    public function ensureMigrationsTable(): void
    {
        $this->pdo->exec(
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                migration VARCHAR(190) NOT NULL PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Run every migration file that hasn't been applied yet.
     *
     * @return array{applied:array<string>, skipped:array<string>}
     */
    public function migrate(): array
    {
        $this->ensureMigrationsTable();
        $files = glob($this->migrationsDir . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);
        $applied = [];
        $skipped = [];
        foreach ($files as $file) {
            $name = basename($file);
            $exists = (bool) $this->pdo->query(
                'SELECT 1 FROM schema_migrations WHERE migration = ' . $this->pdo->quote($name) . ' LIMIT 1'
            )->fetchColumn();
            if ($exists) { $skipped[] = $name; continue; }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException('Cannot read migration: ' . $file);
            }
            $this->execMulti($sql);
            $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)');
            $stmt->execute([':m' => $name]);
            $applied[] = $name;
        }
        return ['applied' => $applied, 'skipped' => $skipped];
    }

    /**
     * Apply seeds. Each seed is tracked similarly to migrations.
     *
     * @return array{applied:array<string>, skipped:array<string>}
     */
    public function seed(): array
    {
        $this->ensureMigrationsTable();
        $files = glob($this->seedsDir . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);
        $applied = [];
        $skipped = [];
        foreach ($files as $file) {
            $name = 'seed:' . basename($file);
            $exists = (bool) $this->pdo->query(
                'SELECT 1 FROM schema_migrations WHERE migration = ' . $this->pdo->quote($name) . ' LIMIT 1'
            )->fetchColumn();
            if ($exists) { $skipped[] = $name; continue; }
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException('Cannot read seed: ' . $file);
            }
            $this->execMulti($sql);
            $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:m)');
            $stmt->execute([':m' => $name]);
            $applied[] = $name;
        }
        return ['applied' => $applied, 'skipped' => $skipped];
    }

    /** Splits and executes a SQL file with multiple statements. */
    private function execMulti(string $sql): void
    {
        // Strip /* ... */ comments
        $sql = preg_replace('#/\*.*?\*/#s', '', $sql) ?? $sql;
        // Naive split on `;` at end of line that's outside string literals.
        $statements = self::splitSql($sql);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') continue;
            $this->pdo->exec($stmt);
        }
    }

    /** @return string[] */
    public static function splitSql(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $len = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $inLineComment = false;
        for ($i = 0; $i < $len; $i++) {
            $c  = $sql[$i];
            $nx = $i + 1 < $len ? $sql[$i + 1] : '';
            if ($inLineComment) {
                if ($c === "\n") $inLineComment = false;
                $buffer .= $c;
                continue;
            }
            if (!$inSingle && !$inDouble && $c === '-' && $nx === '-') {
                $inLineComment = true;
                $buffer .= $c;
                continue;
            }
            if (!$inDouble && $c === "'" && ($i === 0 || $sql[$i - 1] !== '\\')) $inSingle = !$inSingle;
            elseif (!$inSingle && $c === '"' && ($i === 0 || $sql[$i - 1] !== '\\')) $inDouble = !$inDouble;
            if (!$inSingle && !$inDouble && $c === ';') {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }
            $buffer .= $c;
        }
        if (trim($buffer) !== '') $statements[] = $buffer;
        return $statements;
    }
}
