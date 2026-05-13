<?php

if (getPermission($user_priviledge, 'users') != 3) {
    echo json_encode([
        'result_code' => 400,
        'message' => "You don't have permission to De/Activate."
    ]);
    return;
}

$us = new Users\UserManage();
$inputData = json_decode(file_get_contents('php://input'), true);

// Extract and validate input
$authorizationUserId = (int)($inputData['authorizationUserId'] ?? 0);
$extensionHour = (int)($inputData['extensionHour'] ?? 0);
$extensionDate = $inputData['extensionDate'] ?? '';

$userIdsRaw = $inputData['bulkUserIds'] ?? '[]';
$bulkUserIds = is_string($userIdsRaw) ? json_decode($userIdsRaw, true) : $userIdsRaw;

// Validate bulkUserIds
if (!is_array($bulkUserIds)) {
    echo json_encode([
        'result_code' => 400,
        'message' => 'error: Invalid user ID data.'
    ]);
    return;
}

$totalUsers = count($bulkUserIds);
$allData = array_map(fn($userId) => [
    'userid'          => (int)$userId,
    'extension_hour'  => $extensionHour,
    'extension_date'  => $extensionDate,
    'authorized_user' => $authorizationUserId,
], $bulkUserIds);

$success = $us->BulkWorkHourExtension($allData);

$logMessage = sprintf(
    "%s to update %d user(s) Work Hour to %d on %s",
    $success ? 'Successfully' : 'Failed',
    $totalUsers,
    $extensionHour,
    $extensionDate
);

logUserActivity($current_userid, $platform, 'users', $logMessage, $success ? 'success' : 'failed');

echo json_encode([
    'result_code' => $success ? 200 : 400,
    'total'       => $success,
    'message'     => $success
        ? "success: {$totalUsers} User(s) Work Hour updated to {$extensionHour} Hours on {$extensionDate} ({$success})"
        : "error: Unable to update Work Hour for {$totalUsers} User(s) to {$extensionHour} Hours on {$extensionDate}. Please try again later. ({$success})"
]);
