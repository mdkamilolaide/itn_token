<?php

/*
     *  Runs e-Netcard Samples
     *
     *  Netcard movement from LGA to Ward
     */
if (getPermission($user_priviledge, 'enetcard') == 3) {
    $nt = new Netcard\NetcardTrans();
    $total = CleanData("total");
    $lgaid = CleanData("originid");
    $wardid = CleanData("destinationid");
    $userid = CleanData("id");
    #
    $nt->LgaToWardMovement($total, $lgaid, $wardid, $userid);
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully Moved from LGA (LGA ID: $lgaid) to Ward (Ward ID: $wardid) by user with the Login ID: " . $current_loginid . " :", $result);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => ' Netcard movement from LGA to Ward',
        'message' => "$total"
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
