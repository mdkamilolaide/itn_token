<?php
// $privilege = $_SESSION[$instance_token.'privileges'];
$privilege = $token->system_privilege;


$privi = 'dashboard';
if (IsPrivilegeInArray(json_decode($privilege, true), $privi)) {

?>
    <style>
        .left-ctr {
            padding: .8rem 1rem !important;
            border-bottom: 1px solid #ebe9f1 !important;
            background-color: #fff;
            border-top-right-radius: 0.357rem;
        }

        /* Custom scrollbar track */
        .lgaAggregate1::-webkit-scrollbar-track {
            background-color: rgba(75, 70, 92, .4);
            /* Light grey */
        }

        /* Custom scrollbar thumb */
        .lgaAggregate1::-webkit-scrollbar-thumb {
            background-color: #888;
            /* Dark grey */
            border-radius: 5px;
            /* Rounded corners */
            background-color: darkgrey;
            visibility: hidden;
        }

        /* Optional: Hide scrollbar when not hovered */
        .lgaAggregate1::-webkit-scrollbar {
            width: 2px;
            height: 2px;
        }

        .lgaAggregate1::-webkit-scrollbar-thumb:hover {
            background-color: #555;
            /* Darker grey on hover */
        }

        /* Optional: Hide scrollbar on Firefox */
        .lgaAggregate1 {
            scrollbar-width: thin;
            overflow: hidden;
            /* Firefox */
        }

        .lgaAggregate1:hover,
        .lgaAggregate1:active::-webkit-scrollbar-thumb,
        .lgaAggregate1:focus::-webkit-scrollbar-thumb,
        .lgaAggregate1:hover::-webkit-scrollbar-thumb {
            visibility: visible;
            scrollbar-width: thin;
            overflow-x: scroll;
        }

        /* Custom CSS for sticky table head */
        .table-fixed thead {
            position: sticky;
            top: 0;
            z-index: 1;
            background-color: #fff;
            /* Optional: Background color for the sticky header */
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