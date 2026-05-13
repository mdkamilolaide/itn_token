<?php

/*
     *  Runs e-Netcard Samples
     *
     *  Netcard reverse order HHM back to ward 
     */

$inputData = json_decode(file_get_contents('php://input'), true)[0];
// 'total'=>10, 'wardid'=>1, 'mobilizerid'=>4, 'userid'=>2

$nt = new Netcard\NetcardTrans();
$order_total = $inputData['total'];
$mobilizerid = $inputData['mobilizerid'];
$wardid = $inputData['wardid'];
$userid = $inputData['userid'];
$device_serial = $inputData['device_serial'];
#
if ($nt->ReverseAllocationOrder($mobilizerid, $userid, $order_total, $device_serial)) {
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$order_total e-Netcard Reversed Order Successfully placed to be retracted from Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result);
    echo json_encode(array(
        'result_code' => 200,
        'message' => "e-Netcard reversal order has been placed successfully",
        'total' => $order_total
    ));
} else {
    //User Log Activity
    $result = "failed";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$order_total e-Netcard Reversed Order Failed to be placed to Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result);
    echo json_encode(array(
        'result_code' => 400,
        'message' => "Unable to place e-Netcard reversal order at the moment, please try again later"
    ));
}
