<?php
// session_start();
include_once('lib/autoload.php');
include_once('lib/common.php');
include_once('lib/config.php');
//  Log actions before leaving
log_system_access();
# Detect and safe base directory
$system_base_directory = __DIR__;

$default_home = ($config_pre_append_link ?? '') . 'login';
/*
if (!isset($_SESSION[$instance_token . '_guid']) && !$_SESSION[$instance_token . '_loggedin'] == true) {
    //
    http_response_code(404);
    echo json_encode(array(
        'result_code' => 404,
        'message' => 'Error:  404, Page not Found'
    ));
}
*/

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
// use Smc\Dashboard;

$secret_key = file_get_contents('lib/privateKey.pem');

require('lib/vendor/autoload.php');    //JWT Autoload
/*
     *  configure required protocol access
     */
$jwt_token = $_COOKIE[$secret_code_token];

$token = JWT::decode($jwt_token, new Key($secret_key, 'HS512'));



if ($token->iss !== $issuer_claim && $token->nbf > $issuedat_claim->getTimestamp() || $token->exp < $issuedat_claim->getTimestamp()) {
    //
    http_response_code(404);
    echo json_encode(array(
        'result_code' => 404,
        'message' => 'Error:  404, Page not Found'
    ));
}

$current_userid = $token->user_id;
$current_loginid = $token->login_id;
$priviledges = $token->system_privilege;
$user_priviledge = $token->system_privilege;

$v_g_id = $token->user_id;
$v_g_fullname = $token->fullname;
$v_g_loginid = $token->login_id;
$v_g_geo_level = $token->geo_level;
$v_g_geo_level_id = $token->geo_level_id;
$v_g_rolename = $token->role;
$v_g_pass_change = $token->user_change_password;
$priority = $token->priority;


$platform = "web";

// Get Priviledge permission
if (!function_exists('getPermission')) {
    function getPermission($user_priviledge, $module)
    {
        $arr = json_decode($user_priviledge, true);
        $user_privilege = GetPrivilegeInArray($arr, $module);
        return $user_privilege['permission_value'];
    }
}

#   Log users activity
if (!function_exists('logUserActivity')) {
    function logUserActivity($userid, $platform, $module, $description, $result)
    {
        /*
                $userid = 1;
                $platform = "web";
                $module = "Users management";
                $description = "Update user data: ";
                $result = "success";
            */
        $logid = System\General::LogActivity($userid, $platform, $module, $description, $result);
        if ($logid) {
            return;
            // echo "Created log for the activity successfully";
        } else {
            return;
            // echo "Unable to create log at the moment, please try again later";
        }
    }
}

#
#   GENERAL BLOCK ENDPOINT
#   Starts
#

