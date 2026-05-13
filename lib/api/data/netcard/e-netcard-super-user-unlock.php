<?php


if (getPermission($user_priviledge, 'enetcard') == 3) {
    /*
        *  Runs e-Netcard Samples
        *
        *  e-Netcard Unlock on Devices 
        */
    $nt = new Netcard\NetcardTrans();

    $userid = CleanData('userid');
    $device_serial = CleanData('device_serial');
    $requester_userid = CleanData('requester_userid');

    $total = $nt->SuperUserUnlockNetcard($userid, $device_serial, $requester_userid);
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard has been Successfully Unlocked, from Household Mobilizers '" . $userid . " by user with the Login ID: " . $current_loginid . " :", $result);

    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' => $total . ' e-Netcard has been unlocked',
        'total' => $total
    ));
} else {
    //User Log Activity
    $result = "failed";
    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Reverse eNetcard from HHM with ID: " . $userid, $result, $longtitude = "", $latitude = "");

    http_response_code(401);
    echo json_encode(array(
        'result_code' => 401,
        'message' => 'Unauthorized User Priviledge on E-Netcard Module Module'
    ));
}
