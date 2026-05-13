<?php

#
#   Deacvtivate users by Group
#
if (getPermission($user_priviledge, 'users') == 3) {
    $usr = new Users\UserManage();
    #   users list
    $group = CleanData("e");
    if ($usr->DeavtivateUserByGroup($group)) {
        // echo "$group user group has been deactivated successfully";
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $group . " User Group Successfully Deactivated", $result);

        #On Usergroup Deactivation
        echo json_encode(array(
            'result_code' => 201,
            'message' => 'success',
            'group' => $group
        ));
    } else {
        // echo "Unable to deactivate $group at the moment please try again later.";
        //Log User Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $group . " User Group failed to be Deactivated", $result);
        #On Usergroup Deactivation
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
