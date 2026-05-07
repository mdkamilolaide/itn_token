<?php
namespace Smc;

use DbHelper;

include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Reporting{
    private $db;
    public function __construct()
    {
        $this->db = GetMysqlDatabase();
    }
    private function TransportDataJson($query, $sheetname = "Export"){
        $data = $this->db->ExcelDataTable($query);
         #   Prep Payload
         $json_data = array(array(
            "sheetName" => $sheetname ,
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    //
    //====  Drug Administration ====
    // filter['periodid'=>'2,'is_eligible'=>[yes|no],'is_redose'=>[yes|no],'reg_date'=>'date','geo_id'=>1234,'geo_level'=>'lga','beneficiaryid']
    public function CountDrugAdminBase($filter = []){
        #
        #   Filters
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';    #  period ID   
        $is_eligible = (is_array($filter) && array_key_exists('is_eligible', $filter)) ? $filter['is_eligible'] : '';    #  The child is eligible must be yes
        $is_redose = (is_array($filter) && array_key_exists('is_redose', $filter)) ? $filter['is_redose'] : '';    #  Redose must be yes
        $reg_date = (is_array($filter) && array_key_exists('reg_date', $filter)) ? $filter['reg_date'] : '';     #   Registration date
        $geo_id = (is_array($filter) && array_key_exists('geo_id', $filter)) ? $filter['geo_id'] : '';     #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('geo_level', $filter)) ? $filter['geo_level'] : '';     #   Geo-Level
        $beneficiary_id = (is_array($filter) && array_key_exists('beneficiaryid', $filter)) ? $filter['beneficiaryid'] : '';     #   Beneficiary ID
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid = $periodid ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.periodid = $periodid ";
        }
        if ($is_eligible == 'yes') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.is_eligible = 1 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.is_eligible = 1 ";
        }
        if ($is_eligible == 'no') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.is_eligible = 0 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.is_eligible = 0 ";
        }
        if ($is_redose == 'yes') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.redose_count = 1 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.redose_count = 1 ";
        }
        if ($is_redose == 'no') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.redose_count = 0 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.redose_count = 0 ";
        }
        if ($reg_date) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) = DATE('$reg_date') ";
                $seed = 1;
            } else
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) = DATE('$reg_date') ";
        }
        if ($geo_id && $geo_level) {
            if ($seed == 0) {
                $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
                $seed = 1;
            } else
                $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
        }
        if ($beneficiary_id) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_child.beneficiary_id = '$beneficiary_id' ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_child.beneficiary_id = '$beneficiary_id' ";
        }
        #
        $query = "SELECT COUNT(*) 
                FROM `smc_drug_administration`
                INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
                INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
                $where_condition ";
        return DbHelper::GetScalar($query);
    }
    // filter['periodid'=>'2,'is_eligible'=>[yes|no],'is_redose'=>[yes|no],'reg_date'=>'date','geo_id'=>1234,'geo_level'=>'lga','beneficiaryid']
    public function DrugAdminBase($filter = []){
        #
        #   Filters
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';    #  period ID   
        $is_eligible = (is_array($filter) && array_key_exists('is_eligible', $filter)) ? $filter['is_eligible'] : '';    #  The child is eligible must be yes
        $is_redose = (is_array($filter) && array_key_exists('is_redose', $filter)) ? $filter['is_redose'] : '';    #  Redose must be yes
        $reg_date = (is_array($filter) && array_key_exists('reg_date', $filter)) ? $filter['reg_date'] : '';     #   Registration date
        $geo_id = (is_array($filter) && array_key_exists('geo_id', $filter)) ? $filter['geo_id'] : '';     #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('geo_level', $filter)) ? $filter['geo_level'] : '';     #   Geo-Level
        $beneficiary_id = (is_array($filter) && array_key_exists('beneficiaryid', $filter)) ? $filter['beneficiaryid'] : '';     #   Beneficiary ID
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid = $periodid ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.periodid = $periodid ";
        }
        if ($is_eligible == 'yes') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.is_eligible = 1 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.is_eligible = 1 ";
        }
        if ($is_eligible == 'no') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.is_eligible = 0 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.is_eligible = 0 ";
        }
        if ($is_redose == 'yes') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.redose_count = 1 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.redose_count = 1 ";
        }
        if ($is_redose == 'no') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.redose_count = 0 ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.redose_count = 0 ";
        }
        if ($reg_date) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) = DATE('$reg_date') ";
                $seed = 1;
            } else
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) = DATE('$reg_date') ";
        }
        if ($geo_id && $geo_level) {
            if ($seed == 0) {
                $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
                $seed = 1;
            } else
                $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
        }
        if ($beneficiary_id) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_child.beneficiary_id = '$beneficiary_id' ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_child.beneficiary_id = '$beneficiary_id' ";
        }
        #
        $query = "SELECT 
                smc_drug_administration.adm_id AS `id`,
                smc_period.title AS `visit`, 
                sys_geo_codex.geo_string AS `location`,
                smc_child.`name` AS `beneficiary`,
                smc_child.beneficiary_id AS `beneficiary id`,
                smc_child.dob AS `beneficiary dob`,
                smc_drug_administration.drug,
                if(smc_drug_administration.redose_count,'Redosed','NA') AS `redose`,
                smc_drug_administration.redose_reason AS `redose reason`,
                if(smc_drug_administration.is_eligible = 0,'Not Eligible','NA') AS `eligibility`,
                smc_drug_administration.not_eligible_reason AS `eligibility reason`,
                smc_drug_administration.collected_date AS `date`
                FROM `smc_drug_administration`
                INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
                INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
                INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
                $where_condition 
                ORDER BY smc_drug_administration.periodid, sys_geo_codex.geo_string";
        #   return payload
        return $this->TransportDataJson($query, "Drug Admin");
    }
    //
    //====  Referral ====
    //
    //  filter['periodid'=>1,'geo_id'=>1234,'geo_level'=>'lga','attended'=>[yes|no]]
    public function CountReferralBase($filter = []){
        #
        #   Filters
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';      #  period ID
        $geo_id = (is_array($filter) && array_key_exists('geo_id', $filter)) ? $filter['geo_id'] : '';         #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('geo_level', $filter)) ? $filter['geo_level'] : '';      #   Geo-Level
        $attended = (is_array($filter) && array_key_exists('attended', $filter)) ? $filter['attended'] : '';       #   Attended filter
        #  Where condition
        $where_condition = " WHERE smc_drug_administration.is_refer = 1 ";
        $seed = 1;
        #
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($periodid) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.periodid IN ($periodid) ";
        }
        if ($geo_id && $geo_level) {
            if ($seed == 0) {
                $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
                $seed = 1;
            } else
                $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
        }
        if (strtolower($attended) == 'yes') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_referer_record.ref_id IS NOT NULL ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_referer_record.ref_id IS NOT NULL ";
        }
        if (strtolower($attended) == 'no') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_referer_record.ref_id IS NULL ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_referer_record.ref_id IS NULL ";
        }
        #
        $query = "SELECT COUNT(*)
                FROM
                smc_drug_administration
                LEFT JOIN smc_referer_record ON smc_drug_administration.adm_id = smc_referer_record.adm_id
                INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid 
                AND sys_geo_codex.geo_level = 'dp'
                $where_condition ";
        return DbHelper::GetScalar($query);
    }
    //  filter['periodid'=>1,'geo_id'=>1234,'geo_level'=>'lga','attended'=>[yes|no]]
    public function ReferralBase($filter = []){
        #
        #   Filters
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';      #  period ID
        $geo_id = (is_array($filter) && array_key_exists('geo_id', $filter)) ? $filter['geo_id'] : '';         #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('geo_level', $filter)) ? $filter['geo_level'] : '';      #   Geo-Level
        $attended = (is_array($filter) && array_key_exists('attended', $filter)) ? $filter['attended'] : '';       #   Attended filter
        #  Where condition
        $where_condition = " WHERE smc_drug_administration.is_refer = 1 ";
        $seed = 1;
        #
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($periodid) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.periodid IN ($periodid) ";
        }
        if ($geo_id && $geo_level) {
            if ($seed == 0) {
                $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
                $seed = 1;
            } else
                $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
        }
        if (strtolower($attended) == 'yes') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_referer_record.ref_id IS NOT NULL ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_referer_record.ref_id IS NOT NULL ";
        }
        if (strtolower($attended) == 'no') {
            if ($seed == 0) {
                $where_condition = " WHERE smc_referer_record.ref_id IS NULL ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_referer_record.ref_id IS NULL ";
        }
        #
        $query = "SELECT
                    smc_drug_administration.adm_id ,
                    sys_geo_codex.geo_string,
                    smc_period.title AS `visit`,
                    smc_child.`name`,
                    smc_child.beneficiary_id,
                    smc_drug_administration.not_eligible_reason AS refer_type,
                IF
                    ( smc_referer_record.ref_id IS NOT NULL, 'Yes', 'No' ) AS attended,
                    
                    smc_drug_administration.collected_date AS referred_date,
                    smc_referer_record.collected_date AS attended_date 
                FROM
                    smc_drug_administration
                    LEFT JOIN smc_referer_record ON smc_drug_administration.adm_id = smc_referer_record.adm_id
                    INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
                    INNER JOIN smc_child ON smc_child.beneficiary_id = smc_drug_administration.beneficiary_id
                    INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid 
                    AND sys_geo_codex.geo_level = 'dp'
                $where_condition 
                ORDER BY smc_drug_administration.periodid, sys_geo_codex.geo_string";
        #   return payload
        return $this->TransportDataJson($query, "Referral");
    }
    //
    //====  Inventory Control ====
    //
    //  filter['periodid'=>1,'geo_id'=>1234,'geo_level'=>'lga']
    public function CountIccCddBase($filter = []){
        #
        #   Filters
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';       #  period ID
        $geo_id = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_id'] : '';         #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_level'] : '';      #   Geo-Level
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_issue.periodid IN ($periodid) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_icc_issue.periodid IN ($periodid) ";
        }
        if ($geo_id && $geo_level) {
            $level = "";
            if($geo_level == 'lga'){ $level = "sys_geo_codex.lgaid"; }
            elseif($geo_level == 'ward'){ $level = "sys_geo_codex.wardid"; }
            elseif($geo_level == 'dp'){ $level = "sys_geo_codex.dpid"; }
            #
            if ($seed == 0) {
                $where_condition = " WHERE $level = $geo_id ";
                $seed = 1;
            } else {
                $where_condition .= " AND $level = $geo_id ";
            }
        }
        #
        $query = "SELECT COUNT(*)
            FROM
            smc_icc_issue
            INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
            INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid
            $where_condition
            GROUP BY smc_icc_issue.cdd_lead_id, smc_icc_issue.issue_drug";
        return DbHelper::GetScalar($query);
    }
    public function IccCddBase($filter = []){
        #
        #   Filters
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';       #  period ID
        $geo_id = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_id'] : '';         #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_level'] : '';      #   Geo-Level
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_issue.periodid IN ($periodid) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_icc_issue.periodid IN ($periodid) ";
        }
        if ($geo_id && $geo_level) {
            $level = "";
            if($geo_level == 'lga'){ $level = "sys_geo_codex.lgaid"; }
            elseif($geo_level == 'ward'){ $level = "sys_geo_codex.wardid"; }
            elseif($geo_level == 'dp'){ $level = "sys_geo_codex.dpid"; }
            #
            if ($seed == 0) {
                $where_condition = " WHERE $level = $geo_id ";
                $seed = 1;
            } else {
                $where_condition .= " AND $level = $geo_id ";
            }
        }
        #
        $query = "SELECT
            sys_geo_codex.geo_string AS location,
            smc_period.title AS visit,
            usr_login.loginid AS `receiver login id`,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `receiver`,
            usr_identity.phone AS `receiver phone` ,
            smc_icc_issue.issue_drug AS `issue drug`,
            Sum(smc_icc_issue.drug_qty) AS `total issued`,
            Sum(smc_icc_reconcile.remaining) AS `total unused`,
            Sum(smc_icc_reconcile.`full`) AS `total full used`,
            Sum(smc_icc_reconcile.partial) AS `total partial used`,
            Sum(smc_icc_reconcile.wasted) AS `wasted`
            FROM
            smc_icc_issue
            INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
            INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid
            INNER JOIN usr_login ON smc_icc_issue.cdd_lead_id = usr_login.userid
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN smc_period ON smc_icc_issue.periodid = smc_period.periodid
            $where_condition
            GROUP BY smc_icc_issue.cdd_lead_id, smc_icc_issue.issue_drug
            ORDER BY visit, sys_geo_codex.geo_string";
        #   return payload
        return $this->TransportDataJson($query, "ICC by CDD");
    }
    //
    public function CountIccDetail($filter = []){
        #
        #   Filters
        
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';       #  period ID
        $geo_id = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_id'] : '';         #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_level'] : '';      #   Geo-Level
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #
        
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_issue.periodid IN ($periodid) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_icc_issue.periodid IN ($periodid) ";
        }
        if ($geo_id && $geo_level) {
            $level = "";
            if($geo_level == 'lga'){ $level = "sys_geo_codex.lgaid"; }
            elseif($geo_level == 'ward'){ $level = "sys_geo_codex.wardid"; }
            elseif($geo_level == 'dp'){ $level = "sys_geo_codex.dpid"; }
            #
            if ($seed == 0) {
                $where_condition = " WHERE $level = $geo_id ";
                $seed = 1;
            } else {
                $where_condition .= " AND $level = $geo_id ";
            }
        }
        #
        $query = "SELECT
        COUNT(*)
        FROM
        smc_icc_issue
        INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
        INNER JOIN usr_login ON smc_icc_issue.issuer_id = usr_login.userid
        INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition
        GROUP BY smc_icc_issue.cdd_lead_id, smc_icc_issue.issue_drug";
        #   return payload
        return DbHelper::GetScalar($query);
    }
    public function IccDetail($filter = []){
        #
        #   Filters
        
        $periodid = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['periodid'] : '';       #  period ID
        $geo_id = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_id'] : '';         #   Geo_level_id
        $geo_level = (is_array($filter) && array_key_exists('periodid', $filter)) ? $filter['geo_level'] : '';      #   Geo-Level
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        
        if ($periodid) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_issue.periodid IN ($periodid) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_icc_issue.periodid IN ($periodid) ";
        }
        if ($geo_id && $geo_level) {
            $level = "";
            if($geo_level == 'lga'){ $level = "sys_geo_codex.lgaid"; }
            elseif($geo_level == 'ward'){ $level = "sys_geo_codex.wardid"; }
            elseif($geo_level == 'dp'){ $level = "sys_geo_codex.dpid"; }
            #
            if ($seed == 0) {
                $where_condition = " WHERE $level = $geo_id ";
                $seed = 1;
            } else {
                $where_condition .= " AND $level = $geo_id ";
            }
        }
        #
        $query = "SELECT
        sys_geo_codex.geo_string AS `location`,
        smc_icc_issue.issue_id AS `issue id`,
        smc_icc_issue.issue_drug `issue drug`,
        smc_icc_issue.drug_qty AS `issued qty`,
        COALESCE(smc_icc_reconcile.remaining, smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial)) AS `unused qty`,
        smc_icc_reconcile.`full` AS `used full`,
        smc_icc_reconcile.partial AS `used partial`,
        smc_icc_reconcile.wasted AS `used wasted`,
        DATE(smc_icc_issue.issue_date) AS `issued date`,
        DATE(smc_icc_reconcile.reconcile_date) AS `reconciled date`,
        if(smc_icc_reconcile.is_reconcile_ready, 'Yes','No') AS push_reconcile,
        if(smc_icc_reconcile.returned, 'Yas','No') AS has_reconciled,
        usr_login.loginid AS `issuer loginid`,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS `issuer name`,
        r.loginid AS `receiver loginid`,
        r.fullname AS `receiver name`
        FROM
        smc_icc_issue
        INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
        INNER JOIN usr_login ON smc_icc_issue.issuer_id = usr_login.userid
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        LEFT JOIN 
        (SELECT
        usr_login.userid,
        usr_login.loginid AS loginid,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) r ON smc_icc_reconcile.receiver_id = r.userid
        INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition
        GROUP BY smc_icc_issue.cdd_lead_id, smc_icc_issue.issue_drug
        ORDER BY visit, sys_geo_codex.geo_string";
        #   return payload
        return $this->TransportDataJson($query, "ICC Details");
    }
}
    
?>