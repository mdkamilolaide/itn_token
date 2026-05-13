<?php

#
if (getPermission($user_priviledge, 'users') == 3) {

    #   Update user level
    $us = new Users\UserManage();
    $inputData = json_decode(file_get_contents('php://input'), true);

    #

    $userid = $inputData["u"];
    $geo_level = $inputData["l"];
    $geo_level_id = $inputData["id"];

    if ($us->ChangeUserLevel($userid, $geo_level, $geo_level_id)) {
        // echo "User Geo Level updated successfully";
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . CleanData("u") . " Geo Level, Successfully Updated to, Geo Level: $geo_level , Geo Level ID: $geo_level_id", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'success: User Geo Level updated successfully'
        ));
    } else {
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $userid . " Geo Level, Failed to Updated to, Geo Level: $geo_level , Geo Level ID: $geo_level_id", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error: Unable to update the geo leve at the moment please try again later.'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
