<?php

#
#   update user role
#
if (getPermission($user_priviledge, 'users') == 3) {

    $role_id = CleanData("r");
    $user_id = CleanData("u");

    $usr = new Users\UserManage();
    #   users list
    #
    #   UpdateUserRole($role_id, $user_id)
    $data = $usr->UpdateUserRole($role_id, $user_id);
    #
    #
    if ($data) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $userid . " Role Successfully Updated to Role ID: $role_id", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'success',
            'data' => $data
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $userid . " Role Failed to be Updated to Role ID: $role_id", $result);

        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error',
            'data' => $data
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
