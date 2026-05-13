<?php

#
# PRODUCT VALIDITY CHECK
#
$cr = new Smc\Inventory();


if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $checkData = $cr->ProcessProductValidityCheck($inputData['periodid'], $inputData['product_code']);

    if ($checkData) {
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = $inputData['product_code'] . " Product Validity Check by User ID: " . $current_userid . " Successful", $result);
        #On User Creation
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  $inputData['product_code'] . ' Product Validy Check Successful',
            'data' => $checkData
        ));
    } else {
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Creation failed by Userd ID: " . $current_userid, $result);
        #On user creation failed
        http_response_code(400);
        echo json_encode(array(
            'result_code' => 400,
            'message' =>  $inputData['product_code'] . ' Product Validity Check failed. Error: ' . $checkData
        ));
    }
} else {
    $json_data = array(
        "result_code" => 401,
        'message' => 'You don\'t have permission to Check Product Validity'
    );
    echo json_encode($json_data);
}
