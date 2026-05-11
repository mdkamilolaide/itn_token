<?php

declare(strict_types=1);

# Detect and safe base directory
$system_base_directory = __DIR__;
include("lib/config.php");
include("lib/common.php");
include("lib/autoload.php");
//
log_system_access();

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

require('lib/vendor/autoload.php');    //JWT Autoload

$secret_key = file_get_contents('lib/privateKey.pem');


if (isset($_COOKIE[$secret_code_token])) {
    setcookie($secret_code_token, '', time() - 3600, '/');
}


$err = "";
$pass_change = !isset($_GET['succ']) ? "" : $_GET['succ'];
#   Log users activity
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
    return;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = new Users\Login();
    $login->SetLoginId(CleanData("login_id"), CleanData("login_password"));
    if ($login->RunLogin()) {
        $loginDetails = $login->GetLoginData();

        //  Payload 
        $token =

            array(
                "iss" => $issuer_claim,
                "aud" => $config_pre_append_link,
                "iat" => $issuedat_claim->getTimestamp(),
                "nbf" => $issuedat_claim->getTimestamp(),
                "exp" => $expire_claim,

                "login_id" => $loginDetails['loginid'],
                "user_id" => $loginDetails['userid'],
                "fullname" => $loginDetails['fullname'],
                "username" => $loginDetails['username'],
                "active" => $loginDetails['active'],
                "user_change_password" => $loginDetails['user_change_password'],

                "guid" => $loginDetails['guid'],

                "role_id" => $loginDetails['roleid'],
                "role" => $loginDetails['role'],
                "role_code" => $loginDetails['role_code'],

                "geo_level" => $loginDetails['geo_level'],
                "geo_level_id" => $loginDetails['geo_level_id'],
                "geo_value" => $loginDetails['geo_value'],
                "geo_title" => $loginDetails['geo_title'],
                "geo_string" => $loginDetails['geo_string'],

                "system_privilege" => $loginDetails['system_privilege'],
                "platform_priv" => $loginDetails['platform'],
                "priority" => $loginDetails['priority'],
                "user_group" => $loginDetails['user_group']
            );


        // Encrypt user data and set token
        $jwt = JWT::encode($token, $secret_key, 'HS512');

        //  Determine where to go
        #
        #   Check the Platform availability
        $platform_priv = "";
        $platform = $loginDetails['platform'] ?? "";
        $platf = 'web';

        if (IsPlatformInArray(json_decode($platform, true), $platf)) {
            $default_home = "./";

            //  Set Token cookie
            setcookie($secret_code_token, $jwt, time() + 36800, '/', '');


            //Log User Activities
            logUserActivity($userid = $loginDetails['userid'], $platform = $platf, $module = "login", $description = $loginDetails['fullname'] . ' (' . $loginDetails['loginid'] . ') Successfully Logged In (' . $_SERVER['HTTP_HOST'] . ')', $result = 'success');

            //  Redirect
            header("location: $default_home");
        } else {
            //Log User Activities
            logUserActivity($userid = $loginDetails['userid'], $platform = $platf, $module = "login", $description = $loginDetails['fullname'] . ' (' . $loginDetails['loginid'] . ') tried to Login on the web (' . $_SERVER['HTTP_HOST'] . ') but don\'t have access to the web platform', $result = 'failed');

            // session_unset();
            // session_destroy();
            $err = "You don't have access to the web platform, kindly check with your supervisor";
        }
    } else {
        // header("location: $default_home");
        //Log User Activities
        $err = $login->LastError;
        logUserActivity($userid = 0, $platform = "web", $module = "login", $description = 'An Uknown User is trying to access the web platform  (' . $_SERVER['HTTP_HOST'] . ')  using a wrong user details; Login ID: ' . CleanData("login_id") . ' - ' . $err, $result = 'failed');
    }
}



?>
<!DOCTYPE html>
<html class="loading semi-dark-layout" lang="en" data-layout="semi-dark-layout" data-textdirection="ltr">
<!-- BEGIN: Head-->

