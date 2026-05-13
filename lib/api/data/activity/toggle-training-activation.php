<?php

#
if (getPermission($user_priviledge, 'activity') == 3) {

    #   Toggle training activation
    $trainingid = CleanData("e");

    $tr = new Training\Training();
    if ($tr->ToggleTraining($trainingid)) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " De/Activated Activity with ID " . $trainingid . " and Successfull: ", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'Training De/Activated successfully'
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried De/Activating Activity with ID " . $trainingid . " and Failed: ", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'unable to update Activity status at the moment, please try again later.'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
