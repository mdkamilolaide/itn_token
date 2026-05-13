<?php

#
#
if (getPermission($user_priviledge, 'activity') == 3) {

    #   Create Session
    $tr = new Training\Training();
    $inputData = json_decode(file_get_contents('php://input'), true);
    #
    $training_id = $inputData['trainingid'];
    $session_title = $inputData['title'];
    $session_date = $inputData['date'];

    $id = $tr->CreateSession($training_id, $session_title, $session_date);
    if ($id) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully created a Activity Session Title: " . $session_title . " on Activity with Activity ID: $training_id :", $result);
        echo json_encode(array(
            'result_code' => 201,
            'message' => 'Session created successfully. <b>Session ID:</b> ' . $id
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried to create a Activity Session Title: " . $session_title . " on Activity with Activity ID: $training_id and Failed:", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'Unable to create session at the moment, please try again later'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
