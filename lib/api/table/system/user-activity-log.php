<?php

$columns = array('id', 'userid', 'loginid', 'fullname', 'platform', 'module', 'ip', 'description', 'result', 'created');
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
$userid = CleanData("uid");     #   by user ID
$loginid = CleanData("lid");    #   by user login id
$platform = CleanData("pla");   #   by platform [ web | mobile | pos ]
$module = CleanData("mod");     #   by module
$result = CleanData("res");     #   by result [ success | failed ]
#

if ($userid) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_user_activity.userid = $userid  ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_user_activity.userid = $userid ";
}
if ($loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE usr.loginid = '$loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr.loginid = '$loginid' ";
}
if ($platform) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_user_activity.platform = '$platform' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_user_activity.platform = '$platform' ";
}
if ($module) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_user_activity.module = '$module' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_user_activity.module = '$module' ";
}
if ($result) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_user_activity.result = '$result' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_user_activity.result = '$result' ";
}
#
#  Query composition
$sql_query = "SELECT
    usr_user_activity.id,
    usr_user_activity.userid,
    usr.loginid,
    usr.fullname,
    usr_user_activity.platform,
    usr_user_activity.module,
    usr_user_activity.ip,
    usr_user_activity.description,
    usr_user_activity.result,
    usr_user_activity.created
    FROM
    usr_user_activity
    LEFT JOIN (SELECT
    usr_login.loginid,
    usr_login.userid,
    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS usr ON usr_user_activity.userid = usr.userid
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    usr_user_activity
    LEFT JOIN (SELECT
    usr_login.loginid,
    usr_login.userid,
    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS usr ON usr_user_activity.userid = usr.userid
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
