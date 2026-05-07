<?php

namespace Tests\Unit\Controllers\Netcard;

use Netcard\Etoken;

/**
 * Unit Test: E-Token Controller
 * 
 * Tests the e-token controller methods in isolation
 */
class EtokenControllerTest extends NetcardTestCase
{
    public function testGenerateLiteReturnsSerials(): void
    {
        if (!$this->tableHasColumns('nc_token', ['tokenid', 'uuid', 'serial_no', 'status', 'status_code'])) {
            $this->markTestSkipped('Token schema not available');
        }

        $etoken = new Etoken('DEV-2', 2);
        $data = $etoken->GenerateLite();
        $this->assertCount(2, $data);
        $this->assertNotEmpty($data[0]['serial_no']);

        foreach ($data as $row) {
            $this->recordCleanup('nc_token', 'tokenid', $row['tokenid']);
            $this->recordCleanup('nc_token', 'serial_no', $row['serial_no']);
        }
    }

    public function testChangeLengthSupportsZero(): void
    {
        $etoken = new Etoken('DEV-3', 1);
        $etoken->ChangeLength(0);

        $data = $etoken->GenerateLite();
        $this->assertSame([], $data);
    }

    public function testUpdateTokenStatusHelpers(): void
    {
        if (!$this->tableHasColumns('nc_token', ['tokenid', 'status', 'status_code', 'updated'])) {
            $this->markTestSkipped('Token schema not available');
        }

        $tokenId = $this->seedToken([
            'uuid' => md5(uniqid('', true)),
            'status' => 'pending',
            'status_code' => 2,
            'serial_no' => 'ET' . random_int(1000, 9999),
        ]);

        $this->assertTrue(Etoken::UpdateTokenUsed($tokenId));
        $rows = $this->getDb()->DataTable("SELECT status, status_code FROM nc_token WHERE tokenid = {$tokenId}");
        $this->assertSame('used', $rows[0]['status']);
        $this->assertSame('4', (string) $rows[0]['status_code']);

        $this->assertTrue(Etoken::UpdateTokenCancel($tokenId));
        $rows = $this->getDb()->DataTable("SELECT status, status_code FROM nc_token WHERE tokenid = {$tokenId}");
        $this->assertSame('cancel', $rows[0]['status']);
        $this->assertSame('3', (string) $rows[0]['status_code']);
    }
}
