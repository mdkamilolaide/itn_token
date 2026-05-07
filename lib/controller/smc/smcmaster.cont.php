<?php
namespace Smc;
use DbHelper;
#   Collect master data through this
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class SmcMaster {
    private $db;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    #   Get commodity
    #   ['commodity_id'.'name','description','com_value']
    public function GetCommodity(){
        return DbHelper::Table("SELECT
        smc_commodity.product_id,
        smc_commodity.product_code,
        smc_commodity.`name`,
        smc_commodity.description,
        smc_commodity.min_age,
        smc_commodity.max_age,
        smc_commodity.extension_age
        FROM
        smc_commodity");
    }
    #   ['reason','category']
    public function GetReasons(){
        return DbHelper::Table("SELECT
        sms_reasons.reason,
        sms_reasons.category
        FROM
        sms_reasons");
    }
    #   ['periodid','title','start_date','end_date']
    public function GetPeriodActive(){
        return DbHelper::Table("SELECT
        smc_period.periodid,
        smc_period.title,
        smc_period.start_date,
        smc_period.end_date
        FROM
        smc_period
        WHERE
        smc_period.active = 1");
    }
    #   ['hhid','comid','hh_token','hoh_name','hoh_phone']
    public function GetMasterHousehold($dp){
        return DbHelper::Table("SELECT
        smc_child_household.hhid,
        smc_child_household.dpid,
        smc_child_household.hh_token,
        smc_child_household.hoh_name,
        smc_child_household.hoh_phone
        FROM
        sys_geo_codex
        INNER JOIN smc_child_household ON smc_child_household.dpid = sys_geo_codex.geo_level_id AND sys_geo_codex.geo_level = 'dp'
        WHERE sys_geo_codex.dpid = $dp");
    }
    #   ['child_id','hh_token','beneficiary_id','comid','name','gender','dob']
    public function GetMasterChild($dp){
        return DbHelper::Table("SELECT
        smc_child.child_id,
        smc_child.hh_token,
        smc_child.beneficiary_id,
        smc_child.dpid,
        smc_child.`name`,
        smc_child.gender,
        smc_child.dob, 
        a.periodid AS last_visit_periodid,
        a.period AS last_visit_period,
        a.collected_date AS last_visit_date
        FROM
        sys_geo_codex
        INNER JOIN smc_child ON sys_geo_codex.geo_level_id = smc_child.dpid AND sys_geo_codex.geo_level = 'dp' 
        LEFT JOIN(SELECT
        s1.beneficiary_id,
        s1.periodid,
        smc_period.title AS `period`,
        s1.collected_date
        FROM
        smc_drug_administration s1
        INNER JOIN smc_period ON s1.periodid = smc_period.periodid
        INNER JOIN ( SELECT beneficiary_id, MAX( collected_date ) AS max_collected_date FROM smc_drug_administration GROUP BY beneficiary_id ) s2 ON s1.beneficiary_id = s2.beneficiary_id 
        AND s1.collected_date = s2.max_collected_date AND s1.dpid = $dp) a ON smc_child.beneficiary_id = a.beneficiary_id
        WHERE
        sys_geo_codex.dpid = $dp");
    }
    # 
    public function GetCddLead($dpid){
        return DbHelper::Table("select `usr_login`.`userid` AS `userid`, `usr_login`.`loginid` AS `loginid`,`usr_identity`.`first` AS `first`,`usr_identity`.`middle` AS `middle`,`usr_identity`.`last` AS `last`,`usr_identity`.`gender` AS `gender`,`usr_identity`.`phone` AS `phone` from (`usr_login` join `usr_identity` on(`usr_login`.`userid` = `usr_identity`.`userid`)) where `usr_login`.`geo_level` = 'dp' and `usr_login`.`geo_level_id` = $dpid and `usr_login`.`roleid` = 54");
    }
    #
    public function GetAllPeriods(){
        return DbHelper::Table("SELECT
        smc_period.periodid,
        smc_period.title AS period,
        smc_period.active
        FROM
        smc_period
        ORDER BY
        smc_period.start_date ASC");
    }
    #
    public function GetCmsLocations(){
        return DbHelper::Table("SELECT
        smc_cms_location.location_id,
        smc_cms_location.cms_name,
        smc_cms_location.`level`,
        smc_cms_location.address,
        smc_cms_location.poc,
        smc_cms_location.poc_phone,
        'CMS' AS `location_type`,
        smc_cms_location.created
        FROM
        smc_cms_location");
    }
    #
    public function GetFacilityLocations($lgaid){
        return DbHelper::Table("SELECT
        sys_geo_codex.dpid,
        ms_geo_dp.dp,
        'Facility' AS `location_type`,
        sys_geo_codex.geo_string
        FROM
        sys_geo_codex
        INNER JOIN ms_geo_dp ON sys_geo_codex.dpid = ms_geo_dp.dpid AND sys_geo_codex.geo_level = 'dp'
        WHERE sys_geo_codex.lgaid = $lgaid");
    }
    #
    public function GetTransporter(){
        return DbHelper::Table("SELECT
            smc_logistics_transporter.transporter_id,
            smc_logistics_transporter.transporter,
            smc_logistics_transporter.address,
            smc_logistics_transporter.poc,
            smc_logistics_transporter.poc_phone
            FROM
            smc_logistics_transporter
            ORDER BY
            smc_logistics_transporter.transporter ASC");
    }
    #
    public function GetConveyors(){
        return DbHelper::Table("SELECT
                usr_login.userid,
                usr_login.loginid,
                CONCAT_WS(' ',usr_identity.`first`,usr_identity.last) AS fullname,
                usr_identity.phone,
                usr_role.title AS role
                FROM
                usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
                INNER JOIN usr_role ON usr_login.roleid = usr_role.roleid
                WHERE
                usr_login.roleid = 55");
    }
}
?>