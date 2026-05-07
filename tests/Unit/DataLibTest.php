<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/lib/mysql.min.php';

final class DataLibTest extends TestCase
{
    public function testColumnReturnsFirstValues(): void
    {
        $input = [
            ['first' => 'a', 'second' => 'x'],
            ['first' => 'b', 'second' => 'y'],
        ];

        $this->assertSame(['a', 'b'], DataLib::Column($input));
    }

    public function testColumnToIntCastsValues(): void
    {
        $input = [
            ['5'],
            ['-2'],
            ['0'],
        ];

        $this->assertSame([5, -2, 0], DataLib::ColumnToInt($input));
    }
}
