<?php

$columns = array('hhid', 'geo_string', 'geo_level', 'geo_name', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'collected_date', 'mobilizer', 'mobilizer_loginid', 'mobilizer_userid');
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
$where_condition = "   ";
$seed = 0;
#
#
#   Filter column
#   Filter by mobilizer's login id
$loginid = CleanData('lgid');
#   Filtered by mobilized date
$mob_date = CleanData('mdt');
#   Filtered by Geo-Level
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
#
if ($loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE a.loginid = '$loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND a.loginid = '$loginid' ";
}
if ($mob_date) {
    if ($seed == 0) {
        $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$mob_date') ";
        $seed = 1;
    } else
        $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$mob_date') ";
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
    sys_geo_codex.geo_string,
    sys_geo_codex.geo_level,
    sys_geo_codex.title AS geo_name,
    hhm_mobilization.hoh_first,
    hhm_mobilization.hoh_last,
    hhm_mobilization.hoh_phone,
    hhm_mobilization.hoh_gender,
    hhm_mobilization.family_size,
    hhm_mobilization.allocated_net,
    hhm_mobilization.location_description,
    hhm_mobilization.collected_date,
    a.fullname AS mobilizer,
    a.loginid AS mobilizer_loginid,
    a.userid AS mobilizer_userid,
    '' AS pick
    FROM
    hhm_mobilization
    INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.geo_level_id AND sys_geo_codex.geo_value = 10
    LEFT JOIN
    (SELECT
    usr_login.userid,
    usr_login.loginid,
    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";
# count
$sql_count = "SELECT
    COUNT(*)
    FROM
    hhm_mobilization
    LEFT JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.geo_level_id AND sys_geo_codex.geo_value = 10
    LEFT JOIN
    (SELECT
    usr_login.userid,
    usr_login.loginid,
    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
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
