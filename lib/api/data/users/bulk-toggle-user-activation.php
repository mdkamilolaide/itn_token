<?php

#
#   Activate/Deavtivate bulk users
#
if (getPermission($user_priviledge, 'users') == 3) {

    $userids = json_decode(file_get_contents('php://input'), true);


    $usr = new Users\UserManage();
    #   users list
    $users = $userids;
    $total = $usr->BulkToggleUserStatus($users);
    if ($total) {
        $result = "success";
    } else {
        $result = "failed";
    }
    logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "$total User(s) with User ID [" . implode(', ', $userids) . "] De/Activated", $result);

    $json_data = array(
        "result_code" => 200,
        'message' => 'success',
        "total" => $total
    );
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
}

echo json_encode($json_data);
