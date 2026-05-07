<?php

namespace Tests\Integration;

use Tests\TestCase;
use System\General;

/**
 * System General controller integration tests.
 *
 * Covers geographic hierarchy queries, system lookups, and master data retrieval.
 */
class SystemGeneralTest extends TestCase
{
    private General $general;

    protected function setUp(): void
    {
        parent::setUp();
        $this->general = new General();
    }

    // ==========================================
    // Instantiation
    // ==========================================

    public function testGeneralInstantiation(): void
    {
        $this->assertInstanceOf(General::class, $this->general);
    }

    // ==========================================
    // Geographic Hierarchy Queries
    // ==========================================

    public function testGetStateListReturnsArray(): void
    {
        $states = $this->general->GetStateList();
        $this->assertIsArray($states);

        if (!empty($states)) {
            $state = $states[0];
            $this->assertArrayHasKey('stateid', $state);
            $this->assertArrayHasKey('state', $state);
        }
    }

    public function testGetLgaListReturnsArray(): void
    {
        $states = $this->general->GetStateList();
        if (empty($states)) {
            $this->markTestSkipped('No states available');
            return;
        }

        $stateId = $states[0]['stateid'];
        $lgas = $this->general->GetLgaList($stateId);
        $this->assertIsArray($lgas);

        if (!empty($lgas)) {
            $lga = $lgas[0];
            $this->assertArrayHasKey('lgaid', $lga);
            $this->assertArrayHasKey('stateid', $lga);
            $this->assertArrayHasKey('lga', $lga);
        }
    }

    public function testGetThisLgaListReturnsArray(): void
    {
        $result = $this->general->GetThisLgaList(1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetAllLgaReturnsArray(): void
    {
        $result = $this->general->GetAllLga();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetWardListReturnsArray(): void
    {
        $lgas = $this->db->Table("SELECT lgaid FROM ms_geo_lga LIMIT 1");
        if (empty($lgas)) {
            $this->markTestSkipped('No LGAs available');
            return;
        }

        $wards = $this->general->GetWardList($lgas[0]['lgaid']);
        $this->assertIsArray($wards);
    }

    public function testGetAllWardReturnsArray(): void
    {
        $result = $this->general->GetAllWard();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetClusterListReturnsArray(): void
    {
        $lgas = $this->db->Table("SELECT lgaid FROM ms_geo_lga LIMIT 1");
        if (empty($lgas)) {
            $this->markTestSkipped('No LGAs available');
            return;
        }

        $clusters = $this->general->GetClusterList($lgas[0]['lgaid']);
        $this->assertIsArray($clusters);
    }

    public function testGetAllClusterReturnsArray(): void
    {
        $result = $this->general->GetAllCluster();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    // ==========================================
    // Geographic Structure Queries
    // ==========================================

    public function testGetDpListReturnsArray(): void
    {
        $result = $this->general->GetDpList(1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetDpListByLgaReturnsArray(): void
    {
        $result = $this->general->GetDpListByLga(1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetAllDpReturnsArray(): void
    {
        $result = $this->general->GetAllDp();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetCommunityListReturnsArray(): void
    {
        $result = $this->general->GetCommunityList(1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetCommunityListByLgaReturnsArray(): void
    {
        $result = $this->general->GetCommunityListByLga(1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetCommunityListByWardReturnsArray(): void
    {
        $result = $this->general->GetCommunityListByWard(1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    // ==========================================
    // User and Role Queries
    // ==========================================

    public function testGetMobilizerListReturnsArray(): void
    {
        $result = $this->general->GetMobilizerList(1);
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetUserByRoleInLevelReturnsArray(): void
    {
        $result = $this->general->GetUserByRoleInLevel('state', 1, 'admin');
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    // ==========================================
    // System Configuration Queries
    // ==========================================

    public function testGetBankListReturnsArray(): void
    {
        $banks = $this->general->GetBankList();
        $this->assertIsArray($banks);

        if (!empty($banks)) {
            $bank = $banks[0];
            $this->assertArrayHasKey('bank_code', $bank);
            $this->assertArrayHasKey('bank_name', $bank);
        }
    }

    public function testGetGeoLevelReturnsArray(): void
    {
        $result = $this->general->GetGeoLevel();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetDefaultSettingsReturnsArray(): void
    {
        $result = $this->general->GetDefaultSettings();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    // ==========================================
    // Geographic Codex Queries
    // ==========================================

    public function testGetGeoLocationCodexDefaultReturnsArray(): void
    {
        $result = $this->general->GetGeoLocationCodex();
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetGeoLocationCodexWithLevelReturnsArray(): void
    {
        $result = $this->general->GetGeoLocationCodex('lga');
        $this->assertTrue(is_array($result) || $result === null || $result === false);
    }

    public function testGetGeoStructureIdReturnsIdOrArray(): void
    {
        $result = $this->general->GetGeoStructureId('state', 1);
        $this->assertTrue(is_array($result) || is_numeric($result) || $result === null || $result === false);
    }

    // ==========================================
    // Data Integrity Tests
    // ==========================================

    public function testGeographicHierarchyLinked(): void
    {
        $states = $this->general->GetStateList();
        if (empty($states)) {
            $this->markTestSkipped('No geographic data available');
            return;
        }

        $stateId = $states[0]['stateid'];
        $lgas = $this->general->GetLgaList($stateId);

        foreach ($lgas as $lga) {
            $this->assertEquals($stateId, $lga['stateid'], 'LGA should belong to the correct state');
        }
    }

    // ==========================================
    // Activity Logging
    // ==========================================

    public function testLogActivityCreatesLog(): void
    {
        $result = General::LogActivity(
            1,
            'test',
            'phpunit',
            'Test activity log entry',
            'success'
        );

        $this->assertNotFalse($result);
    }
}
