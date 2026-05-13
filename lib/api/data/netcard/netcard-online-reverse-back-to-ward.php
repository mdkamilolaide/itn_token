<?php

/**
 * Check User Priviledge 
 * For Netcard Allocation
 */
$inputData = json_decode(file_get_contents('php://input'), true)[0];
$total = $inputData['total'];
$mobilizerid = $inputData['mobilizerid'];
$wardid = $inputData['wardid'];

$longtitude = isset($inputData['long']) ? $inputData['long'] : "";
$latitude = isset($inputData['lat']) ? $inputData['lat'] : "";

if (getPermission($user_priviledge, 'allocation') == 3) {
    /*
        *  Runs e-Netcard Samples
        *
        *  Netcard Online reverse back to ward 
        */
    $nt = new Netcard\NetcardTrans();

    $total_reverse = $nt->DirectReverseAllocation($total,  $mobilizerid, $current_userid);
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total_reverse e-Netcard Online Reversed Successfull, from Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result);

    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' => "e-Netcard reversal successful: " . $mobilizerid . " Total: " . $total_reverse . " Ward ID:" . $wardid,
        'total' => $total_reverse
    ));
} else {
    //User Log Activity
    $result = "failed";
    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "allocation", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Reverse eNetcard from HHM with ID: " . $mobilizerid, $result, $longtitude = "", $latitude = "");

    http_response_code(401);
    echo json_encode(array(
        'result_code' => 401,
        'message' => 'Unauthorized User Priviledge on Allocation Module'
    ));
}
