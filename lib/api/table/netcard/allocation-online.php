<?php

/*
     *      e-Netcard Allocation reverse order
     */
$columns = array('id', 'mobilizer', 'mobilizer_loginid', 'mobilizer_userid', 'requester', 'requester_loginid', 'requester_userid', 'amount', 'created', 'fulfilled_date');
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
        $where_condition = " WHERE DATE(nc_netcard_allocation_online.created) = DATE('$requested_date') ";
        $seed = 1;
    } else
        $where_condition .= " AND DATE(nc_netcard_allocation_online.created) = DATE('$requested_date') ";
}
#
#  Query composition
$sql_query = "SELECT
    nc_netcard_allocation_online.id,
    a.fullname AS mobilizer,
    a.loginid AS mobilizer_loginid,
    a.userid AS mobilizer_userid,
    b.fullname AS requester,
    b.loginid AS requester_loginid,
    b.userid AS requester_userid,
    nc_netcard_allocation_online.amount,
    nc_netcard_allocation_online.created,
    '' AS pick 
    FROM
    nc_netcard_allocation_online
    LEFT JOIN (
    SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
    FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
    ) a ON nc_netcard_allocation_online.hhm_id = a.userid
    LEFT JOIN (
    SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
    FROM
        usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
    ) b ON nc_netcard_allocation_online.requester_id = b.userid
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";
# count
$sql_count = "SELECT
    COUNT(*)
    FROM
    nc_netcard_allocation_order
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
