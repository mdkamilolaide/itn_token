<?php

#
if (getPermission($user_priviledge, 'activity') >= 2) {

    #   Update Training
    $tr = new Training\Training();
    #
    $inputData = json_decode(file_get_contents('php://input'), true);

    #   $tr->UpdateTraining('Training Tite', 'Geo location', 'Geo location id(int)', 'Training description', 'start date', 'end date','training id');
    if ($tr->UpdateTraining($inputData['title'], $inputData['geoLevel'], $inputData['geoLevelId'], $inputData['description'], $inputData['start_date'], $inputData['end_date'], $inputData['training_id'])) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully Updated " . $inputData['title'] . " Activity :", $result);
        echo json_encode(array(
            'result_code' => 201,
            'message' => "Activity updated successfully. Activity ID:" . $inputData['training_id']
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Tried to Update " . $inputData['title'] . " Activity but Failed: ", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'unable to update Activity at the moment, please try gain later.'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
