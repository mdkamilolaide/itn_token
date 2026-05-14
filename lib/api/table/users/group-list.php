<?php

/*
     *      User group Table List
     */
$columns = array('total', 'user_group');
//  Require variable
$perpage = intval($_REQUEST['length']);
$currentPage = $_REQUEST['draw'];
$sortColumn = $_REQUEST['order_column'];
$orderDir = $_REQUEST['order_dir']; // asc | desc
$orderField = $columns[$_REQUEST['order_column']];
$limitStart = $_REQUEST['start'];
//  Where condition
$where_condition = "  ";
$seed = 0;
#
#   Filter column
#
$user_group = CleanData('gr');
#
if ($user_group) {
    if ($seed == 0) {
        $where_condition = " WHERE  usr_login.user_group LIKE '%$user_group%' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.user_group LIKE '%$user_group%' ";
}
//  Query composition
$sql_query = "SELECT
    Count(usr_login.userid) AS total,
    usr_login.user_group,
    '' AS pick
    FROM
    usr_login
    $where_condition
    GROUP BY
    usr_login.user_group
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(DISTINCT usr_login.user_group)
    FROM
    usr_login
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
