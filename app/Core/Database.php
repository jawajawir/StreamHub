<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private string $prefix = '';

    private function __construct() {}

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->connect();
        }
        return self::$instance;
    }

    public static function hasInstance(): bool
    {
        return self::$instance !== null && self::$instance->pdo !== null;
    }

    public static function setInstance(?Database $db): void
    {
        self::$instance = $db;
    }

    public function connect(): void
    {
        $cfg = require STREAMHUB_BASE . '/config/database.php';
        $this->prefix = (string) ($cfg['prefix'] ?? '');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'], (int) $cfg['port'], $cfg['name'], $cfg['charset']);
        $this->pdo = new PDO($dsn, (string) $cfg['user'], (string) $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $cfg['charset'],
        ]);
    }

    public static function tryConnect(array $cfg): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'], (int) $cfg['port'], $cfg['name'], $cfg['charset'] ?? 'utf8mb4');
            $pdo = new PDO($dsn, (string) $cfg['user'], (string) ($cfg['pass'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return ['ok' => true, 'pdo' => $pdo];
        } catch (PDOException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) $this->connect();
        return $this->pdo;
    }

    public function table(string $name): string
    {
        return $this->prefix . $name;
    }

    public function query(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    public function fetch(string $sql, array $bindings = []): ?array
    {
        $row = $this->query($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $bindings = []): array
    {
        return $this->query($sql, $bindings)->fetchAll();
    }

    public function fetchValue(string $sql, array $bindings = [])
    {
        $row = $this->query($sql, $bindings)->fetch(PDO::FETCH_NUM);
        return $row === false ? null : ($row[0] ?? null);
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $placeholders = array_map(static fn($c) => ':' . $c, $cols);
        $sql = sprintf('INSERT INTO `%s` (`%s`) VALUES (%s)',
            $this->table($table),
            implode('`,`', $cols),
            implode(',', $placeholders)
        );
        $bindings = [];
        foreach ($data as $k => $v) {
            $bindings[':' . $k] = $v;
        }
        $this->query($sql, $bindings);
        return (int) $this->pdo()->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereBindings = []): int
    {
        $sets = [];
        $bindings = [];
        foreach ($data as $k => $v) {
            $sets[] = "`$k` = :set_$k";
            $bindings[":set_$k"] = $v;
        }
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s',
            $this->table($table),
            implode(', ', $sets),
            $where
        );
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array_merge($bindings, $whereBindings));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $bindings = []): int
    {
        $sql = sprintf('DELETE FROM `%s` WHERE %s', $this->table($table), $where);
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    public function transaction(callable $callback)
    {
        $this->pdo()->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo()->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            throw $e;
        }
    }
}
