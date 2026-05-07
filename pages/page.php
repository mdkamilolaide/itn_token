<?php

/*
     *
     *  Page Loader
     * 
     */
$module = CleanData('module');
$submodule = CleanData('submodule');
$module_list = $config_modules;
$extra_script = "";

if (in_array($module, $module_list)) {
    //  you can chose to reject here
    //  by telling the users the page does not exist
    //  or presenting something diferrent
    //  return;
}

/*
*      Process the pages presentation
*/
if ($module == 'sample') {
    #   Sample module
    if ($submodule == "sample") {
        #load sample/sample
        include('pages/sample.php');
    } elseif ($submodule == 'table') {
        include('pages/sample/table.php');
    } elseif ($submodule == 'datatable') {
        include('pages/sample/datatable.php');
    } else {
        #   Default
    }
} elseif ($module == 'dashboard')
#   Home module
{
    #   Home module
    if ($submodule == 'home') {
        #load Home Dashboard
        include('pages/dashboard/home.php');
    } else {
        #   Default
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'admin')
#   Admin module
{
    #   Users module
    if ($submodule == 'log') {
        #load users/Users list
        include('pages/admin/log.php');
    } elseif ($submodule == 'provision') {
        #load users/Users list
        include('pages/admin/provision.php');
    } else {
        #   Default
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'device')
#   Device module
{
    #   Device module
    if ($submodule == 'registry') {
        include('pages/device/registry.php');
        #load Device Registry
    }
    #   Device Login Log
    elseif ($submodule == 'loginlog') {
        include('pages/device/loginlog.php');
        #load Device Registry
    } elseif ($submodule == 'allocation') {
        #load users/Users list
        include('pages/device/deviceallocation.php');
    } else {
        #   Default
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'users')
#   Users module
{
    #   Users module
    if ($submodule == 'list') {
        #load users/Users list
        include('pages/users/list.php');
    } elseif (
        $submodule == 'group'
    ) {
        #load users/Users list
        include('pages/users/group.php');
    } elseif ($submodule == '') {
        #load users/Users list
        include('pages/users/dashboard.php');
    } else {
        #   Load Default if not Found
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'activity')
#   Trainners module converted to activity
{
    #   Trainners module
    if ($submodule == 'list') {
        #load Trainings/list
        include('pages/activity/list.php');
    } elseif ($submodule == '' || $submodule == 'reporting') {
        #load users/Users list
        include('pages/activity/dashboard.php');
    } else {
        #   Load Default if not Found
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'monitoring')
#   Monitoring module converted to activity
{

    if ($submodule == '') {
        #load Monitoring/list
        include('pages/monitoring/home.php');
    } else {
        #   Default
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'reporting')
#   Reporting module converted to activity
{

    if ($submodule == '') {
        #load Reptoring/list
        include('pages/reporting/home.php');
    } else {
        #   Default
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'netcard')
#   Netcard module
{
    #   Netcard module
    if ($submodule == 'movement') {
        #Netcard Movement
        include('pages/netcard/movement.php');
    } elseif ($submodule == 'allocation') {
        #Netcard Allocation
        include('pages/netcard/allocation.php');
    } elseif ($submodule == 'unlock') {
        #Netcard Usage
        include('pages/netcard/unlock.php');
    } elseif ($submodule == 'pushed') {
        #Netcard Usage
        include('pages/netcard/pushed.php');
    } elseif ($submodule == '') {
        #Netcard Usage
        include('pages/netcard/dashboard.php');
    } else {
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'eolin')
#   Netcard module
{
    #   Netcard module
    if ($submodule == '') {
        #Netcard Usage
        include('pages/netcard/dashboard.php');
    } else {
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'mobilization')
#   Mobilization module
{
    #   Mobilization module
    if ($submodule == 'map') {
        #Map View
        include('pages/mobilization/map.php');
    } elseif ($submodule == 'microlist') {
        #Micro List
        include('pages/mobilization/microlist.php');
    } elseif ($submodule == 'dashboard' || $submodule == 'list' || $submodule == 'reporting' || $submodule == '') {
        #Dashboard
        include('pages/mobilization/dashboard.php');
    } else {
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'distribution')
#   Users module
{
    #   Users module
    if ($submodule == 'dplist') {
        #load dp/Dp list
        include('pages/distribution/dplist.php');
    } elseif ($submodule == 'list') {
        #load users/Users list
        include('pages/distribution/list.php');
    } elseif ($submodule == 'unredeemnet') {
        #load users/Users list
        include('pages/distribution/unredeemnet.php');
    } elseif ($submodule == '' || $submodule == 'reporting') {
        #   Default
        include('pages/distribution/dashboard.php');
    } else {
        #   Default
        include('pages/home/pagenotfound.php');
    }
} elseif ($module == 'smc')
#   SMC Module
{
    #   Trainners module
    if ($submodule == 'visit' || $submodule == 'drugadministration' || $submodule == 'cohorttracking' || $submodule == 'balance') {
        #load Trainings/list
        include('pages/smc/visit.php');
    } elseif ($submodule == 'logisticsallocation') {
        #Allocation
        include('pages/smc/logistics/allocation.php');
    } elseif ($submodule == 'logisticsavailabilitycheck') {
        #AvailaBILITY Check
        include('pages/smc/logistics/availabilitycheck.php');
    } elseif ($submodule == 'logisticsinboundwarehouse') {
        #Warehouse Inbound
        include('pages/smc/logistics/inboundwarehouse.php');
    } elseif ($submodule == 'logisticsstockbatchmanagement') {
        #Stock Batch Management
        include('pages/smc/logistics/stockbatchmanagement.php');
    } elseif ($submodule == 'logisticsshipment') {
        #Shipment Management
        include('pages/smc/logistics/shipment.php');
    } elseif ($submodule == 'logisticsmovement') {
        #Movement Management
        include('pages/smc/logistics/movement.php');
    } else {
        #   Default
        include('pages/smc/dashboard.php');
    }
}
#
elseif ($module != '' && strlen($module) >= 1) {
    include('pages/home/pagenotfound.php');
} else {
    //  Load default
    include('pages/home/home.php');
    // include('pages/dashboard/home.php');
}
// echo $module;