#   Get Geo level List
if (CleanData("qid") == 'gen001') {
    #
    #   Get Geo level List
    #
    $gn = new System\General();
    $data = $gn->GetGeoLevel();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get State List
elseif (CleanData("qid") == 'gen002') {
    #
    #   Get State List
    $gn = new System\General();
    $data = $gn->GetStateList();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get LGA List
elseif (CleanData("qid") == 'gen003') {
    #
    #   Get LGA List
    $stateid = json_decode(file_get_contents('php://input'), true);
    $gn = new System\General();
    $data = $gn->GetLgaList($stateid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Cluster List
elseif (CleanData("qid") == 'gen004') {
    #
    #   Get Cluster List
    $lgaid = CleanData('e');
    $gn = new System\General();
    $data = $gn->GetClusterList($lgaid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Ward List
elseif (CleanData("qid") == 'gen005') {
    #
    #   Get Ward List
    $lgaid = CleanData('e');
    $gn = new System\General();
    $data = $gn->GetWardList($lgaid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Dp List
elseif (CleanData("qid") == 'gen006') {
    #
    #   Get Dp List
    $gn = new System\General();
    $wardid = CleanData('wardid');
    $data = $gn->GetDpList($wardid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Sample Get System Default List
elseif (CleanData("qid") == 'gen007') {
    #
    #   Sample Get System Default List
    $gn = new System\General();
    $data = $gn->GetDefaultSettings();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Bank List
elseif (CleanData("qid") == 'gen008') {
    #
    #   Get Bank List
    $gn = new System\General();
    $data = $gn->GetBankList();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Goe location codex
elseif (CleanData('qid') == 'gen009') {
    #
    #   Get Goe location codex
    #
    $mo = new System\General();
    #   parameter options ['dp','ward','lga','state'] default is dp (i.e without any parameter)
    #   Get details
    $data = $mo->GetGeoLocationCodex("all");
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Geo Location Codex',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Facility List Using and LGA ID
elseif (CleanData('qid') == 'gen010') {
    #
    #   Get Facility List Using and LGA ID
    #

    $master = new Smc\Logistics();
    $inputData = json_decode(file_get_contents('php://input'), true);

    #   Get details
    $data = $master->GetIssueByPeriod($inputData['periodId'], $inputData['lgaId']);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get All Facility Data by LGA',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Product Data
elseif (CleanData('qid') == 'gen011') {
    #
    #   Get Product Data
    #

    $master = new Smc\SmcMaster();
    $lgaId = json_decode(file_get_contents('php://input'), true);

    #   Get details
    $data = $master->GetCommodity();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Product List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get CMS Location Data
elseif (CleanData('qid') == 'gen012') {
    #
    #   Get CMS Location Data
    #
    $master = new Smc\SmcMaster();

    #   Get details
    $data = $master->GetCmsLocations();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'CMS Master List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get receipt header
elseif (CleanData('qid') == 'gen0013') {
    #
    #   Get receipt header
    $ex = new Mobilization\Mobilization();
    $data = $ex->GetReceiptHeader();
    #
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Receipt Header',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Transporter Data
elseif (CleanData('qid') == 'gen014') {
    #
    #   Get CMS Location Data
    #
    $master = new Smc\SmcMaster();

    #   Get details
    $data = $master->GetTransporter();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Transporter Master List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Transporter Data
elseif (CleanData('qid') == 'gen015') {
    #
    #   Get CMS Location Data
    #
    $master = new Smc\SmcMaster();

    #   Get details
    $data = $master->GetConveyors();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Conveyor Master List',
        'message' => 'success',
        'data' => $data
    ));
}





#
#   GENERAL BLOCK ENDPOINT
#   Ends
#


#   Sample Page
elseif (CleanData('qid') == 'sam001') {
    $uc = new Users\UserManage();
    $data = $uc->TableTestList();

    echo json_encode(array('id' => 200, 'data' => $data, 'message' => 'success'));
}

#   Activate/Deavtivate bulk users
elseif (CleanData("qid") == '001') {
    #
    #   Activate/Deavtivate bulk users
    #
    if (getPermission($user_priviledge, 'users') == 3) {

        $userids = json_decode(file_get_contents('php://input'), true);


        $usr = new Users\UserManage();
        #   users list
        $users = $userids;
        $total = $usr->BulkToggleUserStatus($users);
        if ($total) {
            $result = "success";
        } else {
            $result = "failed";
        }
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "$total User(s) with User ID [" . implode(', ', $userids) . "] De/Activated", $result);

        $json_data = array(
            "result_code" => 200,
            'message' => 'success',
            "total" => $total
        );
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
    }

    echo json_encode($json_data);
}
#   Create Bulk Users
elseif (CleanData("qid") == '002') {
    #
    #   Create Bulk Users
    #
    if (getPermission($user_priviledge, 'users') >= 2) {
        $inputData = json_decode(file_get_contents('php://input'), true);
        $role_id = $inputData['roleid'];

        if (!empty($inputData['groupName']) && !empty($inputData['password']) && !empty($inputData['geoLevel']) && !empty($inputData['geoLevelId'])) {
            $usr = new Users\BulkUser($inputData['groupName'], $inputData['password'], $inputData['geoLevel'], $inputData['geoLevelId'], $role_id);
            $total = $usr->CreateBulkUser($inputData['totalUser']);
            if ($total) {
                $result = "success";
                logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "$total User(s) Created with " . $inputData['groupName'] . " as Group Name, " . $inputData['geoLevel'] . " as Geo Level: ", $result);
                #On User Creation
                echo json_encode(array(
                    'result_code' => 201,
                    'message' => 'success',
                    'total' => $total
                ));
            } else {
                $result = "failed";
                logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $inputData['totalUser'] . " User(s) failed to be Created with " . $inputData['groupName'] . " as Group Name, " . $inputData['geoLevel'] . " as Geo Level: ", $result);
                #On user creation failed
                echo json_encode(array(
                    'result_code' => 400,
                    'message' => 'error',
                    'total' => $total
                ));
            }
        } else {
            //Log User Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = " User(s) Creation failed due to wrong data input: ", $result);
            #If all data supplied are wrong
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Deactivate users by Group
elseif (CleanData("qid") == '003') {
    #
    #   Deacvtivate users by Group
    #
    if (getPermission($user_priviledge, 'users') == 3) {
        $usr = new Users\UserManage();
        #   users list
        $group = CleanData("e");
        if ($usr->DeavtivateUserByGroup($group)) {
            // echo "$group user group has been deactivated successfully";
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $group . " User Group Successfully Deactivated", $result);

            #On Usergroup Deactivation
            echo json_encode(array(
                'result_code' => 201,
                'message' => 'success',
                'group' => $group
            ));
        } else {
            // echo "Unable to deactivate $group at the moment please try again later.";
            //Log User Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $group . " User Group failed to be Deactivated", $result);
            #On Usergroup Deactivation
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error',
                'group' => $group
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Activate users by Group
elseif (CleanData("qid") == '004') {
    #
    #   Activate users by Group
    #
    if (getPermission($user_priviledge, 'users') == 3) {

        $usr = new Users\UserManage();
        #   users list
        $group = CleanData("e");
        if ($usr->ActivateUserByGroup($group)) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = $group . " User Group Successfully Activated: ", $result);

            // $group user group has been activated successfully
            echo json_encode(array(
                'result_code' => 201,
                'message' => 'success',
                'group' => $group
            ));
        } else {
            // Unable to activate $group at the moment please try again later.
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "Unable to activate $group User Group at the moment please try again later", $result);

            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error',
                'group' => $group
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
} elseif (CleanData("qid") == '005')
#   Get user informations (Details)
{
    #   Get user informations (Details)
    #   Init User manage class
    $us = new Users\UserManage();
    $userid = CleanData("e");
    #   Get User's Base info returns array result single row
    $base = $us->GetUserBaseInfo($userid);
    #   Get User's Finance returns array result single row
    $finance = $us->GetUserFinance($userid);
    #   Get User's Identity returns array result single row
    $identity = $us->GetUserIdentity($userid);
    #   Get User's role structure returns array result single row
    $role = $us->GetUserRoleStructure($userid);
    #
    $data = array(
        "result_code" => 201,
        "message" => "success",
        "base" => $base,
        "finance" => $finance,
        "identity" => $identity,
        "role" => $role
    );

    #
    echo json_encode($data);
}
#   Bulk user update
elseif (CleanData("qid") == '006') {
    #
    #   Bulk user update
    #
    if (getPermission($user_priviledge, 'users') >= 2) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        $usr = new Users\UserManage();
        #
        # userid, roleid, first, middle, last, gender, email, phone, bank_name, account_name, account_no, bank_code, bio_feature
        #

        $userData = array($inputData);
        $total = $usr->BulkUserUpdate($userData);
        if ($total) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID " . $inputData['userid'] . " details Successfully Updated: ", $result);
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID " . $inputData['userid'] . " details Update Failed: ", $result);
        }
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'success',
            'total' => $total
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Get role list
elseif (CleanData("qid") == '007') {
    #
    #   Get role list
    #
    $usr = new Users\UserManage();
    #   users list
    $data = $usr->GetRoleList($priority);
    // $data = $usr->GetRoleList(3);
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   update user role
elseif (CleanData("qid") == '008') {
    #
    #   update user role
    #
    if (getPermission($user_priviledge, 'users') == 3) {

        $role_id = CleanData("r");
        $user_id = CleanData("u");

        $usr = new Users\UserManage();
        #   users list
        #
        #   UpdateUserRole($role_id, $user_id)
        $data = $usr->UpdateUserRole($role_id, $user_id);
        #
        #
        if ($data) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $userid . " Role Successfully Updated to Role ID: $role_id", $result);
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'success',
                'data' => $data
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $userid . " Role Failed to be Updated to Role ID: $role_id", $result);

            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error',
                'data' => $data
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}

#   Bulk User Role Update
elseif (CleanData("qid") == '008a') {
    #
    #   Bulk User Role Update
    #
    if (getPermission($user_priviledge, 'users') == 3) {

        $roleId = CleanData("r");

        $usr = new Users\UserManage();
        $inputData = json_decode(file_get_contents('php://input'), true);
        #
        #
        $userIds = $inputData;
        #   users list
        #
        #   UpdateUserRole($role_id, $user_id)
        $data = $usr->BulkChangeRole($userIds, $roleId);
        #
        #
        if ($data) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $current_userid . " change " . $data . " Users Roles with IDs" . json_encode($userIds) . " Role Successfully Updated to Role ID: $roleId", $result);
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'success',
                'data' => $data
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . json_encode($userIds) . " Role Failed to be Updated to Role ID: $roleId", $result);

            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error',
                'data' => $data
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Update user geo level
elseif (CleanData("qid") == '009') {
    #
    if (getPermission($user_priviledge, 'users') == 3) {

        #   Update user level
        $us = new Users\UserManage();
        $inputData = json_decode(file_get_contents('php://input'), true);

        #

        $userid = $inputData["u"];
        $geo_level = $inputData["l"];
        $geo_level_id = $inputData["id"];

        if ($us->ChangeUserLevel($userid, $geo_level, $geo_level_id)) {
            // echo "User Geo Level updated successfully";
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . CleanData("u") . " Geo Level, Successfully Updated to, Geo Level: $geo_level , Geo Level ID: $geo_level_id", $result);
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'success: User Geo Level updated successfully'
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the User ID: " . $userid . " Geo Level, Failed to Updated to, Geo Level: $geo_level , Geo Level ID: $geo_level_id", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error: Unable to update the geo leve at the moment please try again later.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}

#   Update user geo level
elseif (CleanData("qid") === '009a') {
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
}

#   Get group list
elseif (CleanData("qid") == '010') {
    #
    #   Get group list
    $us = new Users\UserManage();
    #
    $data = $us->GetUserGroupList();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Change user password using login ID
elseif (CleanData("qid") == '011') {
    #
    #   User login sample
    #
    #   Change user password using login ID
    #
    $mg = new Users\UserManage();
    $inputData = json_decode(file_get_contents('php://input'), true)[0];
    #

    $loginid = $inputData["loginid"];
    $old = $inputData["old"];
    $new = $inputData["new"];

    if ($mg->ChangePassword($loginid, $old, $new)) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " password was Successfully Changed: ", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'Password Successfully Changed'
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " tried to change his/her password and Failed: ", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'Unable to change password, maybe user does not exist, or incorrect old password, please try again later'
        ));
    }
}
#   Reset user password using login ID
elseif (CleanData("qid") == '012') {
    #
    #   Reset user password using login IDs
    #
    $mg = new Users\UserManage();
    $inputData = json_decode(file_get_contents('php://input'), true);
    $loginid = $inputData['loginid'];


    if ($mg->ResetPassword($inputData['loginid'], $inputData['new'])) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " password was Successfully Reset: ", $result);
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' => "Password for <b>$loginid</b> has been reset successfully"
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "users", $description = "User with the Login ID: " . $loginid . " password Failed to be Reset: ", $result);
        http_response_code(400);
        echo json_encode(array(
            'result_code' => 400,
            'message' => "Unable to reset password, maybe use does not exist or system error, please try again later"
        ));
    }
}
#   Bulk User password Reset using User ID
elseif (CleanData("qid") === '012a') {
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
}

#   Run User bank account validation 
elseif (CleanData("qid") == '013') {
    #
    $usr = new Users\UserManage();
    #   
    $userid = CleanData('userid');
    $data = $usr->RunBankVerification($userid);
    #
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}

#   User excel export
elseif (CleanData("qid") == '014') {
    #
    #   Count user list to export
    // $v_g_geo_level = $_SESSION[$instance_token . '_geo_level'];
    // $v_g_geo_level_id = $_SESSION[$instance_token . '_geo_level_id'];

    #
    #   Filter column
    #
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('gl_id');

    $loginid = CleanData('lo');
    $active = CleanData('ac');
    $phone = CleanData('ph');
    $user_group = CleanData('gr');
    $name = CleanData('na');
    $bank_verification_status = CleanData('bv');    // parameters['failed' | 'success' | 'none']
    $role_id = CleanData('ri');                     # user filter by role id


    $us = new Users\UserManage();
    //  The first 2 parameters are required, the users geo-level & geo-level-id, the remaining are optional for filter
    // $total = $us->ExcelCountUsers($v_g_geo_level,$v_g_geo_level_id); ##other parameters are optional for filter
    $total = $us->ExcelCountUsers($v_g_geo_level, $v_g_geo_level_id, $loginid, $active, $phone, $user_group, $name, $geo_level, $geo_level_id, $bank_verification_status, $role_id); ##other parameters are optional for filter
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Count Users to download - ' . $v_g_geo_level_id . ' - ' . $v_g_geo_level,
        'message' => 'success',
        'total' => $total
    ));
}

#Add work hour extension to user
elseif (CleanData("qid") === '015') {
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
}








/*
*      User dashboard basic options
*/

#   Get Total users counts
elseif (CleanData("qid") == '020') {
    #
    #   Get Total users counts
    $us = new Users\UserManage();
    $data = $us->DashCountUser();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'total_user' => $data
    ));
}
#   Get total active and inavtive users
elseif (CleanData("qid") == '021') {
    #
    #   Get total active and inavtive users
    $us = new Users\UserManage();
    $data = $us->DashCountActive();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get count by geo level
elseif (CleanData("qid") == '022') {
    #
    #   Get count by geo level
    $us = new Users\UserManage();
    $data = $us->DashCountGeoLevel();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get users count by group
elseif (CleanData("qid") == '023') {
    #
    #   Get users count by group
    $us = new Users\UserManage();
    $data = $us->DashCountUserGroup();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get count total users group
elseif (CleanData("qid") == '024') {
    #
    #   Get count total users group
    $us = new Users\UserManage();
    $data = $us->DashCountTotalGroup();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get users by gender distributions
elseif (CleanData("qid") == '025') {
    #
    #   Get users by gender distributions
    $us = new Users\UserManage();
    $data = $us->DashCountGender();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get group list
elseif (CleanData("qid") == '026') {
    #
    #   Get group list
    $us = new Users\UserManage();
    #
    $data = $us->GetUserGroupList();
    #
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Mobilier's List
elseif (CleanData("qid") == '027') {
    #
    #   Get Mobilier List
    $gn = new System\General();
    $wardid = CleanData("wardid");
    $data = $gn->GetMobilizerList($wardid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}


/*
     *      Training Section
     *      changed to Activity
     *      Starts
     */
#   Create Training
elseif (CleanData("qid") == '101') {
    #
    if (getPermission($user_priviledge, 'activity') >= 2) {

        #   Create Training
        $tr = new Training\Training();
        #   data
        $inputData = json_decode(file_get_contents('php://input'), true);
        #   $tr->CreateTraining('Training Tite', 'Geo location', 'Geo location id(int)', 'Training description', 'start date', 'end date');

        $trainingid = $tr->CreateTraining($inputData['title'], $inputData['geoLevel'], $inputData['geoLevelId'], $inputData['description'], $inputData['start_date'], $inputData['end_date']);
        #
        if ($trainingid) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully Created " . $inputData['title'] . " at " . $inputData['geoLevel'] . " Level", $result);
            echo json_encode(array(
                'result_code' => 201,
                'message' => "Training created successfully. Activity ID: $trainingid"
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried creating a Activity title " . $inputData['title'] . " at " . $inputData['geoLevel'] . " Level but Failed", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Create new Activity failed, unable to create at the moment, please try again later.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Update Training
elseif (CleanData("qid") == '102') {
    #
    if (getPermission($user_priviledge, 'activity') >= 2) {

        #   Update Training
        $tr = new Training\Training();
        #
        $inputData = json_decode(file_get_contents('php://input'), true);

        #   $tr->UpdateTraining('Training Tite', 'Geo location', 'Geo location id(int)', 'Training description', 'start date', 'end date','training id');
        if ($tr->UpdateTraining($inputData['title'], $inputData['geoLevel'], $inputData['geoLevelId'], $inputData['description'], $inputData['start_date'], $inputData['end_date'], $inputData['training_id'])) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully Updated " . $inputData['title'] . " Activity :", $result);
            echo json_encode(array(
                'result_code' => 201,
                'message' => "Activity updated successfully. Activity ID:" . $inputData['training_id']
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Tried to Update " . $inputData['title'] . " Activity but Failed: ", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'unable to update Activity at the moment, please try gain later.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Toggle training activation
elseif (CleanData('qid') == '103') {
    #
    if (getPermission($user_priviledge, 'activity') == 3) {

        #   Toggle training activation
        $trainingid = CleanData("e");

        $tr = new Training\Training();
        if ($tr->ToggleTraining($trainingid)) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " De/Activated Activity with ID " . $trainingid . " and Successfull: ", $result);
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'Training De/Activated successfully'
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried De/Activating Activity with ID " . $trainingid . " and Failed: ", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'unable to update Activity status at the moment, please try again later.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Get generic Training list (training list without privilege)
if (CleanData('qid') == '104a') {

    #
    #   Get generic Training list (training list without privilege)
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');

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
elseif (CleanData('qid') == '104') {
    #
    #
    #   Get generic Training Session list (training Session without privilege)
    $tr = new Training\Training();

    $training_id = CleanData("e");
    $data = $tr->getGenericSession($training_id);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Generic Session List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Create Session
elseif (CleanData('qid') == '105') {
    #
    #
    if (getPermission($user_priviledge, 'activity') == 3) {

        #   Create Session
        $tr = new Training\Training();
        $inputData = json_decode(file_get_contents('php://input'), true);
        #
        $training_id = $inputData['trainingid'];
        $session_title = $inputData['title'];
        $session_date = $inputData['date'];

        $id = $tr->CreateSession($training_id, $session_title, $session_date);
        if ($id) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully created a Activity Session Title: " . $session_title . " on Activity with Activity ID: $training_id :", $result);
            echo json_encode(array(
                'result_code' => 201,
                'message' => 'Session created successfully. <b>Session ID:</b> ' . $id
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried to create a Activity Session Title: " . $session_title . " on Activity with Activity ID: $training_id and Failed:", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Unable to create session at the moment, please try again later'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Update Session
elseif (CleanData('qid') == '106') {
    #
    #
    if (getPermission($user_priviledge, 'activity') >= 2) {

        #   Update Session
        $tr = new Training\Training();
        $inputData = json_decode(file_get_contents('php://input'), true);
        #
        $training_id = $inputData['trainingid'];
        $session_id =  $inputData['sessionid'];
        $session_title = $inputData['title'];
        $session_date = $inputData['date'];

        #
        if ($tr->UpdateSession($training_id, $session_title, $session_date, $session_id)) {
            #   successful
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully Updated Activity Session Title: " . $session_title . " on Activity with Activity ID: $training_id :", $result);
            echo json_encode(array(
                'result_code' => 201,
                'message' => 'Session updated successfully'
            ));
        } else {
            #   failed
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried to Update a Activity Session Title: " . $session_title . " on Activity with Activity ID: $training_id and Failed:", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Unable to update session at the moment please try again later.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Delete Session
elseif (CleanData('qid') == '107') {
    #
    #
    if (getPermission($user_priviledge, 'activity') == 3) {

        #   Delete Session
        $tr = new Training\Training();

        #
        $session_id = CleanData('e');
        if ($tr->DeleteSession($session_id)) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully Deleted a Activity Session with Session ID: " . $session_id . " :", $result);
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'Session deleted successfully'
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " tried to Delete a Activity Session with Session ID: " . $session_id . " and Failed:", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Unable to delete session at the moment, please try again later'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Get participants list for a particular training

/*
        elseif(CleanData('qid') == '108')
        {
            #
            #
            #   Get participants list for a particular training
            $tr = new Training\Training();
            $training_id = 4;
            $data = $tr->getParticipantsList($training_id);
            echo json_encode(array(
                'result_code'=>200,
                'dataset'=>'Participants List',
                'message'=>'success',
                'data'=>$data
            ));
        }
    */
#   Delete/Remove Participants 
elseif (CleanData('qid') == '109') {
    #
    #   Delete/Remove Participants 
    #
    if (getPermission($user_priviledge, 'activity') == 3) {
        $tr = new Training\Training();
        #   $total = $tr->AddParticipantsByGroup($training_id, $group_name);
        $inputData = json_decode(file_get_contents('php://input'), true);

        $participant_id_list = $inputData['selectedid'];     //  Users List
        $training_id = $inputData['trainingid'];

        // echo json_encode($participant_id_list);
        #
        $total = $tr->RemoveParticipant($training_id, $participant_id_list);

        if ($total) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully removed User(s) with User IDs [" . implode(', ', $participant_id_list) . "] from Activity with Activity ID: " . $training_id . " :", $result);
            echo json_encode(array(
                'result_code' => 200,
                'message' => "$total participant(s) removed successfully ",
                'total' => $total
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Tried removing the User(s) with User IDs [" . implode(', ', $participant_id_list) . "] from Activity with Activity ID: " . $training_id . " and Failed: ", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Unable to remove participant at the moment, please try again.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Add Participants by group name
elseif (CleanData('qid') == '110') {
    #
    #   Add Participants by group name
    #
    if (getPermission($user_priviledge, 'activity') >= 2) {
        $tr = new Training\Training();
        $inputData = json_decode(file_get_contents('php://input'), true);
        $training_id = $inputData['trainingid'];
        $group_name = $inputData['group_name'];
        // $total = $tr->AddParticipantsByGroup(4,'tta');
        $total = $tr->AddParticipantsByGroup($training_id, $group_name);

        if ($total) {
            //User Log Activity
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Successfully add Participant in " . $group_name . " Group to Activity with Activity ID: " . $training_id . " :", $result);
            echo json_encode(array(
                'result_code' => 200,
                'message' => "$total participant(s) successfully added",
                'total' => $total
            ));
        } else {
            //User Log Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "User with the Login ID: " . $current_loginid . " Failed to add Participant in " . $group_name . " Group to Activity with Activity ID: " . $training_id . " :", $result);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Unable to add participant at the moment, please try again.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}

/*
     *  Training Dashboard
     */
#   Dashboard count total Training
#   Get count total Training
elseif (CleanData('qid') == '111') {
    #
    #   Get count total Training
    $us = new Training\Training();
    $data = $us->DashCountTraining();
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Count Total Training',
        'message' => 'success',
        'data' => $data
    ));
}
#   Dashboard count Training active & inactive
elseif (CleanData('qid') == '112') {
    #
    #
    #   Dashboard count Training active & inactive
    $us = new Training\Training();
    $data = $us->DashCountActive();
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Count Active/inactive training',
        'message' => 'success',
        'data' => $data
    ));
}
#   Dashboard count Session
elseif (CleanData('qid') == '113') {
    #
    #
    #   Dashboard count Session
    $us = new Training\Training();
    $data = $us->DashCountSession();
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Count Session',
        'message' => 'success',
        'data' => $data
    ));
}
#
#   Excel Export Count
#
#   Excel Export Count Participants in the training (Active participants only)
elseif (CleanData('qid') == '114') {
    #
    #
    #   Excel Export Count Participants in the training (Active participants only)
    $us = new Training\Training();
    $training_id = CleanData('tid');
    $total = $us->ExcelCountParticipantList($training_id);
    #
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "$total Participant records was Successfully exported by user with the Login ID: " . $current_loginid . " and Activity ID: " . $training_id . " :", $result);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Count Participant List',
        'message' => 'success',
        'total' => $total
    ));
}
#   Excel Export Count Attendance list in a session
elseif (CleanData('qid') == '115') {
    #
    #
    #   Excel Export Count Attendance list in a session
    $us = new Training\Training();
    $sessionid = CleanData('sid');
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    $total = $us->ExcelCountAttendanceList($sessionid, $geo_level, $geo_level_id);
    #
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "training", $description = "$total Attendance records was Successfully exported by user with the Login ID: " . $current_loginid . " and Session ID: " . $sessionid . " :", $result);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Count Attendance List',
        'message' => 'success',
        'total' => $total
    ));
}
#   Web get the list of the attendance in a session
elseif (CleanData('qid') == '116') {
    #
    #
    #   Web get the list of the attendance in a session
    $us = new Training\Training();
    $sessionid = CleanData('sid');
    $data = $us->getAttendanceList($sessionid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Attendance List',
        'message' => 'success',
        'data' => $data
    ));
}
/*
    *      Training Section
    *      Ends
    */



/*
    *   E-Netcard Transactions
    * 
    */
#  All Netcard count at State, LGA, and Ward
elseif (CleanData('qid') == '201') {
    /*
         *  Runs e-Netcard Samples
         *
         *  List count of Location
         */
    #  All Netcard count at State, LGA, and Ward
    $nt = new Netcard\NetcardTrans();
    $dd = $nt->GetCountByLocation();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard Location List',
        'message' => 'success',
        'data' => $dd
    ));
}
#  Count Total active cards
elseif (CleanData('qid') == '201a') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Count Total active cards
         */

    $nt = new Netcard\NetcardTrans();
    $data = $nt->CountTotalNetcard();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Total Active Netcard existing',
        'data' => $data
    ));
}
#   Netcard movement from state to LGA
elseif (CleanData('qid') == '202') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard movement from state to LGA
         */
    if (getPermission($user_priviledge, 'enetcard') == 3) {
        $nt = new Netcard\NetcardTrans();
        // for stock count
        $total = CleanData("total");
        $stateid = CleanData("stateid");
        $lgaid = CleanData("lgaid");
        $userid = CleanData("id");
        $nt->StateToLgaMovement($total, $stateid, $lgaid, $userid);

        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully moved from State (State ID: $stateid) to LGA (LGA ID: $lgaid) by user with the Login ID: " . $current_loginid . " :", $result);
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Netcard movement from state to LGA',
            'message' => "$total"
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Netcard reverse movement from LGA to State
elseif (CleanData('qid') == '203') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard reverse movement from LGA to State
         */
    if (getPermission($user_priviledge, 'enetcard') == 3) {

        $nt = new Netcard\NetcardTrans();
        $total = CleanData("total");
        $lgaid = CleanData("originid");
        $stateid = CleanData("destinationid");
        $userid = CleanData("id");
        #
        $nt->LgaToStateMovement($total, $lgaid, $stateid, $userid);
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully Reversed from LGA (LGA ID: $lgaid) to State (State ID: $stateid) by user with the Login ID: " . $current_loginid . " :", $result);
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Netcard reverse movement from LGA to State',
            'message' => "$total"
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to De/Activate'
        );
        echo json_encode($json_data);
    }
}
#   Netcard movement from LGA to Ward
elseif (CleanData('qid') == '204') {
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
}
#   Netcard reverse movement from Ward to lga
elseif (CleanData('qid') == '205') {
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
}
#  List count LGAs balances
elseif (CleanData('qid') == '206') {
    /*
         *  Runs e-Netcard Samples
         *
         *  List count LGAs balances
         */
    $nt = new Netcard\NetcardTrans();
    $dd = $nt->GetCountLgaList();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard LGA List',
        'message' => 'success',
        'data' => $dd
    ));
}
#  List count Ward balances
elseif (CleanData('qid') == '207') {
    /*
         *  Runs e-Netcard Samples
         *
         *  List count Ward balances
         */
    $nt = new Netcard\NetcardTrans();
    $lgaid = CleanData("lgaid");
    $dd = $nt->GetCountWardList($lgaid);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard Ward List',
        'message' => 'success',
        'data' => $dd
    ));
}
#  List count Mobilizer balances
elseif (CleanData('qid') == '208') {
    /*
         *  Runs e-Netcard 
         *
         *  List count Mobilizerd balances
         */
    $nt = new Netcard\NetcardTrans();
    $wardid = CleanData('wardid');
    $data = $nt->GetMobilizersList($wardid);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard Mobilizers List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Bulk e-Netcard Allocation Ward to mobilizer
elseif (CleanData('qid') == '209') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Bulk e-Netcard Allocation Ward to mobilizer
         */
    $nt = new Netcard\NetcardTrans();

    $inputData = json_decode(file_get_contents('php://input'), true);

    # ['total'=>$total, 'wardid'=>$wardid, 'mobilizerid'=>$mobilizerid, 'userid'=>$userid]
    //  $bulk_data = [array('total'=>10, 'wardid'=>1, 'mobilizerid'=>3, 'userid'=>2),
    //  array('total'=>10, 'wardid'=>1, 'mobilizerid'=>4, 'userid'=>2),
    //  array('total'=>10, 'wardid'=>1, 'mobilizerid'=>5, 'userid'=>2)];
    $mobid = "";
    for ($i = 0; $i < count($inputData); $i++) {
        # code...
        $mobid .= "," . $inputData[$i]['mobilizerid'];
    }
    $mobid = substr($mobid, 1);

    $bulk_data = $inputData;
    #
    $total = $nt->BulkAllocationTransfer($bulk_data);
    //User Log Activity
    $result = "success";
    logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully Allocated to Household Mobilizers (" . $mobid . ") by user with the Login ID: " . $current_loginid . " :", $result);

    echo json_encode(array(
        'result_code' => 200,
        'message' => "e-Netcard Successfully Allocated to HHM",
        'total' => $total
    ));
}
#   Netcard Allocation reverse order
elseif (CleanData('qid') == '210') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard reverse order HHM back to ward 
         */

    $inputData = json_decode(file_get_contents('php://input'), true)[0];
    // 'total'=>10, 'wardid'=>1, 'mobilizerid'=>4, 'userid'=>2

    $nt = new Netcard\NetcardTrans();
    $order_total = $inputData['total'];
    $mobilizerid = $inputData['mobilizerid'];
    $wardid = $inputData['wardid'];
    $userid = $inputData['userid'];
    $device_serial = $inputData['device_serial'];
    #
    if ($nt->ReverseAllocationOrder($mobilizerid, $userid, $order_total, $device_serial)) {
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$order_total e-Netcard Reversed Order Successfully placed to be retracted from Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result);
        echo json_encode(array(
            'result_code' => 200,
            'message' => "e-Netcard reversal order has been placed successfully",
            'total' => $order_total
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$order_total e-Netcard Reversed Order Failed to be placed to Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result);
        echo json_encode(array(
            'result_code' => 400,
            'message' => "Unable to place e-Netcard reversal order at the moment, please try again later"
        ));
    }
}
#   e-Netcard Get combined mobilizer's balance (Without Duplicate)
elseif (CleanData('qid') == '211') {
    /**
     * Check User Priviledge 
     * For Netcard Allocation
     */
    #
    $wardid = CleanData('wardid');
    $nt = new Netcard\NetcardTrans();
    // VErsion 1
    $data = $nt->GetCombinedMobilizerBalance($wardid);

    //Version 2
    // $data = $nt->GetcAllMobilizerBalance($wardid);


    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Combined Mobilizers Balance List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Netcard Online reverse back to ward
elseif (CleanData('qid') == '212') {
    /**
     * Check User Priviledge 
     * For Netcard Allocation
     */
    $inputData = json_decode(file_get_contents('php://input'), true)[0];
    $total = $inputData['total'];
    $mobilizerid = $inputData['mobilizerid'];
    $wardid = $inputData['wardid'];

    $longtitude = isset($inputData['long']) ? $inputData['long'] : "";
    $latitude = isset($inputData['lat']) ? $inputData['lat'] : "";

    if (getPermission($user_priviledge, 'allocation') == 3) {
        /*
            *  Runs e-Netcard Samples
            *
            *  Netcard Online reverse back to ward 
            */
        $nt = new Netcard\NetcardTrans();

        $total_reverse = $nt->DirectReverseAllocation($total,  $mobilizerid, $current_userid);
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total_reverse e-Netcard Online Reversed Successfull, from Household Mobilizers '" . $mobilizerid . "' in Ward '" . $wardid . "' by user with the Login ID: " . $current_loginid . " :", $result);

        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' => "e-Netcard reversal successful: " . $mobilizerid . " Total: " . $total_reverse . " Ward ID:" . $wardid,
            'total' => $total_reverse
        ));
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
#  Get a single Ward Balance using the waardid
elseif (CleanData('qid') == '214') {

    /*
        *  Runs e-Netcard 
        *
        *  Get a single Ward Balance using the waardid
        */
    $nt = new Netcard\NetcardTrans();
    $wardid = CleanData('wardid');

    $data = $nt->CombinedBalanceForApp($wardid);
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard Allocation Mobile App Balances',
        'message' => 'success',
        'data' => $data
    ));
}

#   Online reverse transaction history
elseif (CleanData('qid') == '213') {
    #   Online reverse transaction history
    $nt = new Netcard\NetcardTrans();

    $wardid = CleanData('wardid');
    $data = $nt->GetAllocationDirectReverseList($ward);
    #
    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'e-Netcard Online Reverse Transaction List',
        'data' => $data
    ));
}

#   e-Netcard Super User Unlock
elseif (CleanData('qid') == '215') {

    if (getPermission($user_priviledge, 'enetcard') == 3) {
        /*
            *  Runs e-Netcard Samples
            *
            *  e-Netcard Unlock on Devices 
            */
        $nt = new Netcard\NetcardTrans();

        $userid = CleanData('userid');
        $device_serial = CleanData('device_serial');
        $requester_userid = CleanData('requester_userid');

        $total = $nt->SuperUserUnlockNetcard($userid, $device_serial, $requester_userid);
        //User Log Activity
        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard has been Successfully Unlocked, from Household Mobilizers '" . $userid . " by user with the Login ID: " . $current_loginid . " :", $result);

        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' => $total . ' e-Netcard has been unlocked',
            'total' => $total
        ));
    } else {
        //User Log Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform = "mobile", $module = "enetcard", $description = "User with the User ID: " . $current_userid . " does not have priviledge to Reverse eNetcard from HHM with ID: " . $userid, $result, $longtitude = "", $latitude = "");

        http_response_code(401);
        echo json_encode(array(
            'result_code' => 401,
            'message' => 'Unauthorized User Priviledge on E-Netcard Module Module'
        ));
    }
}
#   e-Netcard Get Offline mobilizer's balance
elseif (CleanData('qid') == '216') {
    #
    $wardid = CleanData('wardid');
    $nt = new Netcard\NetcardTrans();
    $data = $nt->GetOfflineMobilizerBalance($wardid);

    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Offline Device Mobilizers Balance List',
        'message' => 'success',
        'data' => $data
    ));
}
#  All e-Netcard Dashboard Top Summary
elseif (CleanData('qid') == '217') {
    /*
    *
    *  e-Necard Dashboard Top Summary
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Enetcard();
    $dd = $nt->TopSummary();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard Dashboard Top Summary',
        'message' => 'success',
        'data' => $dd
    ));
}
#  e-Netcard LGA Top Summary Dashboard
elseif (CleanData('qid') == '218') {
    /*
    *
    *  e-Netcard LGA Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Enetcard();
    $result = $nt->TopLgaSummary();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard LGA Top Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}
#  e-Netcard Ward Top Summary Dashboard
elseif (CleanData('qid') == '219') {
    /*
    *
    *  e-Netcard WARD Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Enetcard();
    $result = $nt->TopWardSummary($lgaid = CleanData('lgaId'));
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard Ward Top Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}
#  e-Netcard HHM in a ward Top Summary Dashboard
elseif (CleanData('qid') == '220') {
    /*
    *
    *  e-Netcard HHM in a Ward Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Enetcard();
    $result = $nt->TopMobilizerSummary(CleanData('wardId'));
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'e-Netcard Top Mobilizer in a Ward Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}



/**
 * Mobilization
 */
elseif (CleanData('qid') == '301') {
    #
    #
    #   Excel Export Count Mobilization
    $loginid = CleanData('lgid');
    #   Filtered by mobilized data
    $mob_date = CleanData('mdt');
    #   Filtered by Geo-Level
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    $ex = new Mobilization\Mobilization();
    $total = $ex->ExcelCountMobilization($loginid, $mob_date, $geo_level, $geo_level_id);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Excel Export Count Mobilization',
        'message' => 'success',
        'total' => $total
    ));
}
#   Dashboard summary with options
elseif (CleanData('qid') == '302') {
    #
    #
    #
    #   Filtered by mobilized date
    $mob_date = CleanData('mdt');
    #   Filtered by Geo-Level
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    $ex = new Mobilization\Mobilization();
    $total = $ex->DashSummary($mob_date, $geo_level, $geo_level_id);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Dashboard summary',
        'message' => 'success',
        'total' => $total
    ));
}
#   Get micro-palnning by LGA
elseif (CleanData('qid') == '303') {
    #
    #
    #   Get micro-palnning by LGA
    #
    $ex = new Mobilization\Mobilization();
    $lgaid = CleanData("lgaid");
    $data = $ex->GetMicroPosition($lgaid);

    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get micro-palnning by LGA',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Excel count micro-palnning by LGA
elseif (CleanData('qid') == '304') {
    #
    #
    #   Get Excel count micro-palnning by LGA
    #
    $ex = new Mobilization\Mobilization();
    $lgaid = CleanData("lgaid");
    $data = $ex->ExcelGetMicroPosition($lgaid);
    #
    echo  $data;
}
#   Get Excel Get Data micro-palnning by LGA
elseif (CleanData('qid') == '305') {
    #
    #
    #   Mobilization Master
    #
    #   Get micro-palnning by LGA
    $ex = new Mobilization\Mobilization();
    $lgaid = CleanData("lgaid");
    $total = $ex->ExcelCountMicroPosition($lgaid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get count micro-palnning by LGA',
        'message' => 'success',
        'total' => $total
    ));
}


/**
 * Distribution
 */
elseif (CleanData('qid') == '401') {
    #
    #   Distribution 
    #
    #   Get DP Locations details with DP ID
    $ex = new Distribution\Distribution();
    $wardid = CleanData('wardid');
    $data = $ex->GetDpLocationMaster($wardid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Distribution Point List for Badge Printing',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '401a') {
    #
    #   Distribution 
    #
    #   Get DP Locations details with DP ID
    $ex = new Distribution\Distribution();
    $lgaid = CleanData('lgaid');
    $data = $ex->GetDpLocationMasterByLga($lgaid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Distribution Point List for Badge Printing',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get DP Locations details with DP ID
elseif (CleanData('qid') == '402') {
    #
    #   Distribution 
    #
    #   Get DP Locations details with DP ID
    $ex = new Distribution\Distribution();
    $lgaid = CleanData('lgaid');
    $data = $ex->GetDpLocationMasterByLga($lgaid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Distribution Point List for Badge Printing',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Distribution Top Level Dashboard Summary
elseif (CleanData('qid') == '403') {
    #
    #   Get Distribution Top Level Dashboard Summary
    $ex = new Dashboard\Distribution();
    $data = $ex->TopSummary();
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Distribution Top Level Dashboard Summary',
        'message' => 'success',
        'data' => $data
    ));
}

#   Get Distribution LGA Dashboard Aggregate Table
elseif (CleanData('qid') == '404a') {
    #
    #   Get Distribution LGA Dashboard Aggregate Table
    $ex = new Dashboard\Distribution();
    $data = $ex->LgaAggregateByLocation();
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Distribution Top Level Dashboard Summary',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Distribution WARD Aggregate Table Dashboard Data
elseif (CleanData('qid') == '404b') {
    #
    #   Get Distribution WARD Dashboard Aggregate Table
    $lgaid = CleanData('lgaId');
    $ex = new Dashboard\Distribution();
    $data = $ex->WardAggregateByLocation($lgaid);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Ward Aggregate Table Data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Distribution DP Aggregate Table Dashboard Data
elseif (CleanData('qid') == '404c') {
    #
    #   Get Distribution WARD Dashboard Aggregate Table
    $wardId = CleanData('wardId');
    $ex = new Dashboard\Distribution();
    $data = $ex->DpAggregateByLocation($wardId);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get DP Aggregate Table Data',
        'message' => 'success',
        'data' => $data
    ));
}

#   Get Daily Distribution aggregate Dashboard Data
elseif (CleanData('qid') == '405a') {
    #
    #   Get Daily Distribution aggregate Dashboard Data

    $ex = new Dashboard\Distribution();
    $data = $ex->TopAggregateByDate();

    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'household_redeemed');
    $netcards = DataLib::Column($data, 'net_redeemed');
    $family_size = DataLib::Column($data, 'familysize_redeemed');

    $chart_data = array(
        array(
            array('name' => 'Households', 'data' => $household),
            array('name' => 'Net Redeemed', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(
        array(
            'result_code' => 200,
            'dataset' => 'Top Daily Distribution aggregate Data',
            'message' => 'success',
            'data' => $data,
            'chart' => $chart_data
        )
    );
    return;
}
#   Get LGA Distribution per day aggregate Dashboard Data
elseif (CleanData('qid') == '405b') {
    #
    #   Get LGA Distribution per date aggregate Dashboard Data

    $date = CleanData('date');
    $ex = new Dashboard\Distribution();
    $data = $ex->LgaAggregateByDate($date);
    #

    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'household_redeemed');
    $netcards = DataLib::Column($data, 'net_redeemed');
    $family_size = DataLib::Column($data, 'familysize_redeemed');

    $chart_data = array(
        array(
            array('name' => 'Households', 'data' => $household),
            array('name' => 'Net Redeemed', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(
        array(
            'result_code' => 200,
            'dataset' => 'Get LGA Distribution per date aggregate',
            'message' => 'success',
            'data' => $data,
            'chart' => $chart_data
        )
    );
    return;
}
#   Get Ward Aggregate by date Dashboard Data
elseif (CleanData('qid') == '405c') {
    #
    #   Get Ward Aggregate by date Dashboard Data

    $date = CleanData('date');
    $lgaid = CleanData('lgaId');

    $ex = new Dashboard\Distribution();
    $data = $ex->WardAggregateByDate($date, $lgaid);
    #

    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'household_redeemed');
    $netcards = DataLib::Column($data, 'net_redeemed');
    $family_size = DataLib::Column($data, 'familysize_redeemed');

    $chart_data = array(
        array(
            array('name' => 'Households', 'data' => $household),
            array('name' => 'Net Redeemed', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(
        array(
            'result_code' => 200,
            'dataset' => 'Get Ward Aggregate by date Dashboard Data',
            'message' => 'success',
            'data' => $data,
            'chart' => $chart_data
        )
    );
    return;
}
#   Get Ward Aggregate by date Dashboard Data
elseif (CleanData('qid') == '405d') {
    #
    #   Get Ward Aggregate by date Dashboard Data

    $date = CleanData('date');
    $wardid = CleanData('wardId');

    $ex = new Dashboard\Distribution();
    $data = $ex->DpAggregateByDate($date, $wardid);
    #

    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'household_redeemed');
    $netcards = DataLib::Column($data, 'net_redeemed');
    $family_size = DataLib::Column($data, 'familysize_redeemed');

    $chart_data = array(
        array(
            array('name' => 'Households', 'data' => $household),
            array('name' => 'Net Redeemed', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(
        array(
            'result_code' => 200,
            'dataset' => 'Get Ward Aggregate by date Dashboard Data',
            'message' => 'success',
            'data' => $data,
            'chart' => $chart_data
        )
    );
    return;
}
// 



/**
 * Device Management
 */
#   Toggle Device Activation
elseif (CleanData('qid') == '501') {
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
}

#   Bulk device toggle activation
elseif (CleanData('qid') == '502') {
    #
    #   Bulk device toggle activation
    #
    $ex = new System\Devices();
    $inputData = json_decode(file_get_contents('php://input'), true);

    // Defensive: require a JSON array of serials
    if (!is_array($inputData)) {
        echo json_encode(['result_code' => 400, 'message' => 'invalid input']);
        return;
    }

    $devices = array_values($inputData);
    $total = $ex->BulkToggleActive($devices);
    if ($total) {
        logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with ID: $current_userid, De/Activated $total Device(s) with Serial Nos: [" . implode(', ', $devices) . "] ", $result = "sucess");
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'success',
            'total' => $total
        ));
    } else {
        logUserActivity($userid = $current_userid, $platform, $module = "device", $description = "User with ID: $current_userid, tried to De/Activated $total Device(s) with Serial Nos: [" . implode(', ', $devices) . "] but failed", $result = "failed");
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error',
            'data' => 'Unable to active toggle device at the moment, please try again later'
        ));
    }
}
#   Bulk delete device 
elseif (CleanData('qid') == '503') {
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
}
#   Single device detail update with serial
elseif (CleanData('qid') == '504') {
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
}


/*
     *
     *  MAP Data
     * 
     */
#   Mobilizer Dataset
elseif (CleanData('qid') == '601') {
    #
    #   Mobilizer Data
    #
    $ex = new Mobilization\MapData();
    #
    // $mobilizerid = "CGF00003";      #   compulsory field to get all mobilizer
    // $start_date = "2022-05-16";
    // $end_date = "2022-05-17";
    // $wardid = "1";        

    $mobilizerid = CleanData("mob");      #   compulsory field to get all mobilizer
    $start_date = CleanData("s_date");
    $end_date = CleanData("e_date");
    $wardid = CleanData("wardid");
    #
    $data = $ex->GetMobilizationData($wardid, $mobilizerid, $start_date, $end_date);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get mobilizer mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get map data test all 
elseif (CleanData('qid') == '602') {
    #
    #   Get map data test all  Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $hhid = "41";
    #
    $data = $ex->GetTestAllData();
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get test all mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get DP Dataset (3 options)
elseif (CleanData('qid') == '603') {
    #
    #   Get DP Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $dpid = CleanData("dpid");      #   compulsory field to get dp data
    $wardid = CleanData("wardid");                  #   required data as well
    $start_date = CleanData("s_date");
    $end_date = CleanData("e_date");

    #
    $data = $ex->GetDpData($wardid, $dpid, $start_date, $end_date);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get DP mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get WARD daily Dataset
elseif (CleanData('qid') == '604') {
    #
    #   Get WARD daily Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $wardid = CleanData("wardid");                  #   required data as well
    $date = CleanData("s_date");

    #
    $data = $ex->GetWardData($wardid, $date);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Ward daily mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get LGA daily Dataset
elseif (CleanData('qid') == '605') {
    #
    #   Get LGA daily Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $lgaid = CleanData("lgaid");                  #   required data as well
    $date = CleanData("s_date");

    #
    $data = $ex->GetLgaData($lgaid, $date);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get LGA Daily mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get State daily Dataset
elseif (CleanData('qid') == '606') {
    #
    #   Get State daily Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $stateid = CleanData("stateid");                  #   required data as well
    $date = CleanData("s_date");

    #
    $data = $ex->GetStateData($stateid, $date);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get State Daily mapping data',
        'message' => 'success',
        'data' => $data
    ));
}


#   Get list of form and count
elseif (CleanData('qid') == '700') {
    if (getPermission($user_priviledge, 'monitoring') >= 2) {

        $fm = new Monitor\Monitor();
        $data = $fm->GetFormStatusList();
        #

        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "monitoring", $description = "Monitoring tools List loaded ", $result);

        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Monitoring tool list',
            'message' => 'success',
            'data' => $data
        ));
    } else {
        //Log User Activity
        $result = "failed";
        logUserActivity($userid = $current_userid, $platform, $module = "monitoring", $description = "Monitoring Toools List Failed to Load ", $result);
        #If all data supplied are wrong
        echo json_encode(array(
            'result_code' => 400,
            'message' => 'error'
        ));
    }
}

/*
     *
     * ====     MOBILIZATION DASHBOARD   =====
     * 
     */
# Top level fields [households, netcards, family_size]
elseif (CleanData('qid') == '750') {
    $dhb = new Dashboard\Mobilization();
    $data = $dhb->TopSummary();
    echo json_encode(array('info' => 'Top Summary dataset', 'data' => $data));
}
/*
     *      Aggregate by date
     *
     */
# Top level aggregated summary by date [date, households, netcards, family_size]
elseif (CleanData('qid') == '751') {

    $dhb = new Dashboard\Mobilization();
    $data = $dhb->TopSummaryByDate();
    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Households', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data, 'level' => 0));
}
# Top level aggregated summary by LGA [lga, households, netcards, family_size, lgaid]
elseif (CleanData('qid') == '752') {
    $dhb = new Dashboard\Mobilization();
    $data = $dhb->TopSummaryByLocation();
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Households', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data, 'level' => 0));
}
# LGA Aggregate by date Drill level 1 - [lga, households, netcards, family_size, lgaid] Mobilization @ selected date
elseif (CleanData('qid') == '753') {
    $dhb = new Dashboard\Mobilization();
    $date = CleanData("date");
    $data = $dhb->LgaAggregateByDate($date);
    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Household', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data, 'level' => 1));
} elseif (CleanData('qid') == '754') {
    $dhb = new Dashboard\Mobilization();
    $date = CleanData("date");
    $lgaid = CleanData("lgaid");

    $data = $dhb->WardAggregateByDate($date, $lgaid);
    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Household', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array(
        'table' => $data,
        'chart' => $chart_data,
        'level' => 2
    ));
} elseif (CleanData('qid') == '755') {
    $dhb = new Dashboard\Mobilization();
    $date = CleanData("date");
    $wardid = CleanData("wardid");

    $data = $dhb->DpAggregateByDate($date, $wardid);

    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Household', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array(
        'table' => $data,
        'chart' => $chart_data,
        'level' => 3
    ));
}
# Aggregate Drill by LGA -> Ward -> DPs 
elseif (CleanData('qid') == '756') {

    $dhb = new Dashboard\Mobilization();
    $lgaid = CleanData("lgaid");
    $data = $dhb->WardAggregateByLocation($lgaid);

    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Household', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array(
        'table' => $data,
        'chart' => $chart_data,
        'level' => 1
    ));
} elseif (CleanData('qid') == '757') {

    $dhb = new Dashboard\Mobilization();
    $wardid = CleanData('wardid');
    $data = $dhb->DpAggregateByLocation($wardid);

    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Household', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array(
        'table' => $data,
        'chart' => $chart_data,
        'level' => 2
    ));
}


#
#
#==============================================
#   SMC FEATURES 
#==============================================
#
#
#
elseif (CleanData('qid') == '1000') {
    #
    #   Create new Period
    #

    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        if (!empty($inputData['period_title']) && !empty($inputData['start_date']) && !empty($inputData['end_date'])) {

            $pr = new Smc\Period();
            $name = $inputData['period_title'];
            $start_date = $inputData['start_date'];
            $end_date = $inputData['end_date'];
            #
            $id = $pr->Create($name, $start_date, $end_date);

            if ($id) {
                $result = "success";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Period Successfully Created with Period ID " . $id . "and Title " . $inputData['period_title'] . " Start Date, " . $inputData['start_date'] . " and End Date: " . $inputData['end_date'], $result);
                #On User Creation
                echo json_encode(array(
                    'result_code' => 201,
                    'message' => 'Visit Created Successfully',
                    'id' => $id
                ));
            } else {
                $result = "failed";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Period was Failed to be Created with Period Title" . $inputData['period_title'] . " Start Date, " . $inputData['start_date'] . " and End Date: " . $inputData['end_date'], $result);
                #On user creation failed
                echo json_encode(array(
                    'result_code' => 400,
                    'message' => 'Visit Creation Failed'
                ));
            }
        } else {
            //Log User Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = " Period Creation failed due to wrong data input: ", $result);
            #If all data supplied are wrong
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Create Period/Visit'
        );
        echo json_encode($json_data);
    }
}
#   update period
elseif (CleanData('qid') == '1001') {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $name = $inputData['period_title'];
    $start_date = $inputData['start_date'];
    $end_date = $inputData['end_date'];
    $period_id = $inputData['period_id'];

    if (getPermission($user_priviledge, 'smc') >= 2) {

        if (!empty($name) && !empty($start_date) && !empty($end_date)) {

            $pr = new Smc\Period();


            if ($pr->Update($name, $start_date, $end_date, $period_id)) {
                $result = "success";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID " . $period_id . "and Title " . $inputData['period_title'] . " Start Date, " . $inputData['start_date'] . " and End Date: " . $inputData['end_date'] . " Successfully Updated", $result);
                #On User Creation
                echo json_encode(array(
                    'result_code' => 200,
                    'message' => 'Visit Updated Successfully',
                    'id' => $period_id
                ));
            } else {
                $result = "failed";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID: " . $period_id . " failed to Update", $result);
                #On user creation failed
                echo json_encode(array(
                    'result_code' => 400,
                    'message' => 'Visit Update Failed'
                ));
            }
        } else {
            //Log User Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = " Visit Update failed due to wrong data input: ", $result);
            #If all data supplied are wrong
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Create Period/Visit'
        );
        echo json_encode($json_data);
    }
}
#   Delete period
elseif (CleanData('qid') == '1002') {

    $period_id = CleanData('period_id');

    if (getPermission($user_priviledge, 'smc') >= 3) {

        $pr = new Smc\Period();


        if ($pr->Delete($period_id)) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID " . $period_id . " Deleted Successfully", $result);
            #On User Creation
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'Visit Deleted Successfully',
                'id' => $period_id
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID: " . $period_id . " failed to Delete", $result);
            #On user creation failed
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Unable to delete period at the moment, please try again later.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Delete Period/Visit'
        );
        echo json_encode($json_data);
    }
}
#   Activate period (only one per time)
elseif (CleanData('qid') == '1003') {

    $period_id = CleanData('period_id');

    if (getPermission($user_priviledge, 'smc') >= 3) {
        $pr = new Smc\Period();

        if ($pr->Activate($period_id)) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID " . $period_id . " Activated Successfully", $result);
            #On User Creation
            echo json_encode(array(
                'result_code' => 200,
                'message' => 'Visit activated successfully',
                'id' => $period_id
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "A Visit with Period ID: " . $period_id . " failed to Update", $result);
            #On user creation failed
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'Unable to activate period at the moment, please try again later.'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Create Period/Visit'
        );
        echo json_encode($json_data);
    }
}
#   Get period list
elseif (CleanData('qid') == '1004') {

    if (getPermission($user_priviledge, 'smc') >= 1) {

        $pr = new Smc\Period();
        #
        $data = $pr->GetList();

        #On User Creation
        echo json_encode(array(
            'result_code' => 200,
            'message' => 'success',
            'data' => $data,
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Create Period/Visit'
        );
        echo json_encode($json_data);
    }
}
#LGA Cohort Tracking
# Top level fields [households, netcards, family_size]
elseif (CleanData('qid') == '1005') {

    if (getPermission($user_priviledge, 'smc') >= 1) {
        $pr = new Smc\DrugAdmin();
        $data = $pr->GetCohortLgaLevel();
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get All LGA Cohort Tracking Data',
            'message' => 'success',
            'level' => 2,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Cohort Tracking per LGA'
        );
        echo json_encode($json_data);
    }
}
#Ward Cohort Tracking
elseif (CleanData('qid') == '1006') {

    if (getPermission($user_priviledge, 'smc') >= 1) {
        $lgaid = CleanData('filterId');
        $pr = new Smc\DrugAdmin();
        $data = $pr->GetCohortWardLevel($lgaid);
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get All Ward Level in an LGA with ID: $lgaid Cohort Tracking Data',
            'message' => 'success',
            'level' => 3,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Cohort Tracking per Ward'
        );
        echo json_encode($json_data);
    }
}

#DP Level Cohort Tracking
elseif (CleanData('qid') == '1007') {

    if (getPermission($user_priviledge, 'smc') >= 1) {
        $wardid = CleanData('filterId');
        $pr = new Smc\DrugAdmin();
        $data = $pr->GetCohortDpLevel($wardid);
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get All DP Level Cohort Tracking in the Ward with ID: $wardid Cohort Tracking Data',
            'message' => 'success',
            'level' => 4,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Cohort Tracking per Ward'
        );
        echo json_encode($json_data);
    }
}

#Child Cohort Tracking using dpID
elseif (CleanData('qid') == '1008') {

    if (getPermission($user_priviledge, 'smc') >= 1) {
        $dpid = CleanData('filterId');
        $pr = new Smc\DrugAdmin();
        $data = $pr->GetCohortChildLevel($dpid);
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get All Child Cohort Tracking in the DP with ID: $dpid  Data',
            'message' => 'success',
            'level' => 5,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Cohort Tracking per Ward'
        );
        echo json_encode($json_data);
    }
}

#Child Cohort Details Tracking using Beneficiary ID
elseif (CleanData('qid') == '1009') {

    if (getPermission($user_priviledge, 'smc') >= 1) {
        $beneficiary_id = CleanData('bid');
        $pr = new Smc\DrugAdmin();
        $data = $pr->GetCohortChildDetails($beneficiary_id);
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get a Child Cohort Tracking Details',
            'message' => 'success',
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Child Cohort Details'
        );
        echo json_encode($json_data);
    }
}
#   Get Referral summary card data
elseif (CleanData('qid') == '1110') {
    $pr = new Smc\DrugAdmin();
    #   Filters
    $periodid = CleanData("pid");       #  period ID
    $geo_id = CleanData("gid");         #   Geo_level_id
    $geo_level = CleanData("glv");      #   Geo-Level
    $attended = CleanData('atd');       #   Attended filter
    #
    $data = $pr->GetReferralCount($periodid, $geo_id, $geo_level, $attended);

    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get State Daily mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#
#=========================================
#SMC Dashboard
#

#
#      SMC DASHBOARD SAMPLE
#
#   Child LGA list
elseif (CleanData('qid') == '1111') {
    if (getPermission($user_priviledge, 'smc') >= 1) {

        $dhb = new Smc\Dashboard();
        #
        #   Filter 
        $period_list = CleanData('pid');  // sample 1,2,3 or 1,3 not in use here
        $startDate = CleanData('sdate');
        $endDate = CleanData('edate');
        $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

        #implement list LGA DATA [id, title, total, male, female]
        $data = $dhb->ChildListLgaSummary($startDate, $endDate);
        //  Transform chart
        $label = DataLib::Column($data, 'title');
        $male = DataLib::Column($data, 'male');
        $female = DataLib::Column($data, 'female');
        $chart_data = array(
            array(
                array(
                    'name' => 'male',
                    'data' => $male
                ),
                array('name' => 'female', 'data' => $female)
            ),
            $label
        );
        $allData = array('table' => $data, 'chart' => $chart_data);

        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get All LGA Child Dashboard Data',
            'message' => 'success',
            'level' => 2,
            'data' => $allData
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Child Dashboard per LGA'
        );
        echo json_encode($json_data);
    }
}
#   Child Ward list
elseif (CleanData('qid') == '1112') {
    if (getPermission($user_priviledge, 'smc') >= 1) {
        $dhb = new Smc\Dashboard();
        #
        #   Filter 
        $id = (int) CleanData('filterId');
        $period_list = CleanData('pid');  // sample 1,2,3 or 1,3 not in use here
        $startDate = CleanData('sdate');
        $endDate = CleanData('edate');
        $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

        #implement list Ward data [id, title, total, male, female]
        $data = $dhb->ChildListWardSummary($id, $startDate, $endDate);
        //  Transform chart 
        $label = DataLib::Column($data, 'title');
        $male = DataLib::Column($data, 'male');
        $female = DataLib::Column($data, 'female');
        $chart_data = array(
            array(
                array(
                    'name' => 'male',
                    'data' => $male
                ),
                array('name' => 'female', 'data' => $female)
            ),
            $label
        );
        $allData = array('table' => $data, 'chart' => $chart_data);

        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get All LGA Child Dashboard Data Ward Level',
            'message' => 'success',
            'level' => 3,
            'data' => $allData
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Cohort Tracking per Ward Level'
        );
        echo json_encode($json_data);
    }
}
#   Child DP list
elseif (CleanData('qid') == '1113') {
    if (getPermission($user_priviledge, 'smc') >= 1) {

        $dhb = new Smc\Dashboard();
        #
        #   Filter 
        $id = (int) CleanData('filterId');
        $period_list = CleanData('pid');  // sample 1,2,3 or 1,3 not in use here
        $startDate = CleanData('sdate');
        $endDate = CleanData('edate');
        $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

        #implement list Ward data [id, title, total, male, female]
        $data = $dhb->ChildListDpSummary($id, $startDate, $endDate);
        //  Transform chart 
        $label = DataLib::Column($data, 'title');
        $male = DataLib::Column($data, 'male');
        $female = DataLib::Column($data, 'female');
        $chart_data = array(
            array(
                array(
                    'name' => 'male',
                    'data' => $male
                ),
                array('name' => 'female', 'data' => $female)
            ),
            $label
        );
        $allData = array('table' => $data, 'chart' => $chart_data);

        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get All DP Child Dashboard Data in a Ward',
            'message' => 'success',
            'level' => 4,
            'data' => $allData
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Cohort Tracking per Ward Level'
        );
        echo json_encode($json_data);
    }
}
#
#   Drug Admin Dashboard
#   Drug Administration LGA list
elseif (CleanData('qid') == '1114') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $period_list = CleanData('pid');  // sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    $data = $dhb->DrugAdminListLga($period_list, $startDate, $endDate);
    //  Transform chart part (eligible, spaq1 & spaq2)
    $label = DataLib::Column($data, 'title');
    $eligible = DataLib::Column($data, 'eligible');
    $spaq1 = DataLib::Column($data, 'spaq1');
    $spaq2 = DataLib::Column($data, 'spaq2');
    $chart_data = array(
        array(
            array('name' => 'SPAQ 1', 'data' => $spaq1),
            array('name' => 'SPAQ 2', 'data' => $spaq2)
        ),
        $label
    );
    $allData = array('table' => $data, 'chart' => $chart_data);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All LGA Drug Administration Dashboard Data',
        'message' => 'success',
        'level' => 2,
        'data' => $allData
    ));
}
#   Drug Administration Ward list
elseif (CleanData('qid') == '1115') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = (int) CleanData('filterId');
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    $data = $dhb->DrugAdminListWard($id, $period_list, $startDate, $endDate);
    //  Transform chart part (eligible, spaq1 & spaq2)
    $label = DataLib::Column($data, 'title');
    $eligible = DataLib::Column($data, 'eligible');
    $spaq1 = DataLib::Column($data, 'spaq1');
    $spaq2 = DataLib::Column($data, 'spaq2');
    $chart_data = array(
        array(
            array('name' => 'SPAQ 1', 'data' => $spaq1),
            array('name' => 'SPAQ 2', 'data' => $spaq2)
        ),
        $label
    );
    $allData = array('table' => $data, 'chart' => $chart_data);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All Ward Drug Administration Dashboard Data in an LGA',
        'message' => 'success',
        'level' => 3,
        'data' => $allData
    ));
}
#   Drug Administration DP list
elseif (CleanData('qid') == '1116') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = (int) CleanData('filterId');
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    $data = $dhb->DrugAdminListDp($id, $period_list, $startDate, $endDate);
    //  Transform chart part (eligible, spaq1 & spaq2)
    $label = DataLib::Column($data, 'title');
    $eligible = DataLib::Column($data, 'eligible');
    $spaq1 = DataLib::Column($data, 'spaq1');
    $spaq2 = DataLib::Column($data, 'spaq2');
    $chart_data = array(
        array(
            array('name' => 'SPAQ 1', 'data' => $spaq1),
            array('name' => 'SPAQ 2', 'data' => $spaq2)
        ),
        $label
    );
    $allData = array('table' => $data, 'chart' => $chart_data);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All LGA Drug Administration Dashboard Data',
        'message' => 'success',
        'level' => 4,
        'data' => $allData
    ));
}

