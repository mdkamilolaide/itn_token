<?php

$columns = array('id', 'device_name', 'device_id', 'guid', 'serial_no', 'device_type', 'active', 'connected', 'created', 'updated');
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
$active = CleanData("act");     #   Active
$serial_no = CleanData("sno");    #   Serial

if (strval($active) == '0' || strval($active) == '1') {
    if ($seed == 0) {
        $where_condition = " WHERE sys_device_registry.active = $active  ";
        $seed = 1;
    } else
        $where_condition .= " AND sys_device_registry.active = $active ";
}
if ($serial_no) {
    if ($seed == 0) {
        $where_condition = " WHERE sys_device_registry.serial_no = '$serial_no' ";
        $seed = 1;
    } else
        $where_condition .= " AND sys_device_registry.serial_no = '$serial_no' ";
}

#
#  Query composition
$sql_query = "SELECT
    sys_device_registry.id,
    sys_device_registry.device_name,
    sys_device_registry.device_id,
    sys_device_registry.guid,
    sys_device_registry.serial_no,
    sys_device_registry.device_type,
    sys_device_registry.imei1,
    sys_device_registry.imei2,
    sys_device_registry.phone_serial,
    sys_device_registry.sim_network,
    sys_device_registry.sim_serial,
    sys_device_registry.active,
    sys_device_registry.connected,
    sys_device_registry.created,
    sys_device_registry.updated,
    '' AS pick
    FROM
    sys_device_registry
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    sys_device_registry
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
