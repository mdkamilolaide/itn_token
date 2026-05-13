<?php

#
# Shipment Lists
#
$shipment = new Smc\Logistics();


if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $shipmentId = (int) $inputData['shipmentId'];

    //
    $data  = $shipment->getShipmentItems($shipmentId);
    //


    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "User with ID: " . $current_userid . " get shipmentItem for shipment with ID: " . $shipmentId, $result);
    #On Shipment Request
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' =>  "Shipment Items " . $result,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 401,
        'message' => 'You don\'t have permission to Check Product Validity'
    );
    echo json_encode($json_data);
}
