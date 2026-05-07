<?php

namespace Tests\Helpers;

/**
 * Simple database helper for tests
 * Provides convenience CRUD/query helpers on top of MysqlPdo.
 */
class DatabaseHelper
{
    private \MysqlPdo $db;

    public function __construct()
    {
        $root = realpath(__DIR__ . '/..');
        if ($root && file_exists($root . '/../lib/mysql.min.php')) {
            require_once $root . '/../lib/mysql.min.php';
        } else {
            require_once __DIR__ . '/../../lib/mysql.min.php';
        }

        $this->db = GetMysqlDatabase();
    }

    public function insert(string $table, array $data)
    {
        return \DbHelper::Insert($table, $data);
    }

    public function update(string $table, array $data, array $where): bool
    {
        $setParts = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }

        $whereParts = [];
        foreach ($where as $column => $value) {
            $whereParts[] = "$column = ?";
            $params[] = $value;
        }

        $whereSql = empty($whereParts) ? '1=1' : implode(' AND ', $whereParts);
        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $whereSql";

        $stmt = $this->db->Conn->prepare($sql);
        return (bool) $stmt->execute($params);
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->Conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function queryRow(string $sql, array $params = []): ?array
    {
        $rows = $this->query($sql, $params);
        return $rows[0] ?? null;
    }

    public function queryOne(string $sql, array $params = [])
    {
        $stmt = $this->db->Conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commitTransaction();
    }

    public function rollBack(): void
    {
        if ($this->db->Conn) {
            $this->db->Conn->rollBack();
        }
    }

    public function getLastInsertId()
    {
        return $this->db->Conn->lastInsertId();
    }
}
