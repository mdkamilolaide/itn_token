<?php

#
#   Count user list to export
// $v_g_geo_level = $_SESSION[$instance_token . '_geo_level'];
// $v_g_geo_level_id = $_SESSION[$instance_token . '_geo_level_id'];

#
#   Filter column
#
$geo_level = CleanData('gl');
$geo_level_id = CleanData('gl_id');

$loginid = CleanData('lo');
$active = CleanData('ac');
$phone = CleanData('ph');
$user_group = CleanData('gr');
$name = CleanData('na');
$bank_verification_status = CleanData('bv');    // parameters['failed' | 'success' | 'none']
$role_id = CleanData('ri');                     # user filter by role id


$us = new Users\UserManage();
//  The first 2 parameters are required, the users geo-level & geo-level-id, the remaining are optional for filter
// $total = $us->ExcelCountUsers($v_g_geo_level,$v_g_geo_level_id); ##other parameters are optional for filter
$total = $us->ExcelCountUsers($v_g_geo_level, $v_g_geo_level_id, $loginid, $active, $phone, $user_group, $name, $geo_level, $geo_level_id, $bank_verification_status, $role_id); ##other parameters are optional for filter
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Count Users to download - ' . $v_g_geo_level_id . ' - ' . $v_g_geo_level,
    'message' => 'success',
    'total' => $total
));
