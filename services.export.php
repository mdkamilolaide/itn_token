<?php
/*
 *  Export API entry point.
 *  Loads bootstrap + JWT + scope, then dispatches the request to
 *  lib/api/export/{qid}.php. Each handler file inherits this script's
 *  local scope ($token, etc.) and echoes its own Excel/JSON payload.
 */

include_once('lib/autoload.php');
include_once('lib/common.php');
include_once('lib/config.php');
session_start();

$default_home = $config_pre_append_link . 'login';
log_system_access();

$system_base_directory = __DIR__;

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

/*
 *  Dispatch
 *
 *  $qid is resolved through lib/api/export/_routes.php (qid => relative path).
 *  Lookup is O(1) hash; the manifest is loaded once and cached by opcache.
 *  The whitelist regex defends against path traversal even though the
 *  manifest paths are static.
 */
$qid = CleanData('qid');
$routes = require __DIR__ . '/lib/api/export/_routes.php';

if ($qid !== '' && isset($routes[$qid]) && preg_match('#^[A-Za-z0-9_\-/]+\.php$#', $routes[$qid])) {
    $handler = __DIR__ . '/lib/api/export/' . $routes[$qid];
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
