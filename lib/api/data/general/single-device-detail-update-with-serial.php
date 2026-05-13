<?php

#
#   Single update
$inputData = json_decode(file_get_contents('php://input'), true);
$ex = new System\Devices();
#
// Require an input object and an appSerial to identify the device
if (!is_array($inputData) || empty($inputData['appSerial'])) {
    echo json_encode(['result_code' => 400, 'message' => 'invalid input']);
    return;
}

$imei1 = $inputData['imeiOne'] ?? '';
$imei2 = $inputData['imeiTwo'] ?? '';
$phone_serial = $inputData['deviceSerial'] ?? '';
$sim_network = $inputData['networkType'] ?? '';
$sim_serial = $inputData['simCardSerialNo'] ?? '';
$device_serial = $inputData['appSerial']; #Device Unique identifier
#
if ($ex->UpdateDeviceWithSerial($imei1, $imei2, $phone_serial, $sim_network, $sim_serial, $device_serial)) {
    $result = 'success';
    logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with User ID: $current_userid Device with Serial Nos: $device_serial Details Successfully Updated", $result);
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => 'Device details was updated successfully'
    ));
} else {
    $result = 'failed';
    logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with User ID: $current_userid Device with Serial Nos: $device_serial Details Failed to update", $result);
    echo json_encode(array(
        'result_code' => 400,
        'message' => 'error',
        'data' => 'Error: unable to update the device details at the moment please try again later'
    ));
}
