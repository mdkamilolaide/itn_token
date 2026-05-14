<?php

$columns = array('hhid', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'collected_date');
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
$where_condition = " WHERE hhm_distribution.hhid IS NULL  ";
$seed = 1;
#
#
#   Filter column
#   Filtered by 
$hh_phone = CleanData('pph');
$etoken_serial = CleanData('ets');
$etoken_pin = CleanData('etp');
$mobilization_date = CleanData('mdt');
$geo_level = CleanData('glv');
$geo_level_id = CleanData('gid');
#
if ($hh_phone) {
    if ($seed == 0) {
        $where_condition = " WHERE hhm_mobilization.hoh_phone LIKE '%$hh_phone%' ";
        $seed = 1;
    } else
        $where_condition .= " AND hhm_mobilization.hoh_phone LIKE '%$hh_phone%' ";
}
if ($etoken_serial) {
    if ($seed == 0) {
        $where_condition = " WHERE hhm_mobilization.etoken_serial LIKE '%$etoken_serial%' ";
        $seed = 1;
    } else
        $where_condition .= " AND hhm_mobilization.etoken_serial LIKE '%$etoken_serial%' ";
}
if ($etoken_pin) {
    if ($seed == 0) {
        $where_condition = " WHERE hhm_mobilization.etoken_pin LIKE '%$etoken_pin%' ";
        $seed = 1;
    } else
        $where_condition .= " AND hhm_mobilization.etoken_pin LIKE '%$etoken_pin%' ";
}
if ($mobilization_date) {
    if ($seed == 0) {
        $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$mobilization_date') ";
        $seed = 1;
    } else
        $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$mobilization_date') ";
}
if ($geo_level && $geo_level_id) {
    if ($seed == 0) {
        $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
        $seed = 1;
    } else
        $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
}
#
#  Query composition

$sql_query = "SELECT
    hhm_mobilization.hhid,
    hhm_mobilization.hoh_first,
    hhm_mobilization.hoh_last,
    hhm_mobilization.hoh_phone,
    hhm_mobilization.hoh_gender,
    hhm_mobilization.family_size,
    hhm_mobilization.hod_mother,
    hhm_mobilization.allocated_net,
    hhm_mobilization.sleeping_space,
    hhm_mobilization.adult_female,
    hhm_mobilization.adult_male,
    hhm_mobilization.children,
    hhm_mobilization.etoken_serial,
    hhm_mobilization.etoken_pin,
    sys_geo_codex.geo_string,
    hhm_mobilization.collected_date
    FROM
    hhm_mobilization
    LEFT JOIN hhm_distribution ON hhm_mobilization.hhid = hhm_distribution.hhid
    INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";
# count
$sql_count = "SELECT
    COUNT(*)
    FROM
    hhm_mobilization
    LEFT JOIN hhm_distribution ON hhm_mobilization.hhid = hhm_distribution.hhid
    INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
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