#   Referral LGA list
elseif (CleanData('qid') == '1117') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->ReferralListLga($period_list, $startDate, $endDate);
    //  Transform chart part (referred, attended)
    $label = DataLib::Column($data, 'title');
    $referred = DataLib::Column($data, 'referred');
    $attended = DataLib::Column($data, 'attended');
    $chart_data = array(
        array(
            array('name' => 'Referred', 'data' => $referred),
            array('name' => 'Attended', 'data' => $attended)
        ),
        $label
    );
    $allData = array('table' => $data, 'chart' => $chart_data);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All LGA Referral Dashboard Data',
        'message' => 'success',
        'level' => 2,
        'data' => $allData
    ));
}
#   Referral Ward list
elseif (CleanData('qid') == '1118') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = (int) CleanData('filterId');
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->ReferralListWard($id, $period_list, $startDate, $endDate);
    //  Transform chart part (referred, attended)
    $label = DataLib::Column($data, 'title');
    $referred = DataLib::Column($data, 'referred');
    $attended = DataLib::Column($data, 'attended');
    $chart_data = array(
        array(
            array('name' => 'Referred', 'data' => $referred),
            array('name' => 'Attended', 'data' => $attended)
        ),
        $label
    );
    $allData = array('table' => $data, 'chart' => $chart_data);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All Ward Referral Dashboard Data',
        'message' => 'success',
        'level' => 3,
        'data' => $allData
    ));
}
#   Referral DP list
elseif (CleanData('qid') == '1119') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = (int) CleanData('filterId');
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->ReferralListDp($id, $period_list, $startDate, $endDate);
    //  Transform chart part (referred, attended)
    $label = DataLib::Column($data, 'title');
    $referred = DataLib::Column($data, 'referred');
    $attended = DataLib::Column($data, 'attended');
    $chart_data = array(
        array(
            array('name' => 'Referred', 'data' => $referred),
            array('name' => 'Attended', 'data' => $attended)
        ),
        $label
    );
    $allData = array('table' => $data, 'chart' => $chart_data);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All DP Referral Dashboard Data',
        'message' => 'success',
        'level' => 4,
        'data' => $allData
    ));
}


