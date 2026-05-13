<?php

#
#   Bulk User Role Update
#
if (getPermission($user_priviledge, 'users') == 3) {

    $roleId = CleanData("r");

    $usr = new Users\UserManage();
    $inputData = json_decode(file_get_contents('php://input'), true);
    #
    #
    $userIds = $inputData;
    #   users list
    #
    #   UpdateUserRole($role_id, $user_id)
    $data = $usr->BulkChangeRole($userIds, $roleId);
    #
    #
    if ($data) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $current_userid . " change " . $data . " Users Roles with IDs" . json_encode($userIds) . " Role Successfully Updated to Role ID: $roleId", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'success',
            'data' => $data
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . json_encode($userIds) . " Role Failed to be Updated to Role ID: $roleId", $result);

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
