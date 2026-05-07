<?php
// $privilege = $_SESSION[$instance_token.'privileges'];
$privi = 'users';
if (IsPrivilegeInArray(json_decode($privilege, true), $privi)) {

?>
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