/*
*      Inventory control
* 
*/
#   ICC LGA list
elseif (CleanData('qid') == '1120') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->IccListLga($period_list, $startDate, $endDate);
    //  Transform chart part (referred, attended)
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All LGA ICC Dashboard Data',
        'message' => 'success',
        'level' => 2,
        'data' => $data
    ));
}

#   ICC Ward list
elseif (CleanData('qid') == '1121') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = (int) CleanData('filterId');
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->IccListWard($id, $period_list, $startDate, $endDate);
    //  Transform chart part (referred, attended)
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All Ward ICC Dashboard Data',
        'message' => 'success',
        'level' => 3,
        'data' => $data
    ));
}
#   ICC DP list
elseif (CleanData('qid') == '1122') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = (int) CleanData('filterId');
    $period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->IccListDp($id, $period_list, $startDate, $endDate);
    //  Transform chart part (referred, attended)
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All DP ICC Dashboard Data',
        'message' => 'success',
        'level' => 4,
        'data' => $data
    ));
}

#   ICC 
elseif (CleanData('qid') == '1123') {
    if (getPermission($user_priviledge, 'smc') >= 1) {

        $dhb = new Smc\Icc();
        #
        #   Filter 
        $cddid = CleanData('cddid');
        $period_filter = CleanData('pid');

        $data1 = $dhb->GetIccIssueByCdd($cddid, $period_filter);
        $data2 = $dhb->GetIccReceiveByCdd($cddid, $period_filter);
        //  Transform chart 
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get CDD ICC Issued and Received by CDD',
            'message' => 'success',
            'data' => array($data1, $data2)
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Child Cohort Details'
        );
        echo json_encode($json_data);
    }
}

