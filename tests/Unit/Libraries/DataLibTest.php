<?php

namespace Tests\Unit\Libraries;

use PHPUnit\Framework\TestCase;
use DataLib;

/**
 * Unit tests for DataLib utility class
 */
class DataLibTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../../lib/mysql.min.php';
    }

    /**
     * Test Column() method returns correct column value
     */
    public function testColumnReturnsCorrectValue()
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ];
        $result = DataLib::Column($data, 'name');
        $this->assertEquals(['John', 'Jane'], $result);
    }

    /**
     * Test ColumnToInt() method converts to integer
     */
    public function testColumnToIntConvertsToInteger()
    {
        $data = [
            ['count' => '42'],
            ['count' => '7'],
        ];
        $result = DataLib::ColumnToInt($data, 'count');
        $this->assertSame([42, 7], $result);
    }

    /**
     * Test Column() returns null for missing key
     */
    public function testColumnReturnsNullForMissingKey()
    {
        $data = [
            ['name' => 'John'],
        ];
        $result = DataLib::Column($data, 'missing');
        $this->assertSame([null], $result);
    }
}
