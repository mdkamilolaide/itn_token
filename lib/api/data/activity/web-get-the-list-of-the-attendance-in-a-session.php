<?php

#
#
#   Web get the list of the attendance in a session
$us = new Training\Training();
$sessionid = CleanData('sid');
$data = $us->getAttendanceList($sessionid);
#
echo json_encode(array(
    'status_code' => 200,
    'dataset' => 'Get Attendance List',
    'message' => 'success',
    'data' => $data
));