#   DRUG Administration Export COUNT
elseif (CleanData('qid') == '1124') {
    if (getPermission($user_priviledge, 'smc') >= 1) {

        #
        #
        #   Excel Export Count
        $periodid = CleanData("pid");     #  period ID
        $is_eligible = CleanData("ise");    #  The child is eligible must be yes
        $is_redose = CleanData("isr");    #  Redose must be yes
        $reg_date = CleanData("rda");    #   Registration date
        $geo_id = CleanData("gid");    #   Geo_level_id
        $geo_level = CleanData("glv");    #   Geo-Level
        $beneficiary_id = CleanData("bid");    #   Beneficiary ID

        $filter = [
            'periodid' => $periodid,
            'is_eligible' => $is_eligible,
            'is_redose' => $is_redose,
            'reg_date' => $reg_date,
            'geo_id' => $geo_id,
            'geo_level' => $geo_level,
            'beneficiaryid' => $beneficiary_id
        ];

        $ex = new Smc\Reporting();
        $total = $ex->CountDrugAdminBase($filter);
        #
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Excel Export Count Drug Administration',
            'message' => 'success',
            'total' => $total
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Drug Administration'
        );
        echo json_encode($json_data);
    }
}

#   Child Refferal Export Count
elseif (CleanData('qid') == '1125') {
    if (getPermission($user_priviledge, 'smc') >= 1) {

        #
        #
        #   Excel Export Count Drug Refferal
        $periodid = CleanData("pid");       #  period ID
        $geo_id = CleanData("gid");         #   Geo_level_id
        $geo_level = CleanData("glv");      #   Geo-Level
        $attended = CleanData('atd');       #   Attended filter

        $filter = [
            'periodid' => $periodid,
            'geo_id' => $geo_id,
            'geo_level' => $geo_level,
            'attended' => $attended
        ];

        $ex = new Smc\Reporting();
        $total = $ex->CountReferralBase($filter);
        #
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Excel Export Count Refferal',
            'message' => 'success',
            'total' => $total
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load Refferal Records'
        );
        echo json_encode($json_data);
    }
}

