<?php

#
#
#   Export Attendance list
$session_id = CleanData('id');
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
#
$ex = new Training\Training();
echo $ex->ExcelGetAttendanceList($session_id, $geo_level, $geo_level_id);
