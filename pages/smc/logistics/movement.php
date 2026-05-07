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

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 8px;
            right: 4px;
            color: #dad8e0;
        }

        .select2-container--default .select2-selection--single {
            border-color: #dad8e0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #757382;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            color: #7367f0;
            font-size: 16px;
        }




        span.select2-selection.select2-selection--single.is-invalid {
            border-color: #ea5455 !important;
        }
    </style>
    <!-- Page container end -->

<?php
} else {
    error_msg("FATAL ERROR:: Privilege abuse fatal error, you did not have privilege to access this page, kindly logout and login again.");
}

?>