#   ICC Export Count
elseif (CleanData('qid') == '1126') {
    if (getPermission($user_priviledge, 'smc') >= 1) {

        #
        #
        #   Excel Export Count Drug Refferal
        $periodid = CleanData("pid");       #  period ID
        $geo_id = CleanData("gid");         #   Geo_level_id
        $geo_level = CleanData("glv");      #   Geo-Level

        $filter = ['periodid' => $periodid, 'geo_id' => $geo_id, 'geo_level' => $geo_level];

        $ex = new Smc\Reporting();
        $total = $ex->CountIccCddBase($filter);
        #
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Excel Export Count ICC',
            'message' => 'success',
            'total' => $total
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to Load ICC Records'
        );
        echo json_encode($json_data);
    }
}

#   ICC Isued and reconcile
elseif (CleanData('qid') == '1127') {
    if (getPermission($user_priviledge, 'smc') >= 1) {

        $dhb = new Smc\Icc();
        #
        #   Filter 
        $cddid = CleanData('cddid');
        $period_filter = CleanData('pid');

        $data1 = $dhb->GetIccFlowDetailByCdd($cddid);
        //  Transform chart 
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => 'Get Detail Icc Isued and reconcile',
            'message' => 'success',
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to view ICC Details'
        );
        echo json_encode($json_data);
    }
}

