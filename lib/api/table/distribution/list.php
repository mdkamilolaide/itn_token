<?php

$columns = array('dis_id', 'geo_level', 'dpid', 'geo_string', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'etoken_serial', 'collected_nets', 'is_gs_one_record', 'recorder_name', 'recorder_loginid', 'collected_date', 'created');
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
$where_condition = " WHERE sys_geo_codex.geo_level = 'dp'  ";
$seed = 1;
#
#
#   Filter column
#   Filtered by distribution date
$dis_date = CleanData('dst');
#   Filtered by Geo-Level
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
#
if ($dis_date) {
    if ($seed == 0) {
        $where_condition = " WHERE DATE(hhm_distribution.collected_date) = DATE('$dis_date') ";
        $seed = 1;
    } else
        $where_condition .= " AND DATE(hhm_distribution.collected_date) = DATE('$dis_date') ";
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
    hhm_distribution.dis_id,
    sys_geo_codex.geo_level,
    sys_geo_codex.dpid,
    sys_geo_codex.geo_string,
    hhm_mobilization.hoh_first,
    hhm_mobilization.hoh_last,
    hhm_mobilization.hoh_phone,
    hhm_mobilization.hoh_gender,
    hhm_mobilization.family_size,
    hhm_mobilization.allocated_net,
    hhm_mobilization.location_description,
    hhm_mobilization.etoken_serial,
    hhm_distribution.collected_nets,
    IF
        ( hhm_distribution.is_gs_net, 'Yes', 'No' ) AS is_gs_one_record,
    a.fullname AS recorder_name,
    a.loginid AS recorder_loginid,
    b.fullname AS distributor_name,
    b.loginid AS distributor_loginid,
    hhm_distribution.collected_date,
    hhm_distribution.created,
    hhm_mobilization.longitude,
    hhm_mobilization.Latitude AS latitude,
    '' AS pick
    FROM
    hhm_distribution
    INNER JOIN nc_token ON hhm_distribution.etoken_id = nc_token.tokenid
    INNER JOIN hhm_mobilization ON hhm_distribution.hhid = hhm_mobilization.hhid
    LEFT JOIN sys_geo_codex ON hhm_distribution.dp_id = sys_geo_codex.dpid
    LEFT JOIN (
        SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
        FROM
            usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
        ) AS a ON hhm_distribution.recorder_id = a.userid
        LEFT JOIN (
        SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
        FROM
            usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
        ) AS b ON hhm_distribution.distributor_id = b.userid        
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";
# count
$sql_count = "SELECT
    COUNT(*)
    FROM
    hhm_distribution
    INNER JOIN nc_token ON hhm_distribution.etoken_id = nc_token.tokenid
    INNER JOIN hhm_mobilization ON hhm_distribution.hhid = hhm_mobilization.hhid
    LEFT JOIN sys_geo_codex ON hhm_distribution.dp_id = sys_geo_codex.dpid
    LEFT JOIN (SELECT
    usr_login.userid,
    usr_login.loginid,
    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_distribution.recorder_id = a.userid
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
