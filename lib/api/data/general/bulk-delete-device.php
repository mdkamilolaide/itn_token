<?php

#
#   Bulk delete device 
$ex = new System\Devices();
$inputData = json_decode(file_get_contents('php://input'), true);

// Defensive: require a JSON array of serials
if (!is_array($inputData)) {
    echo json_encode(['result_code' => 400, 'message' => 'invalid input']);
    return;
}

$devices = array_values($inputData);
// $devices = array('KVZ001','OWS004','SZX006');
$total = $ex->BulkDelete($devices);
if ($total) {
    logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with ID: $current_userid , $total Device(s) with Serial Nos: [" . implode(', ', $devices) . "] Deleted successfully", $result = "sucess");
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'total' => $total
    ));
} else {
    logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with ID: $current_userid , tried to Delete $total Device(s) with Serial Nos: [" . implode(', ', $devices) . "] but failed", $result = "failed");
    echo json_encode(array(
        'result_code' => 400,
        'message' => 'error',
        'data' => 'Unable to delete device at the moment, please try again later'
    ));
}
