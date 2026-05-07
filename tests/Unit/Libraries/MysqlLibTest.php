<?php

namespace Tests\Unit\Libraries;

use PHPUnit\Framework\TestCase;

/**
 * Unit Test: MySQL Database Library
 * 
 * Tests the MySQL database wrapper library methods in isolation
 */
class MysqlLibTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../lib/mysql.min.php';
    }

    private function getDb(): \MysqlPdo
    {
        return GetMysqlDatabase();
    }

    private function skipIfNoDb(\MysqlPdo $db): void
    {
        if (!$db->Conn || $db->ConnMsg === 'error' || $db->ErrorMessage) {
            $this->markTestSkipped('Database connection not available for MysqlLib tests');
        }
    }

    private function createTempTable(\MysqlPdo $db): string
    {
        $table = 'lib_test_' . uniqid();
        $db->Execute("CREATE TABLE {$table} (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50))", []);
        return $table;
    }

    private function dropTempTable(\MysqlPdo $db, string $table): void
    {
        $db->Execute("DROP TABLE IF EXISTS {$table}", []);
    }

    public function testConnect()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $this->assertNotNull($db->Conn);
        $this->assertEmpty($db->ErrorMessage ?? '');
    }

    public function testQuery()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $rows = $db->DataTable('SELECT 1 AS val');
        $this->assertSame('1', (string) $rows[0]['val']);
    }

    public function testInsert()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $table = $this->createTempTable($db);
        $id = $db->Insert("INSERT INTO {$table} (name) VALUES (?)", ['alpha']);
        $this->assertNotEmpty($id);

        $rows = $db->DataTable("SELECT name FROM {$table} WHERE id = {$id}");
        $this->assertSame('alpha', $rows[0]['name']);

        $this->dropTempTable($db, $table);
    }

    public function testUpdate()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $table = $this->createTempTable($db);
        $id = $db->Insert("INSERT INTO {$table} (name) VALUES (?)", ['alpha']);
        $db->Execute("UPDATE {$table} SET name = ? WHERE id = ?", ['beta', $id]);

        $rows = $db->DataTable("SELECT name FROM {$table} WHERE id = {$id}");
        $this->assertSame('beta', $rows[0]['name']);

        $this->dropTempTable($db, $table);
    }

    public function testDelete()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $table = $this->createTempTable($db);
        $id = $db->Insert("INSERT INTO {$table} (name) VALUES (?)", ['alpha']);
        $db->Execute("DELETE FROM {$table} WHERE id = ?", [$id]);

        $rows = $db->DataTable("SELECT id FROM {$table} WHERE id = {$id}");
        $this->assertSame([], $rows);

        $this->dropTempTable($db, $table);
    }

    public function testSelect()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $value = $db->SelectResult('SELECT 2');
        $this->assertSame('2', (string) $value);
    }

    public function testGetMysqlDatabaseIsIdempotent()
    {
        $db1 = $this->getDb();
        $this->skipIfNoDb($db1);

        $db2 = GetMysqlDatabase();
        $this->assertSame($db1, $db2, 'GetMysqlDatabase() should return the same MysqlPdo instance for the same DSN in a process');

        $db3 = GetMysqlDatabase();
        $this->assertSame($db2, $db3);
    }

    public function testEscapeString()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $table = $this->createTempTable($db);
        $db->Execute("INSERT INTO {$table} (name) VALUES (?)", ["O'Reilly"]);
        $rows = $db->DataTable("SELECT name FROM {$table} LIMIT 1");
        $this->assertSame("O'Reilly", $rows[0]['name']);

        $this->dropTempTable($db, $table);
    }

    public function testBeginTransaction()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $db->beginTransaction();
        $this->assertTrue($db->Conn->inTransaction());
        $db->Conn->rollBack();
    }

    public function testCommitTransaction()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $table = $this->createTempTable($db);
        $db->beginTransaction();
        $db->Execute("INSERT INTO {$table} (name) VALUES (?)", ['commit']);
        $db->commitTransaction();

        $rows = $db->DataTable("SELECT name FROM {$table} WHERE name = 'commit'");
        $this->assertCount(1, $rows);

        $this->dropTempTable($db, $table);
    }

    public function testRollbackTransaction()
    {
        $db = $this->getDb();
        $this->skipIfNoDb($db);

        $table = $this->createTempTable($db);
        $db->beginTransaction();
        $db->Execute("INSERT INTO {$table} (name) VALUES (?)", ['rollback']);
        $db->Conn->rollBack();

        $rows = $db->DataTable("SELECT name FROM {$table} WHERE name = 'rollback'");
        $this->assertSame([], $rows);

        $this->dropTempTable($db, $table);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