<head>
    <script>
        window.localStorage.clear();
    </script>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="description" content="Ipolongo Magic, by GHSC-PSM">
    <meta name="keywords" content="Ipolongo Magic, by GHSC-PSM">
    <meta name="author" content="GHSC-PSM">
    <title>ITN Platform</title>
    <link rel="apple-touch-icon" href="app-assets/images/ico/Ipolongo-icon-colored-128x128.ico">
    <link rel="shortcut icon" type="image/x-icon" href="app-assets/images/ico/Ipolongo-icon-colored-256X256.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

    <!-- BEGIN: CSS-->
    <?php include("pages/css.php"); ?>

    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="app-assets/css/plugins/forms/form-validation.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/pages/page-auth.css">
    <!-- END: Page CSS-->


</head>
<!-- END: Head-->

<!-- BEGIN: Body-->

<body class="vertical-layout vertical-menu-modern blank-page navbar-floating footer-static  " data-open="click" data-menu="vertical-menu-modern" data-col="blank-page">
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <div class="auth-wrapper auth-v1 px-2">
                    <div class="auth-inner py-2">
                        <!-- Login v1 -->
                        <div class="card mb-0">
                            <div class="card-body">
                                <span class="server-type <?php echo $server_type != 'Demo' ? 'live' : ''; ?>"><?php echo $server_type; ?></span>
                                <a href="javascript:void(0);" class="brand-logo">
                                    <img src="app-assets/images/logo/logo.png" alt="" style="width: auto; height: 65px;">
                                </a>

                                <!-- <p class="card-text mb-2">Please sign-in to your account using your login details</p> -->

                                <form class="auth-login-form mt-2" action="" method="POST">
                                    <?php
                                    if ($err) {
                                        echo
                                        "<div class='alert alert-danger alert-dismissible fade show p-1' role='alert'>
                                                    <i class='mr-50 align-middle feather icon-info'></i>
                                                    $err
                                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                                        <span aria-hidden='true'>&times;</span>
                                                    </button>
                                                </div>";
                                    }

                                    if ($pass_change == 'yes') {
                                        echo
                                        "<div class='alert alert-success alert-dismissible fade show p-1' role='alert'>
                                                    <i class='mr-50 align-middle feather icon-check-circle'></i>
                                                    Password Successfully changed
                                                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                                        <span aria-hidden='true'>&times;</span>
                                                    </button>
                                                </div>";
                                    }

                                    ?>
                                    <div class="form-group">
                                        <label for="login_id" class="form-label">Login ID</label>
                                        <input type="text" class="form-control" id="login_id" name="login_id" placeholder="Login ID" aria-describedby="login_id" tabindex="1" autocomplete="username" autofocus />
                                    </div>

                                    <div class="form-group mt-2">
                                        <div class="d-flex justify-content-between">
                                            <label for="login-password">Password</label>
                                            <!-- 
                                                <a href="page-auth-forgot-password-v1.html">
                                                    <small>Forgot Password?</small>
                                                </a> 
                                            -->
                                        </div>
                                        <div class="input-group input-group-merge form-password-toggle">
                                            <input type="password" class="form-control form-control-merge" id="login_password" name="login_password" tabindex="2" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="login-password" autocomplete="current-password" />
                                            <div class="input-group-append">
                                                <span class="input-group-text cursor-pointer"><i data-feather="eye"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input" type="checkbox" id="remember-me" tabindex="3" />
                                            <label class="custom-control-label" for="remember-me"> Remember Me </label>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-block" tabindex="4">Sign in</button>
                                </form>

                            </div>
                        </div>
                        <!-- /Login v1 -->
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- END: Content-->


    <!-- BEGIN: Vendor JS-->
    <script src="app-assets/vendors/js/vendors.min.js"></script>
    <!-- BEGIN Vendor JS-->

    <!-- BEGIN: Page Vendor JS-->
    <script src="app-assets/vendors/js/forms/validation/jquery.validate.min.js"></script>
    <!-- END: Page Vendor JS-->

    <!-- BEGIN: Theme JS-->
    <script src="app-assets/js/core/app-menu.js"></script>
    <script src="app-assets/js/core/app.js"></script>
    <!-- END: Theme JS-->

    <!-- BEGIN: Page JS-->
    <script src="app-assets/js/scripts/pages/page-auth-login.js"></script>
    <!-- END: Page JS-->

    <script>
        $(window).on('load', function() {
            if (feather) {
                feather.replace({
                    width: 14,
                    height: 14
                });
            }
            window.history.pushState('', '', 'login');
        })
    </script>
</body>
<!-- END: Body-->

</html>