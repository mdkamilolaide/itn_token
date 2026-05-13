<?php

#
#   Activate users by Group
#
if (getPermission($user_priviledge, 'users') == 3) {

    $usr = new Users\UserManage();
    #   users list
    $group = CleanData("e");
    if ($usr->ActivateUserByGroup($group)) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $group . " User Group Successfully Activated: ", $result);

        // $group user group has been activated successfully
        echo json_encode(array(
            'result_code' => 201,
            'message' => 'success',
            'group' => $group
        ));
    } else {
        // Unable to activate $group at the moment please try again later.
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "Unable to activate $group User Group at the moment please try again later", $result);

        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error',
            'group' => $group
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
