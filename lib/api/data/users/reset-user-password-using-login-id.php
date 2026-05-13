<?php

#
#   Reset user password using login IDs
#
$mg = new Users\UserManage();
$inputData = json_decode(file_get_contents('php://input'), true);
$loginid = $inputData['loginid'];


if ($mg->ResetPassword($inputData['loginid'], $inputData['new'])) {
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " password was Successfully Reset: ", $result);
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' => "Password for <b>$loginid</b> has been reset successfully"
    ));
} else {
    //User Log Activity
    $result = "failed";
    logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " password Failed to be Reset: ", $result);
    http_response_code(400);
    echo json_encode(array(
        'result_code' => 400,
        'message' => "Unable to reset password, maybe use does not exist or system error, please try again later"
    ));
}
