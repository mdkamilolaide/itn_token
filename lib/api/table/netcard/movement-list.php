<?php

/*
     *      Training participant list
     */
$columns = array('mtid', 'total', 'move_type', 'origin_level', 'origin', 'destination_level', 'destination', 'user_fullname', 'created');
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
#   Filter column
$move_type = CleanData('mt');
#
if ($move_type) {
    if ($seed == 0) {
        $where_condition = " WHERE nc_netcard_movement.move_type = '$move_type' ";
        $seed = 1;
    } else
        $where_condition .= " AND nc_netcard_movement.move_type = '$move_type' ";
}
#
#  Query composition
$sql_query = "SELECT
    nc_netcard_movement.mtid,
    nc_netcard_movement.total,
    nc_netcard_movement.move_type,
    nc_netcard_movement.origin_level,
    (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE nc_netcard_movement.origin_level = sys_geo_codex.geo_level AND nc_netcard_movement.origin_level_id = sys_geo_codex.geo_level_id) AS origin,
    nc_netcard_movement.destination_level,
    (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE nc_netcard_movement.destination_level = sys_geo_codex.geo_level AND nc_netcard_movement.destination_level_id = sys_geo_codex.geo_level_id) AS destination,
    CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS user_fullname,
    nc_netcard_movement.created,
    '' AS pick
    FROM
    nc_netcard_movement
    LEFT JOIN usr_identity ON nc_netcard_movement.userid = usr_identity.userid
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    nc_netcard_movement
    LEFT JOIN usr_identity ON nc_netcard_movement.userid = usr_identity.userid
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
