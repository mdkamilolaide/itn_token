<?php

#
# Generate STock Batch Allocation/ Management
#
$cr = new Smc\Logistics();


if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $checkData = $cr->generateInventoryAllocations($inputData['periodid']);

    if ($checkData) {
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodid'] . " Batch Stock Generated  by User ID: " . $current_userid . " Successful", $result);
        #On User Creation
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  $inputData['periodid'] . ' Stock List Allocation Successful',
            'data' => $checkData
        ));
    } else {
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = " Batch Stock Generation for Period ID " . $inputData['periodid'] . " failed by Userd ID: " . $current_userid, $result);
        #On user creation failed
        http_response_code(400);
        // $checkData may be [], null, false, or an error string; coerce to a
        // safe scalar before concatenation so PHP doesn't warn "Array to
        // string conversion" and the client gets a useful message.
        $errDetail = is_array($checkData)
            ? (empty($checkData) ? 'no allocations available' : json_encode($checkData))
            : (string) $checkData;
        echo json_encode(array(
            'result_code' => 400,
            'message' =>  $inputData['periodid'] . ' Product Validity Check failed. Error: ' . $errDetail
        ));
    }
} else {
    $json_data = array(
        "result_code" => 401,
        'message' => 'You don\'t have permission to Check Product Validity'
    );
    echo json_encode($json_data);
}
