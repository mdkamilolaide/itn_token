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
        .inbound-item .close-btn {

            margin: -2.4rem -1.84rem -1.8rem auto;
            text-shadow: 0 1px 0 #F8F8F8;
            padding: .1rem .62rem;
            box-shadow: 0 5px 20px 0 rgba(34, 41, 47, .1);
            border-radius: .357rem;
            background: #82868b !important;
            opacity: 1;
            -webkit-transition: all .23s ease .1s;
            transition: all .23s ease .1s;
            position: relative;
            -webkit-transform: translate(8px, -2px);
            -ms-transform: translate(8px, -2px);
            transform: translate(8px, -2px);
            border: none;
            float: right;
            font-size: 1.2em;
            line-height: 1.4;
            color: #fff;
        }

        .w-text {
            width: 75px;
            display: inline-block;
        }
    </style>
    <!-- Page container end -->

<?php
} else {
    error_msg("FATAL ERROR:: Privilege abuse fatal error, you did not have privilege to access this page, kindly logout and login again.");
}

?>