<?php

declare(strict_types=1);

include_once('lib/autoload.php');
include("lib/config.php");
include_once('lib/common.php');

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require('lib/vendor/autoload.php');    //JWT Autoload

// --- INITIALIZATION ---
// log_system_access();
$secret_key = file_get_contents('lib/privateKey.pem');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$inputData = json_decode(file_get_contents('php://input'), true) ?? [];
$header = getallheaders();
$jwt = $header['jwt'] ?? "";
$qid = (string)CleanData("qid");

// --- HELPERS ---

/**
 * Standardized JSON Response
 */
$respond = static function (int $code, string $message, $data = null, array $extra = []): never {
    http_response_code($code);

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    echo json_encode(['result_code' => $code, 'message' => $message, 'data' => $data, ...$extra], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
};


/**
 * Logic for Logging Activity
 */
$log = function ($userid, $platform, $module, $desc, $result) use ($header) {
    $lon = $header['long'] ?? "";
    $lat = $header['lat'] ?? "";
    System\General::LogActivity($userid, $platform, $module, $desc, $result, $lon, $lat);
};

/**
 * Check Privilege Middleware
 */
$checkPriv = function ($token, $requiredModule) use ($respond, $log) {
    $privs = json_decode($token->system_privilege ?? '[]', true);
    $modules = is_array($requiredModule) ? $requiredModule : [$requiredModule];

    $hasPriv = false;
    foreach ($modules as $mod) {
        if (IsPrivilegeInArray($privs, $mod)) {
            $hasPriv = true;
            break;
        }
    }

    if (!$hasPriv) {
        $moduleDisplay = is_array($requiredModule) ? implode(' or ', array_map('ucfirst', $requiredModule)) : ucfirst($requiredModule);
        $log($token->userid, "mobile", is_array($requiredModule) ? $requiredModule[0] : $requiredModule, "Unauthorized access attempt", "failed");
        $respond(401, "Unauthorized User Privilege on $moduleDisplay Module");
    }
};

// --- ROUTE REGISTRIES ---

/**
 * PUBLIC ROUTES (No JWT Required)
 */
$publicRoutes = [
    // Login via Credentials
    '010' => function () use ($header, $respond, $log, $secret_key, $issuer_claim, $config_pre_append_link, $issuedat_claim, $expire_claim) {
        $login = new Users\Login();
        $login->SetLoginId($header['loginid'] ?? "", $header['password'] ?? "");
        $device_serial = $header['device_serial'] ?? "";

        if ($login->RunLogin($device_serial)) {
            $loginData = $login->GetLoginData();
            $usr = new Users\UserManage();
            $loginData['work_hour_data'] = $usr->GetUserWorkingHours($loginData['userid']);

            $payload = [
                "iss" => $issuer_claim,
                "aud" => $config_pre_append_link,
                "iat" => $issuedat_claim->getTimestamp(),
                "nbf" => $issuedat_claim->getTimestamp(),
                "exp" => $expire_claim,
                "loginId" => $loginData['loginid'],
                "userid" => $loginData['userid'],
                "guid" => $loginData['guid'],
                "geo_level" => $loginData['geo_level'],
                "geo_level_id" => $loginData['geo_level_id'],
                "geo_value" => $loginData['geo_value'],
                "system_privilege" => $loginData['system_privilege'],
                "platform" => $loginData['platform'],
                "priority" => $loginData['priority'],
                "device_serial" => $device_serial
            ];

            $jwt = JWT::encode($payload, $secret_key, 'HS512');
            $log($loginData['userid'], 'mobile', "login", $loginData['fullname'] . " Logged In", "success");
            $respond(200, "User Successfully login.", $loginData, ["jwt" => $jwt]);
        }
        $log(0, "mobile", "login", "Failed login for " . ($header['loginid'] ?? ""), "failed");
        $respond(400, $login->LastError);
    },

    // Login via Badge
    '011' => function () use ($header, $respond, $log, $secret_key, $issuer_claim, $config_pre_append_link, $issuedat_claim, $expire_claim) {
        $login = new Users\Login('badge');
        $login->SetBadge($header['badge_data'] ?? "");
        $device_serial = $header['device_serial'] ?? "";

        if ($login->RunLogin($device_serial)) {
            $loginData = $login->GetLoginData();

            #   Get User working hours by days
            $usr = new Users\UserManage();
            $loginData['work_hour_data'] = $usr->GetUserWorkingHours($loginData['userid']);


            $payload = [
                "iss" => $issuer_claim,
                "aud" => $config_pre_append_link,
                "iat" => $issuedat_claim->getTimestamp(),
                "nbf" => $issuedat_claim->getTimestamp(),
                "exp" => $expire_claim,
                "loginId" => $loginData['loginid'],
                "userid" => $loginData['userid'],
                "guid" => $loginData['guid'],

                "geo_level" => $loginData['geo_level'],
                "geo_level_id" => $loginData['geo_level_id'],
                "geo_value" => $loginData['geo_value'],
                "system_privilege" => $loginData['system_privilege'],
                "platform" => $loginData['platform'],
                "priority" => $loginData['priority'],
                "device_serial" => $device_serial
            ];
            $jwt = JWT::encode($payload, $secret_key, 'HS512');

            $log($loginData['userid'], 'pos', "login", $loginData['fullname'] . ' (' . $loginData['loginid'] . ') Successfully Logged In using badge', "success");
            $respond(200, "User Successfully login.", $loginData, ["jwt" => $jwt]);
        }

        $badge_data = $header['badge_data'] ?? "";
        $log(0, "pos", "login", "An Uknown User is trying to access the POS platform using a wrong Badge details; Badge Data:{$badge_data}; {$login->LastError}", "failed");
        $respond(400, $login->LastError);
    },

    // Device Management
    '501' => function () use ($header, $respond, $log) {
        $ex = new System\Devices();
        $device_data = $ex->RegisterDevice($header['device_name'], $header['device_id'], $header['device_type']);
        if (is_array($device_data)) {
            // $log(0, $header['device_type'], "Device Management", "Registration success", "success");
            $extra = ['dataset' => 'Device Registered'];
            $respond(200, "success", $device_data, $extra);
        }
        $extra = ['device_id' => $header['device_id'], 'dataset' => 'Check Device'];
        $respond(200, "success", $ex->CheckDevice($header['device_id']), $extra);
    },

    //Check Device
    '503' => function () use ($respond, $inputData) {
        $ex = new System\Devices();
        $device_data = $ex->CheckDevice($inputData['device_id']);

        $extra = ['device_id' => $inputData['device_id'], 'dataset' => 'Check Device'];
        $respond(200, "success", $device_data, $extra);
    },
];

/**
 * PRIVATE ROUTES (JWT Required)
 */
$privateRoutes = [
    // --- USER MODULE ---
    '012' => function ($token) use ($respond, $checkPriv) {
        $checkPriv($token, 'users');
        $usr = new Users\UserManage();
        $data = $usr->ListUserFull();
        $data ? $respond(200, "success", $data) : $respond(400, "Failed to Download");
    },

    //Bulk User Update
    '006' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'users');
        $usr = new Users\UserManage();
        $total = $usr->BulkUserUpdate($inputData);
        $log($token->userid, "mobile", "users", "Bulk update success", "success");
        $respond(200, "success", null, ["total" => $total]);
    },

    //Get role list
    '007' => function ($token) use ($respond, $checkPriv) {
        $priority = $token->priority ?? 1;
        $checkPriv($token, 'users');
        $usr = new Users\UserManage();
        $data = $usr->GetRoleList($priority);
        $data ? $respond(200, "success", $data) : $respond(400, "Failed to download role list");
    },

    //update user role
    '008' => function ($token) use ($respond, $checkPriv, $header, $log) {
        $checkPriv($token, 'users');
        $usr = new Users\UserManage();
        $data = $usr->UpdateUserRole($header['role_id'], $header['user_id']);
        $log($token->userid, "mobile", "users", "User with the User ID: " . $header['user_id'] . " Role Successfully Updated to Role ID: " . $header['role_id'], "success");
        $data ? $respond(200, "success", $data) : $respond(400, "Failed to update user role");
    },

    //Run User bank Account Validation
    '009' => function ($token) use ($respond, $inputData, $log) {
        // $checkPriv($token, 'users');
        $usr = new Users\UserManage();
        $userid = $inputData['user_id'] ?? "";
        $data = $usr->RunBankVerification($userid);
        $log($token->userid, "mobile", "users", "User with the User ID: " . $userid . " Account Verification done", "success");
        $data ? $respond(200, "Verification Done on User with ID " . $userid, $data) : $respond(400, "Failed to run user bank account validation");
    },

    //User FCM Register
    '013' => function ($token) use ($respond, $inputData, $log) {
        $us = new Users\UserManage();
        $userid = $inputData["userid"] ?? "";
        $device_serial = $inputData["device_serial"] ?? "";
        $fcm_token = $inputData["fcm_token"] ?? "";

        $data = $us->RegisterUserFcm($userid, $device_serial, $fcm_token);
        $log($token->userid, "pos|mobile", "users", "User with the User ID: " . $token->userid . " updated its firebase token", "success");
        $respond(200, "success", $data);
    },

    /**
     * User Modules
     * Ends
     * **********************************************************************************
     */


    /**
     * Training Modules 
     * Begin
     * ********************************************************************************
     */

    //Get generic Training list
    '100' => function ($token) use ($respond, $inputData) {
        $tr = new Training\Training();
        $data = $tr->getGenericTraining($inputData['geo_level'] ?? "", $inputData['geo_level_id'] ?? "");
        $respond(200, "success", $data, ['dataset' => 'Generic Activity List']);
    },

    //Get generic Training Session list
    '101' => function ($token) use ($respond, $header) {
        $tr = new Training\Training();
        $data = $tr->getGenericSession($header['training_id'] ?? "");
        $respond(200, "success", $data, ['dataset' => 'Generic Session List']);
    },

    //Get participants list for a particular training
    '102' => function ($token) use ($respond, $header, $log) {
        $tr = new Training\Training();
        $data = $tr->getParticipantsList($header['training_id'] ?? "", $header['geo_level'] ?? "", $header['geo_level_id'] ?? "");
        $log($token->userid, "mobile", "Participants Download", "User with the User ID: " . $token->userid . " Download Participant for training with ID " . ($header['training_id'] ?? ""), "success");
        $respond(200, "success", $data, ['dataset' => 'Participants List']);
    },

    //Add attendance bulk
    '103' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'activity');
        $tr = new Training\Training();
        $participant_ids = implode(',', array_column($inputData, 'participant_id'));
        $total = $tr->AddAttendancebulk($inputData);
        if ($total) {
            $log($token->userid, "mobile", "training", "$total Users attendance was Successfully Taken and Updated ($participant_ids)", "success");
            $respond(200, "Total $total attendance was uploaded successfully", null, ["total" => $total]);
        }
        $respond(400, "{$total} Users attendace Update Failed to be updated (" . $participant_ids . ") by user with the Login ID: " . $token->userid);
    },


    /**
     * Netcard Allocation Modules
     * Begin
     * ********************************************************************************
     */

    //List count Mobilizer balances
    '201' => function ($token) use ($respond, $checkPriv, $inputData, $log) {

        $nt = new Netcard\NetcardTrans();
        $total = $nt->GetMobilizersList($inputData['wardid'] ?? "");
        $respond(200, "success", $total, ['dataset' => 'e-Netcard Mobilizers List']);
    },


    //Bulk e-Netcard Allocation Ward to mobilizer
    '202' => function ($token) use ($respond, $checkPriv, $inputData) {
        $checkPriv($token, 'allocation');
        $nt = new Netcard\NetcardTrans();
        $total = $nt->BulkAllocationTransfer($inputData);
        $respond(200, "e-Netcards allocation transfer has been performed from Ward to HH Mobilizers successfully", null, ['total' => $total]);
    },

    //List count Allocation mobile app balances
    '203' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $wardid = CleanData("wardid") ?? $inputData['wardid'];
        $data = $nt->CombinedBalanceForApp($wardid);
        $respond(200, "success", $data, ['dataset' => 'e-Netcard Allocation Mobile App Balances']);
    },

    //List count Allocation mobile app transaction list
    '204' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $wardid = CleanData("wardid") ?? $inputData['wardid'];
        $data = $nt->GetAllocationTransferHistoryList($wardid);
        $respond(200, "success", $data, ['dataset' => 'e-Netcard Allocation Mobile App Transaction list']);
    },

    //List count Allocation mobile app reverse order history list
    '205' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $wardid = CleanData("wardid") ?? $inputData['wardid'];
        $data = $nt->GetAllocationReverseHistoryList($wardid);
        $respond(200, "success", $data, ['dataset' => 'e-Netcard Allocation Mobile App reverse order history list']);
    },

    //Online reverse transaction history
    '206' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $wardid = CleanData("wardid") ?? $inputData['wardid'];
        $data = $nt->GetAllocationDirectReverseList($wardid);
        $respond(200, "success", $data, ['dataset' => 'e-Netcard Online Revere history list']);
    },

    //Netcard Allocation reverse order
    '207' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'allocation');
        $nt = new Netcard\NetcardTrans();
        $order_total = $inputData['total'] ?? 0;
        $mobilizerid = $inputData['mobilizerid'] ?? "";
        $wardid = CleanData("wardid") ?? $inputData['wardid'];
        $device_serial = $inputData['device_serial'] ?? "";

        if ($nt->ReverseAllocationOrder($mobilizerid, $token->userid, $order_total, $device_serial)) {
            $log($token->userid, "mobile", "enetcard", "{$order_total} e-Netcard Reversed Order Successfully placed to be retracted from Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "'", "success");
            $respond(200, "e-Netcard reversal order has been placed successfully", null, ['total' => $order_total]);
        }
        $log($token->userid, "mobile", "enetcard", "{$order_total} e-Netcard Reversed Order Failed to be placed to Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "'", "failed");
        $respond(400, "Unable to place e-Netcard reversal order at the moment, please try again later");
    },

    //e-Netcard Get combined mobilizer's balance (Without Duplicate)
    '208' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $wardid = $inputData['wardid'] ?? "";
        $data = $nt->GetCombinedMobilizerBalance($wardid);
        $respond(200, "success", $data, ['dataset' => 'Combined Mobilizers Balance List']);
    },

    //Netcard Online reverse back to ward
    '209' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'allocation');
        $nt = new Netcard\NetcardTrans();
        $total = $inputData['total'] ?? 0;
        $mobilizerid = $inputData['mobilizerid'] ?? "";
        $total_reverse = $nt->DirectReverseAllocation($total, $mobilizerid, $token->userid);

        if ($total_reverse > 0) {
            $log($token->userid, "mobile", "enetcard", "{$total_reverse} e-Netcard Reversed Successfully placed to be retracted from Household Mobilizers '" . $mobilizerid . "'", "success");
            $respond(200, "e-Netcard reversal successful", null, ['total' => $total_reverse]);
        } elseif ($total_reverse === 0) {
            $log($token->userid, "mobile", "enetcard", "{$total} e-Netcard Reversed Failed to be placed to Household Mobilizers '" . $mobilizerid . "'", "failed");
            $respond(400, "Unable to place reversal-Netcard at the moment, please try again later", null, ['total' => 0]);
        } else {
            $log($token->userid, "mobile", "enetcard", "{$total} e-Netcard Reversed Failed to be placed to Household Mobilizers '" . $mobilizerid . "'", "failed");
            $respond(400, "Unable to place reversal-Netcard at the moment, please try again later", null, ['total' => 0]);
        }
    },

    //e-Netcard Get combined mobilizer's balance With Pending
    '210' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $wardid = $inputData['wardid'] ?? "";
        $data = $nt->GetMobilizerBalanceBySupervisor($wardid);
        $respond(200, "success", $data, ['dataset' => 'New Combined Mobilizers Balance List']);
    },

    /**
     * Netcard Allocation Modules
     * Ends
     * **********************************************************************************
     */

    /**
     * Mobilization Modules 
     * Begin
     * ********************************************************************************
     */

    //Get Communities list by Ward ID
    '300a' => function ($token) use ($respond, $inputData) {
        $us = new System\General();
        $wardid = $inputData['wardid'] ?? "";
        $data = $us->GetCommunityListByWard($wardid);
        $respond(200, "success", $data, ['dataset' => 'Community list in a Ward']);
    },

    //Get Communities list by DP ID
    '300b' => function ($token) use ($respond, $inputData) {
        $us = new System\General();
        $dpid = $inputData['dpid'] ?? "";
        $data = $us->GetCommunityList($dpid);
        $respond(200, "success", $data, ['dataset' => 'Community list in DP']);
    },

    //Get DP list
    '301' => function ($token) use ($respond, $inputData) {
        $us = new System\General();
        $wardid = $inputData['wardid'] ?? "";
        $data = $us->GetDpList($wardid);
        $respond(200, "success", $data, ['dataset' => 'DP list in a ward']);
    },

    //Download e-netcard
    '302' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'mobilization');
        $us = new Mobilization\Mobilization();
        $mobilizerid = $inputData['mobilizerid'] ?? "";
        $device_serial = $inputData['device_serial'] ?? "";
        $data = $us->DownloadEnetcard($mobilizerid, $device_serial);
        $log($mobilizerid, "pos", "Wallet Download", "e-Netcard Successfully downloaded by Household Mobilizer with ID {$mobilizerid}", "success");
        $respond(200, "success", $data, ['dataset' => 'Download e-Netcard (Precious payload) on this device with device serial ' . ($inputData['device_serial'] ?? "") . ' Mobilizer ID: ' . $mobilizerid]);
    },

    //Check for Pending Reverse Order
    '303' => function ($token) use ($respond, $inputData) {
        $us = new Mobilization\Mobilization();
        $mobilizerid = $inputData['mobilizerid'] ?? "";
        $device_serial = $inputData['device_serial'] ?? "";
        $data = $us->GetPendingReverseOrder($mobilizerid, $device_serial);
        $respond(200, "success", $data, ['dataset' => "Get pending reverse order from Mobilizer with ID {$mobilizerid} and Device Serial {$device_serial}"]);
    },

    //Generate e-Token list
    '304' => function ($token) use ($respond, $checkPriv, $inputData) {
        $checkPriv($token, ['mobilization', 'smc']);

        $device_id = $inputData['device_id'] ?? "";
        $total = $inputData['total'] ?? 0;
        $tk = new Netcard\Etoken($device_id, $total);
        $data = $tk->Generate();
        $respond(200, "success", $data, ['dataset' => 'Generate e-Token']);
    },

    //Bulk Posting mobilization data
    '305' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'mobilization');
        $mo = new Mobilization\Mobilization();
        $total = $mo->BulkMobilization($inputData);
        if ($total) {
            $log($token->userid, "pos", "mobilization sync", "User with the User ID: {$token->userid} Synchronize {$total} Mobilization data ", "success");
            $respond(200, "success", $inputData, ['dataset' => "Total {$total} bulk mobilization has been submitted successfully", 'total' => $inputData]);
        }
        $log($token->userid, "pos", "mobilization sync", "User with the User ID: {$token->userid} tried to Synchronize Mobilization data and failed ", "failed");
        $respond(400, "Unable to submit the mobilization bulk data", 0, ['total' => 0]);
    },

    //Get balances HH Mobilizer
    '306' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $userid = $inputData['mobilizerid'] ?? "";
        $data = $nt->ThisCountHHMobilizerBalance($userid);
        $respond(200, "success", $data, ['dataset' => 'HH Mobilizer by userid Balance']);
    },

    //Netcard Allocation reverse order fulfilment
    '307' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'mobilization');
        $nt = new Netcard\NetcardTrans();
        $orderid = $inputData['orderid'] ?? "";
        $netcard_list = $inputData['netcards'] ?? [];
        $mobilizerid = $inputData['mobilizerid'] ?? "";
        $wardid = $inputData['wardid'] ?? "";
        $userid = $inputData['userid'] ?? "";
        $total = $nt->HHMobilizerToWardFulfulment($orderid, $netcard_list, $mobilizerid, $wardid, $userid);
        $respond(200, "success", $total, ['dataset' => "$total e-Netcard was fulfilled successfully", 'total' => $total]);
    },

    //e-Netcard Mobilizer push netcard online
    '308' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'mobilization');
        $nt = new Netcard\NetcardTrans();
        $netcard_list = $inputData['netcards'] ?? [];
        $hhm_id = $inputData['mobilizerid'] ?? "";
        $device_serial = $inputData['device_serial'] ?? "";
        $total = $nt->PushNetcardOnline($netcard_list, $hhm_id, $device_serial);
        $log($token->userid, "pos", "mobilization", "User with the User ID: {$token->userid}  push {$total} e-Netcard Online", "success");
        $respond(200, "success", $total, ['dataset' => "{$total} e-Netcard was successfully pushed back to the ward", 'total' => $total]);
    },

    //Mobilizer confirm download
    '309' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'mobilization');
        $us = new Mobilization\Mobilization();
        $download_id = $inputData['download_id'] ?? "";
        $hhm_id = $inputData['mobilizerid'] ?? "";
        $device_serial = $inputData['device_serial'] ?? "";

        if (empty($download_id) || empty($hhm_id) || empty($device_serial)) {
            $log($token->userid, "pos", "mobilization", "User with the User ID: {$token->userid} - ({$hhm_id}) - {$device_serial} confirm eNetcard download with Download ID:{$download_id}", "failed");
            $respond(400, "Invalid request", 0, ['dataset' => "Missing required fields: download_id, mobilizerid, or device_serial"]);
        }

        $confirm_result = $us->ConfirmDownload($hhm_id, $device_serial, $download_id);
        $confirm_result['download_id'] = $download_id;

        $log($token->userid, "pos", "mobilization", "User with the User ID: {$token->userid} confirm eNetcard download with Download ID:{$download_id}", "success");
        $respond(200, "success", $confirm_result, ['dataset' => "e-Netcard with Download ID: {$download_id} confirmation was successfully"]);
    },



    /**
     * Mobilization Modules
     * Ends
     * **********************************************************************************
     */

    /**
     * Netcard Movement Modules 
     * Begin
     * ********************************************************************************
     */

    //Get LGA e-Netcard movement mobile app dashboard balances
    '700' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $lgaid = $inputData['lgaid'] ?? "";
        $lgaBalances = $nt->GetMovementDashboardBalances($lgaid);
        $lgaMovementTopHistory = $nt->GetMovementTopHistory($lgaid);
        $data = [
            "lga_balances" => $lgaBalances,
            "lga_movement_top_history" => $lgaMovementTopHistory
        ];
        $respond(200, "success", $data, ['dataset' => 'LGA Movement Dashboard balances and TOp 5 Histories']);
    },

    //Get LGA movement History Lists
    '701' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $lgaid = $inputData['lgaid'] ?? "";
        $data = $nt->GetMovementListHistory($lgaid);
        $respond(200, "success", $data, ['dataset' => 'LGA Movement mobile app dashboard balances']);
    },

    //Get Ward List and e-Netcard balances
    '702' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $lgaid = $inputData['lgaid'] ?? "";
        $data = $nt->GetWardListAndBalances($lgaid);
        $respond(200, "success", $data, ['dataset' => 'Ward List and their e-Netcard balances']);
    },

    //Get mobilizers balances per Ward Level
    '703' => function ($token) use ($respond, $inputData) {
        $nt = new Netcard\NetcardTrans();
        $lgaid = $inputData['lgaid'] ?? "";
        $data = $nt->GetWardLevelMobilizersBalances($lgaid);
        $respond(200, "success", $data, ['dataset' => 'Get mobilizers balances per Ward Level']);
    },

    //Netcard movement from LGA to Ward (A2)
    '704' => function ($token) use ($respond, $inputData, $log) {
        $nt = new Netcard\NetcardTrans();
        $total = $inputData['total'] ?? 0;
        $lgaid = $inputData['originatingLgaid'] ?? "";
        $wardid = $inputData['destinationWardid'] ?? "";
        $userid = $token->userid ?? $inputData['userid'];

        $result = $nt->LgaToWardMovement($total, $lgaid, $wardid, $userid);
        if ($result > 0) {
            $log($token->userid, "mobile", "enetcard", "User with the User ID: " . $token->userid . " Moved " . $result . " eNetcard to a ward ", "success");
            $respond(200, "success", ["total" => $result], ['dataset' => 'Netcard movement from LGA to Ward Successful', 'all_result' => $result]);
        } elseif ($result === 0) {
            $log($token->userid, "mobile", "enetcard", "User with the User ID: " . $token->userid . " Tried to move " . $total . " eNetcard to a ward but failed due to insufficient balance", "failed");
            $respond(400, "Insufficient eNetcard balance in the source location", ["total" => 0], ['dataset' => 'Netcard movement from LGA to Ward Failed due to insufficient balance', 'all_result' => $result]);
        } else {
            $log($token->userid, "mobile", "enetcard", "User with the User ID: " . $token->userid . " Tried to move " . $total . " eNetcard to a ward and failed due to technical error", "failed");
            $respond(400, "failed", ["total" => 0], ['dataset' => 'Netcard movement from LGA to Ward Failed due to technical error', 'all_result' => $result]);
        }
    },

    //Netcard reverse movement from Ward to lga (A5)
    '705' => function ($token) use ($respond, $inputData, $log) {
        $nt = new Netcard\NetcardTrans();
        $total = $inputData['total'] ?? 0;
        $lgaid = $inputData['destinationLgaid'] ?? "";
        $wardid = $inputData['originatingWardid'] ?? "";
        $userid = $token->userid;

        $result = $nt->WardToLgaMovement($total, $wardid, $lgaid, $userid);
        if ($result > 0) {
            $log($token->userid, "mobile", "enetcard", "User with the User ID: " . $token->userid . " successfully reversed " . $result . " eNetcard to LGA with ID: " . $lgaid, "success");
            $respond(200, "success", ["total" => $result], ['dataset' => 'Netcard reversal from Ward to LGA Successful', 'all_result' => $result]);
        } elseif ($result === 0) {
            $log($token->userid, "mobile", "enetcard", "User with the User ID: " . $token->userid . " Tried to reverse " . $total . " eNetcard to LGA with ID: " . $lgaid . " and failed due to insufficient balance", "failed");
            $respond(400, "Insufficient eNetcard balance in the source location", ["total" => 0], ['dataset' => 'Netcard reversal from Ward to LGA Failed due to insufficient balance', 'all_result' => $result]);
        } else {
            $log($token->userid, "mobile", "enetcard", "User with the User ID: " . $token->userid . " Tried to reverse " . $total . " eNetcard to LGA with ID: " . $lgaid . " and failed due to technical error", "failed");
            $respond(400, "failed", ["total" => 0], ['dataset' => 'Netcard reversal from Ward to LGA Failed due to technical error', 'all_result' => $result]);
        }
    },


    //Dashboard summary with options
    'gen005' => function ($token) use ($respond, $inputData) {
        $mob_date = CleanData('mdt');
        $geo_level = CleanData('gl');
        $geo_level_id = CleanData('glid');
        $ex = new Mobilization\Mobilization();
        $total = $ex->DashSummary($mob_date, $geo_level, $geo_level_id);
        $respond(200, "success", "Dashboard summary", ['dataset' => 'Dashboard summary', 'total' => $total]);
    },

    /**
     * Netcard Movement Modules
     * Ends
     * **********************************************************************************
     */

    /**
     * Distribution Modules 
     * Begin
     * ********************************************************************************
     */

    //Download Mobilization Data
    '401' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'distribution');
        $ex = new Distribution\Distribution();
        $dpid = $inputData['dpid'] ?? "";
        $data = $ex->DownloadMobilizationData($dpid);
        $log($token->userid, "pos", "Distribution Master", "User with the User ID: " . $token->userid . " Download Mobilization data ", "success");
        $respond(200, "success", $data, ['dataset' => 'Download Mobilization Dataset for distribution']);
    },

    //Distribution Bulk distribution data upload
    '402' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'distribution');
        $ex = new Distribution\Distribution();
        $result = $ex->BulkDistibutionStatus($inputData);

        if ($result['success'] || $result['fail']) {
            $log($token->userid, "pos", "Distribution Sync", "User with the User ID: " . $token->userid . " Synchronize " . $result['success'] . " Distribution data ", "success");
            $respond(200, "success", "", ['dataset' => 'Total ' . $result['success'] . ' distribution data uploaded successfully', 'total' => $result['success']]);
        }
        $respond(400, "Unable to upload bulk distribution data at the moment", $result['success'] ?? 0, ['dataset' => "Unable to upload bulk distribution data at the moment", 'total' => $result['success'] ?? 0]);
    },

    // Bulk Distribution Data Upload with returning e-token ID
    '402a' => function ($token) use ($respond, $checkPriv, $inputData, $log) {

        $checkPriv($token, 'distribution');

        if (empty($inputData) || !is_array($inputData)) {
            $respond(400, 'Bad Data Request', [], ['dataset' => [], 'total' => 0]);
        }

        $ex = new Distribution\Distribution();
        // Process distribution
        $resultData = $ex->BulkDistibutionWithReturns($inputData);

        // Merge & deduplicate once
        $success = $resultData['success'] ?? [];
        $failed  = $resultData['failed']  ?? [];

        $etokenSerialSet = array_keys(
            array_flip([...($success ?? []), ...($failed ?? [])])
        );

        $total = count($etokenSerialSet);

        if ($total > 0) {

            $log($token->userid, 'pos', 'Distribution Sync', "User ID {$token->userid} synchronized {$total} distribution data", 'success');

            $respond(200, "{$total} distribution data uploaded successfully", "", ['dataset' => $etokenSerialSet, 'total' => $total]);
        }

        $respond(400, 'Unable to upload bulk distribution data at the moment', [], ['dataset' => [], 'total' => 0]);
    },

    //Get DP Locations details with DP ID
    '403' => function ($token) use ($respond, $inputData) {
        $ex = new Distribution\Distribution();
        $guid = $inputData['guid'] ?? "";
        $data = $ex->GetGeoCodexDetails($guid);
        $respond(200, "success", $data, ['dataset' => 'Get Geo location codex by guid']);
    },

    /**
     * Distribution Modules
     * Ends
     * **********************************************************************************
     */

    /**
     * Traceability Search Modules 
     * Begin
     * ********************************************************************************
     */

    '600' => function ($token) use ($respond, $inputData) {
        $gtin = $inputData['gtin'] ?? "";
        $sgtin = $inputData['sgtin'] ?? "";
        $ex = new Distribution\GsVerification();
        $data = $ex->TraceabilitySearch($gtin, $sgtin);
        $respond(200, "success", $data, ['dataset' => 'Get Geo location codex by guid']);
    },

    /**
     * Traceability Search Modules
     * Ends
     * **********************************************************************************
     */

    /**
     * SMC MODULES 
     * Begin
     * ********************************************************************************
     */

    //Create Bulk Parent Record
    '900' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');

        if (empty($inputData)) {
            $respond(400, 'Bad Data Request', [], ['dataset' => [], 'total' => 0]);
        }

        $hh = new Smc\Registration();
        $data_result = $hh->CreateHouseholdBulk($inputData);
        $total = count($data_result);
        if ($total > 0) {
            $log($token->userid, "mobile", "smc", "$total Households was Successfully Registered", "success");
            $respond(200, "Total of $total Household was created successfully", $data_result, ["dataset" => $data_result, "total" => $total]);
        }
        $log($token->userid, "mobile", "smc", "Household registration failed", "failed");
        $respond(400, "Unable to Create Household at the moment.", $data_result, ["dataset" => $data_result, "total" => 0]);
    },

    //Update Parent Bulk Record
    '901' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, 'Bad Data Request', [], ['dataset' => [], 'total' => 0]);
        }

        $hh = new Smc\Registration();
        $data_result = $hh->UpdateHouseholdBulk($inputData);
        $total = count($data_result);
        if ($total > 0) {
            $log($token->userid, "mobile", "smc", "$total Households was Successfully Updated", "success");
            $respond(200, "Total of $total Household was updated successfully", $data_result, ["dataset" => $data_result, "total" => $total]);
        }
        $log($token->userid, "mobile", "smc", "Household update failed", "failed");
        $respond(400, "Unable to Update Household at the moment.", $data_result, ["dataset" => $data_result, "total" => 0]);
    },

    //Create Bulk Child Record
    '902' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');

        if (empty($inputData)) {
            $respond(400, 'Bad Data Request', [], ['dataset' => [], 'total' => 0]);
        }

        $hh = new Smc\Registration();
        $data_result = $hh->CreateChildBulk($inputData);
        $total = count($data_result);
        if ($total > 0) {
            $log($token->userid, "mobile", "smc", "$total Child was Successfully Registered", "success");
            $respond(200, "Total of $total Child Record was created successfully", $data_result, ["dataset" => $data_result, "total" => $total]);
        }
        $log($token->userid, "mobile", "smc", "Child registration failed", "failed");
        $respond(400, "Unable to Create Child Record at the moment.", $data_result, ["dataset" => $data_result, "total" => 0]);
    },

    //Update Bulk Child Record
    '903' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');

        if (empty($inputData)) {
            $respond(400, 'Bad Data Request', [], ['dataset' => [], 'total' => 0]);
        }

        $hh = new Smc\Registration();
        $data_result = $hh->UpdateChildBulk($inputData);
        $total = count($data_result);
        if ($total > 0) {
            $log($token->userid, "mobile", "smc", "$total Child was Successfully Updated", "success");
            $respond(200, "Total of $total Household was updated successfully", $data_result, ["dataset" => $data_result, "total" => $total]);
        }
        $log($token->userid, "mobile", "smc", "Child update failed", "failed");
        $respond(400, "Unable to Update Child Record at the moment.", $data_result, ["dataset" => $data_result, "total" => 0]);
    },

    //Drug Administration
    '904' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\DrugAdmin();
        $data_result = $hh->BulkSave($inputData);
        $total = count($data_result);
        if ($total > 0) {
            $log($token->userid, "mobile", "smc", "$total Drugs was Successfully Administered", "success");
            $respond(200, "Total of $total Drug Administered successfully Saved", $data_result, ["dataset" => $data_result, "total" => $total]);
        }
        $log($token->userid, "mobile", "smc", "Drug administration failed", "failed");
        $respond(400, "Unable to Save Drug Data at the moment.", $data_result, ["dataset" => $data_result, "total" => 0]);
    },

    //Update Drug Administration (Redose)
    '904b' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\DrugAdmin();
        $data_result = $hh->BulkRedose($inputData);
        $total = count($data_result);
        if ($total > 0) {
            $log($token->userid, "mobile", "smc", "$total Drugs Redose was Successfully Updated", "success");
            $respond(200, "Total of $total Drug Redose successfully Updated", $data_result, ["dataset" => $data_result, "total" => $total]);
        }
        $log($token->userid, "mobile", "smc", "Drug redose failed", "failed");
        $respond(400, "Unable to Save Drug Redose Data at the moment.", $data_result, ["dataset" => $data_result, "total" => 0]);
    },

    //ICC - Issue: Inventory Control Administration (ICC)
    '905' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $ic = new Smc\Icc();
        $data_result = $ic->BulkIccIssue($inputData);
        $data_result = $data_result !== false ? $data_result : [];
        $total = count($data_result);
        $log($token->userid, "mobile", "smc", "$total ICC Record Successfully uploaded", "success");
        $respond(200, "Total of $total ICC Issued Record successfully Uploaded", $data_result, ["dataset" => $data_result, "total" => $total]);
    },

    //ICC - Receive: Inventory Control Administration (ICC)
    '906' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $ic = new Smc\Icc();
        $data_result = $ic->BulkIccReceive($inputData);
        $data_result = $data_result !== false ? $data_result : [];
        $total = count($data_result);
        $log($token->userid, "mobile", "smc", "$total ICC Record Successfully uploaded", "success");
        $respond(200, "Total of $total ICC Received Record successfully Uploaded", $data_result, ["dataset" => $data_result, "total" => $total]);
    },

    //Get ICC Administration Record List using dpid
    '907' => function ($token) use ($respond, $inputData) {
        if (empty($inputData)) {
            $respond(400, 'Bad Data Request', [], ['dataset' => []]);
        }
        $us = new Smc\Icc();
        $dpid = $inputData['dpid'] ?? "";
        $data = $us->GetAdministrationRecord($dpid);
        $respond(200, "success", $data, ['dataset' => 'ICC Administartion Record List using DP ID']);
    },

    //Bulk Update Drug Referrer attended to
    '908' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, 'Bad Data Request', [], ['dataset' => []]);
        }
        $hh = new Smc\Icc();
        $data_result = $hh->BulkSaveReferrer($inputData);
        $data_result = $data_result !== false ? $data_result : [];
        $total = count($data_result);
        $log($token->userid, "mobile", "smc", "$total Child Referrer recorded Successfully Uploaded", "success");
        $respond(200, "Total of $total Child Referrer Record successfully Uploaded", $data_result, ["dataset" => $data_result, "total" => $total]);
    },

    //Download ICC Balance
    '909' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Icc();
        $period_id = $inputData['periodid'] ?? "";
        $cddid = $inputData['cddid'] ?? "";
        $device_id = $inputData['device_id'] ?? "";
        $app_version = $inputData['app_version'] ?? "";
        $d = $hh->IccDownloadBalance($period_id, $cddid, $device_id, $app_version);
        $data = $d != false ? $d : [];
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Download ICC Balance on device ID" . $device_id . " (" . json_encode($d) . ")", "success");
        $respond(200, "success", $data, ['dataset' => 'Download ICC Balance']);
    },

    //ICC Download Confirmation
    '909a' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "failed"]);
        }
        $hh = new Smc\Icc();
        $download_id = $inputData['download_id'] ?? null;
        $cdd_lead_id = $inputData['cddid'] ?? null;
        $issue_id = $inputData['issue_id'] ?? null;
        $data = [
            'status' => $hh->ConfirmDownload($download_id, $cdd_lead_id, $issue_id),
            'download_id' => $download_id,
            'issue_id' => $issue_id,
            'cddid' => $cdd_lead_id
        ];
        $log($token->userid, "mobile", "smc", "User with the User ID: " . ($cdd_lead_id ?? $token->userid) . " Confirmed the download of an ICC with issue ID:" . $issue_id, "success");
        $respond(200, "success", $data, ['dataset' => 'ICC Downloaded Accepted']);
    },

    //ICC Download Acceptance Confirmation
    '909b' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Icc();
        $issue_id = $inputData['issue_id'] ?? null;
        $status = $hh->AcceptanceAccept($issue_id);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Accepted the download of an ICC with issue ID:" . $issue_id, "success");
        $respond(200, "success", ['status' => $status, 'issue_id' => $issue_id], ['dataset' => 'ICC Downloaded Confirmed']);
    },

    //ICC Rejection Confirmation
    '909c' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Icc();
        $issue_id = $inputData['issue_id'] ?? null;
        $reasons = $inputData['reasons'] ?? "";
        $status = $hh->AcceptanceReject($issue_id, $reasons);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Reject the download of an ICC with issue ID:" . $issue_id, "success");
        $respond(200, "success", ['status' => $status, 'issue_id' => $issue_id], ['dataset' => 'ICC Downloaded Rejection Successful']);
    },

    //Get ICC Reconcilation Data
    '910' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Icc();
        $dpid = $inputData['dpid'] ?? "";
        $periodid = $inputData['periodid'] ?? "";
        $data = $hh->GetReconciliationMaster($periodid, $dpid);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Download ICC Reconcilation Data", "success");
        $respond(200, "success", $data, ['dataset' => 'Get ICC Reconcilation Data']);
    },

    //Bulk ICC Reconcile Upload
    '911' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Icc();
        $data_result = is_array($result = $hh->BulkSaveRconciliation($inputData)) ? $result : [];


        $total = count($data_result);

        $log($token->userid, "mobile", "smc", "{$total} Drug Balances reconcile Successfully Uploaded by user with the Login ID: " . $token->userid, "success");
        $respond(200, "{$total} Drug Balances reconcile Successfully Uploaded by user with the Login ID: " . $token->userid, $data_result, [
            "dataset" => $data_result,
            "total" => $total
        ]);
    },

    //Push CCD Lead Drug Balance Online
    '912' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (!is_array($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $nt = new Smc\Icc();
        $data_result = is_array($result = $nt->PushBalance($inputData)) ? $result : [];
        $total = count($data_result);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " push " . $total . " Drug Balances Online", "success");
        $respond(200, $total . ' Drug Balance was successfully pushed back Online to the HF', $data_result, ["dataset" => $data_result, "total" => $total]);
    },

    //Reconcile balance
    '913' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');

        $nt = new Smc\Icc();
        $logs = "";
        if (is_array($inputData)) {
            $logs = implode(', ', array_map(
                fn($i) => "{$i['drug']} = {$i['qty']}",
                $inputData
            )) . " | Device ID: {$inputData[0]['device_id']}";
        }

        $data = is_array($result = $nt->ReconcileBalanceRun($inputData)) ? $result : [];

        if (count($data)) {
            $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " reconcile " . $logs . " successfully", "success");
            $respond(200, 'successfully reconciled', $data);
        }
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " reconcile " . $logs . " failed", "failed");
        $respond(400, "failed", $data);
    },

    //Get DP/Facility Balances
    '914' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        $hh = new Smc\Icc();
        $dpid = $inputData['dpid'] ?? "";
        $periodid = $inputData['periodid'] ?? "";
        $data = $hh->GetIccBalanceForDp($periodid, $dpid);
        // $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " View CDD Wallet balances ", "success");
        $respond(200, "success", $data, ['dataset' => 'Get All CDD Balances']);
    },

    //Bulk ICC Return Upload
    '915' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData)) {
            $respond(400, "Bad Request", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Icc();
        $data_result = is_array($result = $hh->BulkIccReturn($inputData)) ? $result : [];
        $total = count($data_result);
        $log($token->userid, "mobile", "smc", "{$total} ICC Issued Returned Successfully", "success");
        $respond(200, "Total of {$total} ICC Issued returned successfully", $data_result, ["total" => $total]);
    },

    //Get DP/Facility Balances (Inventory)
    '916' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        $hh = new Smc\Inventory();
        $dpid = $inputData['dpid'] ?? "";
        $data = $hh->GetFacilityInventoryBalance($dpid);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Download Wallet Balance for the Facility ", "success");
        $respond(200, "success", $data, ['dataset' => 'Get Current User DP Balance']);
    },

    //Get App Movement List
    '917' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        $hh = new Smc\Logistics();
        $periodId = $inputData['periodId'] ?? "";
        $conveyorId = $inputData['conveyorId'] ?? "";
        $data = $hh->getAppMovementList($periodId, $conveyorId);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " get Movement List ", "success");
        $respond(200, "success", $data, ['dataset' => 'Get Movement List']);
    },

    //Confirm Movement Route
    '918' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        $hh = new Smc\Logistics();
        $movementId = $inputData['movementId'] ?? "";
        $data = $hh->confirmRoute($movementId);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " confirm Route Movement with ID " . $movementId, "success");
        $respond(200, "success", $data, ['dataset' => 'Get Movement Confirmation', 'status' => $data]);
    },

    //Origin Approval
    '919' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData['name']) || empty($inputData['designation']) || empty($inputData['phone']) || empty($inputData['userId']) || empty($inputData['locationString']) || empty($inputData['signature']) || empty($inputData['approveDate']) || empty($inputData['latitude']) || empty($inputData['longitude']) || empty($inputData['deviceSerial']) || empty($inputData['appVersion']) || empty($inputData['movementId'])) {
            $respond(400, "Bad Request, Name or Designation or Phone or User ID or Location String or Signature or Approve Date or Latitude or Longitude or Device Serial or App Version or Movement ID is required", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Logistics();
        $movementId = $inputData['movementId'] ?? "";
        $data = $hh->OriginApproval($movementId, $inputData['name'] ?? "", $inputData['designation'] ?? "", $inputData['phone'] ?? "", $inputData['userId'] ?? $token->userid, $inputData['locationString'] ?? "", $inputData['signature'] ?? "", $inputData['approveDate'] ?? "", $inputData['latitude'] ?? "", $inputData['longitude'] ?? "", $inputData['deviceSerial'] ?? "", $inputData['appVersion'] ?? "");
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " confirm Route Movement with ID " . $movementId, "success");
        $respond(200, "success", $data, ['dataset' => 'Origin Approval', 'status' => $data]);
    },

    //Conveyor Approval
    '920' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData['name']) || empty($inputData['designation']) || empty($inputData['phone'])  || empty($inputData['locationString']) || empty($inputData['signature']) || empty($inputData['approveDate']) || empty($inputData['latitude']) || empty($inputData['longitude']) || empty($inputData['deviceSerial']) || empty($inputData['appVersion']) || empty($inputData['movementId'])) {
            $respond(400, "Bad Request, Name or Designation or Phone or User ID or Location String or Signature or Approve Date or Latitude or Longitude or Device Serial or App Version or Movement ID is required", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Logistics();
        $movementId = $inputData['movementId'] ?? "";
        $data = $hh->ConveyorApproval($movementId, $inputData['name'] ?? "", $inputData['designation'] ?? "", $inputData['phone'] ?? "", $inputData['userId'] ?? $token->userid, $inputData['locationString'] ?? "", $inputData['signature'] ?? "", $inputData['approveDate'] ?? "", $inputData['latitude'] ?? "", $inputData['longitude'] ?? "", $inputData['deviceSerial'] ?? "", $inputData['appVersion'] ?? "");
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Conveyor Approval for movement with ID " . $movementId, "success");
        $respond(200, "success", $data, ['dataset' => 'Conveyor Approval', 'status' => $data]);
    },

    //Destination Approval
    '921' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        if (empty($inputData['name']) || empty($inputData['designation']) || empty($inputData['phone']) || empty($inputData['userId']) || empty($inputData['locationString']) || empty($inputData['signature']) || empty($inputData['approveDate']) || empty($inputData['latitude']) || empty($inputData['longitude']) || empty($inputData['deviceSerial']) || empty($inputData['appVersion']) || empty($inputData['movementId'])) {
            $respond(400, "Bad Request, Name or Designation or Phone or User ID or Location String or Signature or Approve Date or Latitude or Longitude or Device Serial or App Version or Movement ID is required", "", ['dataset' => "Invalid request"]);
        }
        $hh = new Smc\Logistics();
        $movementId = $inputData['movementId'] ?? "";
        $data = $hh->DestinationApproval($movementId, $inputData['shipmentId'] ?? "", $inputData['name'] ?? "", $inputData['designation'] ?? "", $inputData['phone'] ?? "", $inputData['userId'] ?? $token->userid, $inputData['locationString'] ?? "", $inputData['signature'] ?? "", $inputData['approveDate'] ?? "", $inputData['latitude'] ?? "", $inputData['longitude'] ?? "", $inputData['deviceSerial'] ?? "", $inputData['appVersion'] ?? "");
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Destination Approval for movement with ID " . $movementId, "success");
        $respond(200, "success", $data, ['dataset' => 'Destination Approval', 'status' => $data]);
    },

    //Inter Facility Transfer
    '922' => function ($token) use ($respond, $checkPriv, $inputData, $log) {
        $checkPriv($token, 'smc');
        $hh = new Smc\Inventory();
        $data    = $hh->FacilityTransfer($inputData['inventoryId'], $inputData['fromFalicityId'], $inputData['toFacilityId'], $inputData['primaryQty'], $token->userid);
        $log($token->userid, "mobile", "smc", "User with the User ID: " . $token->userid . " Transfer " . $inputData['primaryQty'] . " Commodity from Inventory ID: " . $inputData['inventoryId'] . " in Faicility with ID: " . $inputData['fromFalicityId'] . " To Facility ID: " . $inputData['toFacilityId'], "success");
        $respond(200, "success", $data, ['dataset' => 'Inter Facility Transfer', 'status' => $data]);
    },

    /**
     * SMC Modules
     * Ends
     * **********************************************************************************
     */

    /**
     * MONITORING MODULES 
     * Begin
     * ********************************************************************************
     */

    //Form I-9A
    '1000' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\INineA();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;

        $description = $total
            ? "User with the User ID: $token->userid Sync $total i9a Form Successfully"
            : "User with the User ID: $token->userid i9a Form Synchronization Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    //Form I-9B
    '1001' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\INineB();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;
        $description = $total
            ? "User with the User ID: $token->userid Sync $total i9b Form Successfully"
            : "User with the User ID: $token->userid i9b Form Synchronization Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    //Bulk Upload of Form i9c
    '1002' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\INineC();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;
        $description = $total
            ? "User with the User ID: $token->userid Sync $total i9c Form Successfully"
            : "User with the User ID: $token->userid i9c Form Synchronization Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    //Bulk Upload of End Process Monitoring form
    '1003' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\EndProcess();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;
        $description = $total
            ? "User with the User ID: $token->userid Sync $total End Process Form Successfully"
            : "User with the User ID: $token->userid End Process Form Synchronization Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    //Bulk Upload of 5% Revisit form
    '1004' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\FiveRevisit();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;
        $description = $total
            ? "User with the User ID: $token->userid Sync $total Five Revisit Form Successfully"
            : "User with the User ID: $token->userid Five Revisit Form Synchronization Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    //Bulk Upload of SMC CDD Monitoring form
    '1005' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\SmcCdd();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;
        $description = $total
            ? "User with ID: $token->userid Sync $total SMC CDD Form Successfully"
            : "User with ID: $token->userid , $total SMC CDD Form data Synch Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    //Bulk Upload of SMC HFW Monitoring form
    '1006' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\SmcHfw();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;
        $description = $total
            ? "User with ID: $token->userid Sync $total SMC HFW Form Successfully"
            : "User with ID: $token->userid , $total SMC HFW Form data Synch Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    //Bulk Upload of 5% HHTVT Supervisor form
    '1007' => function ($token) use ($respond, $inputData, $log) {
        $ob = new Form\FiveRevisitSupervisor();
        if (empty($inputData)) {
            $respond(400, "Bad Request, No data provided", "", ['dataset' => "Invalid request"]);
        }
        $list = $ob->BulkSave($inputData);
        $total = count($list);

        $result = $total ? "success" : "failed";
        $responseCode = $total ? 200 : 401;
        $description = $total
            ? "User with ID: $token->userid Sync $total Five Revisit Supervisor Form Successfully"
            : "User with ID: $token->userid , $total Five Revisit Supervisor Form data Synch Failed";

        $log($token->userid, "mobile", "monitoring", $description, $result);
        $respond($responseCode, "$total forms uploaded", $list);
    },

    /**
     * MONITORING MODULES
     * Ends
     * **********************************************************************************
     */

    /**
     * GENERAL MASTER DATA MODULES 
     * Begin
     * ********************************************************************************
     */

    //Get Bank List

    'gen001' => function ($token) use ($respond) {
        $gn = new System\General();
        $data = $gn->GetBankList();
        $respond(200, "success", $data, ['dataset' => 'Bank List']);
    },

    //Change user password using login ID
    'gen002' => function ($token) use ($respond, $inputData) {
        $mg = new Users\UserManage();
        $loginid = $inputData["loginid"] ?? "";
        $old = $inputData["old"] ?? "";
        $new = $inputData["new"] ?? "";

        if (empty($loginid) || empty($old) || empty($new)) {
            $respond(400, "Missing required fields: loginid, old, or new");
        }

        if ($mg->ChangePassword($loginid, $old, $new)) {
            $respond(200, "Password Successfully Changed");
        }
        $respond(400, "Unable to change password, maybe use does not exist, or incorrect old password, please try again later");
    },

    //Get location category list
    'gen003' => function ($token) use ($respond) {
        $us = new Mobilization\Mobilization();
        $data = $us->GetLocationCategories();
        $respond(200, "success", $data, ['dataset' => 'Location Category List']);
    },

    //Get receipt header
    'gen004' => function ($token) use ($respond) {
        $ex = new Mobilization\Mobilization();
        $data = $ex->GetReceiptHeader();
        $respond(200, "success", $data, ['dataset' => 'Mobilization Receipt Header']);
    },

    //Get Commodity list
    'gen006' => function ($token) use ($respond) {
        $us = new Smc\SmcMaster();
        $data = $us->GetCommodity();
        $respond(200, "success", $data, ['dataset' => 'Commodity list']);
    },

    //Get Reason list
    'gen007' => function ($token) use ($respond) {
        $us = new Smc\SmcMaster();
        $data = $us->GetReasons();
        $respond(200, "success", $data, ['dataset' => 'Reason list']);
    },

    //Get Active Period
    'gen008' => function ($token) use ($respond) {
        $us = new Smc\SmcMaster();
        $data = $us->GetPeriodActive();
        $respond(200, "success", $data, ['dataset' => 'Active SMC Period']);
    },

    //Get MasterHousehold using dpid
    'gen009' => function ($token) use ($respond, $inputData) {
        $us = new Smc\SmcMaster();
        $dpid = $inputData['dpid'] ?? "";
        $data = $us->GetMasterHousehold($dpid);
        $respond(200, "success", $data, ['dataset' => 'Household Master Data']);
    },

    //Get MasterChild using dpid
    'gen010' => function ($token) use ($respond, $inputData) {
        $us = new Smc\SmcMaster();
        $dpid = $inputData['dpid'] ?? "";
        $data = $us->GetMasterChild($dpid);
        $respond(200, "success", $data, ['dataset' => 'Child Master Data']);
    },

    //Get CDD Lead Master List using dpid
    'gen011' => function ($token) use ($respond, $inputData) {
        $us = new Smc\SmcMaster();
        $dpid = $inputData['dpid'] ?? "";
        $data = $us->GetCddLead($dpid);
        $respond(200, "success", $data, ['dataset' => 'Get CDD Lead Master List using']);
    },

    //Get Referrer Master Lists using the DP ID and Period ID
    'gen012' => function ($token) use ($respond, $inputData) {
        $hh = new Smc\DrugAdmin();
        $dpid = $inputData['dpid'] ?? "";
        $periodid = $inputData['periodid'] ?? "";
        $data = $hh->GetReferrerList($dpid, $periodid);
        $respond(200, "success", $data, ['dataset' => 'Referrer Master List']);
    },

    //Get Combined geo structure
    'gen013' => function ($token) use ($respond, $inputData) {
        $sy = new System\General();
        $lgaid = $inputData['lgaid'] ?? null;

        if (!empty($lgaid)) {
            $data = [
                'lgaid' => $lgaid,
                'lga' => $sy->GetThisLgaList($lgaid),
                'ward' => $sy->GetWardList($lgaid),
                'dp' => $sy->GetDpListByLga($lgaid),
                'community' => $sy->GetCommunityListByLga($lgaid)
            ];
            $respond(200, 'Master Data Downloaded', $data);
        }

        $geo_level = $inputData['geo_level'] ?? "";
        $geo_level_id = $inputData['geo_level_id'] ?? "";
        $structure = $sy->GetGeoStructureId($geo_level, $geo_level_id);

        if ($structure) {
            $stateid = $structure[0]['stateid'] ?? null;
            $lgaid = $structure[0]['lgaid'] ?? null;

            if ($stateid && $lgaid) {
                $data = [
                    'lgaid' => $lgaid,
                    'lga' => $sy->GetThisLgaList($lgaid),
                    'ward' => $sy->GetWardList($lgaid),
                    'dp' => $sy->GetDpListByLga($lgaid),
                    'community' => $sy->GetCommunityListByLga($lgaid)
                ];
                $respond(200, 'complete data and good to go', $data);
            }
            $respond(401, 'incomplete required data', $structure);
        }
        $respond(401, 'invalid requirement return empty', []);
    },


    /**
 * GENERAL MASTER DATA MODULES
 * Ends
 * **********************************************************************************
 */
];

// --- EXECUTION ---

try {
    if (!$jwt) {
        // Handle Public Requests
        if (isset($publicRoutes[$qid])) {
            $publicRoutes[$qid]();
        } else {
            $log(0, "web", "api", "Unauthorized access to QID: $qid", "failed");
            $respond(401, "Unauthorized Access");
        }
    } else {
        // Decode Token
        $token = JWT::decode($jwt, new Key($secret_key, 'HS512'));

        // Basic Token Validation (Issuer / Expiry)
        if ($token->iss !== $issuer_claim && $token->nbf > $issuedat_claim->getTimestamp() || $token->exp < $issuedat_claim->getTimestamp()) {
            $log($token->userid, "api", "login", "Expired/Invalid token use", "failed");
            $respond(400, "Invalid or Expired Token");
        }

        // Routing
        if (isset($privateRoutes[$qid])) {
            $privateRoutes[$qid]($token);
        } else {
            $respond(404, "Endpoint not found");
        }
    }
} catch (Exception $e) {
    $log(0, "web", "api", "Token Decode Error: " . $e->getMessage(), "failed");
    $respond(401, "Unauthorized Access Token Error");
}
