<?php

if (getPermission($user_priviledge, 'monitoring') >= 2) {

    $fm = new Monitor\Monitor();
    $data = $fm->GetFormStatusList();
    #

    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "monitoring", $description = "Monitoring tools List loaded ", $result);

    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Monitoring tool list',
        'message' => 'success',
        'data' => $data
    ));
} else {
    //Log User Activity
    $result = "failed";
    logUserActivity($userid = $current_userid, $platform, $module = "monitoring", $description = "Monitoring Toools List Failed to Load ", $result);
    #If all data supplied are wrong
    echo json_encode(array(
        'result_code' => 400,
        'message' => 'error'
    ));
}
