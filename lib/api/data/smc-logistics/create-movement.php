<?php

#
# Create Movement
#
$shipment = new Smc\Logistics();


if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);

    $period_id = $inputData['periodId'];
    $transporter_id = $inputData['transporterId'];
    $movement_title = $inputData['title'];
    $shipment_list = $inputData['shipmentIds'];
    $conveyor_id = $inputData['conveyorId'];
    $userid = $current_userid;


    $data  = $shipment->createMovementWithShipments($period_id, $transporter_id, $movement_title, $shipment_list, $conveyor_id, $userid);



    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodId'] . " Movement Generated  by User ID: " . $current_userid . " Successful", $result);
    #On User Creation
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' =>  "Movement Done " . $result,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 401,
        'message' => 'You don\'t have permission to Check Product Validity'
    );
    echo json_encode($json_data);
}
