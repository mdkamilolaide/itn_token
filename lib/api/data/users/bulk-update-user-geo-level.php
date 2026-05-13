<?php

if (getPermission($user_priviledge, 'users') == 3) {
    $us = new Users\UserManage();
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Decode user IDs
    $userIdsRaw = $inputData['u'] ?? '[]';
    $userIds = is_string($userIdsRaw) ? json_decode($userIdsRaw, true) : $userIdsRaw;
    $geo_level = $inputData['l'] ?? '';
    $geo_level_id = $inputData['id'] ?? '';

    // Ensure userIds is an array
    if (!is_array($userIds)) {
        echo json_encode([
            'result_code' => 400,
            'message' => 'error: Invalid user ID data.'
        ]);
        return;
    }

    $totalUsers = is_array($userIds) ? count($userIds) : 0;


    $success = $us->BulkChangeGeoLocation($userIds, $geo_level, $geo_level_id);

    $logDesc = $success
        ? "Successfully updated geo level for $totalUsers user(s) to Level: $geo_level, ID: $geo_level_id"
        : "Failed to update geo level for user(s) to Level: $geo_level, ID: $geo_level_id";

    logUserActivity($current_userid, $platform, 'users', $logDesc, $success ? 'success' : 'failed');

    echo json_encode([
        'result_code' => $success ? 200 : 400,
        'message' => $success
            ? 'success: User geo level updated successfully.' . $geo_level . ' - ' . $geo_level_id
            : 'error: Unable to update the geo level at the moment. Please try again later.'
    ]);
} else {
    echo json_encode([
        'result_code' => 400,
        'message' => 'You don\'t have permission to De/Activate.'
    ]);
}
