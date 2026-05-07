<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Smc\Dashboard as SmcDashboard;

/**
 * Comprehensive tests for Smc\Dashboard controller
 * Covers all 16 methods in the controller
 */
class SmcDashboardControllerTest extends TestCase
{
    private $dashboard;


    protected function setUp(): void
    {
        $this->dashboard = new SmcDashboard();
    }

    // ==========================================
    // INSTANTIATION TESTS
    // ==========================================

    public function testSmcDashboardInstantiation(): void
    {
        $this->assertInstanceOf(SmcDashboard::class, $this->dashboard);
    }

    // ==========================================
    // CHILD LIST METHODS
    // ==========================================

    public function testChildListLgaSummary(): void
    {
        $result = $this->dashboard->ChildListLgaSummary();
        $this->assertIsArray($result);
    }

    public function testChildListLgaSummaryWithDates(): void
    {
        $result = $this->dashboard->ChildListLgaSummary('2025-01-01', '2025-12-31');
        $this->assertIsArray($result);
    }

    public function testChildListWardSummary(): void
    {
        $result = $this->dashboard->ChildListWardSummary(1);
        $this->assertIsArray($result);
    }

    public function testChildListWardSummaryWithDates(): void
    {
        $result = $this->dashboard->ChildListWardSummary(1, '2025-01-01', '2025-12-31');
        $this->assertIsArray($result);
    }

    public function testChildListDpSummary(): void
    {
        $result = $this->dashboard->ChildListDpSummary(1);
        $this->assertIsArray($result);
    }

    // ==========================================
    // DRUG ADMIN LIST METHODS
    // ==========================================

    public function testDrugAdminListLga(): void
    {
        $result = $this->dashboard->DrugAdminListLga();
        $this->assertIsArray($result);
    }

    public function testDrugAdminListLgaWithPeriod(): void
    {
        $result = $this->dashboard->DrugAdminListLga('1', '2025-01-01', '2025-12-31');
        $this->assertIsArray($result);
    }

    public function testDrugAdminListWard(): void
    {
        $result = $this->dashboard->DrugAdminListWard(1);
        $this->assertIsArray($result);
    }

    public function testDrugAdminListDp(): void
    {
        $result = $this->dashboard->DrugAdminListDp(1);
        $this->assertIsArray($result);
    }

    // ==========================================
    // REFERRAL LIST METHODS
    // ==========================================

    public function testReferralListLga(): void
    {
        $result = $this->dashboard->ReferralListLga();
        $this->assertIsArray($result);
    }

    public function testReferralListWard(): void
    {
        $result = $this->dashboard->ReferralListWard(1);
        $this->assertIsArray($result);
    }

    public function testReferralListDp(): void
    {
        $result = $this->dashboard->ReferralListDp(1);
        $this->assertIsArray($result);
    }

    // ==========================================
    // ICC LIST METHODS
    // ==========================================

    public function testIccListLga(): void
    {
        $result = $this->dashboard->IccListLga();
        $this->assertIsArray($result);
    }

    public function testIccListWard(): void
    {
        $result = $this->dashboard->IccListWard(1);
        $this->assertIsArray($result);
    }

    public function testIccListDp(): void
    {
        $result = $this->dashboard->IccListDp(1);
        $this->assertIsArray($result);
    }
}
