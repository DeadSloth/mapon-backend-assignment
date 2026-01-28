<?php

declare(strict_types=1);

namespace App\Lib;

use PDO;

/**
 * Simple database wrapper supporting SQLite and MySQL.
 *
 * Uses PDO with named parameter binding. Query parameters use :name syntax
 * and are automatically escaped.
 *
 * Usage:
 *   $rows = DB::query("SELECT * FROM transactions WHERE car_id = :carId", ['carId' => 123]);
 *   $id = DB::insert("INSERT INTO transactions (car_id, amount) VALUES (:carId, :amount)", [...]);
 *   DB::execute("UPDATE transactions SET amount = :amount WHERE id = :id", [...]);
 */
class DB
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';

            self::$pdo = match ($driver) {
                'sqlite' => self::createSqliteConnection(),
                'mysql' => self::createMysqlConnection(),
                default => throw new \RuntimeException("Unsupported DB driver: {$driver}"),
            };

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return self::$pdo;
    }

    private static function createSqliteConnection(): PDO
    {
        $path = dirname(__DIR__, 2) . '/data/database.sqlite';
        return new PDO("sqlite:{$path}");
    }

    private static function createMysqlConnection(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $name = $_ENV['DB_NAME'] ?? 'fuel_api';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        return new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass
        );
    }

    /**
     * Execute a SELECT query and return all rows.
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a SELECT query and return a single row.
     */
    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Execute an INSERT and return the last insert ID.
     */
    public static function insert(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) self::connection()->lastInsertId();
    }

    /**
     * Execute an UPDATE/DELETE and return affected row count.
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Begin a transaction.
     */
    public static function beginTransaction(): void
    {
        self::connection()->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public static function commit(): void
    {
        self::connection()->commit();
    }

    /**
     * Rollback the current transaction.
     */
    public static function rollback(): void
    {
        self::connection()->rollBack();
    }

    /**
     * Reset connection (useful for testing).
     */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
