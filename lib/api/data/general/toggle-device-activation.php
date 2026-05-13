<?php

#
#   Toggle Device Activation
#
$ex = new System\Devices();
$serial_no = CleanData("sn");
#
#
if ($ex->ToggleActive($serial_no)) {
    #
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with User ID: $current_userid successfully De/Activated Device with Serial Nos: $serial_no", $result);
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success'
    ));
} else {
    $result = "failed";
    logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with User ID: $current_userid tried to De/Activated Device with Serial Nos: $serial_no and failed", $result);
    echo json_encode(array(
        'result_code' => 400,
        'message' => 'error'
    ));
}
