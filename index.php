<?php
# Detect and safe base directory
$system_base_directory = __DIR__;
include("lib/config.php");
include("lib/common.php");
include("lib/autoload.php");
//  
log_system_access();
//
if ($config_ht_protocol_secure) {
    #   Force to secure
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }
} else {
    # dont force to secure
}

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = file_get_contents('lib/privateKey.pem');

require('lib/vendor/autoload.php');    //JWT Autoload
/*
*
*/
//$app_version = "2.0.5";
/*
     *  Ebonyi Implementation feedback upgrade
     *  Version 3.13 on 28/03/2023 upon lots of system upgrade
     *  ------
     *  Cross River System Upgrade 3.11-14
     *  Basic updates for Calabar deployment
     *  -----
     *  Version 3.20 - starting point for 
     *  Basic upgrade and fixes after Cross river deployment
     *  - Change of login system - server based => JWT
     *  - AMF Requirements
     *  - Stability fixes
     *  - 
     *  Version 3.30 
     *  - Start point for fixes for Benue deployment
     *  - monitoring module added
     *  Version 3.3.33
     *  - Reporting module implemented
     *  - couple of fixes
     *  Version 3.3.34
     * - Temp move of pdo transaction compatibility with php 8.xxx
     * - Update of reporting with privileges
     * - 
     *  Version 4.0.35
     * - Add new SMC Module
     * - SMC API, Controller, backend, web features
     * - 
     */
//  $app_version = "4.0.35";
##
##  2025 update
#
#   Version 4.1.36
#   - Add fixes for SMC Module
#   - Add fixes for SMC API, Controller, backend, web features
##$app_version = "5.0.42";
#
#   fix SMC shipment
#   fix SMC e-POD
#   Stable deployment 2025-07-01
#
$app_version = "5.0.49";
#
$default_login = $config_pre_append_link . 'login';
$default_logout = $config_pre_append_link . 'logout';
//  check for cookies validity (serves as server-side timer)
if (isset($_COOKIE[$secret_code_token])) {
    $jwt_token = $_COOKIE[$secret_code_token];
    $token = JWT::decode($jwt_token, new Key($secret_key, 'HS512'));
    //  reset
    if ($token->iss !== $issuer_claim && $token->nbf > $issuedat_claim->getTimestamp() || $token->exp < $issuedat_claim->getTimestamp()) {
        header("location: $default_login");
    } else {
        setcookie($secret_code_token, $jwt_token, time() + 36800, '/', '');
        //  stay login else
        //  Set general variables
        $v_g_id = $token->user_id;
        $v_g_fullname = $token->fullname;
        $v_g_loginid = $token->login_id;
        $v_g_geo_level = $token->geo_level;
        $v_g_geo_level_id = $token->geo_level_id;
        $v_g_rolename = $token->role;
        $v_g_pass_change = $token->user_change_password;
        $priv = $token->system_privilege;
    }
} else {
    //  Log user out
    header("location: $default_logout");
}
?>

<!DOCTYPE html>
<html class="loading semi-dark-layout" lang="en" data-layout="semi-dark-layout" data-textdirection="ltr">
<!-- BEGIN: Head-->

<head>
    <script>
        window.localStorage.setItem('<?php echo "user_roleQr" . $v_g_loginid; ?>', JSON.stringify(<?php echo $priv ?>));
        let per = JSON.parse(localStorage.getItem('<?php echo "user_roleQr" . $v_g_loginid; ?>'));

        function getPermission(per, val) {
            for (let i = 0; i < per.length; i++) {
                /*
                if (per[i]['name'] == val) {
                    return per[i];
                }
                */

                if (per[i]['name'] == val || per[i]['name'] == String(val)) {
                    return per[i];
                }
            }
            return 1;
        }

        
    </script>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="description" content="Ipolongo Magic, by GHSC-PSM">
    <meta name="keywords" content="Ipolongo Magic, by GHSC-PSM">
    <meta name="author" content="GHSC-PSM">
    <title>ITN Platform</title>
    <link rel="apple-touch-icon" href="<?php echo $config_pre_append_link . 'app-assets/images/ico/Ipolongo-icon-colored-128x128.ico'; ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo $config_pre_append_link . 'app-assets/images/ico/Ipolongo-icon-colored-256X256.ico'; ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap" rel="stylesheet">

    <!-- BEGIN: CSS-->
    <?php include("pages/css.php"); ?>
    <!-- END: CSS-->

</head>
<!-- END: Head-->

<!-- BEGIN: Body-->

<body class="vertical-layout vertical-menu-modern  navbar-floating footer-static " data-open="click" data-menu="vertical-menu-modern" data-col="">

    <?php
    #   Add the header
    include('pages/header.php');
    #   Add the Navigation bar
    include('pages/nav.php');
    ?>
    <!-- BEGIN: Content-->
    <div class="app-content content ">
        <div class="content-overlay"></div>
        <div class="header-navbar-shadow"></div>
        <div class="content-wrapper p-0">
            <div class="content-header row">
            </div>
            <div class="content-body">
                <?php include('pages/page.php'); ?>
            </div>
        </div>
    </div>
    <!-- END: Content-->
    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>
    <!-- BEGIN: Footer-->
    <?php include('pages/footer.php'); ?>
    <!-- END: Footer-->
    <!-- BEGIN:  JS-->
    <?php include('pages/js.php'); ?>
    <!-- END:  JS-->


</body>
<!-- END: Body-->

</html>