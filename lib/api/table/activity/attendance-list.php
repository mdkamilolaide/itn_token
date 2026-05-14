<?php

/*
     *      Training attendance list
     */
$columns = array('loginid', 'fullname', 'phone', 'at_type', 'collected', 'bio_auth', 'geo_title', 'geo_level', 'geo_string', 'role', 'attendant_id', 'userid', 'participant_id');
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
#   Filtered by Geo-Level
$session_id = CleanData('se');
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
#

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
    usr.loginid,
    CONCAT_WS( ' ', usr.`first`, usr.middle, usr.last ) AS fullname,
    usr.phone,
    tra_attendant.at_type,
    tra_attendant.collected,
    IF
    ( tra_attendant.bio_auth = 1, 'True', 'False' ) AS `bio_auth`,
    sys_geo_codex.title AS geo_title,
    sys_geo_codex.geo_level,
    sys_geo_codex.geo_string,
    usr.role,
    tra_attendant.attendant_id,
    usr.userid,
    tra_participants.participant_id,
    '' AS pick 
    FROM
    tra_attendant
    INNER JOIN tra_participants ON tra_attendant.participant_id = tra_participants.participant_id
    INNER JOIN (
    SELECT
        usr_login.userid,
        usr_login.loginid,
        usr_login.geo_level,
        usr_login.geo_level_id,
        usr_role.title AS role,
        usr_identity.`first`,
        usr_identity.middle,
        usr_identity.last,
        usr_identity.gender,
        usr_identity.email,
        usr_identity.phone 
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid 
    ) AS usr ON tra_participants.userid = usr.userid 
    LEFT JOIN sys_geo_codex ON usr.geo_level = sys_geo_codex.geo_level AND usr.geo_level_id = sys_geo_codex.geo_level_id
    WHERE
    tra_attendant.session_id = $session_id   
    $where_condition 
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    tra_attendant
    INNER JOIN tra_participants ON tra_attendant.participant_id = tra_participants.participant_id
    INNER JOIN (
    SELECT
        usr_login.userid,
        usr_login.loginid,
        usr_login.geo_level,
        usr_login.geo_level_id,
        usr_role.title AS role,
        usr_identity.`first`,
        usr_identity.middle,
        usr_identity.last,
        usr_identity.gender,
        usr_identity.email,
        usr_identity.phone 
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid 
    ) AS usr ON tra_participants.userid = usr.userid 
    LEFT JOIN sys_geo_codex ON usr.geo_level = sys_geo_codex.geo_level AND usr.geo_level_id = sys_geo_codex.geo_level_id
    WHERE
    tra_attendant.session_id = $session_id   
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
