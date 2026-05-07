<?php

namespace Smc;

use DbHelper;

#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class DrugAdmin
{
    private $db;
    #
    public function __construct()
    {
        $this->db = GetMysqlDatabase();
    }
    # [periodid, uid, dpid, beneficiary_id, is_eligible, not_eligible_reason, is_refer, drug, drug_qty, redose_count, redose_reason, user_id, longitude, latitude, device_serial,app_version,collected_date,issue_id,redose_issue_id]
    public function BulkSave($bulk_data)
    {
        if (count($bulk_data) > 0 && is_array($bulk_data)) {
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $this->db->beginTransaction();
            foreach ($bulk_data as $row) {
                $device_serial = isset($row['device_serial']) ? $row['device_serial'] : '';
                $app_version = isset($row['app_version']) ? $row['app_version'] : '';
                $issue_id = isset($row['issue_id']) ? $row['issue_id'] : 0;
                $redose_issue_id = isset($row['redose_issue_id']) ? $row['redose_issue_id'] : 0;
                #   
                $query = "INSERT `smc_drug_administration` (`periodid`,`uid`,`dpid`, `beneficiary_id`,`is_eligible`,`not_eligible_reason`,`is_refer`,`issue_id`,`redose_issue_id`,`drug`,`drug_qty`,`redose_count`,`redose_reason`,`user_id`,`longitude`,`latitude`,`device_serial`,`app_version`,`collected_date`,`updated`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $this->db->executeTransaction($query, array($row['periodid'], $row['uid'], $row['dpid'], $row['beneficiary_id'], $row['is_eligible'], $row['not_eligible_reason'], $row['is_refer'], $issue_id, $redose_issue_id, $row['drug'], $row['drug_qty'], $row['redose_count'], $row['redose_reason'], $row['user_id'], $row['longitude'], $row['latitude'], $device_serial, $app_version, $row['collected_date'], $date));
                $retarray[] = $row['beneficiary_id'];
            }
            $this->db->commitTransaction();
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if (strlen($error_message) > 0) {
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nDrug administration DB error message: $error_message\r\nData:" . json_encode($bulk_data) . "\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            # Completed
            return $retarray;
        } else {
            return false;
        }
    }
    # [uid,redose_count,redose_reason,redose_issue_id]
    public function BulkRedose($bulk_data)
    {
        if (count($bulk_data) > 0 && is_array($bulk_data)) {
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $this->db->beginTransaction();
            foreach ($bulk_data as $row) {
                $redose_issue_id = isset($row['redose_issue_id']) ? $row['redose_issue_id'] : 0;
                $query = "UPDATE `smc_drug_administration` SET `redose_count`=?,`redose_reason`=?,`redose_issue_id`=?, `updated`=? WHERE `uid`=?";
                $this->db->executeTransaction($query, array($row['redose_count'], $row['redose_reason'],$redose_issue_id, $date, $row['uid']));
                $retarray[] = $row['uid'];
            }
            $this->db->commitTransaction();
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if (strlen($error_message) > 0) {
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nRedose DB error message: $error_message\r\nData:" . json_encode($bulk_data) . "\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            # Completed
            return $retarray;
        } else {
            return false;
        }
    }
    #
    #
    #   [lgaid,lga,period,complete,incomplete]
    public function GetCohortLgaLevel()
    {
        return $this->db->DataTable("SELECT
        sgc.lgaid AS id,
        lga.Fullname AS title,
        ( SELECT val FROM smc_process_setting WHERE pointer = 'period_count' ) AS `period`,
        SUM( CASE WHEN da_status.STATUS = 'Complete' THEN 1 ELSE 0 END ) AS complete,
        SUM( CASE WHEN da_status.STATUS = 'Incomplete' THEN 1 ELSE 0 END ) AS incomplete,
        SUM(da_status.ineligible) AS ineligible 
        FROM
        sys_geo_codex sgc
        INNER JOIN ms_geo_lga lga ON sgc.lgaid = lga.LgaId 
        AND sgc.geo_level = 'dp'
        INNER JOIN (
        SELECT
            sda.dpid,
            sc.`name`,
            sc.beneficiary_id,
            SUM(sda.is_eligible) AS total,
            SUM(CASE WHEN sda.is_eligible = 0 THEN 1 ELSE 0 END) AS ineligible,
            ( SELECT val FROM smc_process_setting WHERE pointer = 'period_count' ) AS `period`,
        CASE
                WHEN SUM(sda.is_eligible) = ( SELECT val FROM smc_process_setting WHERE pointer = 'period_count' ) THEN
                'Complete' ELSE 'Incomplete' 
        END AS STATUS 
        FROM
            smc_drug_administration sda
            INNER JOIN smc_child sc ON sda.beneficiary_id = sc.beneficiary_id 
        GROUP BY
            sda.beneficiary_id 
        ) da_status ON sgc.dpid = da_status.dpid 
        GROUP BY
        sgc.lgaid");
    }
    #   [wardid,ward,period,complete,incomplete]
    public function GetCohortWardLevel($lgaid)
    {
        return $this->db->DataTable("SELECT
        sgc.wardid AS id,
        w.ward AS title,
        (SELECT val FROM smc_process_setting WHERE pointer = 'period_count') AS `period`,
        SUM( CASE WHEN da_status.STATUS = 'Complete' THEN 1 ELSE 0 END ) AS complete,
        SUM( CASE WHEN da_status.STATUS = 'Incomplete' THEN 1 ELSE 0 END ) AS incomplete,
        SUM(da_status.ineligible) AS ineligible 
        FROM
        sys_geo_codex sgc
        INNER JOIN ms_geo_ward w ON sgc.wardid = w.wardid 
        AND sgc.geo_level = 'dp'
        INNER JOIN (
        SELECT
            sda.dpid,
            sc.`name`,
            sc.beneficiary_id,
            SUM(sda.is_eligible) AS total,
            SUM(CASE WHEN sda.is_eligible = 0 THEN 1 ELSE 0 END) AS ineligible,
            (SELECT val FROM smc_process_setting WHERE pointer = 'period_count') AS `period`,
        CASE
                WHEN SUM(sda.is_eligible) = ( SELECT val FROM smc_process_setting WHERE pointer = 'period_count' ) THEN
                'Complete' ELSE 'Incomplete' 
        END AS STATUS 
        FROM
            smc_drug_administration sda
            INNER JOIN smc_child sc ON sda.beneficiary_id = sc.beneficiary_id 
        GROUP BY
            sda.beneficiary_id 
        ) da_status ON sgc.dpid = da_status.dpid 
        WHERE
        sgc.lgaid = $lgaid
        GROUP BY
        sgc.wardid;");
    }
    # [dpid,dp,period,complete,incomplete]
    public function GetCohortDpLevel($wardid)
    {
        return $this->db->DataTable("SELECT
        sgc.dpid AS id,
        dp.dp AS title,
        (SELECT val FROM smc_process_setting WHERE pointer = 'period_count') AS `period`,
        SUM( CASE WHEN da_status.STATUS = 'Complete' THEN 1 ELSE 0 END ) AS complete,
        SUM( CASE WHEN da_status.STATUS = 'Incomplete' THEN 1 ELSE 0 END ) AS incomplete,
        SUM(da_status.ineligible) AS ineligible 
        FROM
        sys_geo_codex sgc
        INNER JOIN ms_geo_dp dp ON sgc.dpid = dp.dpid 
        AND sgc.geo_level = 'dp'
        INNER JOIN (
        SELECT
            sda.dpid,
            sc.`name`,
            sc.beneficiary_id,
            SUM(sda.is_eligible) AS total,
            SUM(CASE WHEN sda.is_eligible = 0 THEN 1 ELSE 0 END) AS ineligible,
            ( SELECT val FROM smc_process_setting WHERE pointer = 'period_count' ) AS `period`,
        CASE
                WHEN SUM(sda.is_eligible) = ( SELECT val FROM smc_process_setting WHERE pointer = 'period_count' ) THEN
                'Complete' ELSE 'Incomplete' 
        END AS STATUS 
        FROM
            smc_drug_administration sda
            INNER JOIN smc_child sc ON sda.beneficiary_id = sc.beneficiary_id 
        GROUP BY
            sda.beneficiary_id 
        ) da_status ON sgc.dpid = da_status.dpid 
        GROUP BY
        sgc.dpid");
    }
    #   [dpid,name,beneficiary_id,total,period,status]
    public function GetCohortChildLevel($dpid)
    {
        return $this->db->DataTable("SELECT
        sda.dpid,
        sc.`name`,
        sc.beneficiary_id,
        SUM(sda.is_eligible) AS total,
        (SELECT val FROM smc_process_setting WHERE pointer = 'period_count') AS `period`,
        CASE
            WHEN SUM(sda.is_eligible) = ( SELECT val FROM smc_process_setting WHERE pointer = 'period_count' ) THEN
            'Complete' ELSE 'Incomplete' 
        END AS `status` 
        FROM
        smc_drug_administration sda
        INNER JOIN smc_child sc ON sda.beneficiary_id = sc.beneficiary_id 
        WHERE
        sda.dpid = $dpid 
        GROUP BY
        sda.beneficiary_id");
    }
    #   [adm_id,period,beneficiary_id,drug,total,redose_count,redose_reason,collected_date]
    public function GetCohortChildDetails($beneficiary_id)
    {
        return $this->db->DataTable("SELECT
            smc_drug_administration.adm_id,
            smc_period.title AS period,
            smc_drug_administration.beneficiary_id,
            smc_drug_administration.drug,
            smc_drug_administration.redose_count,
            smc_drug_administration.redose_reason,
            CASE WHEN smc_drug_administration.is_eligible = 0 THEN 'Not Eligible' ELSE 'Eligible' END AS eligibility,
            smc_drug_administration.not_eligible_reason,
            smc_drug_administration.collected_date
            FROM
            smc_drug_administration
            INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
            WHERE
            smc_drug_administration.beneficiary_id = '$beneficiary_id'");
    }
    #
    #Get list of referrers
    public function GetReferrerList($dpid, $periodid)
    {
        return DbHelper::Table("SELECT
            smc_drug_administration.adm_id,
            smc_drug_administration.dpid,
            smc_drug_administration.periodid,
            smc_child.`name`,
            smc_drug_administration.beneficiary_id,
            smc_child.gender,
            smc_child.dob,
            smc_drug_administration.collected_date,
            smc_drug_administration.not_eligible_reason,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS referrer_cdd,
            usr_login.loginid AS referrer_cdd_loginid
            FROM
            smc_drug_administration
            INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
            INNER JOIN usr_identity ON smc_drug_administration.user_id = usr_identity.userid
            INNER JOIN usr_login ON usr_identity.userid = usr_login.userid
            WHERE
            smc_drug_administration.is_refer
            AND smc_drug_administration.dpid = $dpid
            AND smc_drug_administration.periodid = $periodid");
    }
    #
    #
    public function GetReferralCount($periodid="",$geo_id="",$geo_level="",$attended=""){
        #  Where condition
        $where_condition = " WHERE smc_drug_administration.is_refer = 1 ";
        $seed = 1;
        #
        #   Filters
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
        return DbHelper::Table("SELECT 
            COUNT(smc_drug_administration.adm_id) AS referrals,
            SUM(CASE WHEN smc_referer_record.ref_id IS NOT NULL THEN 1 ELSE 0 END) AS attended,
            COUNT(DISTINCT smc_drug_administration.periodid) AS period
            FROM smc_drug_administration
            LEFT JOIN smc_referer_record ON smc_drug_administration.adm_id = smc_referer_record.adm_id
            INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
            INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            $where_condition");
    }
}
