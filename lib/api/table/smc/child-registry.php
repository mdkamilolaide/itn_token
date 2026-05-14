<?php

$columns = array('hh_token', 'hoh_name', 'hoh_phone', 'beneficiary_id', 'name', 'gender', 'dob', 'created', 'updated', 'geo_string');
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
$hh_token = CleanData("hht");     #  head od household token or ID
$hh_name = CleanData("hhn");    #   head od household name
$hh_phone = CleanData("hhp");    #   head od household phone
$child_id = CleanData("chi");     #  Beneficiary ID
$child_name = CleanData("chn");    #   Beneficiary name
$reg_date = CleanData("rda");    #   Registration date
$geo_id = CleanData("gid");    #   Geo_level_id
$geo_level = CleanData("glv");    #   Geo-Level

if ($hh_token) {
    if ($seed == 0) {
        $where_condition = " WHERE smc_child_household.hh_token = '$hh_token'  ";
        $seed = 1;
    } else
        $where_condition .= " AND smc_child_household.hh_token = '$hh_token' ";
}
if ($hh_name) {
    if ($seed == 0) {
        $where_condition = " WHERE smc_child_household.hoh_name LIKE '%$hh_name%' ";
        $seed = 1;
    } else
        $where_condition .= " AND smc_child_household.hoh_name LIKE '%$hh_name%' ";
}
if ($hh_phone) {
    if ($seed == 0) {
        $where_condition = " WHERE smc_child_household.hoh_phone LIKE '%$hh_phone%' ";
        $seed = 1;
    } else
        $where_condition .= " AND smc_child_household.hoh_phone LIKE '%$hh_phone%' ";
}
if ($child_id) {
    if ($seed == 0) {
        $where_condition = " WHERE smc_child.beneficiary_id LIKE '%$child_id%' ";
        $seed = 1;
    } else
        $where_condition .= " AND smc_child.beneficiary_id LIKE '%$child_id%' ";
}
if ($child_name) {
    if ($seed == 0) {
        $where_condition = " WHERE smc_child.`name` LIKE '%$child_name%' ";
        $seed = 1;
    } else
        $where_condition .= " AND smc_child.`name` LIKE '%$child_name%' ";
}
if ($reg_date) {
    if ($seed == 0) {
        $where_condition = " WHERE DATE('$reg_date') = DATE(smc_child.created) ";
        $seed = 1;
    } else
        $where_condition .= " AND DATE('$reg_date') = DATE(smc_child.created) ";
}
if ($geo_id && $geo_level) {
    if ($seed == 0) {
        $where_condition = " WHERE sys_geo_codex.geo_level = 'dp' AND sys_geo_codex.geo_level_id = $geo_id ";
        $seed = 1;
    } else
        $where_condition .= " AND sys_geo_codex.geo_level = 'dp' AND sys_geo_codex.geo_level_id = $geo_id ";
}
#
#  Query composition
$sql_query = "SELECT
    smc_child_household.hh_token,
    smc_child_household.hoh_name,
    smc_child_household.hoh_phone,
    smc_child.beneficiary_id,
    smc_child.`name`,
    smc_child.gender,
    smc_child.dob,
    smc_child.created,
    smc_child.updated,
    sys_geo_codex.geo_string
    FROM
    smc_child_household
    INNER JOIN smc_child ON smc_child_household.hh_token = smc_child.hh_token
    INNER JOIN sys_geo_codex ON smc_child.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    smc_child_household
    INNER JOIN smc_child ON smc_child_household.hh_token = smc_child.hh_token
    INNER JOIN sys_geo_codex ON smc_child.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
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
