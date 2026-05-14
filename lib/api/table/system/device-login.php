<?php

$columns = array('id', 'device_name', 'device_id', 'serial_no', 'device_type', 'active', 'loginid', 'first', 'middle', 'last', 'created');
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
$date = CleanData("dat");     #  date
$loginid = CleanData("lid");    #   loginid 
$serial_no = CleanData("sno");    #   device serial

if ($date) {
    if ($seed == 0) {
        $where_condition = " WHERE DATE(sys_device_login.created) = DATE('$date')  ";
        $seed = 1;
    } else
        $where_condition .= " AND  DATE(sys_device_login.created) = DATE('$date') ";
}
if ($loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE sys_device_login.loginid = '$loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND sys_device_login.loginid = '$loginid' ";
}
if ($serial_no) {
    if ($seed == 0) {
        $where_condition = " WHERE sys_device_login.device_serial = '$serial_no' ";
        $seed = 1;
    } else
        $where_condition .= " AND sys_device_login.device_serial = '$serial_no' ";
}

#
#  Query composition
$sql_query = "SELECT
    sys_device_login.id,
    sys_device_registry.device_name,
    sys_device_registry.device_id,
    sys_device_registry.serial_no,
    sys_device_registry.device_type,
    sys_device_registry.active,
    sys_device_login.loginid,
    usr_identity.`first`,
    usr_identity.middle,
    usr_identity.last,
    sys_device_login.created,
    '' AS pick
    FROM
    sys_device_login
    LEFT JOIN sys_device_registry ON sys_device_login.device_serial = sys_device_registry.serial_no
    LEFT JOIN usr_login ON sys_device_login.loginid = usr_login.loginid
    LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    sys_device_login
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
