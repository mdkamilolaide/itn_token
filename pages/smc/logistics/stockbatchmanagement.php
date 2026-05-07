<?php
// $privilege = $_SESSION[$instance_token.'privileges'];

$privi = 'smc';
if (IsPrivilegeInArray(json_decode($privilege, true), $privi)) {

?>
    <noscript>
        <strong>We're sorry, this app doesn't work properly without JavaScript enabled. Please enable it to continue.</strong>
    </noscript>
    <!-- Page container -->
    <section id="dashboard-analytics">
        <div id="app"></div>
    </section>
    <style>
        .form-group.middle-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            /* Ensure there's height to center within */
            text-align: center;
        }

        .transfer-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            /* margin-top: -10px; */
        }

        .icon-group {
            display: flex;
            flex-direction: row;
            gap: 8px;
        }

        .d-sm-none .icon-group {
            flex-direction: column;
        }

        .transfer-circle i {
            font-size: 16px;
            line-height: 1;
        }
    </style>
    <!-- Page container end -->

<?php
} else {
    error_msg("FATAL ERROR:: Privilege abuse fatal error, you did not have privilege to access this page, kindly logout and login again.");
}

?>