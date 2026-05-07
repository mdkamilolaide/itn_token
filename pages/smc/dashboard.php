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
        <style>
            #icc_long.table-responsive {
                overflow-x: auto;
                position: relative;
            }

            #icc_long table th,
            #icc_long table td {
                white-space: nowrap;
                text-align: center;
                vertical-align: middle;
                background: #fff;
            }

            .sticky-col {
                position: sticky;
                background: #fff;
                z-index: 2;
            }

            .col-1 {
                left: 0;
                z-index: 3;
            }

            .col-2,
            .col-3 {
                left: 0;
                /* will be set via JavaScript */
            }

            .w-text {
                width: 35px;
                display: inline-block;
            }
        </style>
    </section>
    <!-- Page container end -->

    </script>
<?php
} else {
    error_msg("FATAL ERROR:: Privilege abuse fatal error, you did not have privilege to access this page, kindly logout and login again.");
}

?>