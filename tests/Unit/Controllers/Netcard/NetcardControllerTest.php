<?php

namespace Tests\Unit\Controllers\Netcard;

use Netcard\Netcard;

/**
 * Unit Test: Netcard Controller
 * 
 * Tests the netcard controller methods in isolation
 */
class NetcardControllerTest extends NetcardTestCase
{
    public function testGenerateNetcardsUsesDefaultState(): void
    {
        $netcardColumns = ['ncid', 'uuid', 'active', 'location', 'location_value', 'geo_level', 'geo_level_id', 'stateid', 'status'];
        if (!$this->tableHasColumns('nc_netcard', $netcardColumns)
            || !$this->tableHasColumns('sys_default_settings', ['id', 'stateid'])
        ) {
            $this->markTestSkipped('Netcard schema not available');
        }

        $geo = $this->seedGeoHierarchy('Net');
        $this->seedDefaultSettings($geo['stateid']);

        $netcard = new Netcard(2);
        $count = $netcard->Generate();
        $this->assertSame(2, $count);

        $rows = $this->getDb()->DataTable("SELECT ncid, uuid, location_value, stateid FROM nc_netcard WHERE stateid = {$geo['stateid']} ORDER BY ncid DESC LIMIT 2");
        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->recordCleanup('nc_netcard', 'ncid', $row['ncid']);
            $this->recordCleanup('nc_netcard', 'uuid', $row['uuid']);
            $this->assertSame('100', (string) $row['location_value']);
            $this->assertSame((string) $geo['stateid'], (string) $row['stateid']);
        }
    }

}
