<?php

namespace Tests\Unit\Controllers\Dashboard;

use Dashboard\Mobilization;

/**
 * Unit Test: Dashboard Mobilization Controller
 * 
 * Tests the mobilization dashboard controller methods in isolation
 */
class MobilizationControllerTest extends DashboardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testValidateMobilizationData(): void
    {
        $this->requireMobilizationSchema();

        $controller = new Mobilization();
        $summary = $controller->TopSummary();
        $this->assertNotEmpty($summary);
        $this->assertArrayHasKey('netcards', $summary[0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
