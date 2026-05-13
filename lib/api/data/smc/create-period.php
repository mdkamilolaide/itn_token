<?php

#
#   Create new Period
#

if (getPermission($user_priviledge, 'smc') >= 3) {
    $inputData = json_decode(file_get_contents('php://input'), true);

    if (!empty($inputData['period_title']) && !empty($inputData['start_date']) && !empty($inputData['end_date'])) {

        $pr = new Smc\Period();
        $name = $inputData['period_title'];
        $start_date = $inputData['start_date'];
        $end_date = $inputData['end_date'];
        #
        $id = $pr->Create($name, $start_date, $end_date);

        if ($id) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Period Successfully Created with Period ID " . $id . "and Title " . $inputData['period_title'] . " Start Date, " . $inputData['start_date'] . " and End Date: " . $inputData['end_date'], $result);
            #On User Creation
            echo json_encode(array(
                'result_code' => 201,
                'message' => 'Visit Created Successfully',
                'id' => $id
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Period was Failed to be Created with Period Title" . $inputData['period_title'] . " Start Date, " . $inputData['start_date'] . " and End Date: " . $inputData['end_date'], $result);
            #On user creation failed
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Visit Creation Failed'
            ));
        }
    } else {
        //Log User Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = " Period Creation failed due to wrong data input: ", $result);
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
