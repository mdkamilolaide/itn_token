<?php

#
#   Bulk user update
#
if (getPermission($user_priviledge, 'users') >= 2) {
    $inputData = json_decode(file_get_contents('php://input'), true);

    $usr = new Users\UserManage();
    #
    # userid, roleid, first, middle, last, gender, email, phone, bank_name, account_name, account_no, bank_code, bio_feature
    #

    $userData = array($inputData);
    $total = $usr->BulkUserUpdate($userData);
    if ($total) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID " . $inputData['userid'] . " details Successfully Updated: ", $result);
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID " . $inputData['userid'] . " details Update Failed: ", $result);
    }
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'total' => $total
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
