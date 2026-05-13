<?php

#
#   Add Participants by group name
#
if (getPermission($user_priviledge, 'activity') >= 2) {
    $tr = new Training\Training();
    $inputData = json_decode(file_get_contents('php://input'), true);
    $training_id = $inputData['trainingid'];
    $group_name = $inputData['group_name'];
    // $total = $tr->AddParticipantsByGroup(4,'tta');
    $total = $tr->AddParticipantsByGroup($training_id, $group_name);

    if ($total) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully add Participant in " . $group_name . " Group to Activity with Activity ID: " . $training_id . " :", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => "$total participant(s) successfully added",
            'total' => $total
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Failed to add Participant in " . $group_name . " Group to Activity with Activity ID: " . $training_id . " :", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'Unable to add participant at the moment, please try again.'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
