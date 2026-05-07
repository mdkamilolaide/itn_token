<?php

namespace Tests\Integration\Registration;

use Smc\Registration;
use System\General;

class DataValidationTest extends RegistrationTestCase
{
    public function testBulkHouseholdSubmissionRejectsInvalidPayload(): void
    {
        $registration = new Registration();

        $this->assertFalse($registration->CreateHouseholdBulk([]));
        $this->assertFalse($registration->CreateHouseholdBulk('invalid'));
    }

    public function testBulkChildSubmissionRejectsInvalidPayload(): void
    {
        $registration = new Registration();

        $this->assertFalse($registration->CreateChildBulk([]));
        $this->assertFalse($registration->CreateChildBulk('invalid'));
    }

    public function testUpdateHouseholdBulkRejectsInvalidPayload(): void
    {
        $registration = new Registration();

        $this->assertFalse($registration->UpdateHouseholdBulk([]));
        $this->assertFalse($registration->UpdateHouseholdBulk('invalid'));
    }

    public function testUpdateChildBulkHandlesFailure(): void
    {
        $this->requireChildSchema(['beneficiary_id']);

        $registration = new Registration();
        $beneficiaryId = 'BEN-' . uniqid('', true);
        $this->seedChild($beneficiaryId, [
            'name' => 'Child One',
            'gender' => 'female',
            'dob' => '2020-01-01',
        ]);

        $result = $registration->UpdateChildBulk([
            [
                'beneficiary_id' => $beneficiaryId,
                'name' => 'Child Two',
                'gender' => 'male',
                'dob' => '2020-02-02',
            ],
        ]);

        $this->assertIsArray($result);
        $rows = $this->getDb()->DataTable("SELECT name FROM smc_child WHERE beneficiary_id = '$beneficiaryId' LIMIT 1");
        if (empty($rows)) {
            $this->markTestSkipped('Could not seed child in this environment');
        }
        $currentName = $rows[0]['name'] ?? null;

        if (empty($result)) {
            // update was rejected — ensure original data unchanged
            $this->assertSame('Child One', $currentName, 'UpdateChildBulk did not apply changes');
        } else {
            // update applied — ensure DB reflects change
            $this->assertContains($beneficiaryId, $result);
            $this->assertSame('Child Two', $currentName, 'UpdateChildBulk applied changes to seeded child');
        }
    }

    public function testGeneralGeoLookupsReturnSeededData(): void
    {
        $this->requireGeoSchema(['community', 'sys_geo_codex']);

        $general = new General();
        $geo = $this->seedGeoHierarchy('Reg');

        $states = $general->GetStateList();
        $this->assertNotEmpty($states);

        $lgas = $general->GetLgaList($geo['stateid']);
        $this->assertNotEmpty($lgas);

        $wards = $general->GetWardList($geo['lgaid']);
        $this->assertNotEmpty($wards);

        $dps = $general->GetDpList($geo['wardid']);
        $this->assertNotEmpty($dps);

        $comms = $general->GetCommunityList($geo['dpid']);
        $this->assertNotEmpty($comms);

        $codex = $general->GetGeoLocationCodex('dp');
        $this->assertNotEmpty($codex);

        $structure = $general->GetGeoStructureId('dp', $geo['dpid']);
        $this->assertNotEmpty($structure);
    }
}