#   ICC Balance Unlock
elseif (CleanData('qid') == '1128') {
    if (getPermission($user_priviledge, 'smc') >= 1) {
        // $dpid, $cdd_id, $drug, $qty, $user_id
        $dhb = new Smc\Icc();
        #
        #   Filter 
        $dpid = CleanData('dpid');
        $cdd_id = CleanData('cddid');
        $drug = CleanData('drug');
        $qty = CleanData('qty');
        $user_id = CleanData('user_id');
        $issueId = CleanData('issueId');

        // $period_filter = CleanData('pid');

        $data = $dhb->UnlockBalance($issueId, $dpid, $cdd_id, $drug, $qty, $user_id);
        //  Transform chart 
        echo json_encode(array(
            'result_code' => 200,
            'dataset' => $qty . ' ' . $drug . ' Unlocked',
            'message' => 'success',
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 400,
            'message' => 'You don\'t have permission to view ICC Details'
        );
        echo json_encode($json_data);
    }
}
# Create New Issues
elseif (CleanData('qid') == '1129') {
    #
    # Create New Issues
    #
    $cr = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        if (is_array($inputData) && !empty($inputData)) {

            $total = $cr->ProcessBulkIssue($inputData);
            if ($total) {
                $result = "success";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Issues Created Successfully by User ID: " . $current_userid, $result);
                #On User Creation
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'message' => $total . ' Issues Created Successfully',
                ));
            } else {
                $result = "failed";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Creation failed by Userd ID: " . $current_userid, $result);
                #On user creation failed
                http_response_code(400);
                echo json_encode(array(
                    'result_code' => 400,
                    'message' => 'Issue Creation failed'
                ));
            }
        } else {
            //Log User Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Issue  Creation failed due to wrong data input: ", $result);
            #If all data supplied are wrong
            http_response_code(400);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Create Issues'
        );
        echo json_encode($json_data);
    }
}
# CMS WAREHOUSE INBOUND Shipment
elseif (CleanData('qid') == '1130') {
    #
    # CMS WAREHOUSE INBOUND Shipment
    #
    $cr = new Smc\Inventory();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        if (is_array($inputData) && !empty($inputData)) {

            if ($cr->CmsInboundShipment($inputData)) {
                $result = "success";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Warehouse Inbound Created Successfully by User ID: " . $current_userid, $result);
                #On User Creation
                http_response_code(200);
                echo json_encode(array(
                    'result_code' => 200,
                    'message' =>  ' Warehouse Inbound Created Successfully',
                ));
            } else {
                $result = "failed";
                logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Creation failed by Userd ID: " . $current_userid, $result);
                #On user creation failed
                http_response_code(400);
                echo json_encode(array(
                    'result_code' => 400,
                    'message' => 'Warehouse Inbound Creation failed'
                ));
            }
        } else {
            //Log User Activity
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Warehouse Inbound Creation failed due to wrong data input: ", $result);
            #If all data supplied are wrong
            http_response_code(400);
            echo json_encode(array(
                'result_code' => 400,
                'message' => 'error'
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Create Issues'
        );
        echo json_encode($json_data);
    }
}
# PRODUCT VALIDITY CHECK
elseif (CleanData('qid') == '1131') {
    #
    # PRODUCT VALIDITY CHECK
    #
    $cr = new Smc\Inventory();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);
        $checkData = $cr->ProcessProductValidityCheck($inputData['periodid'], $inputData['product_code']);

        if ($checkData) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = $inputData['product_code'] . " Product Validity Check by User ID: " . $current_userid . " Successful", $result);
            #On User Creation
            http_response_code(200);
            echo json_encode(array(
                'result_code' => 200,
                'message' =>  $inputData['product_code'] . ' Product Validy Check Successful',
                'data' => $checkData
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Creation failed by Userd ID: " . $current_userid, $result);
            #On user creation failed
            http_response_code(400);
            echo json_encode(array(
                'result_code' => 400,
                'message' =>  $inputData['product_code'] . ' Product Validity Check failed. Error: ' . $checkData
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}
# Generate STock Batch Allocation/ Management
elseif (CleanData('qid') == '1132') {
    #
    # Generate STock Batch Allocation/ Management
    #
    $cr = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);
        $checkData = $cr->generateInventoryAllocations($inputData['periodid']);

        if ($checkData) {
            $result = "success";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodid'] . " Batch Stock Generated  by User ID: " . $current_userid . " Successful", $result);
            #On User Creation
            http_response_code(200);
            echo json_encode(array(
                'result_code' => 200,
                'message' =>  $inputData['periodid'] . ' Stock List Allocation Successful',
                'data' => $checkData
            ));
        } else {
            $result = "failed";
            logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = " Batch Stock Generation for Period ID " . $inputData['periodid'] . " failed by Userd ID: " . $current_userid, $result);
            #On user creation failed
            http_response_code(400);
            echo json_encode(array(
                'result_code' => 400,
                'message' =>  $inputData['periodid'] . ' Product Validity Check failed. Error: ' . $checkData
            ));
        }
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}
# Excute Shipment
elseif (CleanData('qid') == '1133') {
    #
    # Excute Shipment
    #
    $movement = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        $data  = $movement->executeForwardShipment($inputData['periodid'], $current_userid);

        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodid'] . " Shipment Execution " . $result . "  by User ID: " . $current_userid . " Successful", $result);
        #On User Creation
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  "Shipment Execution " . $result,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}
# Shipment Lists
elseif (CleanData('qid') == '1134') {
    #
    # Shipment Lists
    #
    $shipment = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        //
        $data  = $shipment->getShipmentList($inputData['periodid']);
        //


        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodid'] . " Shipment List Generated  by User ID: " . $current_userid . " Successful", $result);
        #On User Creation
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  "Shipment List Generated " . $result,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}
# Create Movement
elseif (CleanData('qid') == '1135') {
    #
    # Create Movement
    #
    $shipment = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        $period_id = $inputData['periodId'];
        $transporter_id = $inputData['transporterId'];
        $movement_title = $inputData['title'];
        $shipment_list = $inputData['shipmentIds'];
        $conveyor_id = $inputData['conveyorId'];
        $userid = $current_userid;


        $data  = $shipment->createMovementWithShipments($period_id, $transporter_id, $movement_title, $shipment_list, $conveyor_id, $userid);



        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "Period ID: " . $inputData['periodId'] . " Movement Generated  by User ID: " . $current_userid . " Successful", $result);
        #On User Creation
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  "Movement Done " . $result,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}
#Get Shipment Item(s)/Details
elseif (CleanData('qid') == '1136') {
    #
    # Shipment Lists
    #
    $shipment = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);
        $shipmentId = (int) $inputData['shipmentId'];

        //
        $data  = $shipment->getShipmentItems($shipmentId);
        //


        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "User with ID: " . $current_userid . " get shipmentItem for shipment with ID: " . $shipmentId, $result);
        #On Shipment Request
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  "Shipment Items " . $result,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}
#Get Shipment Details
elseif (CleanData('qid') == '1136a') {
    #
    # Shipment Lists
    #
    $shipment = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);
        $shipmentId = (int) $inputData['shipmentId'];

        //
        $data  = $shipment->getShipmentDetails($shipmentId);
        //


        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "User with ID: " . $current_userid . " get shipmentItem for shipment with ID: " . $shipmentId, $result);
        #On Shipment Request
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  "Shipment Details " . $result,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}
#Get Movement List
elseif (CleanData('qid') == '1137') {
    #
    # Shipment Lists
    #
    $shipment = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        //
        $data  = $shipment->getMovementList($inputData['periodId']);
        //


        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "User with ID: " . $current_userid . " get Movement for Period with ID: " . $inputData['periodId'], $result);
        #On Shipment Request
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  "Shipment Items " . $result,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}

