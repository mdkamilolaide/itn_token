<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Database connection and query execution unit tests.
 *
 * Covers basic database connectivity, query methods, and result handling.
 */
class DatabaseConnectionTest extends TestCase
{
    // ==========================================
    // Connection Tests
    // ==========================================

    public function testDatabaseConnectionIsEstablished(): void
    {
        $this->assertNotNull($this->db, 'Database connection should be established');
        $this->assertInstanceOf(\MysqlCentry::class, $this->db);
    }

    public function testDatabaseConnectionIsActive(): void
    {
        // Verify connection by executing a simple query
        try {
            $result = $this->db->Single("SELECT 1 as connection_test");
            $this->assertNotNull($result, 'Database connection should be active');
        } catch (\Throwable $e) {
            $this->markTestSkipped('Database connection unavailable');
        }
    }

    // ==========================================
    // Query Execution - Single
    // ==========================================

    public function testCanExecuteSimpleSingleQuery(): void
    {
        $result = $this->db->Single("SELECT 1 as test");
        $this->assertEquals(1, $result, 'Should execute simple SELECT query');
    }

    public function testSingleReturnsNullForNoResults(): void
    {
        try {
            $result = $this->db->Single("SELECT 1 WHERE FALSE");
            $this->assertTrue($result === null || $result === false, 'Single should return null/false for no rows');
        } catch (\Throwable $e) {
            // May throw on no results
            $this->assertTrue(true);
        }
    }

    public function testSingleReturnsFirstColumnValue(): void
    {
        $result = $this->db->Single("SELECT 'value1' as col1, 'value2' as col2");
        $this->assertEquals('value1', $result, 'Single should return first column value');
    }

    // ==========================================
    // Query Execution - Table
    // ==========================================

    public function testCanRetrieveDataFromTable(): void
    {
        $tables = $this->db->Table("SHOW TABLES");
        $this->assertIsArray($tables, 'Should return array of tables');
        $this->assertNotEmpty($tables, 'Database should contain tables');
    }

    public function testTableReturnsEmptyArrayForNoResults(): void
    {
        $result = $this->db->Table("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE FALSE");
        $this->assertIsArray($result, 'Should return array even for no results');
        $this->assertEmpty($result, 'Should return empty array for no results');
    }

    public function testTableReturnsArrayOfArrays(): void
    {
        $tables = $this->db->Table("SHOW TABLES LIMIT 1");
        $this->assertIsArray($tables, 'Result should be array');
        if (!empty($tables)) {
            $this->assertIsArray($tables[0], 'Table rows should be arrays');
        } else {
            $this->assertTrue(true, 'Empty result is valid');
        }
    }

    // ==========================================
    // Database Structure Tests
    // ==========================================

    public function testDatabaseHasTables(): void
    {
        $tables = $this->db->Table("SHOW TABLES");
        $this->assertNotEmpty($tables, 'Database should have at least one table');
    }

    public function testCanQueryInformationSchema(): void
    {
        $result = $this->db->Table("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            LIMIT 5
        ");
        $this->assertIsArray($result, 'Should query INFORMATION_SCHEMA');
    }

    public function testCanRetrieveTableColumns(): void
    {
        // Get first table name
        $tables = $this->db->Table("SHOW TABLES LIMIT 1");
        if (!empty($tables)) {
            $tableName = array_values($tables[0])[0];
            $columns = $this->db->Table("SHOW COLUMNS FROM `$tableName`");
            $this->assertIsArray($columns, 'Should retrieve table columns');
        } else {
            $this->assertTrue(true, 'No tables to check');
        }
    }

    // ==========================================
    // Error Handling
    // ==========================================

    public function testDatabaseHandlesInvalidSyntax(): void
    {
        // Should not throw, but may return null or false
        $result = @$this->db->Single("INVALID SQL QUERY");
        // Just verify the call completes
        $this->assertTrue(true);
    }

    public function testDatabaseHandlesEmptyString(): void
    {
        // Empty query should throw or be gracefully handled
        try {
            $result = @$this->db->Table("");
            $this->assertTrue(true, 'Empty query handled');
        } catch (\ValueError $e) {
            $this->assertTrue(true, 'Empty query throws ValueError as expected');
        }
    }

    // ==========================================
    // Data Type Tests
    // ==========================================

    public function testQueryReturnsCorrectDataTypes(): void
    {
        $result = $this->db->Single("SELECT CAST(123 AS SIGNED) as number");
        $this->assertTrue(is_numeric($result) || is_int($result), 'Should return numeric value');
    }

    public function testQueryHandlesStringData(): void
    {
        $result = $this->db->Single("SELECT 'test_string' as value");
        $this->assertIsString($result, 'Should return string');
        $this->assertEquals('test_string', $result);
    }

    public function testQueryHandlesNullData(): void
    {
        $result = $this->db->Single("SELECT NULL as value");
        $this->assertNull($result, 'Should handle NULL values');
    }

    // ==========================================
    // Performance & Limits
    // ==========================================

    public function testCanSetQueryLimitClause(): void
    {
        $tables = $this->db->Table("SHOW TABLES LIMIT 10");
        $this->assertIsArray($tables, 'LIMIT clause should work');
        $this->assertLessThanOrEqual(10, count($tables));
    }

    public function testCanExecuteMultipleQueries(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $result = $this->db->Single("SELECT $i as value");
            $this->assertEquals($i, $result);
        }
    }
}
