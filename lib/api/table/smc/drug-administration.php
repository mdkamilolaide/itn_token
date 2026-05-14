<?php

$columns = array('geo_string', 'period', 'name', 'beneficiary_id', 'dob', 'drug', 'redose', 'redose_reason', 'eligibility', 'not_eligible_reason', 'collected_date');
#  Require variable
$perpage = intval($_REQUEST['length']);
$currentPage = $_REQUEST['draw'];
$sortColumn = $_REQUEST['order_column'];
$orderDir = $_REQUEST['order_dir']; // asc | desc
$orderField = $columns[$_REQUEST['order_column']];
$limitStart = $_REQUEST['start'];
$date_format = $GLOBALS["conf_db_date_format"];
$dateMed_format = $GLOBALS["conf_db_date_medium_format"];
#
#  Where condition
$where_condition = "  ";
$seed = 0;
#
#   Filters
$periodid = CleanData("pid");     #  period ID
$is_eligible = CleanData("ise");    #  The child is eligible must be yes
$is_redose = CleanData("isr");    #  Redose must be yes
$reg_date = CleanData("rda");    #   Registration date
$geo_id = CleanData("gid");    #   Geo_level_id
$geo_level = CleanData("glv");    #   Geo-Level
$beneficiary_id = CleanData("bid");    #   Beneficiary ID

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
#  Query composition
$sql_query = "SELECT
    sys_geo_codex.geo_string,
    smc_period.title AS period,
    smc_child.`name`,
    smc_child.beneficiary_id,
    smc_child.dob,
    smc_drug_administration.drug,
    if(smc_drug_administration.redose_count,'Redosed','NA') AS redose,
    smc_drug_administration.redose_reason,
    smc_drug_administration.collected_date,
    if(smc_drug_administration.is_eligible = 0,'Not Eligible','NA') AS eligibility,
    smc_drug_administration.not_eligible_reason
    FROM
    smc_drug_administration
    INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
    INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
    INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    smc_drug_administration
    INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
    INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
    INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
    $where_condition";
#  Access Database
$c = new MysqlCentry();
$data = $c->Table($sql_query);
$count = $c->Single($sql_count);
#
$json_data = array(
    "draw" => $currentPage,
    "recordsTotal" => $count,
    "recordsFiltered" => $count,
    "data" => $data
);
echo json_encode($json_data);
