<?php

#
# Shipment Lists
#
$shipment = new Smc\Logistics();


if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);

    //
    $data  = $shipment->getShipmentList($inputData['periodid']);
    //


    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodid'] . " Shipment List Generated  by User ID: " . $current_userid . " Successful", $result);
    #On User Creation
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' =>  "Shipment List Generated " . $result,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 401,
        'message' => 'You don\'t have permission to Check Product Validity'
    );
    echo json_encode($json_data);
}
