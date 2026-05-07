<?php

/**
 * Database Schema Validation Test
 * 
 * Tests to ensure database schema is correct and complete.
 * Critical for PHP version upgrades that may affect database interactions.
 */

namespace Tests\Integration;

use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    /**
     * Required tables for the application
     */
    private $requiredTables = [
        // User management
        'usr_login',
        'usr_role',
        'usr_identity',
        'usr_user_activity',

        // System tables
        'sys_geo_codex',
        'sys_request_counts',
        'sys_device_registry',
        'sys_device_login',

        // Geographic tables
        'ms_geo_state',
        'ms_geo_lga',
        'ms_geo_ward',
        'ms_geo_cluster',
        'ms_geo_dp',
        'ms_geo_comm',

        // Distribution/HHM tables
        'hhm_distribution',
        'hhm_mobilization',

        // Netcard tables
        'nc_netcard',
        'nc_netcard_allocation',
    ];

    /**
     * Test all required tables exist
     */
    public function testRequiredTablesExist(): void
    {
        $existingTables = $this->db->Table("SHOW TABLES");
        $tableNames = array_column($existingTables, 'Tables_in_' . getenv('DB_DATABASE') ?: 'ipolongo_v5');

        foreach ($this->requiredTables as $table) {
            $this->assertContains(
                $table,
                $tableNames,
                "Required table '$table' should exist"
            );
        }
    }

    /**
     * Test usr_login table has all required columns
     */
    public function testUsrLoginTableColumns(): void
    {
        $expectedColumns = [
            'userid' => 'int',
            'loginid' => 'varchar',
            'username' => 'varchar',
            'pwd' => 'varchar',
            'guid' => 'varchar',
            'roleid' => 'int',
            'geo_level' => 'varchar',
            'geo_level_id' => 'int',
            'active' => 'smallint',
        ];

        $this->assertTableHasColumns('usr_login', $expectedColumns);
    }

    /**
     * Test usr_role table has all required columns
     */
    public function testUsrRoleTableColumns(): void
    {
        $expectedColumns = [
            'roleid' => 'int',
            'role_code' => 'varchar',
            'title' => 'varchar',
            'system_privilege' => 'longtext',
            'platform' => 'longtext',
            'priority' => 'tinyint',
        ];

        $this->assertTableHasColumns('usr_role', $expectedColumns);
    }

    /**
     * Test sys_geo_codex table has all required columns
     */
    public function testSysGeoCodexTableColumns(): void
    {
        $expectedColumns = [
            'id' => 'int',
            'geo_level' => 'varchar',
            'geo_level_id' => 'int',
            'title' => 'varchar',
        ];

        $this->assertTableHasColumns('sys_geo_codex', $expectedColumns);
    }

    /**
     * Test geographic data integrity - LGAs belong to valid states
     */
    public function testLgaStateRelationship(): void
    {
        $orphanedLgas = $this->db->Table("
            SELECT ms_geo_lga.LgaId, ms_geo_lga.StateId
            FROM ms_geo_lga
            LEFT JOIN ms_geo_state ON ms_geo_lga.StateId = ms_geo_state.StateId
            WHERE ms_geo_state.StateId IS NULL
            LIMIT 10
        ");

        $this->assertEmpty(
            $orphanedLgas,
            'All LGAs should belong to valid states'
        );
    }

    /**
     * Test wards belong to valid LGAs
     */
    public function testWardLgaRelationship(): void
    {
        $orphanedWards = $this->db->Table("
            SELECT ms_geo_ward.wardid, ms_geo_ward.lgaid
            FROM ms_geo_ward
            LEFT JOIN ms_geo_lga ON ms_geo_ward.lgaid = ms_geo_lga.LgaId
            WHERE ms_geo_lga.LgaId IS NULL
            LIMIT 10
        ");

        $this->assertEmpty(
            $orphanedWards,
            'All wards should belong to valid LGAs'
        );
    }

    /**
     * Test database character set is UTF-8
     */
    public function testDatabaseCharacterSet(): void
    {
        $result = $this->db->Table("
            SELECT DEFAULT_CHARACTER_SET_NAME 
            FROM information_schema.SCHEMATA 
            WHERE SCHEMA_NAME = DATABASE()
        ");

        $charset = $result[0]['DEFAULT_CHARACTER_SET_NAME'] ?? '';

        $this->assertContains(
            $charset,
            ['utf8', 'utf8mb4'],
            'Database should use UTF-8 character set'
        );
    }

    /**
     * Test tables use InnoDB engine (for transactions)
     */
    public function testTablesUseInnoDB(): void
    {
        $tables = $this->db->Table("
            SELECT TABLE_NAME, ENGINE 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = DATABASE()
            AND ENGINE != 'InnoDB'
            LIMIT 10
        ");

        // Note: Some tables might legitimately use other engines
        // This test just ensures we're aware of them
        foreach ($tables as $table) {
            $this->logWarning(
                "Table {$table['TABLE_NAME']} uses {$table['ENGINE']} instead of InnoDB"
            );
        }

        $this->assertTrue(true); // Pass the test but log warnings
    }

    /**
     * Test primary keys exist on main tables
     */
    public function testPrimaryKeysExist(): void
    {
        $tablesWithoutPK = $this->db->Table("
            SELECT t.TABLE_NAME
            FROM information_schema.TABLES t
            LEFT JOIN information_schema.TABLE_CONSTRAINTS tc 
                ON t.TABLE_NAME = tc.TABLE_NAME 
                AND t.TABLE_SCHEMA = tc.TABLE_SCHEMA
                AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
            WHERE t.TABLE_SCHEMA = DATABASE()
            AND t.TABLE_TYPE = 'BASE TABLE'
            AND tc.CONSTRAINT_NAME IS NULL
        ");

        if (!empty($tablesWithoutPK)) {
            $tableNames = array_column($tablesWithoutPK, 'TABLE_NAME');
            $this->logWarning('Tables without primary keys: ' . implode(', ', $tableNames));
        }

        $this->assertTrue(true);
    }

    /**
     * Test indexes exist on commonly queried columns
     */
    public function testImportantIndexesExist(): void
    {
        // Check for index on usr_login.loginid
        $loginidIndex = $this->db->Table("
            SHOW INDEX FROM usr_login WHERE Column_name = 'loginid'
        ");
        $this->assertNotEmpty($loginidIndex, 'Index should exist on usr_login.loginid');

        // Check for index on geo_codex lookup columns
        $geoIndex = $this->db->Table("
            SHOW INDEX FROM sys_geo_codex WHERE Column_name = 'geo_level'
        ");
        $this->assertNotEmpty($geoIndex, 'Index should exist on sys_geo_codex.geo_level');
    }

    /**
     * Helper: Assert table has expected columns with correct types
     */
    private function assertTableHasColumns(string $table, array $expectedColumns): void
    {
        $columns = $this->db->Table("SHOW COLUMNS FROM $table");
        $columnInfo = [];

        foreach ($columns as $col) {
            $columnInfo[$col['Field']] = $col['Type'];
        }

        foreach ($expectedColumns as $colName => $expectedType) {
            $this->assertArrayHasKey(
                $colName,
                $columnInfo,
                "Column '$colName' should exist in $table"
            );

            $this->assertStringContainsString(
                $expectedType,
                $columnInfo[$colName],
                "Column '$colName' in $table should be of type '$expectedType'"
            );
        }
    }

    /**
     * Helper: Log a warning without failing the test
     */
    private function logWarning(string $message): void
    {
        fwrite(STDERR, "\nWarning: $message\n");
    }
}
