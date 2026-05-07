<?php

namespace Tests\Feature\Registration;

use Tests\TestCase;

class RegistrationWorkflowTest extends TestCase
{
    private string $projectRoot;
    private array $createdHouseholdTokens = [];
    private array $createdHouseholdIds = [];
    private array $createdChildIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = realpath(__DIR__ . '/../../..');
        chdir($this->projectRoot);

        require_once $this->projectRoot . '/lib/controller/smc/registration.cont.php';
        require_once $this->projectRoot . '/lib/mysql.min.php';
    }

    protected function tearDown(): void
    {
        $db = $this->getDb();

        if (!empty($this->createdChildIds)) {
            $ids = array_map([$db->Conn, 'quote'], $this->createdChildIds);
            $idList = implode(',', $ids);
            $db->executeTransaction("DELETE FROM smc_child WHERE beneficiary_id IN ($idList)", []);
        }
        if (!empty($this->createdHouseholdTokens)) {
            $tokens = array_map([$db->Conn, 'quote'], $this->createdHouseholdTokens);
            $tokenList = implode(',', $tokens);
            $db->executeTransaction("DELETE FROM smc_child_household WHERE hh_token IN ($tokenList)", []);
        }
        if (!empty($this->createdHouseholdIds)) {
            $ids = array_filter(array_map('intval', $this->createdHouseholdIds));
            if (!empty($ids)) {
                $db->executeTransaction('DELETE FROM smc_child_household WHERE hhid IN (' . implode(',', $ids) . ')', []);
            }
        }

        parent::tearDown();
    }

    /**
     * Test complete entity registration workflow
     */
    public function testCompleteEntityRegistrationWorkflow()
    {
        $geo = $this->getGeoSample();
        $registration = new \Smc\Registration();

        $hhToken = $this->uniqueToken('HH');
        $householdId = $registration->CreateHousehold($hhToken, 'Jane Doe', '08000000000');
        $this->assertNotFalse($householdId);
        $this->createdHouseholdTokens[] = $hhToken;
        $this->createdHouseholdIds[] = (int) $householdId;

        $childId = $this->uniqueToken('CH');
        $createdChildren = $registration->CreateChildBulk([
            $this->buildChildPayload($geo, $hhToken, $childId, 'Child One')
        ]);
        $this->assertCount(1, $createdChildren);
        $this->createdChildIds[] = $childId;

        $updated = $registration->UpdateHousehold($hhToken, 'Jane Updated', '08000000001', $householdId);
        $this->assertTrue((bool) $updated);

        $updateChildren = $registration->UpdateChildBulk([
            [
                'beneficiary_id' => $childId,
                'name' => 'Child Updated',
                'gender' => 'M',
                'dob' => '2019-01-01'
            ]
        ]);
        $this->assertIsArray($updateChildren);

        $deleted = $registration->DeleteHousehold($householdId);
        $this->assertTrue((bool) $deleted);

        $this->assertHouseholdMissing($hhToken);
    }

    /**
     * Test household registration workflow
     */
    public function testHouseholdRegistrationWorkflow()
    {
        $geo = $this->getGeoSample();
        $registration = new \Smc\Registration();

        $tokenOne = $this->uniqueToken('HH');
        $tokenTwo = $this->uniqueToken('HH');
        $created = $registration->CreateHouseholdBulk([
            $this->buildHouseholdPayload($geo, $tokenOne, 'John Doe', '08000010001'),
            $this->buildHouseholdPayload($geo, $tokenTwo, 'Mary Doe', '08000010002'),
        ]);
        $this->assertCount(2, $created);

        $this->createdHouseholdTokens[] = $tokenOne;
        $this->createdHouseholdTokens[] = $tokenTwo;

        $updated = $registration->UpdateHouseholdBulk([
            ['hh_token' => $tokenOne, 'hoh' => 'John Updated', 'phone' => '08000020001'],
            ['hh_token' => $tokenTwo, 'hoh' => 'Mary Updated', 'phone' => '08000020002'],
        ]);
        $this->assertCount(2, $updated);

        $this->assertHouseholdName($tokenOne, 'John Updated');
        $this->assertHouseholdName($tokenTwo, 'Mary Updated');
    }

    /**
     * Test registration form submission and validation
     */
    public function testRegistrationFormSubmissionWorkflow()
    {
        $geo = $this->getGeoSample();
        $registration = new \Smc\Registration();

        $token = $this->uniqueToken('HH');
        $registration->CreateHouseholdBulk([
            $this->buildHouseholdPayload($geo, $token, 'Form Parent', '08000030001')
        ]);
        $this->createdHouseholdTokens[] = $token;

        $childIdOne = $this->uniqueToken('CH');
        $childIdTwo = $this->uniqueToken('CH');
        $created = $registration->CreateChildBulk([
            $this->buildChildPayload($geo, $token, $childIdOne, 'Form Child One'),
            $this->buildChildPayload($geo, $token, $childIdTwo, 'Form Child Two'),
        ]);
        $this->assertCount(2, $created);

        $this->createdChildIds[] = $childIdOne;
        $this->createdChildIds[] = $childIdTwo;

        $this->assertChildExists($childIdOne);
        $this->assertChildExists($childIdTwo);
    }

    /**
     * Test registration data verification
     */
    public function testRegistrationDataVerificationWorkflow()
    {
        $geo = $this->getGeoSample();
        $registration = new \Smc\Registration();

        $token = $this->uniqueToken('HH');
        $registration->CreateHouseholdBulk([
            $this->buildHouseholdPayload($geo, $token, 'Verify Parent', '08000040001')
        ]);
        $this->createdHouseholdTokens[] = $token;

        $childId = $this->uniqueToken('CH');
        $registration->CreateChildBulk([
            $this->buildChildPayload($geo, $token, $childId, 'Verify Child')
        ]);
        $this->createdChildIds[] = $childId;

        $this->assertHouseholdName($token, 'Verify Parent');
        $this->assertChildName($childId, 'Verify Child');
    }

    /**
     * Test registration approval and activation
     */
    public function testRegistrationApprovalWorkflow()
    {
        $registration = new \Smc\Registration();
        $token = $this->uniqueToken('HH');

        $householdId = $registration->CreateHousehold($token, 'Approve Parent', '08000050001');
        $this->assertNotFalse($householdId);
        $this->createdHouseholdTokens[] = $token;
        $this->createdHouseholdIds[] = (int) $householdId;

        $deleted = $registration->DeleteHousehold($householdId);
        $this->assertTrue((bool) $deleted);
        $this->assertHouseholdMissing($token);
    }

    private function getDb(): \MysqlPdo
    {
        require_once $this->projectRoot . '/lib/mysql.min.php';
        return GetMysqlDatabase();
    }

    private function getGeoSample(): array
    {
        $db = $this->getDb();
        $dpid = (int) ($this->safeSelectValue($db, 'SELECT dpid AS val FROM ms_geo_dp LIMIT 1') ?? 0);
        $userId = (int) ($this->safeSelectValue($db, 'SELECT userid AS val FROM usr_login LIMIT 1') ?? 1);

        if ($dpid === 0) {
            $row = $db->DataTable("SELECT dpid FROM sys_geo_codex WHERE geo_level='dp' LIMIT 1");
            $dpid = (int) ($row[0]['dpid'] ?? 1);
        }

        return [
            'dpid' => $dpid ?: 1,
            'user_id' => $userId ?: 1,
        ];
    }

    private function buildHouseholdPayload(array $geo, string $token, string $name, string $phone): array
    {
        return [
            'dpid' => $geo['dpid'],
            'hh_token' => $token,
            'hoh' => $name,
            'phone' => $phone,
            'longitude' => '7.1234',
            'latitude' => '8.4321',
            'user_id' => $geo['user_id'],
            'device_serial' => 'DEV-REG-001',
            'app_version' => 'pwa-1.0.0',
            'created' => date('Y-m-d H:i:s'),
        ];
    }

    private function buildChildPayload(array $geo, string $token, string $beneficiaryId, string $name): array
    {
        return [
            'hh_token' => $token,
            'beneficiary_id' => $beneficiaryId,
            'dpid' => $geo['dpid'],
            'name' => $name,
            'gender' => 'F',
            'dob' => '2020-01-01',
            'longitude' => '7.1234',
            'latitude' => '8.4321',
            'user_id' => $geo['user_id'],
            'device_serial' => 'DEV-REG-001',
            'app_version' => 'pwa-1.0.0',
            'created' => date('Y-m-d H:i:s'),
        ];
    }

    private function assertHouseholdName(string $token, string $expected): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT hoh_name FROM smc_child_household WHERE hh_token = '$token' LIMIT 1");
        $this->assertNotEmpty($rows);
        $this->assertEquals($expected, $rows[0]['hoh_name'] ?? null);
    }

    private function assertHouseholdMissing(string $token): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT hhid FROM smc_child_household WHERE hh_token = '$token' LIMIT 1");
        $this->assertEmpty($rows);
    }

    private function assertChildExists(string $beneficiaryId): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT beneficiary_id FROM smc_child WHERE beneficiary_id = '$beneficiaryId' LIMIT 1");
        $this->assertNotEmpty($rows);
    }

    private function assertChildName(string $beneficiaryId, string $expected): void
    {
        $db = $this->getDb();
        $rows = $db->DataTable("SELECT name FROM smc_child WHERE beneficiary_id = '$beneficiaryId' LIMIT 1");
        $this->assertNotEmpty($rows);
        $this->assertEquals($expected, $rows[0]['name'] ?? null);
    }

    private function safeSelectValue(\MysqlPdo $db, string $query): ?string
    {
        $rows = $db->DataTable($query);
        if (count($rows) === 0) {
            return null;
        }
        return $rows[0]['val'] ?? null;
    }

    private function uniqueToken(string $prefix): string
    {
        return $prefix . '-' . strtoupper(substr(uniqid(), -10));
    }
}
