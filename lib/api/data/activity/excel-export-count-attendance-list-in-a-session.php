<?php

#
#
#   Excel Export Count Attendance list in a session
$us = new Training\Training();
$sessionid = CleanData('sid');
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
$total = $us->ExcelCountAttendanceList($sessionid, $geo_level, $geo_level_id);
#
//User Log Activity
$result = "success";
logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "$total Attendance records was Successfully exported by user with the Login ID: " . $current_loginid . " and Session ID: " . $sessionid . " :", $result);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Count Attendance List',
    'message' => 'success',
    'total' => $total
));
