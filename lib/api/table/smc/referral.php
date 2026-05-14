<?php

$columns = array('adm_id', 'period', 'name', 'beneficiary_id', 'refer_type', 'attended', 'geo_string', 'referred_date', 'attended_date');
#  Require variable
$perpage = intval($_REQUEST['length']);
$currentPage = $_REQUEST['draw'];
$sortColumn = $_REQUEST['order_column'];
$orderDir = $_REQUEST['order_dir']; // asc | desc
$orderField = $columns[$_REQUEST['order_column']];
$limitStart = $_REQUEST['start'];
$date_format = $GLOBALS["conf_db_date_format"];
$dateMed_format = $GLOBALS["conf_db_date_medium_format"];
#  Where condition
$where_condition = " WHERE smc_drug_administration.is_refer = 1 ";
$seed = 1;
#
#   Filters
$periodid = CleanData("pid");       #  period ID
$geo_id = CleanData("gid");         #   Geo_level_id
$geo_level = CleanData("glv");      #   Geo-Level
$attended = CleanData('atd');       #   Attended filter
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
#  Query composition
$sql_query = "SELECT
    smc_drug_administration.adm_id,
    smc_period.title AS `period`,
    smc_child.`name`,
    smc_child.beneficiary_id,
    smc_drug_administration.not_eligible_reason AS refer_type,
    IF(smc_referer_record.ref_id IS NOT NULL,'Yes','No') AS attended,
    sys_geo_codex.geo_string,
    smc_drug_administration.collected_date AS referred_date,
    smc_referer_record.collected_date AS attended_date
    FROM
    smc_drug_administration
    LEFT JOIN smc_referer_record ON smc_drug_administration.adm_id = smc_referer_record.adm_id
    INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
    INNER JOIN smc_child ON smc_child.beneficiary_id = smc_drug_administration.beneficiary_id
    INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    smc_drug_administration
    LEFT JOIN smc_referer_record ON smc_drug_administration.adm_id = smc_referer_record.adm_id
    INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
    INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
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
