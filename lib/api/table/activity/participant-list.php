<?php

/*
     *      Training participant list
     */
$columns = array('participant_id', 'userid', 'first', 'middle', 'last', 'gender', 'phone', 'email', 'loginid', 'username', 'active');
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
$seed = 1;
#
#   Filter column
$name = CleanData('na');
$loginid = CleanData('lo');
$training_id = CleanData('id');
#   Filtered by Geo-Level
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
#
if ($name) {
    if ($seed == 0) {
        $where_condition = " WHERE CONCAT_WS(' ', usr_identity.`first`,usr_identity.middle,usr_identity.last) LIKE '%$name%' ";
        $seed = 1;
    } else
        $where_condition .= " AND  CONCAT_WS(' ', usr_identity.`first`,usr_identity.middle,usr_identity.last) LIKE '%$name%'  ";
}
if ($loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_login.loginid = '$loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.loginid = '$loginid' ";
}
if ($geo_level && $geo_level_id) {
    if ($seed == 0) {
        $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
        $seed = 1;
    } else
        $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
}
#
#  Query composition
$sql_query = "SELECT
    tra_participants.participant_id,
    usr_identity.userid,
    usr_identity.`first`,
    usr_identity.middle,
    usr_identity.last,
    usr_identity.gender,
    usr_identity.phone,
    usr_identity.email,
    usr_login.loginid,
    usr_login.username,
    usr_login.user_group,
    usr_login.active,
    '' AS pick,
    sys_geo_codex.title AS geo_title,
    sys_geo_codex.geo_string,
    sys_geo_codex.geo_level
    FROM
    tra_participants
    INNER JOIN usr_identity ON tra_participants.userid = usr_identity.userid
    INNER JOIN usr_login ON tra_participants.userid = usr_login.userid
    LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
    WHERE
    usr_login.active = 1 
    AND tra_participants.trainingid = $training_id     
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    tra_participants
    INNER JOIN usr_identity ON tra_participants.userid = usr_identity.userid
    INNER JOIN usr_login ON tra_participants.userid = usr_login.userid
    WHERE
    tra_participants.trainingid = $training_id     
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
