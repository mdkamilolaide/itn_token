<?php

namespace Tests\Integration\Registration;

use Smc\Registration;

class RegistrationTest extends RegistrationTestCase
{
    public function testNewEntityRegistration(): void
    {
        $this->requireHouseholdSchema(['hhid', 'hh_token']);

        $registration = new Registration();
        $token = 'HH-' . uniqid('', true);
        $householdId = $registration->CreateHousehold($token, 'Jane Doe', '08000000000');

        $this->assertNotEmpty($householdId);

        $rows = $this->getDb()->DataTable("SELECT * FROM smc_child_household WHERE hh_token = '{$token}'");
        $this->assertNotEmpty($rows);

        if ($this->columnExists('smc_child_household', 'hoh_name')) {
            $this->assertSame('Jane Doe', $rows[0]['hoh_name']);
        }
        if ($this->columnExists('smc_child_household', 'hoh_phone')) {
            $this->assertSame('08000000000', $rows[0]['hoh_phone']);
        }
    }

    public function testDuplicateRegistrationPrevention(): void
    {
        $this->requireHouseholdSchema(['hhid', 'hh_token']);

        $registration = new Registration();
        $token = 'HH-' . uniqid('', true);

        $registration->CreateHousehold($token, 'Jane Doe', '08000000000');
        $registration->CreateHousehold($token, 'Jane Doe', '08000000000');

        $rows = $this->getDb()->DataTable("SELECT COUNT(*) AS total FROM smc_child_household WHERE hh_token = '{$token}'");
        $this->assertSame(1, (int) $rows[0]['total']);
    }

    public function testRegistrationConfirmationWorkflow(): void
    {
        $this->requireHouseholdSchema(['hhid', 'hh_token']);

        $registration = new Registration();
        $token = 'HH-' . uniqid('', true);
        $householdId = $registration->CreateHousehold($token, 'Jane Doe', '08000000000');

        $this->assertNotEmpty($householdId);

        $registration->UpdateHousehold($token, 'Janet Doe', '08000000001', $householdId);
        $updated = $this->getDb()->DataTable("SELECT * FROM smc_child_household WHERE hhid = {$householdId} LIMIT 1");
        $this->assertNotEmpty($updated);
        if ($this->columnExists('smc_child_household', 'hoh_name')) {
            $this->assertSame('Janet Doe', $updated[0]['hoh_name']);
        }
        if ($this->columnExists('smc_child_household', 'hoh_phone')) {
            $this->assertSame('08000000001', $updated[0]['hoh_phone']);
        }

        $registration->DeleteHousehold($householdId);
        $deleted = $this->getDb()->DataTable("SELECT hhid FROM smc_child_household WHERE hhid = {$householdId}");
        $this->assertSame([], $deleted);
    }
}
