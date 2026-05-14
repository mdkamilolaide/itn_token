<?php

/*
     *      e-Netcard allocation forward
     */
$columns = array('atid', 'transfer_by', 'total', 'a_type', 'origin', 'destination', 'destination_userid', 'mobilizer', 'created');
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
$where_condition = "  WHERE  nc_netcard_allocation.a_type = 'forward'  ";
$seed = 1;
#
#   Filter column is empty for now
#   Filter column 
$requester_loginid = CleanData("rid");
$mobilizer_loginid = CleanData("mid");
$requested_date = CleanData("rda");
#
if ($requester_loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE b.loginid = '$requester_loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND b.loginid = '$requester_loginid' ";
}
if ($mobilizer_loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE a.loginid = '$mobilizer_loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND a.loginid = '$mobilizer_loginid' ";
}
if ($requested_date) {
    if ($seed == 0) {
        $where_condition = " WHERE DATE(nc_netcard_allocation.created) = DATE('$requested_date') ";
        $seed = 1;
    } else
        $where_condition .= " AND DATE(nc_netcard_allocation.created) = DATE('$requested_date') ";
}
#
#  Query composition
$sql_query = "SELECT
    nc_netcard_allocation.atid,
    b.fullname AS transfer_by,
    nc_netcard_allocation.total,
    nc_netcard_allocation.a_type,
    (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE nc_netcard_allocation.origin = sys_geo_codex.geo_level AND nc_netcard_allocation.origin_id = sys_geo_codex.geo_level_id) AS origin,
    nc_netcard_allocation.destination,
    nc_netcard_allocation.destination_userid,
    CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
    nc_netcard_allocation.created,
    '' AS pick
    FROM
    nc_netcard_allocation
    LEFT JOIN
    (SELECT
    usr_login.userid,
    usr_login.loginid,
    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) a ON nc_netcard_allocation.destination_userid = a.userid
    LEFT JOIN
    (SELECT
    usr_login.userid,
    usr_login.loginid,
    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) b ON nc_netcard_allocation.userid = b.userid
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    nc_netcard_allocation
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
