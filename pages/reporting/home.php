<?php
// $privilege = $_SESSION[$instance_token.'privileges'];
$privilege = $token->system_privilege;


$privi = 'reporting';
if (IsPrivilegeInArray(json_decode($privilege, true), $privi)) {

?>
    <style>
        .left-ctr {
            padding: .8rem 1rem !important;
            border-bottom: 1px solid #ebe9f1 !important;
            background-color: #fff;
            border-top-right-radius: 0.357rem;
        }
    </style>
    <noscript>
        <strong>We're sorry, this app doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
    </noscript>
    <!-- Page container -->
    <section id="dashboard-analytics">
        <div id="app"></div>
    </section>
    <!-- Page container end -->

<?php
} else {
    error_msg("FATAL ERROR:: Privilege abuse fatal error, you did not have privilege to access this page, kindly logout and login again.");
}
?>