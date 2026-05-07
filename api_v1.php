<?php

declare(strict_types=1);
// error_reporting(0);
include_once('lib/autoload.php');
include("lib/config.php");
include_once('lib/common.php');
//  
log_system_access();
//
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require('lib/vendor/autoload.php');    //JWT Autoload

$secret_key = file_get_contents('lib/privateKey.pem');

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$data = json_decode(file_get_contents('php://input'));
$header = getallheaders();
# Set default JWT Configuration to empty if not set
$jwt = isset($header['jwt']) ? $header['jwt'] : "";
$longtitude = "";
$latitude = "";
$current_userid = "";
$current_loginid = "";


#
#   Result Code Documentation (result_code)
#   200 : Success
#   400 : Error
#   401: invalid login/UnAuthorized User

#   Log users activity
function logUserActivity($userid, $platform, $module, $description, $result, $longtitude = "", $latitude = "")
{
    /*
            $userid = 1;
            $platform = "web";
            $module = "Users management";
            $description = "Update user data: ";
            $result = "success";
        */
    $logid = System\General::LogActivity($userid, $platform, $module, $description, $result, $longtitude, $latitude);
    if ($logid) {
        return;
        // echo "Created log for the activity successfully";
    } else {
        return;
        // echo "Unable to create log at the moment, please try again later";
    }
}

