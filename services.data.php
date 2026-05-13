<?php
/*
 *  Data API entry point.
 *  Loads bootstrap + JWT + scope, then dispatches the request to
 *  lib/api/data/{qid}.php. Each handler file inherits this script's
 *  local scope ($token, $current_userid, $user_priviledge, $v_g_*, etc.).
 */

include_once('lib/autoload.php');
include_once('lib/common.php');
include_once('lib/config.php');
log_system_access();

$system_base_directory = __DIR__;
$default_home = ($config_pre_append_link ?? '') . 'login';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = file_get_contents('lib/privateKey.pem');

require('lib/vendor/autoload.php');    //JWT Autoload

$jwt_token = $_COOKIE[$secret_code_token];
$token = JWT::decode($jwt_token, new Key($secret_key, 'HS512'));

if ($token->iss !== $issuer_claim && $token->nbf > $issuedat_claim->getTimestamp() || $token->exp < $issuedat_claim->getTimestamp()) {
    http_response_code(404);
    echo json_encode(array(
        'result_code' => 404,
        'message' => 'Error:  404, Page not Found'
    ));
    return;
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
        $logid = System\General::LogActivity($userid, $platform, $module, $description, $result);
        if ($logid) {
            return;
        } else {
            return;
        }
    }
}

/*
 *  Dispatch
 *
 *  $qid is resolved through lib/api/data/_routes.php (qid => relative path).
 *  Lookup is O(1) hash; the manifest is loaded once and cached by opcache.
 *  The whitelist regex defends against path traversal even though the
 *  manifest paths are static.
 */
$qid = CleanData('qid');
$routes = require __DIR__ . '/lib/api/data/_routes.php';

if ($qid !== '' && isset($routes[$qid]) && preg_match('#^[A-Za-z0-9_\-/]+\.php$#', $routes[$qid])) {
    $handler = __DIR__ . '/lib/api/data/' . $routes[$qid];
    if (is_file($handler)) {
        require $handler;
        return;
    }
}

http_response_code(404);
echo json_encode(array(
    'result_code' => 404,
    'message' => 'Unknown qid'
));
