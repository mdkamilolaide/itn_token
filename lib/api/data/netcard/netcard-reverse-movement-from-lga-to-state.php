<?php

/*
     *  Runs e-Netcard Samples
     *
     *  Netcard reverse movement from LGA to State
     */
if (getPermission($user_priviledge, 'enetcard') == 3) {

    $nt = new Netcard\NetcardTrans();
    $total = CleanData("total");
    $lgaid = CleanData("originid");
    $stateid = CleanData("destinationid");
    $userid = CleanData("id");
    #
    $nt->LgaToStateMovement($total, $lgaid, $stateid, $userid);
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully Reversed from LGA (LGA ID: $lgaid) to State (State ID: $stateid) by user with the Login ID: " . $current_loginid . " :", $result);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Netcard reverse movement from LGA to State',
        'message' => "$total"
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
