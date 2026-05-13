<?php

#
#   Export user list
$v_g_geo_level = $token->geo_level;
$v_g_geo_level_id = $token->geo_level_id;
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

#
$us = new Users\UserManage();
//  The first 2 parameters are required, the users geo-level & geo-level-id, the remaining are optional for filter
// $data = $us->ExcelDownloadUsers($user_geo_level, $user_geo_level_id, $loginid='', $active='', $phone='', $user_group='', $name='', $geo_level='', $geo_level_id='', $bank_verification_status='', $role_id='');
$data = $us->ExcelDownloadUsers($v_g_geo_level, $v_g_geo_level_id, $loginid, $active, $phone, $user_group, $name, $geo_level, $geo_level_id, $bank_verification_status, $role_id);
#
echo $data;
