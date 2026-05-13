<?php

#
#   Delete/Remove Participants 
#
if (getPermission($user_priviledge, 'activity') == 3) {
    $tr = new Training\Training();
    #   $total = $tr->AddParticipantsByGroup($training_id, $group_name);
    $inputData = json_decode(file_get_contents('php://input'), true);

    $participant_id_list = $inputData['selectedid'];     //  Users List
    $training_id = $inputData['trainingid'];

    // echo json_encode($participant_id_list);
    #
    $total = $tr->RemoveParticipant($training_id, $participant_id_list);

    if ($total) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully removed User(s) with User IDs [" . implode(', ', $participant_id_list) . "] from Activity with Activity ID: " . $training_id . " :", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => "$total participant(s) removed successfully ",
            'total' => $total
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Tried removing the User(s) with User IDs [" . implode(', ', $participant_id_list) . "] from Activity with Activity ID: " . $training_id . " and Failed: ", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'Unable to remove participant at the moment, please try again.'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
