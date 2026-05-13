<?php

#
#   User login sample
#
#   Change user password using login ID
#
$mg = new Users\UserManage();
$inputData = json_decode(file_get_contents('php://input'), true)[0];
#

$loginid = $inputData["loginid"];
$old = $inputData["old"];
$new = $inputData["new"];

if ($mg->ChangePassword($loginid, $old, $new)) {
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " password was Successfully Changed: ", $result);
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'Password Successfully Changed'
    ));
} else {
    //User Log Activity
    $result = "failed";
    logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " tried to change his/her password and Failed: ", $result);
    echo json_encode(array(
        'result_code' => 400,
        'message' => 'Unable to change password, maybe user does not exist, or incorrect old password, please try again later'
    ));
}
