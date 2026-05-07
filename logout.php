<?php

//  Includes
include_once('lib/autoload.php');
include_once('lib/common.php');
include_once('lib/config.php');
//
// session_start();
//  Log actions before leaving
$default_home = $config_pre_append_link . 'login';


# Detect and safe base directory
$system_base_directory = __DIR__;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = file_get_contents('lib/privateKey.pem');

require('lib/vendor/autoload.php');    //JWT Autoload
/*
     *  configure required protocol access
     */

if (isset($_COOKIE[$secret_code_token])) {
    $jwt_token = $_COOKIE[$secret_code_token];

    $token = JWT::decode($jwt_token, new Key($secret_key, 'HS512'));
    //

    $logid = System\General::LogActivity($userid = $token->user_id, $platform = $platf, $module = "login", $description = $token->fullnane . " (" . $token->login_id . ") Successfully Logged Out", $result = "success");


    setcookie($secret_code_token, '', time() - 3600, '/');

    header("location: $default_home");
    header("location: $default_home");
    if ($_GET['state'] == 'pass') {
        header("location: $default_home?succ=yes");
    } else {
        header("location: $default_home");
    }
} else {
    header("location: $default_home");
}
