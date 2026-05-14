<?php

/*
     *      Training list table
     */
$columns = array('trainingid', 'title', 'geo_location', 'location_id', 'guid', 'active', 'description', 'start_date', 'end_date', 'participant_count', 'created', 'updated');
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
$id = CleanData('id');
$name = CleanData('tr');
$active = CleanData('ac');

#
if ($id) {
    if ($seed == 0) {
        $where_condition = " WHERE tra_training.trainingid = $id ";
        $seed = 1;
    } else
        $where_condition .= " AND tra_training.trainingid = $id ";
}
if ($name) {
    if ($seed == 0) {
        $where_condition = " WHERE tra_training.title LIKE '%$name%' ";
        $seed = 1;
    } else
        $where_condition .= " AND tra_training.title LIKE '%$name%' ";
}
if ($active) {
    $active = $active == 'active' ? 1 : 0;
    if ($seed == 0) {
        $where_condition = " WHERE  tra_training.active = '$active' ";
        $seed = 1;
    } else
        $where_condition .= " AND tra_training.active = '$active' ";
}
#
#  Query composition
$sql_query = "SELECT
    tra_training.trainingid,
    LPAD(tra_training.trainingid,3,0) AS ui_id,
    tra_training.title,
    tra_training.geo_location,
    tra_training.location_id,
    tra_training.guid,
    tra_training.active,
    tra_training.description,
    DATE_FORMAT(tra_training.start_date,'$dateMed_format') AS start_date,
    tra_training.start_date AS db_start_date,
    DATE_FORMAT(tra_training.end_date,'$dateMed_format') AS end_date,
    tra_training.end_date AS db_end_date,
    tra_training.participant_count,
    DATE_FORMAT(tra_training.created,'$date_format') AS created,
    DATE_FORMAT(tra_training.updated,'$date_format') AS updated,
    '' AS pick
    FROM
    tra_training       
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    tra_training  
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
