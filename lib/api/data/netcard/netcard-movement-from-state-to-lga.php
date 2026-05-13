<?php

/*
     *  Runs e-Netcard Samples
     *
     *  Netcard movement from state to LGA
     */
if (getPermission($user_priviledge, 'enetcard') == 3) {
    $nt = new Netcard\NetcardTrans();
    // for stock count
    $total = CleanData("total");
    $stateid = CleanData("stateid");
    $lgaid = CleanData("lgaid");
    $userid = CleanData("id");
    $nt->StateToLgaMovement($total, $stateid, $lgaid, $userid);

    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully moved from State (State ID: $stateid) to LGA (LGA ID: $lgaid) by user with the Login ID: " . $current_loginid . " :", $result);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Netcard movement from state to LGA',
        'message' => "$total"
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
