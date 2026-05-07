<?php

namespace Tests\Unit\Controllers\Dashboard;

use Dashboard\Enetcard;

/**
 * Unit Test: Dashboard E-Netcard Controller
 * 
 * Tests the e-netcard dashboard controller methods in isolation
 */
class EnetcardControllerTest extends DashboardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCalculateUsageMetrics(): void
    {
        $this->requireEnetcardSchema();

        $controller = new Enetcard();
        $geo = $this->seedGeoHierarchy('NetLga');

        $this->seedNetcards(2, ['location_value' => 80, 'lgaid' => $geo['lgaid']]);
        $rows = $controller->TopLgaSummary();
        $this->assertNotEmpty($rows);
        $this->assertSame((string) $geo['lgaid'], (string) $rows[0]['LgaId']);
    }

    public function testValidateNetcardData(): void
    {
        $this->requireEnetcardSchema();

        $controller = new Enetcard();
        $summary = $controller->TopSummary();
        $this->assertNotEmpty($summary);
        $this->assertArrayHasKey('beneficiary', $summary[0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
