<?php


$period_id = CleanData('period_id');

if (getPermission($user_priviledge, 'smc') >= 3) {

    $pr = new Smc\Period();


    if ($pr->Delete($period_id)) {
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID " . $period_id . " Deleted Successfully", $result);
        #On User Creation
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'Visit Deleted Successfully',
            'id' => $period_id
        ));
    } else {
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID: " . $period_id . " failed to Delete", $result);
        #On user creation failed
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'Unable to delete period at the moment, please try again later.'
        ));
    }
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Delete Period/Visit'
    );
    echo json_encode($json_data);
}
