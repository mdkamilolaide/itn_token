<?php

/*
     *      User List Table
     */
$columns = array('userid', 'loginid', 'username', 'guid', 'user_group', 'role', 'first', 'middle', 'last', 'gender', 'email', 'phone', 'active', 'roleid', 'geo_level', 'geo_level_id', 'geo_string');
//  Require variable
$perpage = intval($_REQUEST['length']);
$currentPage = $_REQUEST['draw'];
$sortColumn = $_REQUEST['order_column'];
$orderDir = $_REQUEST['order_dir']; // asc | desc
$orderField = $columns[$_REQUEST['order_column']];
$limitStart = $_REQUEST['start'];
#
$geo_level = $v_g_geo_level;
$geo_level_id = $v_g_geo_level_id;
$where_key = $geo_level . "id";
//  Where condition
$where_condition = " WHERE `$where_key` = $geo_level_id ";
$seed = 1;
//  Where condition
//$where_condition = "  ";
//$seed = 0;
#
#   Filter column
#
$loginid = CleanData('lo');
$active = CleanData('ac');
$phone = CleanData('ph');
$user_group = CleanData('gr');
$name = CleanData('na');
$geo_level = CleanData('gl');
$geo_level_id = CleanData('gl_id');
$bank_verification_status = CleanData('bv');    // parameters['failed' | 'success' | 'none']
$role_id = CleanData('ri');                     # user filter by role id
#
if ($loginid) {
    if ($seed == 0) {
        $where_condition = " WHERE  usr_login.loginid = '$loginid' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.loginid = '$loginid'";
}
if ($active) {
    $active = $active == 'active' ? 1 : 0;
    if ($seed == 0) {
        $where_condition = " WHERE  usr_login.active = '$active' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.active = '$active' ";
}
if ($user_group) {
    if ($seed == 0) {
        $where_condition = " WHERE  usr_login.user_group LIKE '%$user_group%' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.user_group LIKE '%$user_group%' ";
}
if ($phone) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_identity.phone = '$phone' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_identity.phone = '$phone' ";
}
if ($name) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_identity.`first` LIKE '%$name%' OR
            usr_identity.middle LIKE '%$name%' OR
            usr_identity.last LIKE '%$name%' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_identity.`first` LIKE '%$name%' OR
            usr_identity.middle LIKE '%$name%' OR
            usr_identity.last LIKE '%$name%' ";
}
if ($geo_level && $geo_level_id) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_login.geo_level = '$geo_level' AND usr_login.geo_level_id = '$geo_level_id' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.geo_level = '$geo_level' AND usr_login.geo_level_id = '$geo_level_id' ";
}
if ($bank_verification_status) {
    if ($seed == 0) {
        $where_condition = " WHERE usr_finance.verification_status = '$bank_verification_status' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_finance.verification_status = '$bank_verification_status' ";
}
if ($role_id) {
    if ($seed == 0) {
        $where_condition = " WHERE  usr_login.roleid = '$role_id' ";
        $seed = 1;
    } else
        $where_condition .= " AND usr_login.roleid = '$role_id'";
}
//  Query composition
$sql_query = "SELECT
    usr_login.userid,
    usr_login.loginid,
    usr_login.username,
    usr_login.guid,
    usr_login.user_group,
    usr_role.title AS role,
    usr_identity.`first`,
    usr_identity.middle,
    usr_identity.last,
    usr_identity.gender,
    usr_identity.email,
    usr_identity.phone,
    usr_login.active,
    usr_login.roleid,
    usr_login.geo_level,
    usr_login.geo_level_id,
    '' AS pick,
    sys_geo_codex.title AS geo_title,
    sys_geo_codex.geo_string,
    usr_finance.is_verified,
    usr_finance.verification_status
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
    LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
    LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
    LEFT JOIN usr_finance ON usr_login.userid = usr_finance.userid
    $where_condition
    order by $orderField $orderDir
    LIMIT $limitStart, $perpage";

$sql_count = "SELECT
    COUNT(*)
    FROM
    usr_login
    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
    LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
    LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
    LEFT JOIN usr_finance ON usr_login.userid = usr_finance.userid
    $where_condition";


//  Access Database
$c = new MysqlCentry();
$data = $c->Table($sql_query);
$count = $c->Single($sql_count);


$json_data = array(
    "draw" => $currentPage,
    "recordsTotal" => $count,
    "recordsFiltered" => $count,
    "data" => $data
);

echo json_encode($json_data);

//echo $sql_query;
