<?php

$inputData = json_decode(file_get_contents('php://input'), true);
$name = $inputData['period_title'];
$start_date = $inputData['start_date'];
$end_date = $inputData['end_date'];
$period_id = $inputData['period_id'];

if (getPermission($user_priviledge, 'smc') >= 2) {

    if (!empty($name) && !empty($start_date) && !empty($end_date)) {

        $pr = new Smc\Period();


        if ($pr->Update($name, $start_date, $end_date, $period_id)) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID " . $period_id . "and Title " . $inputData['period_title'] . " Start Date, " . $inputData['start_date'] . " and End Date: " . $inputData['end_date'] . " Successfully Updated", $result);
            #On User Creation
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'Visit Updated Successfully',
                'id' => $period_id
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID: " . $period_id . " failed to Update", $result);
            #On user creation failed
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Visit Update Failed'
            ));
        }
    } else {
        //Log User Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = " Visit Update failed due to wrong data input: ", $result);
        #If all data supplied are wrong
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Create Period/Visit'
    );
    echo json_encode($json_data);
}