# Check if JWT token exist
if (!$jwt) {
    #
    if (CleanData("qid") == '010') {
        #
        #   User login
        #   End Point: ?
        #   Login using user ID and password Test Data
        #   {
        #       'loginid' : 'HYM00001',
        #       'password' : 'DEmo2021'
        #   }

        $loginId = isset($header['loginid']) ? $header['loginid'] : "";
        $password = isset($header['password']) ? $header['password'] : "";
        $device_serial = isset($header['device_serial']) ? $header['device_serial'] : "";

        $longtitude = isset($header['long']) ? $header['long'] : "";
        $latitude = isset($header['lat']) ? $header['lat'] : "";

        $login = new Users\Login();
        #   Set login id
        $login->SetLoginId($loginId, $password);
        #   Run login
        if ($login->RunLogin($device_serial)) {
            #   login successful
            #   Get login Data
            $loginData = $login->GetLoginData();

            #   Get User working hours by days
            $usr = new Users\UserManage();
            $loginData['work_hour_data'] = $usr->GetUserWorkingHours($loginData['userid']);

            $token = array(
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
            );

            //Log User Activities
            logUserActivity($userid = $loginData['userid'], $platform = 'mobile', $module = "login", $description = $loginData['fullname'] . ' (' . $loginData['loginid'] . ') Successfully Logged In', $result = 'success', $longtitude = "", $latitude = "");
            // Encrypt user data and set token
            $jwt = JWT::encode($token, $secret_key, 'HS512');

            http_response_code(200);
            echo json_encode(
                array(
                    "result_code" => 200,
                    "message" => "User Successfully login.",
                    "jwt" => $jwt,
                    "data" => $loginData
                )
            );
        } else {
            $err = $login->LastError;
            //Log User Activities
            logUserActivity($userid = 0, $platform = "mobile", $module = "login", $description = 'An Uknown User is trying to access the mobile platform using a wrong user details; Login ID: ' . $loginId . ' - ' . $err, $result = 'failed', $longtitude = "", $latitude = "");
            #   login failed
            http_response_code(400);
            echo json_encode(
                array(
                    "result_code" => 400,
                    "message" => $err,
                )
            );
        }
    } elseif (CleanData("qid") == '011') {
        #
        #   User login sample
        #
        #   Login using badge
        #   {
        #       'badge_data' : 'JTV00002|79mzhz79-u4h9-8df8-a9o8-9vr3b0zkttxi',
        #   }

        $login = new Users\Login('badge');
        #   Set badge by data - 
        $badge_data = isset($header['badge_data']) ? $header['badge_data'] : "";
        $longtitude = isset($header['long']) ? $header['long'] : "";
        $latitude = isset($header['lat']) ? $header['lat'] : "";

        $device_serial = $header['device_serial'];


        $login->SetBadge($badge_data);
        #   Run login
        if ($login->RunLogin($device_serial)) {
            #   login successful
            #   Get login Data | Level ID, Priviledge
            $loginData = $login->GetLoginData();

            #   Get User working hours by days
            $usr = new Users\UserManage();
            $loginData['work_hour_data'] = $usr->GetUserWorkingHours($loginData['userid']);

            $token = array(
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

            );

            //Log User Activities
            logUserActivity($userid = $loginData['userid'], $platform = 'pos', $module = "login", $description = $loginData['fullname'] . ' (' . $loginData['loginid'] . ') Successfully Logged In using badge', $result = 'success', $longtitude = "", $latitude = "");

            // Encrypt user data and set token
            $jwt = JWT::encode($token, $secret_key, 'HS512');

            http_response_code(200);
            echo json_encode(
                array(
                    "result_code" => 200,
                    "message" => "User Successfully login.",
                    "jwt" => $jwt,
                    "data" => $loginData
                )
            );
        } else {
            $err = $login->LastError;
            //Log User Activities
            logUserActivity($userid = 0, $platform = "pos", $module = "login", $description = 'An Uknown User is trying to access the POS platform using a wrong Badge details; Badge Data: ' . $badge_data . "; " . $err, $result = 'failed', $longtitude = "", $latitude = "");

            #   login failed
            http_response_code(400);
            echo json_encode(
                array(
                    "result_code" => 400,
                    "message" => $err,
                )
            );

            //echo "Login ID: ".$login->GetLoginId();
        }
    }
    /*
        *
        *  Device Management
        * 
        */ elseif (CleanData('qid') == '501') {
        #
        #   register Device
        #
        $ex = new System\Devices();
        #
        $device_name = $header['device_name'];
        $device_id = $header['device_id'];
        $device_type = $header['device_type'];
        $longtitude = $header['long'];
        $latitude = $header['lat'];

        #
        $device_data = $ex->RegisterDevice($device_name, $device_id, $device_type);
        #


        if (is_array($device_data)) {
            http_response_code(200);
            //Log User Activity
            logUserActivity($userid = 0, $platform = $device_type, $module = "Device Management", $description = 'A device with device name :' . $device_name . ' Device ID: ' . $device_id . ' is trying to register', $result = 'failed', $longtitude = "", $latitude = "");

            echo json_encode(array(
                'result_code' => 200,
                'dataset' => 'Register Device on System',
                'message' => 'success',
                'data' => $device_data
            ));
        } else {

            #
            $device_data = $ex->CheckDevice($device_id);
            #
            //Http Response
            http_response_code(200);
            echo json_encode(array(
                'result_code' => 200,
                'dataset' => 'Check Device',
                'message' => 'success',
                'device_id' => $device_id,
                'data' => $device_data
            ));
        }
    } elseif (CleanData('qid') == '503') {
        #
        #   Check Device
        #
        $ex = new System\Devices();
        #
        $inputData = json_decode(file_get_contents('php://input'), true);
        $device_id = $inputData["device_id"];

        #
        $device_data = $ex->CheckDevice($device_id);
        #
        //Http Response
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Check Device',
            'device_id' => $device_id,
            'data' => $device_data
        ));
    }
} elseif ($jwt)
#   If JWT exist, decode the JWT to verify the user identity
{
    try
    #   Try and Decode Token for validation purpose
    {
        # Decode Token for validation purpose
        $token = JWT::decode($jwt, new Key($secret_key, 'HS512'));
        $current_userid = $token->userid;
        $current_loginid = $token->loginId;
        $longtitude = isset($header['long']) ? $header['long'] : "";
        $latitude = isset($header['lat']) ? $header['lat'] : "";

        if ($token->iss !== $issuer_claim && $token->nbf > $issuedat_claim->getTimestamp() || $token->exp < $issuedat_claim->getTimestamp()) {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform = "pos|mobile", $module = "login", $description = "User with the Login ID: " . $current_loginid . " is trying to login using an expired token: ", $result, $longtitude = "", $latitude = "");

            #   Tell the user access denied if the token can't be validated
            http_response_code(400);

            echo json_encode(
                array(
                    "result_code" => 400,
                    "message" => "Invalid Token",
                )
            );
        } else
        # Put All other Endpoint here, if token can be verified
        {
            $privilege = $token->system_privilege;

            // User Priority
            $user_priority = $token->priority ? $token->priority : 1;
            // Device Serial of device that Login
            $current_device_serial = $token->device_serial;

            /**
             * User Modules
             * Begin
             * ********************************************************************************
             */


            #   User List for bulk consumption
            if (CleanData("qid") == '012') {
                /**
                 * Check for User Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'users')) {

                    #
                    #   User List for bulk consumption
                    #   Sample DATA
                    $usr = new Users\UserManage();
                    $data = $usr->ListUserFull();
                    #
                    if ($data) {
                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'message' => 'success',
                            'data' => $data
                        ));
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Failed to Download Data, Retry',
                            'data' => $data
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "users", $description = "User with the User ID: " . $current_userid . " does not have priviledge to update User data ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on User Module'
                    ));
                }
            }
            #   Bulk user update
            elseif (CleanData("qid") == '006') {
                /**
                 * Check for User Priviledge
                 */
                $inputData = json_decode(file_get_contents('php://input'), true);
                $longtitude = isset($inputData['long']) ? $inputData['long'] : "";
                $latitude = isset($inputData['lat']) ? $inputData['lat'] : "";

                if (IsPrivilegeInArray(json_decode($privilege, true), 'users')) {
                    #
                    #   Bulk user update
                    #

                    $usr = new Users\UserManage();
                    #
                    # userid, roleid, first, middle, last, gender, email, phone, bank_name, account_name, account_no, bank_code, bio_feature
                    #

                    //$userData = array($inputData);
                    $total = $usr->BulkUserUpdate($inputData);
                    #
                    //User Log Activity
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "users", $description = "User with the User ID " . $current_userid . " details Successfully Updated: ", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'message' => 'success ',
                        'total' => $total
                    ));
                } else {

                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "users", $description = "User with the User ID: " . $current_userid . " does not have priviledge to update User data ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on User Module'
                    ));
                }
            }
            #   Get role list
            elseif (CleanData("qid") == '007') {

                /**
                 * Check for User Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'users')) {
                    #
                    #   Get role list
                    #
                    $usr = new Users\UserManage();
                    #   users list
                    $data = $usr->GetRoleList($user_priority);
                    #
                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {

                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "users", $description = "User with the User ID: " . $current_userid . " does not have priviledge to get Role List ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on User Module'
                    ));
                }
            }
            #   update user role
            elseif (CleanData("qid") == '008') {

                /**
                 * Check for User Priviledge
                 */
                $role_id = $header["role_id"];
                $user_id = $header["user_id"];
                $longtitude = isset($header['long']) ? $header['long'] : "";
                $latitude = isset($header['lat']) ? $header['lat'] : "";

                if (IsPrivilegeInArray(json_decode($privilege, true), 'users')) {
                    #
                    #   update user role
                    #

                    $usr = new Users\UserManage();
                    #   users list
                    #
                    #   UpdateUserRole($role_id, $user_id)
                    $data = $usr->UpdateUserRole($role_id, $user_id);
                    //User Log Activity
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "users", $description = "User with the User ID: " . $user_id . " Role Successfully Updated to Role ID: $role_id", $result, $longtitude = "", $latitude = "");
                    #
                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {

                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "users", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Update Role List ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on User Module'
                    ));
                }
            }
            #   Run User bank account validation  
            elseif (CleanData("qid") == '009') {

                $usr = new Users\UserManage();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $userid = $inputData['user_id'];
                $longtitude = $inputData['long'];
                $latitude = $inputData['lat'];


                $data = $usr->RunBankVerification($userid);
                #
                #

                //User Log Activity
                $result = "success";
                logUserActivity($userid = $current_userid, $platform = "mobile", $module = "users", $description = "User with the User ID: " . $userid . " Account Verification done ", $result, $longtitude = "", $latitude = "");
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'message' => 'Verification Done on User with ID ' . $userid,
                    'data' => $data
                ));
            }
            #   User FCm Register
            elseif (CleanData('qid') == '013') {
                $inputData = json_decode(file_get_contents('php://input'), true);

                $us = new Users\UserManage();
                $userid = $inputData["userid"];
                $device_serial = $inputData["device_serial"];
                $fcm_token = $inputData["fcm_token"];

                $data = $us->RegisterUserFcm($userid, $device_serial, $fcm_token);
                $result = "success";
                logUserActivity($userid = $current_userid, $platform = "pos|mobile", $module = "users", $description = "User with the User ID: " . $current_userid . " updated its firebase token", $result, $longtitude = "", $latitude = "");
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'message' => 'success',
                    'data' => $data
                ));
            }

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

            #   Get generic Training list (training list without privilege)
            if (CleanData('qid') == '100') {
                $inputData = json_decode(file_get_contents('php://input'), true);
                $geo_level = $inputData['geo_level'];
                $geo_level_id = $inputData['geo_level_id'];
                $longtitude = isset($inputData['long']) ? $inputData['long'] : "";
                $latitude = isset($inputData['lat']) ? $inputData['lat'] : "";

                #
                #
                #   Get generic Training list (training list without privilege)

                $tr = new Training\Training();
                $data = $tr->getGenericTraining($geo_level, $geo_level_id);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Generic Activity List',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Get generic Training Session list (training Session without privilege)
            elseif (CleanData('qid') == '101') {

                #
                #   Get generic Training Session list (training Session without privilege)
                $tr = new Training\Training();

                $training_id = $header["training_id"];
                $data = $tr->getGenericSession($training_id);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Generic Session List',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Get participants list for a particular training
            elseif (CleanData('qid') == '102') {
                /**
                 * Check for Training Priviledge
                 */
                $training_id = $header["training_id"];
                $geo_level = $header["geo_level"];
                $geo_level_id = $header["geo_level_id"];

                #
                #   Get participants list for a particular training
                $tr = new Training\Training();
                $data = $tr->getParticipantsList($training_id, $geo_level, $geo_level_id);

                $result = "success";
                logUserActivity($userid = $current_userid, $platform = "mobile", $module = "Participants Download", $description = "User with the User ID: " . $current_userid . " Download Participant for training with ID " . $training_id, $result, $longtitude = "", $latitude = "");

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Participants List',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Add attendance bulk
            elseif (CleanData('qid') == '103') {

                /**
                 * Check for Training Priviledge
                 */

                if (IsPrivilegeInArray(json_decode($privilege, true), 'activity')) {

                    #
                    #   Add attendance bulk
                    $tr = new Training\Training();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);
                    #   [session_id, participant_id, at_type, bio_auth, collected,longitude,latitude, userid]
                    // $bulk_data = array(
                    //     array('session_id'=>1,'participant_id'=>12,'at_type'=>'ClOCK-OUT','bio_auth'=>true,'collected'=>'2022-03-16 16:00','longitude'=>'8.0027','latitude'=>'5.67822','userid'=>1,'app_version'=>'14.0.5'),
                    //     array('session_id'=>1,'participant_id'=>13,'at_type'=>'ClOCK-in','bio_auth'=>true,'collected'=>'2022-03-16 08:00','longitude'=>'8.0027','latitude'=>'5.67822','userid'=>1,'app_version'=>'14.0.5'),
                    //     array('session_id'=>1,'participant_id'=>14,'at_type'=>'ClOCK-in','bio_auth'=>false,'collected'=>'2022-03-16 08:34','longitude'=>'8.0027','latitude'=>'5.67822','userid'=>1,'app_version'=>'14.0.5'),
                    //     array('session_id'=>1,'participant_id'=>15,'at_type'=>'ClOCK-in','bio_auth'=>true,'collected'=>'2022-03-16 08:46','longitude'=>'8.0027','latitude'=>'5.67822','userid'=>1,'app_version'=>'14.0.5'),
                    //     array('session_id'=>1,'participant_id'=>16,'at_type'=>'ClOCK-in','bio_auth'=>true,'collected'=>'2022-03-16 08:57','longitude'=>'8.0027','latitude'=>'5.67822','userid'=>1,'app_version'=>'14.0.5')
                    // );
                    $mobid = "";
                    $participant_id = "";
                    for ($i = 0; $i < count($bulk_data); $i++) {
                        # code...
                        $participant_id .= "," . $bulk_data[$i]['participant_id'];
                    }
                    $participant_id = substr($participant_id, 1);

                    $total = $tr->AddAttendancebulk($bulk_data);
                    if ($total) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "training", $description = "$total Users attendace was Successfully Taken and Updated (" . $participant_id . ") by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            "result_code" => 200,
                            "message" => "Total of $total attendance was uploaded successfully",
                            "total" => $total
                        ));
                    } else {
                        //User Log Activity
                        $result = "failed";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "training", $description = "$total Users attendace Update Failed to be updated (" . $participant_id . ") by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Unable to upload attendance at the moment.'
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "training", $description = "User with the User ID: " . $current_userid . " does not have priviledge to take Training Attendance ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Training Module'
                    ));
                }
            }

            /**
             * Training Modules
             * Ends
             * **********************************************************************************
             */


            /**
             * Netcard Allocation Modules 
             * Begin
             * ********************************************************************************
             */

            #  List count Mobilizer balances
            if (CleanData('qid') == '201') {

                /*
                        *  Runs e-Netcard 
                        *
                        *  List count Mobilizerd balances
                        */
                $nt = new Netcard\NetcardTrans();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = $inputData['wardid'];

                $data = $nt->GetMobilizersList($wardid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'e-Netcard Mobilizers List',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Bulk e-Netcard Allocation Ward to mobilizer
            elseif (CleanData('qid') == '202') {

                /**
                 * Check User Priviledge 
                 * For Netcard Allocation
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'allocation')) {
                    /*
                            *  Runs e-Netcard Samples
                            *
                            *  Bulk e-Netcard Allocation Ward to mobilizer
                            */

                    $nt = new Netcard\NetcardTrans();

                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    # ['total'=>$total, 'wardid'=>$wardid, 'mobilizerid'=>$mobilizerid, 'userid'=>$userid]
                    //  $bulk_data = [array('total'=>10, 'wardid'=>1, 'mobilizerid'=>3, 'userid'=>2),
                    //  array('total'=>10, 'wardid'=>1, 'mobilizerid'=>4, 'userid'=>2),
                    //  array('total'=>10, 'wardid'=>1, 'mobilizerid'=>5, 'userid'=>2)];

                    #
                    $total = $nt->BulkAllocationTransfer($bulk_data);
                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'message' => "e-Netcards allocation transfer has been performed from Ward to HH Mobilizers successfully",
                        'total' => $total
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "allocation", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Allocate eNetcard ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Allocation Module'
                    ));
                }
            }
            #  List count Allocation mobile app balances
            elseif (CleanData('qid') == '203') {

                /*
                        *  Runs e-Netcard 
                        *
                        *  List count Allocation mobile app balances
                        */
                $nt = new Netcard\NetcardTrans();
                // $wardid = CleanData("wardid");
                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = CleanData("wardid") ? CleanData("wardid") : $inputData['wardid'];
                $data = $nt->CombinedBalanceForApp($wardid);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'e-Netcard Allocation Mobile App Balances',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #  List count Allocation mobile app transaction list
            elseif (CleanData('qid') == '204') {

                /*
                        *  Runs e-Netcard 
                        *
                        *  List count Allocation mobile app transaction list
                        */
                $nt = new Netcard\NetcardTrans();
                //$wardid = CleanData("wardid");
                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = CleanData("wardid") ? CleanData("wardid") : $inputData['wardid'];
                $data = $nt->GetAllocationTransferHistoryList($wardid);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'e-Netcard Allocation Mobile App Transaction list',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #  List count Allocation mobile app reverse order history list
            elseif (CleanData('qid') == '205') {

                /*
                        *  Runs e-Netcard 
                        *
                        *  List count Allocation mobile app reverse order history list
                        */
                $nt = new Netcard\NetcardTrans();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = CleanData("wardid") ? CleanData("wardid") : $inputData['wardid'];
                $data = $nt->GetAllocationReverseHistoryList($wardid);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'e-Netcard Allocation Mobile App reverse order history list',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Online reverse transaction history
            elseif (CleanData('qid') == '206') {
                $nt = new Netcard\NetcardTrans();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = CleanData("wardid") ? CleanData("wardid") : $inputData['wardid'];
                $data = $nt->GetAllocationDirectReverseList($wardid);
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'e-Netcard Online Revere history list',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Netcard Allocation reverse order
            elseif (CleanData('qid') == '207') {
                /**
                 * Check User Priviledge 
                 * For Netcard Allocation
                 */
                $inputData = json_decode(file_get_contents('php://input'), true);
                $order_total = $inputData['total'];
                $mobilizerid = $inputData['mobilizerid'];
                $wardid = $inputData['wardid'];
                $userid = $inputData['userid'];
                $device_serial = $inputData['device_serial'];
                $longtitude = isset($inputData['long']) ? $inputData['long'] : "";
                $latitude = isset($inputData['lat']) ? $inputData['lat'] : "";

                if (IsPrivilegeInArray(json_decode($privilege, true), 'allocation')) {
                    /*
                            *  Runs e-Netcard Samples
                            *
                            *  Netcard reverse order HHM back to ward 
                            */

                    $nt = new Netcard\NetcardTrans();
                    // 'total'=>10, 'wardid'=>1, 'mobilizerid'=>4, 'userid'=>2

                    #
                    // public function ReverseAllocationOrder($hhmid, $requester_id, $order, $device_serial){

                    if ($nt->ReverseAllocationOrder($mobilizerid, $userid, $order_total, $device_serial)) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "$order_total e-Netcard Reversed Order Successfully placed to be retracted from Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'message' => "e-Netcard reversal order has been placed successfully",
                            'total' => $order_total
                        ));
                    } else {
                        $result = "failed";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "$order_total e-Netcard Reversed Order Failed to be placed to Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => "Unable to place e-Netcard reversal order at the moment, please try again later"
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "allocation", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Reverse eNetcard from HHM with ID: " . $mobilizerid, $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Allocation Module'
                    ));
                }
            }
            #   e-Netcard Get combined mobilizer's balance (Without Duplicate)
            elseif (CleanData('qid') == '208') {

                #
                $nt = new Netcard\NetcardTrans();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = $inputData['wardid'];
                $data = $nt->GetCombinedMobilizerBalance($wardid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Combined Mobilizers Balance List',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Netcard Online reverse back to ward
            elseif (CleanData('qid') == '209') {
                /**
                 * Check User Priviledge 
                 * For Netcard Allocation
                 */
                $inputData = json_decode(file_get_contents('php://input'), true);
                $total = $inputData['total'];
                $mobilizerid = $inputData['mobilizerid'];
                $requester_id = $current_userid;

                $longtitude = isset($inputData['long']) ? $inputData['long'] : "";
                $latitude = isset($inputData['lat']) ? $inputData['lat'] : "";

                if (IsPrivilegeInArray(json_decode($privilege, true), 'allocation')) {
                    /*
                            *  Runs e-Netcard Samples
                            *
                            *  Netcard Online reverse back to ward 
                            */
                    $nt = new Netcard\NetcardTrans();

                    if ($total_reverse = $nt->DirectReverseAllocation($total,  $mobilizerid, $requester_id)) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "$total_reverse e-Netcard Reversed Successfully placed to be retracted from Household Mobilizers '" . $mobilizerid . " by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'message' => "e-Netcard reversal successful",
                            'total' => $total_reverse
                        ));
                    } else {
                        $result = "failed";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "$total_reverse e-Netcard Reversed Failed to be placed to Household Mobilizers '" . $mobilizerid . "' by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => "Unable to place reversale-Netcard at the moment, please try again later",
                            'total' => 0
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "allocation", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Reverse eNetcard from HHM with ID: " . $mobilizerid, $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Allocation Module'
                    ));
                }
            }
            #   e-Netcard Get combined mobilizer's balance With Pending 
            /**
             * Version 2
             */
            elseif (CleanData('qid') == '210') {

                #
                $nt = new Netcard\NetcardTrans();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = $inputData['wardid'];
                $data = $nt->GetMobilizerBalanceBySupervisor($wardid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'New Combined Mobilizers Balance List',
                    'message' => 'success',
                    'data' => $data
                ));
            }

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


            #   Get Communities list by Ward ID
            if (CleanData('qid') == '300a') {

                #
                #   Get Community list using Ward ID
                $us = new System\General();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = $inputData['wardid'];

                $data = $us->GetCommunityListByWard($wardid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Community list in a Ward',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get Communities list by DP ID
            elseif (CleanData('qid') == '300b') {

                #
                #   Get Community list using DP ID
                $us = new System\General();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $dpid = $inputData['dpid'];

                # Use DPID to get community list
                $data = $us->GetCommunityList($dpid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Community list in DP',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get DP list
            elseif (CleanData('qid') == '301') {

                #
                #   Get DP list
                $us = new System\General();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $wardid = $inputData['wardid'];
                #$wardid = 1;
                $data = $us->GetDpList($wardid);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'DP list in a ward',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Download e-netcard
            elseif (CleanData('qid') == '302') {
                /**
                 * Check Mobilization Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'mobilization')) {
                    #
                    #   Download e-netcard
                    $us = new Mobilization\Mobilization();
                    $inputData = json_decode(file_get_contents('php://input'), true);
                    $mobilizerid = $inputData['mobilizerid'];
                    $device_serial = $inputData['device_serial'];

                    $data = $us->DownloadEnetcard($mobilizerid, $device_serial);
                    #
                    $result = "success";
                    logUserActivity($userid = $mobilizerid, $platform = "pos", $module = "Wallet Download", $description = "e-Netcard Successfully downloaded by Household Mobilizer with ID '" . $mobilizerid . " and Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Download e-Netcard (Precious payload) on this device with device serial ' . $current_device_serial . ' Mobilizer ID: ' . $mobilizerid,
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization", $description = "User with the User ID: " . $current_userid . " does not have priviledge to download eNetcard ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Mobilization Module'
                    ));
                }
            }
            #   Check for Pending Reverse Order
            elseif (CleanData('qid') == '303') {
                /**
                 * Check Mobilization Priviledge
                 */
                #
                #   Check for Pending Reverse Order
                #
                $us = new Mobilization\Mobilization();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $mobilizerid = $inputData['mobilizerid'];
                $device_serial = $inputData['device_serial'];


                $data = $us->GetPendingReverseOrder($mobilizerid, $device_serial);
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Get pending reverse order from Mobilizer with ID ' . $mobilizerid . " and Device Serial " . $device_serial,
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Generate e-Token list
            elseif (CleanData('qid') == '304') {
                /**
                 * Check Mobilization Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'mobilization') || IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Generate e-Token list
                    #
                    $inputData = json_decode(file_get_contents('php://input'), true);
                    $device_id = $inputData['device_id'];
                    $total = $inputData['total'];

                    $tk = new Netcard\Etoken($device_id, $total);
                    #   Generate the e-token
                    $data = $tk->Generate();
                    #
                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Generate e-Token',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization", $description = "User with the User ID: " . $current_userid . " does not have priviledge to generate e-Token", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Mobilization Module'
                    ));
                }
            }
            #   Bulk Posting mobilization data
            elseif (CleanData('qid') == '305') {
                /**
                 * Check Mobilization Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'mobilization')) {
                    #
                    #
                    #   Bulk Posting mobilization data
                    $mo = new Mobilization\Mobilization();
                    # === mobilization data structure ===
                    #   [dp_id, comid, hm_id, hoh_first, hoh_last, hoh_phone, hoh_gender, 
                    #   family_size, 
                    #   hod_mother, sleeping_space, adult_female, adult_male, Child,
                    #   allocated_net, location_description, longitude, 
                    #   latitude, netcards, etoken_id, etoken_serial, etoken_pin, collected_date]
                    // $bulk_data = [array('dp_id'=>1,'comid'=>4001, 'hm_id'=>5,'hoh_first'=>'Kanzambili','hoh_last'=>'Samuel','hoh_phone'=>'08023456789',
                    //     'hoh_gender'=>'Male','family_size'=>4, 
                    //     'hod_mother'=>'Omowumi Salewa','sleeping_space'=>'12','adult_female'=>'4','adult_male'=>'4','Child'=>'4',
                    //     'allocated_net'=>2,'location_description'=>'Household',
                    //     'longitude'=>'5.67890','latitude'=>'7.2339038',
                    //     'netcards'=>'h24h55kb-id4n-f5nf-9rgm-z3u9f1r663ow,q7e9idm1-3ggr-diwv-idfa-zmpemocb3lob',
                    //     'etoken_id'=>'41','etoken_serial'=>'WO00041','etoken_pin'=>'12345','collected_date'=>'2022-04-20')];
                    $bulk_data = json_decode(file_get_contents('php://input'), true);
                    $total = 0;
                    $total = $mo->BulkMobilization($bulk_data);
                    if ($total) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization sync", $description = "User with the User ID: " . $current_userid . " Synchronize " . $total . "Mobilization data ", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'dataset' => "Total $total bulk mobilization has been submitted successfully",
                            'message' => 'success',
                            'total' => $data
                        ));
                    } else {
                        //User Log Activity
                        $result = "failed";
                        logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization sync", $description = "User with the User ID: " . $current_userid . " tried to Synchronize " . $total . "Mobilization data and failed ", $result, $longtitude = "", $latitude = "");

                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'dataset' => "Unable to submit the mobilization  bulk data",
                            'message' => 'success',
                            // 'total'=>$data
                            'total' => 0
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization sync", $description = "User with the User ID: " . $current_userid . " does not have priviledge to do Bulk Mobilization Update", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Mobilization Module'
                    ));
                }
            }
            #  Get balances HH Mobilizer
            elseif (CleanData('qid') == '306') {

                /*
                        *  Runs e-Netcard Samples
                        *
                        *  Get balances HH Mobilizer
                        */
                $inputData = json_decode(file_get_contents('php://input'), true);
                $nt = new Netcard\NetcardTrans();
                $userid = $inputData['mobilizerid'];

                $data = $nt->ThisCountHHMobilizerBalance($userid);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'HH Mobilizer by userid Balance',
                    'data' => $data
                ));
            }
            #   Netcard Allocation reverse order fulfilment
            elseif (CleanData('qid') == '307') {
                /**
                 * Check Mobilization Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'mobilization')) {
                    #
                    #   Netcard Allocation reverse order fulfilment
                    $nt = new Netcard\NetcardTrans();
                    #
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    // $orderid = 2;
                    // $netcard_list = array('7khh4t0s-ljhz-a7zm-o3lo-wzo3px7tfsmc',
                    //                         'fiofptns-ts73-ierg-085m-shas60b5eq5j',
                    //                         'kot0af87-v3t9-4alu-f7fc-j5r5xwdyym0b');
                    // $mobilizerid = 3;
                    // $wardid = 1;
                    // $userid = 3;

                    $orderid = $inputData['orderid'];
                    $netcard_list = $inputData['netcards'];
                    $mobilizerid = $inputData['mobilizerid'];
                    $wardid = $inputData['wardid'];
                    $userid = $inputData['userid'];
                    #
                    $total = $nt->HHMobilizerToWardFulfulment($orderid, $netcard_list, $mobilizerid, $wardid, $userid);

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => "$total e-Netcard was fulfilled successfully",
                        'message' => 'success',
                        'total' => $total
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Fulfil eNetcard Reversal", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Mobilization Module'
                    ));
                }
            }
            #   e-Netcard Mobilizer push netcard online
            elseif (CleanData('qid') == '308') {
                /**
                 * Check Mobilization Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'mobilization')) {
                    #
                    #   e-Netcard Mobilizer push netcard online
                    $nt = new Netcard\NetcardTrans();
                    #
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $netcard_list = $inputData['netcards'];
                    $hhm_id = $inputData['mobilizerid']; //The mobilizer Id that downloaded the e-Netcard
                    $device_serial = $inputData['device_serial'];
                    #
                    $total = $nt->PushNetcardOnline($netcard_list, $hhm_id, $device_serial);

                    //User Log Activity
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization", $description = "User with the User ID: " . $current_userid . " push " . $total . "e-Netcard Online", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => "$total e-Netcard was successfully pushed back to the ward",
                        'message' => 'success',
                        'total' => $total
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "mobilization", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Push eNetcard back to the ward", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Mobilization Module'
                    ));
                }
            }
            #   Mobilizer confirm download
            elseif (CleanData('qid') == '309') {

                $inputData = json_decode(file_get_contents('php://input'), true);

                $download_id   = $inputData['download_id']   ?? null;
                $hhm_id        = $inputData['mobilizerid']   ?? null; // The mobilizer Id that downloaded the e-Netcard
                $device_serial = $inputData['device_serial'] ?? null;

                if (!$download_id || !$hhm_id || !$device_serial) {
                    http_response_code(400);
                    echo json_encode(array(
                        'result_code' => 400,
                        'message'     => 'Missing required fields: download_id, mobilizerid, or device_serial'
                    ));
                    exit;
                }

                $decoded_privilege = json_decode($privilege, true);

                // Check Mobilization Privilege
                if (IsPrivilegeInArray($decoded_privilege, 'mobilization')) {

                    // e-Netcard Mobilizer push netcard online
                    $us = new Mobilization\Mobilization();
                    $confirm_result = $us->ConfirmDownload($hhm_id, $device_serial, $download_id);
                    $confirm_result['download_id'] = $download_id;

                    // Log User Activity
                    $result = "success";
                    logUserActivity(
                        $userid = $current_userid,
                        $platform = "pos",
                        $module = "mobilization",
                        $description = "User with the User ID: " . $current_userid . " confirm eNetcard download with Download ID:" . $confirm_result['download_id'],
                        $result,
                        $longtitude = "",
                        $latitude = ""
                    );

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset'     => "e-Netcard with Download ID: " . $confirm_result['download_id'] . " confirmation was successfully",
                        'message'     => 'success',
                        'data'        => $confirm_result
                    ));
                } else {
                    // Log Unauthorized Attempt
                    $result = "failed";
                    logUserActivity(
                        $userid = $current_userid,
                        $platform = "pos",
                        $module = "mobilization",
                        $description = "User with the User ID: " . $current_userid . " does not have priviledge to Push eNetcard back to the ward",
                        $result,
                        $longtitude = "",
                        $latitude = ""
                    );

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message'     => 'Unauthorized User Priviledge on Mobilization Module by User with ID: ' . $current_userid
                    ));
                }
            }




            /**
             * Netcard Movement Modules 
             * Begin
             * ********************************************************************************
             */

            #  Get LGA e-Netcard movement mobile app dashboard balances
            elseif (CleanData('qid') == '700') {
                $nt = new Netcard\NetcardTrans();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $lgaid = $inputData['lgaid'];

                #
                #    Get LGA movement mobile app dashboard balances
                $lgaBalances = $nt->GetMovementDashboardBalances($lgaid);
                $lgaMovementTopHistory = $nt->GetMovementTopHistory($lgaid); # You can add option of count
                $data = [
                    "lga_balances" => $lgaBalances,
                    "lga_movement_top_history" => $lgaMovementTopHistory
                ];

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'LGA Movement Dashboard balances and TOp 5 Histories',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get LGA movement History Lists
            elseif (CleanData('qid') == '701') {
                $nt = new Netcard\NetcardTrans();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $lgaid = $inputData['lgaid'];

                $data = $nt->GetMovementListHistory($lgaid); # You can add option of count
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'LGA Movement mobile app dashboard balances',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get Ward List and e-Netcard balances
            elseif (CleanData('qid') == '702') {
                $nt = new Netcard\NetcardTrans();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $lgaid = $inputData['lgaid'];

                $data = $nt->GetWardListAndBalances($lgaid);

                #   Get Ward List and e-Netcard Balances
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Ward List and their e-Netcard balances',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get mobilizers balances per Ward Level
            elseif (CleanData('qid') == '703') {
                $nt = new Netcard\NetcardTrans();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $lgaid = $inputData['lgaid'];

                $data = $nt->GetWardLevelMobilizersBalances($lgaid);
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Get mobilizers balances per Ward Level',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Netcard movement from LGA to Ward (A2)
            elseif (CleanData('qid') == '704') {
                $nt = new Netcard\NetcardTrans();

                $inputData = json_decode(file_get_contents('php://input'), true);

                $total = $inputData['total'];
                $lgaid = $inputData['originatingLgaid'];
                $wardid = $inputData['destinationWardid'];
                $userid = isset($current_userid) ? $current_userid : $inputData['userid'];

                #
                if ($nt->LgaToWardMovement($total, $lgaid, $wardid, $userid)) {
                    $data = [
                        "total" => $total
                    ];
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "User with the User ID: " . $current_userid . " Moved " . $total . " eNetcard to a ward ", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Netcard movement from LGA to Ward',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    $data = [
                        "total" => 0
                    ];

                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "User with the User ID: " . $current_userid . " tried to Moved " . $total . " e-Netcard to a ward and failed ", $result, $longtitude = "", $latitude = "");

                    http_response_code(400);
                    echo json_encode(array(
                        'result_code' => 400,
                        'dataset' => 'Netcard movement from LGA to Ward Failed',
                        'message' => 'failed',
                        'data' => $data
                    ));
                }
            }

            #   Netcard reverse movement from Ward to lga (A5)
            elseif (CleanData('qid') == '705') {
                /*
                *  Runs e-Netcard Samples
                *
                *  Netcard reverse movement from Ward to lga
                */
                $nt = new Netcard\NetcardTrans();
                $inputData = json_decode(file_get_contents('php://input'), true);

                $total = $inputData['total'];
                $lgaid = $inputData['destinationLgaid'];
                $wardid = $inputData['originatingWardid'];
                $userid = isset($current_userid) ? $current_userid : $inputData['userid'];

                #
                if ($nt->WardToLgaMovement($total, $wardid, $lgaid, $userid)) {

                    $data = [
                        "total" => $total
                    ];
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "User with the User ID: " . $current_userid . " successfully reversed " . $total . " eNetcard to a ward with ID: " . $wardid, $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Netcard reversal from Ward to LGA Successful',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {

                    $data = [
                        "total" => $total
                    ];
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "User with the User ID: " . $current_userid . " Tried to reversed " . $total . " eNetcard to a ward with ID: " . $wardid . " and failed", $result, $longtitude = "", $latitude = "");

                    http_response_code(400);
                    echo json_encode(array(
                        'result_code' => 400,
                        'dataset' => 'Netcard reveral from Ward to LGA Failed',
                        'message' => 'failed',
                        'data' => $data
                    ));
                }
            }

            /**
             * Netcard Movement Modules
             * Ends
             * **********************************************************************************
             */

            #   Dashboard summary with options
            elseif (CleanData('qid') == 'gen005') {

                #
                #   Dashboard summary with options
                #
                #   Filtered by mobilized date
                $mob_date = CleanData('mdt');
                #   Filtered by Geo-Level
                $geo_level = CleanData('gl');
                $geo_level_id = CleanData('glid');
                $ex = new Mobilization\Mobilization();
                $total = $ex->DashSummary($mob_date, $geo_level, $geo_level_id);
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Dashboard summary',
                    'message' => 'success',
                    'total' => $total
                ));
            }

            /**
             * Mobilization Modules
             * Ends
             * **********************************************************************************
             */

            /**
             * Distribution Modules 
             * Begin
             * ********************************************************************************
             */


            #   Download Mobilization Data
            if (CleanData('qid') == '401') {
                /**
                 * Check Distribution Privilege
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'distribution')) {
                    #
                    #   Distribution 
                    #
                    #   Download Mobilization Data
                    $ex = new Distribution\Distribution();

                    $inputData = json_decode(file_get_contents('php://input'), true);
                    $dpid = $inputData['dpid'];

                    $data = $ex->DownloadMobilizationData($dpid);
                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "Distribution Master", $description = "User with the User ID: " . $current_userid . " Download Mobilization data ", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Download Mobilization Dataset for distribution',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "distribution", $description = "User with the User ID: " . $current_userid . " is trying to access obilization Data without Distribution Privilege", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Distribution Module'
                    ));
                }
            }
            #   Distribution  Bulk distribution data upload
            elseif (CleanData('qid') == '402') {
                /**
                 * Check Distribution Privilege
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'distribution')) {
                    #
                    #   Distribution  Bulk distribution data upload
                    #
                    #   Bulk upload
                    $ex = new Distribution\Distribution();
                    ##   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, etoken_id]
                    /*
                                //Test Data
                                $bulk_data = [
                                    ['dp_id'=>1,'mobilization_id'=>1,'recorder_id'=>21,'distributor_id'=>25,'collected_nets'=>4,'is_gs_net'=>1,
                                    'gs_net_serial'=>'992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912',
                                    'collected_date'=>'2022-04-22','etoken_id'=>40]
                                ];
                            */

                    ##   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, etoken_id, etoken_serial, longitude, latitude, device_serial, app_version]



                    $bulk_data = json_decode(file_get_contents('php://input'), true);
                    $result = $ex->BulkDistibutionStatus($bulk_data);

                    if ($result['success'] || $result['fail']) {
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "pos", $module = "Distribution Sync", $description = "User with the User ID: " . $current_userid . " Synchronize " . $result['success'] . "Distribution data ", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'dataset' => 'Total ' . $result['success'] . ' distribution data uploaded successfully',
                            'message' => 'success',
                            'total' => $result['success']
                        ));
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'dataset' => "Unable to upload bulk distribution data at the moment",
                            'message' => 'failed',
                            'total' => $result['success']
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "distribution", $description = "User with the User ID: " . $current_userid . " is trying to access Upload Distribution Data without Distribution Privilege", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on Distribution Module'
                    ));
                }
            }
            #   Bulk Distribution Data Upload with returning e-token ID
            elseif (CleanData('qid') == '402a') {

                /**
                 * Check Distribution Privilege
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'distribution')) {

                    #
                    #   Distribution  Bulk distribution data upload
                    #
                    #   Bulk upload
                    $ex = new Distribution\Distribution();
                    $result_data = [];
                    $etoken_serial_set = [];

                    $bulk_data = json_decode(file_get_contents('php://input'), true);
                    $result_data = $ex->BulkDistibutionWithReturns($bulk_data);
                    #get duplicate and success toa single array
                    $etoken_serial_set = array_unique(array_merge($result_data["success"], $result_data["failed"]));
                    #
                    if (is_array($etoken_serial_set) && count($etoken_serial_set)) {

                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "pos", $module = "Distribution Sync", $description = "User with the User ID: " . $current_userid . " Synchronize " . count($etoken_serial_set) . "Distribution data ", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'dataset' => $etoken_serial_set,
                            'message' => count($etoken_serial_set) . ' distribution data uploaded successfully',
                            'total' => count($etoken_serial_set)
                        ));
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'dataset' => $etoken_serial_set,
                            'message' => 'Unable to upload bulk distribution data at the moment',
                            'total' => 0
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "pos", $module = "distribution", $description = "User with the User ID: " . $current_userid . " is trying to access Upload Distribution Data without Distribution Privilege", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'dataset' => '',
                        'message' => 'Unauthorized User Priviledge on Distribution Module',
                        'total' => 0
                    ));
                }
            }

            #   Get DP Locations details with DP ID
            elseif (CleanData('qid') == '403') {

                #
                #   Distribution 
                #
                #   Get DP Locations details with DP ID
                $ex = new Distribution\Distribution();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $guid = $inputData['guid'];

                $data = $ex->GetGeoCodexDetails($guid);
                #
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Get Geo location codex by guid',
                    'message' => 'success',
                    'data' => $data
                ));
            }

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
            elseif (CleanData('qid') == '600') {
                # Traceability search
                $inputData = json_decode(file_get_contents('php://input'), true);
                $gtin = $inputData['gtin'];
                $sgtin = $inputData['sgtin'];

                $ex = new Distribution\GsVerification();
                $data = $ex->TraceabilitySearch($gtin, $sgtin);
                #
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Get Geo location codex by guid',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            /**
             * Traceability Search Modules
             * Ends
             * **********************************************************************************
             */



            /**
             * SMC MODULES 
             * Begin
             * ********************************************************************************
             * Start from 800
             */
            #   Create Bulk Parent Record
            elseif (CleanData('qid') == '900') {
                /**
                 * Check for SMC Priviledge
                 */

                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {

                    #
                    #   Create bulk Households Or Prarent
                    #   ['hh_token','beneficiary_id','dpid','name','gender','dob','longitude','latitude', 'user_id','device_serial','app_version','created']
                    $hh = new Smc\Registration();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $hh->CreateHouseholdBulk($bulk_data);
                        $total = count($data_result);
                        if ($total > 0) {
                            //User Log Activity
                            $result = "success";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Households was Successfully Registered by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(200);
                            echo json_encode(array(
                                "result_code" => 200,
                                "message" => "Total of $total Household was created successfully",
                                "dataset" => $data_result,
                                "total" => $total
                            ));
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Users attendace Update Failed to be updated (" . $hh_token . ") by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Create Household at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Update Parent Bulk Record
            elseif (CleanData('qid') == '901') {
                /**
                 * Check for SMC Priviledge
                 */

                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {

                    #
                    #   Update bulk Households Or Prarent
                    # ['hh_token','hoh','phone']
                    $hh = new Smc\Registration();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $hh->UpdateHouseholdBulk($bulk_data);
                        $total = count($data_result);
                        if ($total > 0) {
                            //User Log Activity
                            $result = "success";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Households was Successfully Updated by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(200);
                            echo json_encode(array(
                                "result_code" => 200,
                                "message" => "Total of $total Household was updated successfully",
                                "dataset" => $data_result,
                                "total" => $total
                            ));
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Household data Failed to be updated by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Update Household at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");
                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }
            # ['hh_token','hoh','phone']
            // public function UpdateHouseholdBulk($bulk_data){


            #   Create Bulk Child Record
            elseif (CleanData('qid') == '902') {

                /**
                 * Check for SMC Priviledge
                 */

                #   ['dpid','hh_token','hoh','phone','longitude','latitude','user_id', 'device_serial', 'app_version','created']
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {

                    #
                    #   Create bulk Child
                    $hh = new Smc\Registration();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $hh->CreateChildBulk($bulk_data);
                        $total = count($data_result);
                        if ($total > 0) {
                            //User Log Activity
                            $result = "success";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Child was Successfully Registered by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(200);
                            echo json_encode(array(
                                "result_code" => 200,
                                "message" => "Total of $total Child Record was created successfully",
                                "dataset" => $data_result,
                                "total" => $total
                            ));
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Child Record Failed to be Created by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Create Child Record at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to uae SMC Module ", $result, $longtitude = "", $latitude = "");
                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Update Bulk Child Record
            elseif (CleanData('qid') == '903') {

                /**
                 * Check for SMC Priviledge
                 */

                #   ['beneficiary_id','name','gender','dob']
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {

                    #
                    #   Update bulk Child
                    $hh = new Smc\Registration();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $hh->UpdateChildBulk($bulk_data);
                        $total = count($data_result);
                        if ($total > 0) {
                            //User Log Activity
                            $result = "success";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Child was Successfully Updated by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(200);
                            echo json_encode(array(
                                "result_code" => 200,
                                "message" => "Total of $total Household was updated successfully",
                                "dataset" => $data_result,
                                "total" => $total
                            ));
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Child Record Failed to be Update by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Update Child Record at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Drug Administration
            elseif (CleanData('qid') == '904') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Drug Administration
                    #   [periodid, uid, dpid, beneficiary_id, is_eligible, not_eligible_reason, is_refer, drug, drug_qty, redose_count, redose_reason, user_id, longitude, latitude, device_serial,app_version, collected_date,issue_id,resode_issue_id]
                    $hh = new Smc\DrugAdmin();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $hh->BulkSave($bulk_data);
                        $total = count($data_result);
                        if ($total > 0) {
                            //User Log Activity
                            $result = "success";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Drugs was Successfully Administered by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(200);
                            echo json_encode(array(
                                "result_code" => 200,
                                "message" => "Total of $total Drug Administered successfully Saved",
                                "dataset" => $data_result,
                                "total" => $total
                            ));
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Drug Administration Failed to be Added by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Save Drug Data at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Update Drug Administration
            elseif (CleanData('qid') == '904b') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Drug Administration
                    #   [uid,redose_count,redose_reason, resode_issue_id]
                    $hh = new Smc\DrugAdmin();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $hh->BulkRedose($bulk_data);
                        $total = count($data_result);
                        if ($total > 0) {
                            //User Log Activity
                            $result = "success";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Drugs Redose was Successfully Updated by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(200);
                            echo json_encode(array(
                                "result_code" => 200,
                                "message" => "Total of $total Drug Redose successfully Updated",
                                "dataset" => $data_result,
                                "total" => $total
                            ));
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Drug Redose Failed to be Updated by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Save Drug Redose Data at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   ICC - Issue: Inventory Control Administration (ICC)
            elseif (CleanData('qid') == '905') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   ICC - Issue: Inventory Control Administration (ICC)
                    #   [uid, dpid, issuer_id, cdd_lead_id, cdd_team_code, periodid, issue_date, issue_day, issue_drug, drug_qty, device_serial, app_version]

                    $ic = new Smc\Icc();
                    #
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $ic->BulkIccIssue($bulk_data);

                        $data_result = $data_result !== false ? $data_result : [];

                        $total = count($data_result);
                        // if ($total > 0) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total ICC Record Successfully uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            "result_code" => 200,
                            "message" => "Total of $total ICC Issued Record successfully Uploaded",
                            "dataset" => $data_result,
                            "total" => $total
                        ));
                        /*
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total ICC Record Failed to be Uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Upload ICC Issued Data at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                            */
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   ICC - Receive: Inventory Control Administration (ICC)
            elseif (CleanData('qid') == '906') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   ICC - Receive: Inventory Control Administration (ICC)
                    #   [uid, dpid, receiver_id, cdd_lead_id, cdd_team_code, periodid, received_date, received_day, received_drug, total_qty, full_dose_qty, partial_qty, wasted_qty]

                    $ic = new Smc\Icc();
                    #
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $ic->BulkIccReceive($bulk_data);
                        $data_result = $data_result !== false ? $data_result : [];


                        $total = count($data_result);
                        // if ($total > 0) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total ICC Record Successfully uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            "result_code" => 200,
                            "message" => "Total of $total ICC Received Record successfully Uploaded",
                            "dataset" => $data_result,
                            "total" => $total
                        ));
                        /*
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total ICC Record Failed to be Uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Upload ICC Received Data at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                            */
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Get ICC Administration Record List using dpid
            elseif (CleanData('qid') == '907') {
                #   Get ICC Administration Record List
                $us = new Smc\Icc();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $dpid = $inputData['dpid'];

                $data = $us->GetAdministrationRecord($dpid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'ICC Administartion Record List using DP ID',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Bulk Update Drug Referrer attended to
            elseif (CleanData('qid') == '908') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Referrer Bulk Upload
                    $hh = new Smc\Icc();
                    #   Data structure
                    $bulk_data = json_decode(file_get_contents('php://input'), true);

                    if (!empty($bulk_data)) {
                        $data_result = $hh->BulkSaveReferrer($bulk_data);
                        $data_result = $data_result !== false ? $data_result : [];

                        $total = count($data_result);
                        // if ($total > 0) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Child Referrer recorded Successfully Uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            "result_code" => 200,
                            "message" => "Total of $total Child Referrer Record successfully Uploaded",
                            "dataset" => $data_result,
                            "total" => $total
                        ));
                        /*
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total Child Referrer Records Failed to be Uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Upload attended Referrer Data at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                        */
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Download ICC Balance
            elseif (CleanData('qid') == '909') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Download ICC Balance
                    $hh = new Smc\Icc();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $period_id = $inputData['periodid'];
                    $cddid = $inputData['cddid'];
                    $device_id = $inputData['device_id'];
                    $app_version = $inputData['app_version'];

                    $d = $hh->IccDownloadBalance($period_id, $cddid, $device_id, $app_version);
                    $data = $d != false ? $d : [];

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Download ICC Balance on device ID" . $device_id . " (" . json_encode($d) . ")", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Download ICC Balance',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }
            #   ICC Downlaod Confirmation
            elseif (CleanData('qid') == '909a') {
                /**
                 * Check for SMC Priviledge
                 */
                $inputData = json_decode(file_get_contents('php://input'), true);

                $download_id = $inputData['download_id'] ?? null;
                $cdd_lead_id = $inputData['cddid'] ?? null;
                $issue_id = $inputData['issue_id'] ?? null;

                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Download ICC Balance
                    $hh = new Smc\Icc();
                    #   Data structure
                    $data = [];

                    $data = [
                        'status' => $hh->ConfirmDownload($download_id, $cdd_lead_id, $issue_id),
                        'download_id' => $download_id,
                        'issue_id' => $issue_id,
                        'cddid' => $cdd_lead_id
                    ];

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $cdd_lead_id . " Confirmed the download of an ICC with issue ID:" . $issue_id, $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'ICC Downloaded Accepted',
                        'message' => 'success',
                        'data' => $data
                    ));
                    return;
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $cdd_lead_id . " ICC Download Confirmation Failed with issue ID:" . $issue_id, $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   ICC Downlaod Acceptance Confirmation
            elseif (CleanData('qid') == '909b') {
                /**
                 * Check for SMC Priviledge
                 */
                $inputData = json_decode(file_get_contents('php://input'), true);

                $issue_id = $inputData['issue_id'] ?? null;

                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   ICC Acceptance
                    $hh = new Smc\Icc();
                    #   Data structure
                    $data = [];

                    $data = [
                        'status' => $hh->AcceptanceAccept($issue_id),
                        'issue_id' => $inputData['issue_id']
                    ];

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Accepted the download of an ICC with issue ID:" . $issue_id, $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'ICC Downloaded Confirmed',
                        'message' => 'success',
                        'data' => $data
                    ));
                    return;
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " ICC Download Acceptance Failed with issue ID:" . $issue_id, $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }
            #   ICC Rejection Confirmation
            elseif (CleanData('qid') == '909c') {
                /**
                 * Check for SMC Priviledge
                 */
                $inputData = json_decode(file_get_contents('php://input'), true);

                $issue_id = $inputData['issue_id'] ?? null;
                $reasons = $inputData['reasons'] ?? "";

                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   ICC Rejection
                    $hh = new Smc\Icc();
                    #   Data structure
                    $data = [];

                    $data = [
                        'status' => $hh->AcceptanceReject($issue_id, $reasons),
                        'issue_id' => $inputData['issue_id']
                    ];

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Reject the download of an ICC with issue ID:" . $issue_id, $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'ICC Downloaded Rejection Successful',
                        'message' => 'success',
                        'data' => $data
                    ));
                    return;
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " ICC Download Rejection Failed with issue ID:" . $issue_id, $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Get ICC Reconcilation Data
            elseif (CleanData('qid') == '910') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Get ICC Reconcilation Data
                    $hh = new Smc\Icc();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $dpid = $inputData['dpid'];
                    $periodid = $inputData['periodid'];
                    $data = $hh->GetReconciliationMaster($periodid, $dpid);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Download ICC Reconcilation Data", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Get ICC Reconcilation Data',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Bulk ICC Reconcile Upload
            elseif (CleanData('qid') == '911') {

                /**
                 * Check User Priviledge 
                 * For SMC
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    /**
                     *  Bulk ICC Reconcile Upload
                     * 
                     */

                    $hh = new Smc\Icc();

                    // New VErsion ['issue_id', 'cdd_lead_id', 'drug', 'used_qty', 'full_qty', 'partial_qty', 'wasted_qty', 'loss_qty', 'loss_reason', 'receiver_id', 'device_serial', 'app_version', 'reconcile_date']

                    $bulk_data = json_decode(file_get_contents('php://input'), true);
                    #
                    if (!empty($bulk_data)) {
                        $data_result = $hh->BulkSaveRconciliation($bulk_data);
                        $data_result = $data_result !== false ? $data_result : [];

                        $total = count($data_result);
                        // if ($total > 0) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total ICC reconcile Successfully Uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            "result_code" => 200,
                            "message" => "Total of $total Drug Reconcilation Record successfully Uploaded",
                            "dataset" => $data_result,
                            "total" => $total
                        ));

                        /*
                        } else {
                            //User Log Activity
                            $result = "failed";
                            logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total ICC Records Failed to be Uploaded by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                            http_response_code(400);
                            echo json_encode(array(
                                'result_code' => 400,
                                'message' => 'Unable to Upload ICC Reconcilation Data at the moment.',
                                'dataset' => $data_result,
                                'total' => $total
                            ));
                        }
                        */
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Upload ICC Reconcilation data ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module'
                    ));
                }
            }

            #   Push CCD Lead Drug Balance Online
            elseif (CleanData('qid') == '912') {
                /**
                 * Check SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Push CCD Lead Drug Balance Online
                    $nt = new Smc\Icc();
                    #
                    $bulk_data = json_decode(file_get_contents('php://input'), true);
                    if (is_array($bulk_data)) {
                        #
                        $data_result = $nt->PushBalance($bulk_data);
                        $data_result = $data_result !== false ? $data_result : [];
                        $total = count($data_result);


                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " push " . $total . "Drug Balances Online", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'message' => $total . ' Drug Balance was successfully pushed back Online to the HF',
                            'dataset' => $data_result,
                            'total' => $total
                        ));
                    } else {
                        $result = "failed";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " is trying to push Drug Balances Online", $result, $longtitude = "", $latitude = "");

                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Invalid data point Upload',
                            'dataset' => $bulk_data,
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Push Drug Balance back to the HF", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module'
                    ));
                }
            }

            #   Reconcile balance
            elseif (CleanData('qid') == '913') {
                /**
                 * Check SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Reconcile CCD Lead Drug Balance
                    $nt = new Smc\Icc();
                    #
                    $bulkData = json_decode(file_get_contents('php://input'), true);
                    $logs = "";

                    // Ensure bulk data is valid and format it for logging
                    if (is_array($bulkData)) {
                        $formatted = array_map(function ($item) {
                            return $item['drug'] . " = " . $item['qty'];
                        }, $bulkData);

                        $logs = implode(", ", $formatted) . " Device ID:" . $bulkData[0]["device_id"];
                    }


                    #
                    $data = $nt->ReconcileBalanceRun($bulkData);
                    $data = $data  !== false ? $data : [];

                    if (count($data)) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " reconcile " . $logs . " successfull", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            'result_code' => 200,
                            'message' => $logs . ' successfully reconciled',
                            'data' => $data
                        ));
                    } else {
                        $result = "failed";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " reconcile " . $logs . " failed", $result, $longtitude = "", $latitude = "");

                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => $logs,
                            'data' => $data
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Drug", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module'
                    ));
                }
            }


            #   Get DP/Facility Balances
            elseif (CleanData('qid') == '914') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Get ICC Reconcilation Data
                    $hh = new Smc\Icc();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $dpid = $inputData['dpid'];
                    $periodid = $inputData['periodid'];
                    $data = $hh->GetIccBalanceForDp($periodid, $dpid);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . "View CDD Wallet balances ", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Get All CDD Balances',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Bulk ICC Reconcile Upload
            elseif (CleanData('qid') == '915') {

                /**
                 * Check User Priviledge 
                 * For SMC
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    /**
                     *  Bulk ICC Reconcile Upload
                     * 
                     */

                    $hh = new Smc\Icc();

                    #   ['issue_id', 'cdd_lead_id', 'drug', 'used_qty', 'full_qty', 'partial_qty', 'wasted_qty', 'loss_qty', 'loss_reason', 'receiver_id', 'device_serial', 'app_version', 'reconcile_date']
                    // $bulk_data = [['issue_id' => 9, 'cdd_lead_id' => 1081, 'drug' => 'SPAQ 1', 'used_qty' => 5, 'full_qty' => 3, 'partial_qty' => 2, 'wasted_qty' => 0, 'loss_qty' => 0, 'loss_reason' => 'none', 'receiver_id' => 2001, 'device_serial' => 'NM0098', 'app_version' => 'v1.0.9', 'reconcile_date' => '2024-05-09 08:32:14']];

                    $bulk_data = json_decode(file_get_contents('php://input'), true);
                    #
                    if (!empty($bulk_data)) {

                        $data_result = $hh->BulkIccReturn($bulk_data);
                        $data_result = $data_result !== false ? $data_result : [];
                        $total = count($data_result);

                        // if ($total > 0) {
                        //User Log Activity
                        $result = "success";
                        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "$total ICC Issued Returned Successfully by user with the Login ID: " . $current_loginid . " :", $result, $longtitude = "", $latitude = "");

                        http_response_code(200);
                        echo json_encode(array(
                            "result_code" => 200,
                            "message" => "Total of $total ICC Issued returned successfully",
                            "dataset" => $data_result,
                            "total" => $total
                        ));
                    } else {
                        http_response_code(400);
                        echo json_encode(array(
                            'result_code' => 400,
                            'message' => 'Bad Request',
                        ));
                    }
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Upload ICC Reconcilation data ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module'
                    ));
                }
            }

            #   Get DP/Facility Balances
            elseif (CleanData('qid') == '916') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Get ICC Reconcilation Data
                    $hh = new Smc\Inventory();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $dpid = $inputData['dpid'];
                    $data = $hh->GetFacilityInventoryBalance($dpid);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Download Wallet Balance for the Facility ", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Get Current User DP Balance',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            #   Get App Movement List 
            elseif (CleanData('qid') == '917') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Get App Movement List 
                    $hh = new Smc\Logistics();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $data = $hh->getAppMovementList($inputData['periodId'], $inputData['conveyorId']);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " get Movement List ", $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Get Movement List',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }
            #   Confirm Movement Route
            elseif (CleanData('qid') == '918') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Get App Movement List 
                    $hh = new Smc\Logistics();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $data = $hh->confirmRoute($inputData['movementId']);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . "confirm Route Movement with ID " . $inputData['movementId'], $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Get Movement Confirmation',
                        'message' => 'success',
                        'status' => $data,
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'status' => false,
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }
            #   Origin Approval
            elseif (CleanData('qid') == '919') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Origin Approval
                    $hh = new Smc\Logistics();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $data = $hh->OriginApproval($inputData['movementId'], $inputData['name'], $inputData['designation'], $inputData['phone'], $inputData['userId'], $inputData['locationString'], $inputData['signature'], $inputData['approveDate'], $inputData['latitude'], $inputData['longitude'], $inputData['deviceSerial'], $inputData['appVersion']);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . "confirm Route Movement with ID " . $inputData['movementId'], $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Origin Approval',
                        'message' => 'success',
                        'status' => $data,
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'status' => false,
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }
            #   Conveyor Approval
            elseif (CleanData('qid') == '920') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Conveyor Approval
                    $hh = new Smc\Logistics();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $data = $hh->ConveyorApproval($inputData['movementId'], $inputData['name'], $inputData['designation'], $inputData['phone'], $inputData['userId'], $inputData['locationString'], $inputData['signature'], $inputData['approveDate'], $inputData['latitude'], $inputData['longitude'], $inputData['deviceSerial'], $inputData['appVersion']);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Conveyor Approval for movement with ID  " . $inputData['movementId'], $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Conveyor Approval',
                        'message' => 'success',
                        'status' => $data,
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'status' => false,
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }
            #   Destination Approval
            elseif (CleanData('qid') == '921') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Destination Approval
                    $hh = new Smc\Logistics();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $data = $hh->DestinationApproval($inputData['movementId'], $inputData['shipmentId'], $inputData['name'], $inputData['designation'], $inputData['phone'], $inputData['userId'], $inputData['locationString'], $inputData['signature'], $inputData['approveDate'], $inputData['latitude'], $inputData['longitude'], $inputData['deviceSerial'], $inputData['appVersion']);

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Destination Approval for movement with ID  " . $inputData['movementId'], $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Destination Approval',
                        'message' => 'success',
                        'status' => $data,
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'status' => false,
                        'dataset' => $result,
                        'total' => 0
                    ));
                }
            }

            // FacilityTransfer($inventory_id, $from_falicity_id, $to_facility_id, $primary_qty, $userid)
            #   Inter Facility Transfer
            elseif (CleanData('qid') == '922') {
                /**
                 * Check for SMC Priviledge
                 */
                if (IsPrivilegeInArray(json_decode($privilege, true), 'smc')) {
                    #
                    #   Download ICC Balance
                    $hh = new Smc\Inventory();
                    #   Data structure
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $d = $hh->FacilityTransfer($inputData['inventoryId'], $inputData['fromFalicityId'], $inputData['toFacilityId'], $inputData['primaryQty'], $current_userid);
                    $data = $d != false ? $d : [];

                    #
                    $result = "success";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " Transfer " . $inputData['primaryQty'] . " Commodity from Inventory ID: " . $inputData['inventoryId'] . " in Faicility with ID: " . $inputData['fromFalicityId'] . " To Facility ID: " . $inputData['toFacilityId'], $result, $longtitude = "", $latitude = "");

                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'dataset' => 'Inter Facility Transfer',
                        'message' => 'success',
                        'data' => $data
                    ));
                } else {
                    //User Log Activity
                    $result = "failed";
                    logUserActivity($userid = $current_userid, $platform = "mobile", $module = "smc", $description = "User with the User ID: " . $current_userid . " does not have priviledge to use SMC Module ", $result, $longtitude = "", $latitude = "");

                    http_response_code(401);
                    echo json_encode(array(
                        'result_code' => 401,
                        'message' => 'Unauthorized User Priviledge on SMC Module',
                        'dataset' => $result,
                        'data' => []
                    ));
                }
            }

            /**
             * SMC MODULES 
             * Ends
             * ********************************************************************************
             */




            /**
             * Monitoring Tools
             * Starts here
             * ********************************************************************************
             */
            #   Bulk Upload of Form i9a
            elseif (CleanData('qid') == '1000') {
                // if (IsPrivilegeInArray(json_decode($privilege, true), 'monitoring')) {

                $ob = new Form\INineA();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $list = $ob->BulkSave($inputData);
                $total = count($list);

                $result = $total ? "success" : "failed";
                $description = $total
                    ? "User with the User ID: $current_userid Sync $total i9a Form Successfully"
                    : "User with the User ID: $current_userid i9a Form Synchronization Failed";

                logUserActivity(
                    $current_userid,
                    "mobile",
                    "monitoring",
                    $description,
                    $result
                );

                http_response_code($total ? 200 : 401);
                echo json_encode([
                    'result_code' => $total ? 200 : 401,
                    'message' => $total
                        ? "$total  Form i9a successfully uploaded"
                        : "$total Bulk upload of Form i9a Failed: unable to save any of the bulk uploaded. Error: " . $ob->ErrorMessage,
                    'data' => $list
                ]);
                return;
                // } else {
                //     $result = "failed";
                //     logUserActivity(
                //         $current_userid,
                //         "mobile",
                //         "monitoring",
                //         "User with the User ID: $current_userid does not have privilege to use Monitoring Module",
                //         $result
                //     );

                //     http_response_code(401);
                //     echo json_encode([
                //         'result_code' => 401,
                //         'message' => 'Unauthorized User Privilege on Monitoring Module',
                //         'dataset' => $result,
                //         'total' => 0
                //     ]);
                // }
            }

            #   Bulk Upload of Form i9b
            elseif (CleanData('qid') == '1001') {
                if (IsPrivilegeInArray(json_decode($privilege, true), 'monitoring')) {

                    $ob = new Form\INineB();
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $list = $ob->BulkSave($inputData);
                    $total = count($list);

                    $result = $total ? "success" : "failed";
                    $description = $total
                        ? "User with the User ID: $current_userid Sync $total i9b Form Successfully"
                        : "User with the User ID: $current_userid i9b Form Synchronization Failed";

                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        $description,
                        $result
                    );

                    http_response_code($total ? 200 : 401);
                    echo json_encode([
                        'result_code' => $total ? 200 : 401,
                        'message' => $total
                            ? "$total  Form i9b successfully uploaded"
                            : "$total Bulk upload of Form i9b Failed: unable to save any of the bulk uploaded. Error: " . $ob->ErrorMessage,
                        'data' => $list
                    ]);
                    return;
                } else {
                    $result = "failed";
                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        "User with the User ID: $current_userid does not have privilege to use Monitoring Module",
                        $result
                    );

                    http_response_code(401);
                    echo json_encode([
                        'result_code' => 401,
                        'message' => 'Unauthorized User Privilege on Monitoring Module',
                        'dataset' => $result,
                    ]);
                }
            }

            #   Bulk Upload of Form i9c
            elseif (CleanData('qid') == '1002') {
                if (IsPrivilegeInArray(json_decode($privilege, true), 'monitoring')) {

                    $ob = new Form\INineC();
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $list = $ob->BulkSave($inputData);
                    $total = count($list);

                    $result = $total ? "success" : "failed";
                    $description = $total
                        ? "User with the User ID: $current_userid Sync $total i9c Form Successfully"
                        : "User with the User ID: $current_userid i9c Form Synchronization Failed";

                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        $description,
                        $result
                    );

                    http_response_code($total ? 200 : 401);
                    echo json_encode([
                        'result_code' => $total ? 200 : 401,
                        'message' => $total
                            ? "$total  Form i9b successfully uploaded"
                            : "$total Bulk upload of Form i9c Failed: unable to save any of the bulk uploaded. Error: " . $ob->ErrorMessage,
                        'data' => $list
                    ]);
                    return;
                } else {
                    $result = "failed";
                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        "User with the User ID: $current_userid does not have privilege to use Monitoring Module",
                        $result
                    );

                    http_response_code(401);
                    echo json_encode([
                        'result_code' => 401,
                        'message' => 'Unauthorized User Privilege on Monitoring Module',
                        'dataset' => $result,
                    ]);
                }
            }

            #   Bulk Upload of End Process Form
            elseif (CleanData('qid') == '1003') {
                if (IsPrivilegeInArray(json_decode($privilege, true), 'monitoring')) {

                    $ob = new Form\EndProcess();
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $list = $ob->BulkSave($inputData);
                    $total = count($list);

                    $result = $total ? "success" : "failed";
                    $description = $total
                        ? "User with the User ID: $current_userid Sync $total End Process Form Successfully"
                        : "User with the User ID: $current_userid End Process Form Synchronization Failed";

                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        $description,
                        $result
                    );

                    http_response_code($total ? 200 : 401);
                    echo json_encode([
                        'result_code' => $total ? 200 : 401,
                        'message' => $total
                            ? "$total  Form End Process successfully uploaded"
                            : "$total Bulk upload of Form End Process Failed: unable to save any of the bulk uploaded. Error: " . $ob->ErrorMessage,
                        'data' => $list
                    ]);
                    return;
                } else {
                    $result = "failed";
                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        "User with the User ID: $current_userid does not have privilege to use Monitoring Module",
                        $result
                    );

                    http_response_code(401);
                    echo json_encode([
                        'result_code' => 401,
                        'message' => 'Unauthorized User Privilege on Monitoring Module',
                        'dataset' => $result,
                    ]);
                }
            }

            #   Bulk Upload of 5% Revisit form
            elseif (CleanData('qid') == '1004') {
                if (IsPrivilegeInArray(json_decode($privilege, true), 'monitoring')) {

                    $ob = new Form\FiveRevisit();
                    $inputData = json_decode(file_get_contents('php://input'), true);

                    $list = $ob->BulkSave($inputData);
                    $total = count($list);

                    $result = $total ? "success" : "failed";
                    $description = $total
                        ? "User with ID: $current_userid Sync $total 5% revisit Form Successfully"
                        : "User with ID: $current_userid , $total 5% revisit Form  data Synchronization Failed";

                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        $description,
                        $result
                    );

                    http_response_code($total ? 200 : 401);
                    echo json_encode([
                        'result_code' => $total ? 200 : 401,
                        'message' => $total
                            ? "$total  5% Revisit bulk upload successfully uploaded"
                            : "$total Bulk upload of 5% Revisit Form Failed: unable to save any of the bulk uploaded. Error: " . $ob->ErrorMessage,
                        'data' => $list
                    ]);
                    return;
                } else {
                    $result = "failed";
                    logUserActivity(
                        $current_userid,
                        "mobile",
                        "monitoring",
                        "User with the User ID: $current_userid does not have privilege to use Monitoring Module",
                        $result
                    );

                    http_response_code(401);
                    echo json_encode([
                        'result_code' => 401,
                        'message' => 'Unauthorized User Privilege on Monitoring Module',
                        'dataset' => $result,
                    ]);
                }
            }

            #   Bulk Upload of CDD Monitoring form
            elseif (CleanData('qid') == '1005') {
                // if (IsPrivilegeInArray(json_decode($privilege, true), 'monitoring')) {

                $ob = new Form\SmcCdd();
                $inputData = json_decode(file_get_contents('php://input'), true);

                $list = $ob->BulkSave($inputData);
                $total = count($list);

                $result = $total ? "success" : "failed";
                $description = $total
                    ? "User with ID: $current_userid Sync $total SMC CDD Form Successfully"
                    : "User with ID: $current_userid , $total SMC CDD Form data Synch Failed";

                logUserActivity(
                    $current_userid,
                    "mobile",
                    "monitoring",
                    $description,
                    $result
                );

                http_response_code($total ? 200 : 401);
                echo json_encode([
                    'result_code' => $total ? 200 : 401,
                    'message' => $total
                        ? "$total SMC CDD bulk upload successfully uploaded"
                        : "$total Bulk upload of SMC CDD Form Failed: unable to save any of the bulk uploaded. Error: " . $ob->ErrorMessage,
                    'data' => $list
                ]);
                return;
                // } else {
                //     $result = "failed";
                //     logUserActivity(
                //         $current_userid,
                //         "mobile",
                //         "monitoring",
                //         "User with the User ID: $current_userid does not have privilege to use Monitoring Module",
                //         $result
                //     );

                //     http_response_code(401);
                //     echo json_encode([
                //         'result_code' => 401,
                //         'message' => 'Unauthorized User Priviledge on Monitoring Module',
                //         'dataset' => $result,
                //     ]);
                // }
            }

            #   Bulk Upload of HFW HFW Monitoring form
            elseif (CleanData('qid') == '1006') {
                // if (IsPrivilegeInArray(json_decode($privilege, true), 'monitoring')) {

                $ob = new Form\SmcHfw();
                $inputData = json_decode(file_get_contents('php://input'), true);

                $list = $ob->BulkSave($inputData);
                $total = count($list);

                $result = $total ? "success" : "failed";
                $description = $total
                    ? "User with ID: $current_userid Sync $total SMC HFW Form Successfully"
                    : "User with ID: $current_userid , $total SMC HFW Form data Synch Failed";

                logUserActivity(
                    $current_userid,
                    "mobile",
                    "monitoring",
                    $description,
                    $result
                );

                http_response_code($total ? 200 : 401);
                echo json_encode([
                    'result_code' => $total ? 200 : 401,
                    'message' => $total
                        ? "$total SMC HFW bulk upload successfully uploaded"
                        : "$total Bulk upload of SMC HFW Form Failed: unable to save any of the bulk uploaded. Error: " . $ob->ErrorMessage,
                    'data' => $list
                ]);
                return;
                // } else {
                //     $result = "failed";
                //     logUserActivity(
                //         $current_userid,
                //         "mobile",
                //         "monitoring",
                //         "User with the User ID: $current_userid does not have privilege to use Monitoring Module",
                //         $result
                //     );

                //     http_response_code(401);
                //     echo json_encode([
                //         'result_code' => 401,
                //         'message' => 'Unauthorized User Priviledge on Monitoring Module',
                //         'dataset' => $result,
                //     ]);
                // }
            }




            /**
             * Monitoring Tools
             * Ends here
             * ********************************************************************************
             */

            #
            #   General API /Master Data Starts here
            #

            #   Get Bank List
            if (CleanData("qid") == 'gen001') {
                #
                #   Get Bank List
                $gn = new System\General();
                $data = $gn->GetBankList();
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Change user password using login ID
            elseif (CleanData("qid") == 'gen002') {
                #
                #   User login sample
                #
                #   Change user password using login ID
                #
                $mg = new Users\UserManage();
                $inputData = json_decode(file_get_contents('php://input'), true);
                #

                $loginid = $inputData["loginid"];
                $old = $inputData["old"];
                $new = $inputData["new"];

                if ($mg->ChangePassword($loginid, $old, $new)) {
                    http_response_code(200);
                    echo json_encode(array(
                        'result_code' => 200,
                        'message' => 'Password Successfully Changed'
                    ));
                } else {
                    http_response_code(400);
                    echo json_encode(array(
                        'result_code' => 400,
                        'message' => 'Unable to change password, maybe use does not exist, or incorrect old password, please try again later'
                    ));
                }
            }
            #   Get location category list
            elseif (CleanData('qid') == 'gen003') {
                #
                #   Get location category list
                $us = new Mobilization\Mobilization();
                $data = $us->GetLocationCategories();
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Location Category List',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Get receipt header
            elseif (CleanData('qid') == 'gen004') {
                #
                #   Mobilization Master
                #   Get receipt header
                $ex = new Mobilization\Mobilization();
                $data = $ex->GetReceiptHeader();
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Excel Export Count Mobilization',
                    'message' => 'success',
                    'data' => $data
                ));
            }
            #   Get User working hours by days
            elseif (CleanData("qid") == 'gen005') {
                #
                #   Get User working hours by days
                $usr = new Users\UserManage();
                #   
                $inputData = json_decode(file_get_contents('php://input'), true);
                $userid = $inputData['userid'];
                #
                $data = $usr->GetUserWorkingHours($userid);
                #
                #
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Specific User working hours',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #
            #   General API /Master Data For SMC
            #

            #   Get Commodity list
            elseif (CleanData('qid') == 'gen006') {
                #   ['commodity_id','name','description','com_value', 'min_age', 'max_age']
                #
                #   Get Commodity list
                $us = new Smc\SmcMaster();
                $data = $us->GetCommodity();

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Commodity list',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get Reason list
            elseif (CleanData('qid') == 'gen007') {
                #   ['reason', 'category']
                #
                #   Get Reason list
                $us = new Smc\SmcMaster();
                $data = $us->GetReasons();

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Reason list',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get Active Period
            elseif (CleanData('qid') == 'gen008') {
                #   ['periodid', 'title', 'start_date', 'end_date']
                #
                #   Get Active Period
                $us = new Smc\SmcMaster();
                $data = $us->GetPeriodActive();

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Active SMC Period',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get MasterHousehold using dpid
            elseif (CleanData('qid') == 'gen009') {
                #   ['hhid','dpid','hh_token','hoh_name','hoh_phone']                #
                #   Get Master Household list
                $us = new Smc\SmcMaster();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $dpid = $inputData['dpid'];

                $data = $us->GetMasterHousehold($dpid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Household Master Data',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get MasterChild using dpid
            elseif (CleanData('qid') == 'gen010') {
                #   ['child_id','hh_token','beneficiary_id','dpid','name','gender','dob']
                #   Get Master Household list
                $us = new Smc\SmcMaster();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $dpid = $inputData['dpid'];

                $data = $us->GetMasterChild($dpid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Child Master Data',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get CDD Lead Master List using dpid
            elseif (CleanData('qid') == 'gen011') {
                #   ['child_id','hh_token','beneficiary_id','dpid','name','gender','dob']
                #   Get CDD Lead Master List
                $us = new Smc\SmcMaster();
                $inputData = json_decode(file_get_contents('php://input'), true);
                $dpid = $inputData['dpid'];

                $data = $us->GetCddLead($dpid);

                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Get CDD Lead Master List using',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get Referrer Master Lists using the DP ID and Period ID
            elseif (CleanData('qid') == 'gen012') {

                # Get Referrer Master List
                $hh = new Smc\DrugAdmin();

                $inputData = json_decode(file_get_contents('php://input'), true);
                $dpid = $inputData['dpid'];
                $periodid = $inputData['periodid'];

                $data = $hh->GetReferrerList($dpid, $periodid);
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'dataset' => 'Referrer Master List',
                    'message' => 'success',
                    'data' => $data
                ));
            }

            #   Get Combined geo structure
            else if (CleanData('qid') == 'gen013') {

                $inputData = json_decode(file_get_contents('php://input'), true);

                $lgaid = $inputData['lgaid'] ?? null;
                $sy = new System\General();
                # Use this endpoint if the User Geolevel is LGA 
                if (isset($lgaid) && !empty($lgaid)) {
                    #   complete data and good to go
                    $lga_data = $sy->GetThisLgaList($lgaid);
                    $ward_data = $sy->GetWardList($lgaid);
                    $dp_data = $sy->GetDpListByLga($lgaid);
                    $com_data = $sy->GetCommunityListByLga($lgaid);
                    #
                    $data = array('lgaid' => $lgaid, 'lga' => $lga_data, 'ward' => $ward_data, 'dp' => $dp_data, 'community' => $com_data);
                    echo json_encode(array(
                        'result_code' => 200,
                        'message' => 'Master Data Downloaded',
                        'data' => $data
                    ));
                }
                # Use this endpoint if the User Geolevel is Below LGA
                else {
                    #
                    $geo_level = $inputData['geo_level'];
                    $geo_level_id = $inputData['geo_level_id'];

                    // $lgaid = $inputData['lgaid'];


                    $structure = $sy->GetGeoStructureId($geo_level, $geo_level_id);

                    if ($structure) {
                        $stateid = $structure[0]['stateid'];
                        $lgaid = $structure[0]['lgaid'];
                        if ($stateid && $lgaid) {
                            #   complete data and good to go
                            $lga_data = $sy->GetThisLgaList($lgaid);
                            $ward_data = $sy->GetWardList($lgaid);
                            $dp_data = $sy->GetDpListByLga($lgaid);
                            $com_data = $sy->GetCommunityListByLga($lgaid);
                            #
                            $data = array('lgaid' => $lgaid, 'lga' => $lga_data, 'ward' => $ward_data, 'dp' => $dp_data, 'community' => $com_data);
                            echo json_encode(array(
                                'result_code' => 200,
                                'message' => 'complete data and good to go',
                                'data' => $data
                            ));
                        } else {
                            #   incomplete required data
                            echo json_encode(array(
                                'result_code' => 401,
                                'message' => 'incomplete required data',
                                'data' => $structure
                            ));
                        }
                    } else {
                        #   invalid requirement return empty
                        echo json_encode(array(
                            'result_code' => 401,
                            'message' => 'invalid requirement return empty',
                            'data' => $structure
                        ));
                    }
                }
            }
        }
    } catch (Exception $e)
    #   Flag the decode error if any
    {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform = "web", $module = "api", $description = "User with the User ID: " . $current_userid . " is trying to login using fake token - " . $e, $result, $longtitude = "", $latitude = "");

        http_response_code(401);
        echo json_encode(array(
            'result_code' => 401,
            'message' => 'Unauthorized Access Token ' . $e
        ));
    }
} else {
    logUserActivity($userid = 0, $platform = 'web', $module = "api", $description = 'An Unknown User is trying to access the API Endpoint: ', $result = 'failed', $longtitude = "", $latitude = "");
    http_response_code(401);
    echo json_encode(array(
        'result_code' => 401,
        'message' => 'Unauthorized Access'
    ));
}
