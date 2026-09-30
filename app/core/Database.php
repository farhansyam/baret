<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/** Pembungkus PDO: semua query memakai prepared statement. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = config('db');
            $dsn = $c['socket']
                ? "mysql:unix_socket={$c['socket']};dbname={$c['name']};charset={$c['charset']}"
                : "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$pdo->exec("SET time_zone = '+07:00'");
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        foreach (array_values(array_is_list($params) ? $params : []) as $i => $v) {
            $stmt->bindValue($i + 1, $v, self::type($v));
        }
        if (!array_is_list($params)) {
            foreach ($params as $k => $v) {
                $stmt->bindValue(':' . ltrim((string) $k, ':'), $v, self::type($v));
            }
        }
        $stmt->execute();
        return $stmt;
    }

    private static function type(mixed $v): int
    {
        return match (true) {
            is_int($v) => PDO::PARAM_INT,
            is_bool($v) => PDO::PARAM_BOOL,
            $v === null => PDO::PARAM_NULL,
            default => PDO::PARAM_STR,
        };
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /** Nama kolom selalu berasal dari kode (bukan input pengguna). */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(fn($c) => "`$c`", $cols)),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        self::query($sql, array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        return self::query("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams))->rowCount();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Placeholder "?, ?, ?" untuk klausa IN. */
    public static function in(array $values): string
    {
        return implode(', ', array_fill(0, max(1, count($values)), '?'));
    }
}
