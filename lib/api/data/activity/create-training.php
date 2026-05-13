<?php

#
if (getPermission($user_priviledge, 'activity') >= 2) {

    #   Create Training
    $tr = new Training\Training();
    #   data
    $inputData = json_decode(file_get_contents('php://input'), true);
    #   $tr->CreateTraining('Training Tite', 'Geo location', 'Geo location id(int)', 'Training description', 'start date', 'end date');

    $trainingid = $tr->CreateTraining($inputData['title'], $inputData['geoLevel'], $inputData['geoLevelId'], $inputData['description'], $inputData['start_date'], $inputData['end_date']);
    #
    if ($trainingid) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully Created " . $inputData['title'] . " at " . $inputData['geoLevel'] . " Level", $result);
        echo json_encode(array(
            'result_code' => 201,
            'message' => "Training created successfully. Activity ID: $trainingid"
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried creating a Activity title " . $inputData['title'] . " at " . $inputData['geoLevel'] . " Level but Failed", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'Create new Activity failed, unable to create at the moment, please try again later.'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
