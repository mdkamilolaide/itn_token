<?php

namespace Tests\Unit\Controllers\Dashboard;

use Dashboard\Distribution;

/**
 * Unit Test: Dashboard Distribution Controller
 * 
 * Tests the distribution dashboard controller methods in isolation
 */
class DistributionControllerTest extends DashboardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock dependencies if needed
    }

    public function testValidateDistributionData(): void
    {
        $this->requireDistributionSchema();

        $controller = new Distribution();
        $summary = $controller->TopSummary();
        $this->assertNotEmpty($summary);
        $this->assertArrayHasKey('household_mobilized', $summary[0]);
        $this->assertArrayHasKey('net_redeemed', $summary[0]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
