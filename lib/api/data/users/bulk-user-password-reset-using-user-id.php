<?php

$mg = new Users\UserManage();
$inputData = json_decode(file_get_contents('php://input'), true);

// Decode stringified array if necessary
$loginIdsRaw = $inputData['loginid'] ?? '[]';
$loginIds = is_string($loginIdsRaw) ? json_decode($loginIdsRaw, true) : $loginIdsRaw;

$newPassword = $inputData['new'] ?? '';
$totalUsers = is_array($loginIds) ? count($loginIds) : 0;

$result = $mg->BulkPasswordReset($loginIds, $newPassword);
$status = $result ? 200 : 400;
$activityResult = $result ? 'success' : 'failed';
$message = $result
    ? "Password for <b>{$totalUsers}</b> Users has been reset successfully"
    : "Unable to reset password, maybe user(s) do not exist or a system error occurred. Please try again later.";

logUserActivity(
    $userid = $current_userid,
    $platform,
    $module = "users",
    $description = $result
        ? "Password reset for {$totalUsers} user(s) was successful."
        : "Password reset for {$totalUsers} user(s) failed.",
    $activityResult
);

http_response_code($status);
echo json_encode([
    'result_code' => $status,
    'message' => $message,
]);
