<?php

$columns = array('inbound_id', 'product_code', 'product_name', 'location_type', 'cms_name', 'batch', 'expiry', 'rate', 'unit', 'previous_primary_qty', 'current_primary_qty', 'total_primary_qty', 'previous_secondary_qty', 'current_secondary_qty', 'total_secondary_qty', 'created');    
#  Require variable
$perpage = intval($_REQUEST['length']);
$currentPage = $_REQUEST['draw'];
$sortColumn = $_REQUEST['order_column'];
$orderDir = $_REQUEST['order_dir']; // asc | desc
$orderField = $columns[$_REQUEST['order_column']];
$limitStart = $_REQUEST['start'];
$date_format = $GLOBALS["conf_db_date_format"];
$dateMed_format = $GLOBALS["conf_db_date_medium_format"];
#  Where condition
$where_condition = "  ";
$seed = 0;
#
#   Filters

#
#  Query composition
$sql_query = "SELECT
    smc_inventory_inbound.inbound_id,
    smc_inventory_inbound.product_code,
    smc_inventory_inbound.product_name,
    smc_inventory_inbound.location_type,
    smc_cms_location.cms_name,
    smc_inventory_inbound.batch,
    smc_inventory_inbound.expiry,
    smc_inventory_inbound.rate,
    smc_inventory_inbound.unit,
    smc_inventory_inbound.previous_primary_qty,
    smc_inventory_inbound.current_primary_qty,
    (smc_inventory_inbound.previous_primary_qty+smc_inventory_inbound.current_primary_qty) AS total_primary_qty,
    smc_inventory_inbound.previous_secondary_qty,
    smc_inventory_inbound.current_secondary_qty,
    (smc_inventory_inbound.previous_secondary_qty+smc_inventory_inbound.current_secondary_qty) AS total_secondary_qty,
    smc_inventory_inbound.created
    FROM
    smc_inventory_inbound
    INNER JOIN smc_cms_location ON smc_inventory_inbound.location_id = smc_cms_location.location_id AND smc_inventory_inbound.location_type = 'CMS'
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT COUNT(*)
		FROM
    smc_inventory_inbound
    INNER JOIN smc_cms_location ON smc_inventory_inbound.location_id = smc_cms_location.location_id AND smc_inventory_inbound.location_type = 'CMS'
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
