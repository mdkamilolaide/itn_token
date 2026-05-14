<?php

$columns = array('issue_id', 'product_code', 'product_name', 'primary_qty', 'secondary_qty', 'created', 'updated', 'geo_string');
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
$product_name = CleanData("pid");       #  Product Name
$geo_id = CleanData("gid");         #   Geo_level_id
$geo_level = CleanData("glv");      #   Geo-Level
#
if ($product_name) {
    if ($seed == 0) {
        $where_condition = " WHERE smc_logistics_issues.product_name = '$product_name' ";
        $seed = 1;
    } else
        $where_condition .= " AND smc_logistics_issues.product_name = '$product_name' ";
}
if ($geo_id && $geo_level) {
    $level = "";
    if ($geo_level == 'lga') {
        $level = "sys_geo_codex.lgaid";
    } elseif ($geo_level == 'ward') {
        $level = "sys_geo_codex.wardid";
    } elseif ($geo_level == 'dp') {
        $level = "sys_geo_codex.dpid";
    }
    #
    if ($seed == 0) {
        $where_condition = " WHERE $level = $geo_id ";
        $seed = 1;
    } else {
        $where_condition .= " AND $level = $geo_id ";
    }
}

#
#  Query composition
$sql_query = "SELECT
    smc_logistics_issues.issue_id,
    smc_logistics_issues.product_code,
    smc_logistics_issues.product_name,
    smc_logistics_issues.primary_qty,
    smc_logistics_issues.secondary_qty,
    smc_logistics_issues.created,
    smc_logistics_issues.updated,
    sys_geo_codex.geo_string
    FROM
    smc_logistics_issues
    INNER JOIN sys_geo_codex ON smc_logistics_issues.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT COUNT(*)
		FROM
    smc_logistics_issues
    INNER JOIN sys_geo_codex ON smc_logistics_issues.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
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
