<?php

namespace Monitor;
use DbHelper;

#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
#
class Monitor{
    private $db;
    private $form_list = array('End Process','5% Revisit','I-9a','I-9b','I-9c');
    #
    public function __construct()
    {
        $this->db = GetMysqlDatabase();
    }
    public function GetFormStatusList(){
        $data = DbHelper::Table("SELECT
        (SELECT COUNT(*) FROM `mo_form_end_process`) AS `end_process`,
        (SELECT COUNT(*) FROM `mo_form_five_revisit`) AS `revisit`,
        (SELECT COUNT(*) FROM `mo_form_i9a`) AS `ininea`,
        (SELECT COUNT(*) FROM `mo_form_i9b`) AS `inineb`,
        (SELECT COUNT(*) FROM `mo_form_i9c`) AS `ininec`,
        (SELECT COUNT(*) FROM `mo_smc_supervisor_cdd`) AS `smc_cdd`,
        (SELECT COUNT(*) FROM `mo_smc_supervisor_hfw`) AS `smc_hfw`");
        if(count($data)){
            $end_process = $data[0]['end_process'];
            $revisit = $data[0]['revisit'];
            $ininea = $data[0]['ininea'];
            $inineb = $data[0]['inineb'];
            $ininec = $data[0]['ininec'];
            $smc_cdd = $data[0]['smc_cdd'];
            $smc_hfw = $data[0]['smc_hfw'];

            return array(
                array('sn'=>1,'name'=>'I-9a Mobilization Spotcheck','total'=>$ininea),
                array('sn'=>2,'name'=>'I-9b Distribution Point (DP) Spotcheck','total'=>$inineb),
                array('sn'=>3,'name'=>'I-9c Distribution HH Spotcheck','total'=>$ininec),
                array('sn'=>4,'name'=>'5% Revisit','total'=>$revisit),
                array('sn'=>5,'name'=>'End Process 1','total'=>$end_process),
                array('sn'=>6,'name'=>'End Process 2','total'=>$end_process),
                array('sn'=>7,'name'=>'SMC Supervisory CDD','total'=>$smc_cdd),
                array('sn'=>8,'name'=>'SMC Supervisory HFW','total'=>$smc_hfw));
        }
        #
        return array();
    }
    #   Ee is Excel Export for i9a
    public function EeFormInineA(){
        $query = "SELECT
        mo_form_i9a.uid,
        usr_login.loginid AS `user Login ID`,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `user fullname`,
        ms_geo_lga.Fullname AS lga,
        ms_geo_ward.ward,
        ms_geo_comm.community,
        mo_form_i9a.aa AS `Is the household marked as having been visited by a mobilizer?`,
        mo_form_i9a.ab AS `Name of household head`,
        mo_form_i9a.ac AS `Has your household received a visit from a Household Mobilizer?`,
        mo_form_i9a.ad AS `What did the household mobilizer tell you was the reason for his/her visit?`,
        mo_form_i9a.ae AS `How many people live in your household on a regular basis?`,
        mo_form_i9a.af AS `Did the mobilizer give you a Net Card for a free ITN?`,
        mo_form_i9a.ag AS `How many Net cards did the mobilizer give you?`,
        mo_form_i9a.ah AS `Ask to see the Net card. Did the mobilizer fill in the correct information on the Net card`,
        mo_form_i9a.ai AS `What did the household mobilizer tell you about malaria during his/her visit?`,
        mo_form_i9a.latitude,
        mo_form_i9a.longitude,
        mo_form_i9a.domain,
        mo_form_i9a.app_version AS `app version`,
        mo_form_i9a.capture_date AS `captured date`,
        mo_form_i9a.created AS `date created`
        FROM
        mo_form_i9a
        LEFT JOIN ms_geo_ward ON mo_form_i9a.wardid = ms_geo_ward.wardid
        LEFT JOIN ms_geo_lga ON mo_form_i9a.lgaid = ms_geo_lga.LgaId
        LEFT JOIN ms_geo_comm ON mo_form_i9a.comid = ms_geo_comm.comid
        LEFT JOIN usr_login ON mo_form_i9a.userid = usr_login.userid
        LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
        $json_data = array(array(
            "sheetName" => "Form-i9a",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    #   Ee is Excel Export for i9b
    public function EeFormInineB(){
        $query = "SELECT
        mo_form_i9b.uid,
        usr_login.loginid AS `user loginid`,
        CONCAT_WS(usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `user fullname`,
        ms_geo_lga.Fullname AS lga,
        ms_geo_ward.ward,
        ms_geo_dp.dp,
        ms_geo_comm.community,
        mo_form_i9b.supervisor,
        mo_form_i9b.aa AS `(1) Does the distribution site have enough ITNs to complete the day's distribution?`,
        mo_form_i9b.ab AS `(1) Comment/Actions`,
        mo_form_i9b.ba AS `(2) Is there a device adequately provisioned to support distribution at the distribution point?`,
        mo_form_i9b.bb AS `(2) Comment/Actions`,
        mo_form_i9b.ca AS `(3) Is there a well organized waiting area for the beneficiaries?`,
        mo_form_i9b.cb AS `(3) Comment/Actions`,
        mo_form_i9b.da AS `(4) Does the site have a net properly hanging for beneficiaries to see?`,
        mo_form_i9b.db AS `(4) Comment/Actions`,
        mo_form_i9b.ea AS `(5) Are all members of the team present at the distribution site (supervisor, team members)?`,
        mo_form_i9b.eb AS `(5) Comment/Actions`,
        mo_form_i9b.fa AS `(6) Are all members of the team correctly identified?`,
        mo_form_i9b.fb AS `(6) Comment/Actions`,
        mo_form_i9b.ga AS `(7) Is the flow of people well organized at the distribution point?`,
        mo_form_i9b.gb AS `(7) Comment/Actions`,
        mo_form_i9b.ha AS `(8) Are crowd control personnel present at the distribution point?`,
        mo_form_i9b.hb AS `(8) Comment/Actions`,
        mo_form_i9b.ia AS `(9) Do the team members give the ITN beneficiaries the key messages (purpose, use and care of the ITN)?`,
        mo_form_i9b.ib AS `(9) Comment/Actions`,
        mo_form_i9b.ja AS `10) Do the teams respect the instructions for ITN distribution (maximum of 4 Net cards, maximum of 4 ITNs)?`,
        mo_form_i9b.jb AS `(10) Comment/Actions`,
        mo_form_i9b.ka AS `(11) Are the ITNs removed from the plastic packaging before being given to the beneficiaries?`,
        mo_form_i9b.kb AS `(11) Comment/Actions`,
        mo_form_i9b.la AS `(12) Are the Net cards being put in the Net card bag?`,
        mo_form_i9b.lb AS `(12) Comment/Actions`,
        mo_form_i9b.ma AS `(13) Is the device for distrbution being used properly to scan net cards?`,
        mo_form_i9b.mb AS `(13) Comment/Actions`,
        mo_form_i9b.na AS `(14) Is the inventory control card (ICC) properly filled?`,
        mo_form_i9b.nb AS `(14) Comment/Actions`,
        mo_form_i9b.oa AS `(15) Is waste being managed correctly and not congesting the distribution point?`,
        mo_form_i9b.ob AS `(15) Comment/Actions`,
        mo_form_i9b.latitude,
        mo_form_i9b.longitude,
        mo_form_i9b.domain,
        mo_form_i9b.app_version,
        mo_form_i9b.capture_date,
        mo_form_i9b.created
        FROM
        mo_form_i9b
        LEFT JOIN ms_geo_lga ON mo_form_i9b.lgaid = ms_geo_lga.LgaId
        LEFT JOIN ms_geo_ward ON mo_form_i9b.wardid = ms_geo_ward.wardid
        LEFT JOIN ms_geo_dp ON mo_form_i9b.dpid = ms_geo_dp.dpid
        LEFT JOIN ms_geo_comm ON mo_form_i9b.comid = ms_geo_comm.comid
        LEFT JOIN usr_login ON mo_form_i9b.userid = usr_login.userid
        LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
        $json_data = array(array(
            "sheetName" => "Form-i9b",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    #   Ee is Excel Export for i9c
    public function EeFormInineC(){
        $query = "SELECT
        mo_form_i9c.uid,
        usr_login.loginid AS `User login id`,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `user fullname`,
        ms_geo_lga.Fullname AS lga,
        ms_geo_ward.ward,
        mo_form_i9c.aa AS `Name of household head`,
        mo_form_i9c.ab AS `Did your household receive Net cards for the ITN distribution that is taking place?`,
        mo_form_i9c.ac AS `Did anyone from your household take the Net cards to the distribution point written on the Net card?`,
        mo_form_i9c.ad AS `Did you receive a ITN when you took your Net cards to the distribution site?`,
        mo_form_i9c.ae AS `How many ITNs did you receive?`,
        mo_form_i9c.af AS `If hanging, did you air  the nets?`,
        mo_form_i9c.ag AS `If nets not hanging, why`,
        mo_form_i9c.ah AS `Do you have any problems with using the net? NOTE: write brief explanation of problem`,
        mo_form_i9c.latitude,
        mo_form_i9c.longitude,
        mo_form_i9c.domain,
        mo_form_i9c.app_version,
        mo_form_i9c.capture_date AS `capture date`,
        mo_form_i9c.created
        FROM
        mo_form_i9c
        LEFT JOIN ms_geo_lga ON mo_form_i9c.lgaid = ms_geo_lga.LgaId
        LEFT JOIN ms_geo_ward ON mo_form_i9c.wardid = ms_geo_ward.wardid
        LEFT JOIN usr_login ON mo_form_i9c.userid = usr_login.userid
        LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
        $json_data = array(array(
            "sheetName" => "Form-i9c",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    #   Ee is Excel Export for 5% revisit
    public function EeFormFiveRevisit(){
        $query = "SELECT
        mo_form_five_revisit.uid,
        usr_login.loginid AS `user login id`,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `user fullname`,
        ms_geo_lga.Fullname AS lga,
        ms_geo_ward.ward,
        ms_geo_dp.dp,
        ms_geo_comm.community,
        mo_form_five_revisit.aa AS `Last Name of household head`,
        mo_form_five_revisit.ab AS `First Name of household head`,
        mo_form_five_revisit.ac AS Gender,
        mo_form_five_revisit.ad AS `Name of Household Head’s Mother`,
        mo_form_five_revisit.ae AS `Household Phone Number`,
        mo_form_five_revisit.af AS `Household Number of Family Members`,
        mo_form_five_revisit.ag AS `Household Number of Sleeping Spaces`,
        mo_form_five_revisit.ah AS `Number of Adult Females`,
        mo_form_five_revisit.ai AS `Number of Adult Males`,
        mo_form_five_revisit.aj AS `Number of Children`,
		mo_form_five_revisit.etoken_serial AS `e-Token Serial`,
		mo_form_five_revisit.etoken_uuid AS `e-Token GUID`,
        mo_form_five_revisit.latitude,
        mo_form_five_revisit.longitude,
        mo_form_five_revisit.domain,
        mo_form_five_revisit.app_version,
        mo_form_five_revisit.capture_date AS `capture date`,
        mo_form_five_revisit.created
        FROM
        mo_form_five_revisit
        LEFT JOIN ms_geo_lga ON mo_form_five_revisit.lgaid = ms_geo_lga.LgaId
        LEFT JOIN ms_geo_ward ON mo_form_five_revisit.wardid = ms_geo_ward.wardid
        LEFT JOIN ms_geo_dp ON mo_form_five_revisit.dpid = ms_geo_dp.dpid
        LEFT JOIN ms_geo_comm ON mo_form_five_revisit.comid = ms_geo_comm.comid
        LEFT JOIN usr_login ON mo_form_five_revisit.userid = usr_login.userid
        LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
        $json_data = array(array(
            "sheetName" => "Revisit",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    #   Ee is Excel Export for End process 1
    public function EeFormEndProOne(){
        $query = "SELECT
        mo_form_end_process.uid,
        usr_login.loginid AS `user loginid`,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `user fullname`,
        ms_geo_lga.Fullname AS lga,
        ms_geo_ward.ward,
        ms_geo_comm.community,
        mo_form_end_process.aa AS `No. of Children Under 5years`,
        mo_form_end_process.ab AS `No. of Pregnant women`,
        mo_form_end_process.ac AS `No. of Others`,
        mo_form_end_process.ad AS `Net Card issued out to Household by Mobilization team`,
        mo_form_end_process.ae AS `Net Card redeemed by household`,
        mo_form_end_process.af AS `Total  number of LLIN received by household`,
        mo_form_end_process.ag AS `No. of LLIN present in Household?`,
        mo_form_end_process.ah AS `No. LLIN Hanging over sleep Area?`,
        mo_form_end_process.ai AS `No. of Children Under 5years that slept inside LLIN last night?`,
        mo_form_end_process.aj AS `No. of Pregnant women that slept inside LLIN last night?`,
        mo_form_end_process.ak AS `No. of others that slept inside LLIN last night?`,
        mo_form_end_process.al AS `Source of information`,
        mo_form_end_process.am AS `Reasons for non-use of LLIN`,
        mo_form_end_process.latitude,
        mo_form_end_process.longitude,
        mo_form_end_process.domain,
        mo_form_end_process.app_version,
        mo_form_end_process.capture_date AS `Capture date`,
        mo_form_end_process.created
        FROM
        mo_form_end_process
        LEFT JOIN ms_geo_lga ON mo_form_end_process.lgaid = ms_geo_lga.LgaId
        LEFT JOIN ms_geo_ward ON mo_form_end_process.wardid = ms_geo_ward.wardid
        LEFT JOIN ms_geo_comm ON mo_form_end_process.comid = ms_geo_comm.comid
        LEFT JOIN usr_login ON mo_form_end_process.userid = usr_login.userid
        LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
        $json_data = array(array(
            "sheetName" => "End-Process-1",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    #   Ee is Excel Export for End process 1
    public function EeFormEndProTwo(){
        $query = "";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
        $json_data = array(array(
            "sheetName" => "End-Process-2",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    #   Ee is Excel Export for SMC Supervisory CDD
    public function EeFormSmcSupervisoryCdd(){
        $query = "SELECT
        mo_smc_supervisor_cdd.id,
        smc_period.title AS visit,
        mo_smc_supervisor_cdd.uid,
        usr_login.loginid AS `user loginid`,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `user fullname`,
        ms_geo_lga.Fullname AS lga,
        ms_geo_ward.ward,
        mo_smc_supervisor_cdd.aa AS '1. CDD is prepared and has all required materials to deliver SMC',
        mo_smc_supervisor_cdd.ab AS '1. Comment',
        mo_smc_supervisor_cdd.ba AS '2. CDD is wearing campaign uniform',
        mo_smc_supervisor_cdd.bb AS '2. Comment',
        mo_smc_supervisor_cdd.ca AS '3. CDD gives caregiver information about SMC',
        mo_smc_supervisor_cdd.cb AS '3. Comment',
        mo_smc_supervisor_cdd.da AS '4. CDD determines child’s age. Excludes children younger than 3 months and older 59 months',
        mo_smc_supervisor_cdd.db AS '4. Comment',
        mo_smc_supervisor_cdd.ea AS '5. CDD asks caregiver questions to determine if child is eligible for SMC',
        mo_smc_supervisor_cdd.eb AS '5. Comment',
        mo_smc_supervisor_cdd.fa AS '6. CDD excludes children with fever, who are very sick, have side effects to SP or AQ, have taken SP or AQ in the past 4 weeks, or are taking cotrimoxazole or Septrin or Bactrim',
        mo_smc_supervisor_cdd.fb AS '6. Comment',
        mo_smc_supervisor_cdd.ga AS '7. CDD refers all sick children, children with fever, or with side effects to SPAQ to the nearest health facility and Correctly completes the SMC Referral Form',
        mo_smc_supervisor_cdd.gb AS '7. Comment',
        mo_smc_supervisor_cdd.ha AS '8. CDD uses good hygiene and administers the correct age dose of SPAQ by directly observed therapy (DOT)',
        mo_smc_supervisor_cdd.hb AS '8. Comment',
        mo_smc_supervisor_cdd.ia AS '9. CDD asks caregiver to observe the child for 30 minutes and notify her/him if the child vomits the medicine',
        mo_smc_supervisor_cdd.ib AS '9. Comment',
        mo_smc_supervisor_cdd.ja AS '10. CDD re-doses the child with one more dose of SPAQ if the child vomits within 30 minutes',
        mo_smc_supervisor_cdd.jb AS '10. Comment',
        mo_smc_supervisor_cdd.ka AS '11. CDD completes the information on the SMC Tally Sheet correctly',
        mo_smc_supervisor_cdd.kb AS '11. Comment',
        mo_smc_supervisor_cdd.la AS '12. CDD completes all information on SMC Child Record Card correctly',
        mo_smc_supervisor_cdd.lb AS '13. Comment',
        mo_smc_supervisor_cdd.ma AS '13. CDD gives caregiver 2 tablets of AQ shows her how to give it at home. Explains the importance of adherence.',
        mo_smc_supervisor_cdd.mb AS '14. Comment',
        mo_smc_supervisor_cdd.na AS '14. Explains to the caregiver how to complete the SMC Child Record Card and why to keep it in a safe place for all 4 cycles',
        mo_smc_supervisor_cdd.nb AS '15. Comment',
        mo_smc_supervisor_cdd.oa AS '15. Explains to caregiver when to take the child to the Health Facility',
        mo_smc_supervisor_cdd.ob AS '16. Comment',
        mo_smc_supervisor_cdd.pa AS '16. CDD uses the SMC Job Aid to give the caregiver messages for prevention of malaria',
        mo_smc_supervisor_cdd.pb AS '17. Comment',
        mo_smc_supervisor_cdd.q AS 'Feedback communicated to the CDD Team',
        mo_smc_supervisor_cdd.r AS 'Skills mentoring provided',
        mo_smc_supervisor_cdd.s AS 'Notes for next supervision visit',
        mo_smc_supervisor_cdd.latitude,
        mo_smc_supervisor_cdd.longitude,
        mo_smc_supervisor_cdd.domain,
        mo_smc_supervisor_cdd.app_version AS 'app version',
        mo_smc_supervisor_cdd.capture_date AS `captured date`,
        mo_smc_supervisor_cdd.created
        FROM
        mo_smc_supervisor_cdd
        LEFT JOIN smc_period ON mo_smc_supervisor_cdd.periodid = smc_period.periodid
        LEFT JOIN ms_geo_lga ON mo_smc_supervisor_cdd.lgaid = ms_geo_lga.LgaId
        LEFT JOIN ms_geo_ward ON mo_smc_supervisor_cdd.wardid = ms_geo_ward.wardid
        LEFT JOIN usr_login ON mo_smc_supervisor_cdd.userid = usr_login.userid
        LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
        $json_data = array(array(
            "sheetName" => "SMC-Supervisory-CDD",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    #   Ee is Excel Export for SMC Supervisory HFW
    public function EeFormSmcSupervisoryHfw(){
        $query = "SELECT
            mo_smc_supervisor_hfw.id,
            smc_period.title AS visit,
            mo_smc_supervisor_hfw.uid,
            usr_login.loginid AS `user loginid`,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `user fullname`,
            ms_geo_lga.Fullname AS lga,
            ms_geo_ward.ward,
            mo_smc_supervisor_hfw.aa AS '1. HFW has successfully completed SMC training',
            mo_smc_supervisor_hfw.ab AS '1. Comment',
            mo_smc_supervisor_hfw.ba AS '2. HFW fully examines a child referred during SMC',
            mo_smc_supervisor_hfw.bb AS '2. Comment',
            mo_smc_supervisor_hfw.ca AS '3. HFW is knowledgeable about management of ADRs to SPAQ',
            mo_smc_supervisor_hfw.cb AS '3. Comment',
            mo_smc_supervisor_hfw.da AS '4. HFW completes the bottom section of the SMC Referral Form correctly',
            mo_smc_supervisor_hfw.db AS '4. Comment',
            mo_smc_supervisor_hfw.ea AS '5. National PV Forms are available and HFWs knows how to complete',
            mo_smc_supervisor_hfw.eb AS '5. Comment',
            mo_smc_supervisor_hfw.fa AS '6. HFW gives the child SPAQ in the HF if the child is eligible for SMC',
            mo_smc_supervisor_hfw.fb AS '6. Comment',
            mo_smc_supervisor_hfw.ga AS '7. HFW uses good hygiene to administer the correct age dose of SPAQ by directly observed therapy (DOT). Disperses SPAQ tablets in a small amount of water and gives the full amount of medicine to the child to swallow fully',
            mo_smc_supervisor_hfw.gb AS '7. Comment',
            mo_smc_supervisor_hfw.ha AS '8. HFW re-doses the child with one more dose of SPAQ if the child vomits within 30 minutes',
            mo_smc_supervisor_hfw.hb AS '8. Comment',
            mo_smc_supervisor_hfw.ia AS '9. HFW completes the information on the SMC Tally Sheet correctly',
            mo_smc_supervisor_hfw.ib AS '9. Comment',
            mo_smc_supervisor_hfw.ja AS '10. HFW completes all information on SMC Child Record Card correctly',
            mo_smc_supervisor_hfw.jb AS '10. Comment',
            mo_smc_supervisor_hfw.ka AS '11. HFW gives caregiver 2 tablets of AQ shows her how to give it at home. Explains the importance of adherence',
            mo_smc_supervisor_hfw.kb AS '11. Comment',
            mo_smc_supervisor_hfw.la AS '12. HFW explains to the caregiver how to complete the SMC Child Record Card and why to keep it in a safe place for all 4 cycles',
            mo_smc_supervisor_hfw.lb AS '12. Comment',
            mo_smc_supervisor_hfw.m1a AS '13a. HF has sufficient stock of SMC and malaria commodities. 3-11 months SPAQ',
            mo_smc_supervisor_hfw.m1b AS '13a. Comment',
            mo_smc_supervisor_hfw.m2a AS '13b. HF has sufficient stock of SMC and malaria commodities. 12-59 months SPAQ',
            mo_smc_supervisor_hfw.m2b AS '13b. Comment',
            mo_smc_supervisor_hfw.m3a AS '13c. HF has sufficient stock of SMC and malaria commodities. Artemether/lumefantrine for all child ages',
            mo_smc_supervisor_hfw.m3b AS '13c. Comment',
            mo_smc_supervisor_hfw.m4a AS '13d. HF has sufficient stock of SMC and malaria commodities. RDTs',
            mo_smc_supervisor_hfw.m4b AS '13d. Comment',
            mo_smc_supervisor_hfw.n1a AS '14a. HF has sufficient stock of SMC data collection tools: Inventory Control Cards',
            mo_smc_supervisor_hfw.n1b AS '14a. Comment',
            mo_smc_supervisor_hfw.n2a AS '14b. HF has sufficient stock of SMC data collection tools: SMC Tally Sheets',
            mo_smc_supervisor_hfw.n2b AS '14b. Comment',
            mo_smc_supervisor_hfw.n3a AS '14c. HF has sufficient stock of SMC data collection tools: SMC Child Record Cards',
            mo_smc_supervisor_hfw.n3b AS '14c. Comment',
            mo_smc_supervisor_hfw.n4a AS '14d. HF has sufficient stock of SMC data collection tools: HF Daily Summary Forms',
            mo_smc_supervisor_hfw.n4b AS '14d. Comment',
            mo_smc_supervisor_hfw.n5a AS '14e. HF has sufficient stock of SMC data collection tools: SMC Referral Forms',
            mo_smc_supervisor_hfw.n5b AS '14e. Comment',
            mo_smc_supervisor_hfw.n6a AS '14f. HF has sufficient stock of SMC data collection tools: SMC End-of-Cycle Reports',
            mo_smc_supervisor_hfw.n6b AS '14f. Comment',
            mo_smc_supervisor_hfw.o1a AS '15a. HF completes SPAQ drug accountability and reconciliation forms accurately: SMC Tally Sheets',
            mo_smc_supervisor_hfw.o1b AS '15a. Comment',
            mo_smc_supervisor_hfw.o2a AS '15b. HF completes SPAQ drug accountability and reconciliation forms accurately: HF Daily Summary Form',
            mo_smc_supervisor_hfw.o2b AS '15b. Comment',
            mo_smc_supervisor_hfw.o3a AS '15c. HF completes SPAQ drug accountability and reconciliation forms accurately: SMC End-of-Cycle Report',
            mo_smc_supervisor_hfw.o3b AS '15c. Comment',
            mo_smc_supervisor_hfw.pa AS '16. HFW reviews all the Tally Sheets and Referral Forms and completes the SMC End-of-Cycle Report at the end of each cycle',
            mo_smc_supervisor_hfw.pb AS '16. Comment',
            mo_smc_supervisor_hfw.q1a AS '17a. HF keeps accurate records of drug stock and Medical Store forms: RIRV receipt vouchers',
            mo_smc_supervisor_hfw.q1b AS '17a. Comment',
            mo_smc_supervisor_hfw.q2a AS '17b. HF keeps accurate records of drug stock and Medical Store forms: Inventory control cards',
            mo_smc_supervisor_hfw.q2b AS '17b. Comment',
            mo_smc_supervisor_hfw.ra AS '18. SPAQ and other malaria commodities are stored in a clean, secure and safe place and FEFO method is used',
            mo_smc_supervisor_hfw.rb AS '18. Comment',
            mo_smc_supervisor_hfw.s AS 'Feedback communicated to the HFWs',
            mo_smc_supervisor_hfw.t AS 'Skills mentoring provided',
            mo_smc_supervisor_hfw.v AS 'Notes for next supervision visit',
            mo_smc_supervisor_hfw.latitude,
            mo_smc_supervisor_hfw.longitude,
            mo_smc_supervisor_hfw.domain,
            mo_smc_supervisor_hfw.app_version AS 'app version',
            mo_smc_supervisor_hfw.capture_date AS `captured date`,
            mo_smc_supervisor_hfw.created
            FROM
            mo_smc_supervisor_hfw
            LEFT JOIN smc_period ON mo_smc_supervisor_hfw.periodid = smc_period.periodid
            LEFT JOIN ms_geo_lga ON mo_smc_supervisor_hfw.lgaid = ms_geo_lga.LgaId
            LEFT JOIN ms_geo_ward ON mo_smc_supervisor_hfw.wardid = ms_geo_ward.wardid
            LEFT JOIN usr_login ON mo_smc_supervisor_hfw.userid = usr_login.userid
            LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid";
            #   Get payload
        $data = $this->db->ExcelDataTable($query);
        #   Prep Payload
        $json_data = array(array(
            "sheetName" => "SMC-Supervisory-HFW",
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
}

?>