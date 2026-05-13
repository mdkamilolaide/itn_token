<?php

#
# Create New Issues
#
$cr = new Smc\Logistics();


if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);

    if (is_array($inputData) && !empty($inputData)) {

        $total = $cr->ProcessBulkIssue($inputData);
        if ($total) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Issues Created Successfully by User ID: " . $current_userid, $result);
            #On User Creation
            http_response_code(200);
            echo json_encode(array(
                'result_code' => 200,
                'message' => $total . ' Issues Created Successfully',
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Creation failed by Userd ID: " . $current_userid, $result);
            #On user creation failed
            http_response_code(400);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Issue Creation failed'
            ));
        }
    } else {
        //Log User Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Issue  Creation failed due to wrong data input: ", $result);
        #If all data supplied are wrong
        http_response_code(400);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 401,
        'message' => 'You don\'t have permission to Create Issues'
    );
    echo json_encode($json_data);
}
