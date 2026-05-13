<?php

/*
     *  Runs e-Netcard Samples
     *
     *  Netcard reverse movement from Ward to lga
     */
if (getPermission($user_priviledge, 'enetcard') == 3) {
    $nt = new Netcard\NetcardTrans();
    $total = CleanData("total");
    $lgaid = CleanData("destinationid");
    $wardid = CleanData("originid");
    $userid = CleanData("id");
    #
    $nt->WardToLgaMovement($total, $wardid, $lgaid, $userid);
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully Reversed from LGA Ward (Ward ID: $wardid) to (LGA ID: $lgaid) by user with the Login ID: " . $current_loginid . " :", $result);

    echo json_encode(array(
        'result_code' => 200,
        'dataset' => ' Netcard reverse movement from Ward to lga',
        'message' => "$total"
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to De/Activate'
    );
    echo json_encode($json_data);
}
