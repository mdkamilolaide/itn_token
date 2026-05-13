<?php

#
#
#   Excel Export Count Participants in the training (Active participants only)
$us = new Training\Training();
$training_id = CleanData('tid');
$total = $us->ExcelCountParticipantList($training_id);
#
//User Log Activity
$result = "success";
logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "$total Participant records was Successfully exported by user with the Login ID: " . $current_loginid . " and Activity ID: " . $training_id . " :", $result);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Count Participant List',
    'message' => 'success',
    'total' => $total
));
