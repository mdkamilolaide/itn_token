<?php

$columns = array('id', 'loginid', 'fullname', 'phone', 'device_serial', 'amount', 'geo_string', 'amount', 'created');
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
#   Filter column is empty for now
#   Filter column 
$loginid = CleanData("lid");
$device_serial = CleanData("dse");
$geo_level = CleanData("lid");
$geo_level_id = CleanData("lid");
#
if ($loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_login.loginid = '$loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.loginid = '$loginid' ";
}
if ($device_serial) {
    if ($seed == 0) {
        $where_condition = " WHERE nc_netcard_unused_pushed.device_serial = '$device_serial' ";
        $seed = 1;
    } else
        $where_condition .= " AND nc_netcard_unused_pushed.device_serial = '$device_serial' ";
}
if ($geo_level && $geo_level_id) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_login.geo_level = '$geo_level' AND  usr_login.geo_level_id = '$geo_level_id' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.geo_level = '$geo_level' AND  usr_login.geo_level_id = '$geo_level_id' ";
}
#
#  Query composition
$sql_query = "SELECT
    nc_netcard_unused_pushed.id,
    usr_login.loginid,
    CONCAT_WS(' ',usr_identity.`first`,usr_identity.gender) AS fullname,
    usr_identity.phone,
    nc_netcard_unused_pushed.device_serial,
    nc_netcard_unused_pushed.amount,
    sys_geo_codex.geo_string,
    nc_netcard_unused_pushed.created
    FROM
    nc_netcard_unused_pushed
    INNER JOIN usr_login ON nc_netcard_unused_pushed.hhm_id = usr_login.userid
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
    INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id        
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";
# count
$sql_count = "SELECT
    COUNT(*)
    FROM
    nc_netcard_unused_pushed
    INNER JOIN usr_login ON nc_netcard_unused_pushed.hhm_id = usr_login.userid
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
    INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
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
