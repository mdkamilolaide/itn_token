<?php

namespace Tests\Helpers\Assertions;

/**
 * Custom assertions for database testing
 * Provides convenient methods for database assertions
 */
trait DatabaseAssertions
{
    protected function getDatabaseConnection(): \MysqlPdo
    {
        $root = realpath(__DIR__ . '/../../..');
        if ($root && file_exists($root . '/lib/mysql.min.php')) {
            require_once $root . '/lib/mysql.min.php';
        }

        return GetMysqlDatabase();
    }

    private function resolvePrimaryKey(string $table): string
    {
        $db = $this->getDatabaseConnection();
        $columns = $db->DataTable('SHOW COLUMNS FROM ' . $table);
        if (empty($columns)) {
            return 'id';
        }

        $names = array_map(fn ($row) => $row['Field'], $columns);
        foreach (['id', 'userid'] as $preferred) {
            if (in_array($preferred, $names, true)) {
                return $preferred;
            }
        }

        return $names[0];
    }

    /**
     * Assert that a record exists in database
     */
    protected function assertRecordExists(string $table, array $attributes): void
    {
        $db = $this->getDatabaseConnection();
        $conditions = [];
        $values = [];

        foreach ($attributes as $column => $value) {
            $conditions[] = "$column = ?";
            $values[] = $value;
        }

        $where = empty($conditions) ? '1=1' : implode(' AND ', $conditions);
        $stmt = $db->Conn->prepare("SELECT COUNT(*) FROM $table WHERE $where");
        $stmt->execute($values);
        $count = (int) $stmt->fetchColumn();

        $this->assertTrue($count > 0, "Failed asserting that a record exists in {$table}.");
    }

    /**
     * Assert that a record does not exist in database
     */
    protected function assertRecordNotExists(string $table, array $attributes)
    {
        $db = $this->getDatabaseConnection();
        $conditions = [];
        $values = [];

        foreach ($attributes as $column => $value) {
            $conditions[] = "$column = ?";
            $values[] = $value;
        }

        $where = empty($conditions) ? '1=1' : implode(' AND ', $conditions);
        $stmt = $db->Conn->prepare("SELECT COUNT(*) FROM $table WHERE $where");
        $stmt->execute($values);
        $count = (int) $stmt->fetchColumn();

        $this->assertEquals(0, $count, "Failed asserting that a record does not exist in {$table}.");
    }

    /**
     * Assert database table count
     */
    protected function assertTableCount(string $table, int $expectedCount)
    {
        $db = $this->getDatabaseConnection();
        $stmt = $db->Conn->prepare("SELECT COUNT(*) FROM $table");
        $stmt->execute();
        $count = (int) $stmt->fetchColumn();

        $this->assertEquals($expectedCount, $count, "Failed asserting table {$table} has expected count.");
    }

    /**
     * Assert record has specific values
     */
    protected function assertRecordHasValues(string $table, $criteria, array $values)
    {
        $db = $this->getDatabaseConnection();

        if (is_array($criteria)) {
            $conditions = [];
            $params = [];
            foreach ($criteria as $column => $value) {
                $conditions[] = "$column = ?";
                $params[] = $value;
            }
            $where = empty($conditions) ? '1=1' : implode(' AND ', $conditions);
            $stmt = $db->Conn->prepare("SELECT * FROM $table WHERE $where LIMIT 1");
            $stmt->execute($params);
        } else {
            $key = $this->resolvePrimaryKey($table);
            $stmt = $db->Conn->prepare("SELECT * FROM $table WHERE $key = ? LIMIT 1");
            $stmt->execute([$criteria]);
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row, "Failed asserting that record exists in {$table}.");

        foreach ($values as $column => $expected) {
            $this->assertArrayHasKey($column, $row, "Column {$column} missing from {$table}.");
            $this->assertEquals($expected, $row[$column], "Value mismatch for {$column} in {$table}.");
        }
    }
}