#Get Movement List
elseif (CleanData('qid') == '1138') {
    #
    # Shipment Lists
    #
    $shipment = new Smc\Logistics();


    if (getPermission($user_priviledge, 'smc') >= 3) {
        $inputData = json_decode(file_get_contents('php://input'), true);

        //
        $data  = $shipment->getMovementDetails($inputData['movementId']);
        //


        $result = "success";
        logUserActivity($userid = $current_userid, $platform, $module = "smc", $description = "User with ID: " . $current_userid . " get Movement for Movement with ID: " . $inputData['movementId'], $result);
        #On Shipment Request
        http_response_code(200);
        echo json_encode(array(
            'result_code' => 200,
            'message' =>  "Shipment Items " . $result,
            'data' => $data
        ));
    } else {
        $json_data = array(
            "result_code" => 401,
            'message' => 'You don\'t have permission to Check Product Validity'
        );
        echo json_encode($json_data);
    }
}



#   EOLIN
#  EOLIN Mobilization Dashboard Top Summary
elseif (CleanData('qid') == '1150') {
    /*
    *
    *  EOLIN Mobilization Dashboard Top Summary
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $dd = $nt->TopSummaryMobilization();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Dashboard Top Summary',
        'message' => 'success',
        'data' => $dd
    ));
}
#  EOLIN Mobilization  LGA Top Summary Dashboard
elseif (CleanData('qid') == '1151') {
    /*
    *
    *  EOLIN Mobilization  LGA Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->LgaSummaryMobilization();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Mobilization LGA Top Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}
#  EOLIN Mobilization  Ward Top Summary Dashboard
elseif (CleanData('qid') == '1152') {
    /*
    *
    *  EOLIN Mobilization  WARD Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->WardSummaryMobilization($lgaid = CleanData('lgaId'));
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Mobilization Ward Top Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}
#  EOLIN Mobilization  DP in a ward Top Summary Dashboard
elseif (CleanData('qid') == '1153') {
    /*
    *
    *  EOLIN Mobilization DP in a Ward Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->DpSummaryMobilization(CleanData('wardId'));
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Mobilization Top DP in a Ward Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}



#  EOLIN Distribution Dashboard Top Summary
elseif (CleanData('qid') == '1180') {
    /*
    *
    *  e-Netcard LGA Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->TopSummaryDistribution();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Diatribution LGA Top Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}

#  EOLIN Distribution  LGA Top Summary Dashboard
elseif (CleanData('qid') == '1181') {
    /*
    *
    *  EOLIN Distribution  LGA Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->LgaSummaryDistribution();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Mobilization LGA Top Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}

#  EOLIN Distribution  Ward Top Summary Dashboard
elseif (CleanData('qid') == '1182') {
    /*
    *
    *  EOLIN Distribution  WARD Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->WardSummaryDistribution($lgaid = CleanData('lgaId'));
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Distribution Ward Top Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}
#  EOLIN Distribution  DP in a ward Top Summary Dashboard
elseif (CleanData('qid') == '1183') {
    /*
    *
    *  EOLIN Distribution DP in a Ward Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->DpSummaryDistribution(CleanData('wardId'));
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Distribution Top DP in a Ward Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}
#  EOLIN Distribution  DP in a ward Top Summary Dashboard
elseif (CleanData('qid') == '1184') {
    /*
    *
    *  EOLIN Distribution DP in a Ward Top Summary Dashboard
    */
    #  All NetcardTop Summary
    $nt = new Dashboard\Eolin();
    $result = $nt->DpSummaryDistribution(CleanData('wardId'));
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'EOLIN Distribution Top DP in a Ward Summary Dashboard',
        'message' => 'success',
        'data' => $result
    ));
}


// getShipmentList($periodid)