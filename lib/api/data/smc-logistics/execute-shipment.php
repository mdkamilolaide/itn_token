<?php

#
# Excute Shipment
#
$movement = new Smc\Logistics();


if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);

    $data  = $movement->executeForwardShipment($inputData['periodid'], $current_userid);

    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodid'] . " Shipment Execution " . $result . "  by User ID: " . $current_userid . " Successful", $result);
    #On User Creation
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' =>  "Shipment Execution " . $result,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 401,
        'message' => 'You don\'t have permission to Check Product Validity'
    );
    echo json_encode($json_data);
}
