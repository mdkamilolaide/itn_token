<?php

#
#
if (getPermission($user_priviledge, 'activity') == 3) {

    #   Delete Session
    $tr = new Training\Training();

    #
    $session_id = CleanData('e');
    if ($tr->DeleteSession($session_id)) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully Deleted a Activity Session with Session ID: " . $session_id . " :", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'Session deleted successfully'
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried to Delete a Activity Session with Session ID: " . $session_id . " and Failed:", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'Unable to delete session at the moment, please try again later'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
