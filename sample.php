<?php

use Dashboard\Dashboard;
use Dashboard\Mobilization;
use Mobilization\Mobilization as Mob;
use System\General;


include_once('lib/autoload.php');
include_once('lib/common.php');
include("lib/config.php");

if (CleanData('qid') == '001') {

    #
    #   Sample run e-netcard generation
    #   The max is set in the library (max = 100k)
    #   Max can only be adjusted in the library
    #
    #   Set default total if not set
    $length = CleanData('length') > 0 ? CleanData('length') : 10;
    #   Init netcard here with count you want
    $nc = new Netcard\Netcard($length);
    #   time to measure duration
    $startTime = time();
    #   Generate the e-netcard
    $total = $nc->Generate();
    $endTime = time();
    $timediff = $endTime - $startTime;
    echo "Total e-Netcard generated was $total, duration was $timediff second(s)";
} elseif (CleanData('qid') == '002') {
    #
    #   Random generate UUID
    #
    for ($a = 0; $a < 50; $a++) {
        echo generateUUID() . '<br>';
    }
}
#
#   E-Token Samples
#
elseif (CleanData('qid') == '003') {
    #
    #   Sample run e-token generation
    #   The max is set in the library (max = 100k)
    #   Max can only be adjusted in the library
    #
    #   Set default total if not set
    $length = CleanData('length') > 0 ? CleanData('length') : 10;
    # init token with device ID and length
    #   Please not that the max length set internally is 2000
    #   It can be changed if need be.
    $device_id = 'KM00299292';
    $tk = new Netcard\Etoken($device_id, $length);
    #   time to measure duration
    $startTime = time();
    #   Generate the e-token
    $etokenData = $tk->Generate();
    $total = count($etokenData);
    $endTime = time();
    $timediff = $endTime - $startTime;
    //echo "Total e-Token generated was $total, duration was $timediff second(s)";

    echo "<pre>";
    //print_r($etokenData);
    echo json_encode($etokenData);
    echo "</pre>";
} 
elseif (CleanData('qid') == '003cc') {
    #
    #   Sample run e-token generation
    #   The max is set in the library (max = 100k)
    #   Max can only be adjusted in the library
    #
    #   Set default total if not set
    $length =1000;
    # init token with device ID and length
    #   Please not that the max length set internally is 2000
    #   It can be changed if need be.
    $device_id = 'KM00299292';
    $tk = new Netcard\Etoken($device_id, $length);
    #   time to measure duration
    $startTime = time();
    #   Generate the e-token
    $etokenData = $tk->GenerateLite();
    $total = count($etokenData);
    $endTime = time();
    $timediff = $endTime - $startTime;
    //echo "Total e-Token generated was $total, duration was $timediff second(s)";
    echo json_encode($etokenData);
}elseif (CleanData('qid') == '004') {
    #
    #   E-token update status
    #
    if (Netcard\Etoken::UpdateTokenUsed('0002')) {
        echo "Token for ID:2 has been used successfully.<br>";
    } else {
        echo "Token for ID:2 Used failed.<br>";
    }
    #
    if (Netcard\Etoken::UpdateTokenCancel(3)) {
        echo "Token for ID:3 has been cancelled successfully.<br>";
    } else {
        echo "Token for ID:3 cancel failed.<br>";
    }
}
#
#   E-Netcard Transactions
#
elseif (CleanData('qid') == '005') {
    /*
         *  Runs e-Netcard Samples
         *
         *  List count of Location
         */
    $nt = new Netcard\NetcardTrans();
    $dd = $nt->GetCountByLocation();
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'e-Netcard Location List',
        'message' => 'success',
        'data' => $dd
    ));
} elseif (CleanData('qid') == '005b') {
    /*
         *  Runs e-Netcard Samples
         *
         *  List count LGAs balances
         */
    $nt = new Netcard\NetcardTrans();
    $dd = $nt->GetCountLgaList();
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'e-Netcard LGA List',
        'message' => 'success',
        'data' => $dd
    ));
} elseif (CleanData('qid') == '005c') {
    /*
         *  Runs e-Netcard Samples
         *
         *  List count Ward balances
         */
    $nt = new Netcard\NetcardTrans();
    $lgaid = 526;
    $dd = $nt->GetCountWardList($lgaid);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'e-Netcard Ward List',
        'message' => 'success',
        'data' => $dd
    ));
} elseif (CleanData('qid') == '005d') {
    /*
         *  Runs e-Netcard 
         *
         *  List count Mobilizerd balances
         */
    $nt = new Netcard\NetcardTrans();
    $wardid = 2;
    $data = $nt->GetMobilizersList($wardid);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'e-Netcard Mobilizers List',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '005e') {
    /*
         *  Runs e-Netcard 
         *
         *  List count Allocation mobile app balances
         */
    $nt = new Netcard\NetcardTrans();
    $wardid = 1;
    $data = $nt->CombinedBalanceForApp($wardid);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'e-Netcard Allocation Mobile App Balances',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '005f') {
    /*
         *  Runs e-Netcard 
         *
         *  List count Allocation mobile app transaction list
         */
    $nt = new Netcard\NetcardTrans();
    $wardid = 1;
    $data = $nt->GetAllocationTransferHistoryList($wardid);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'e-Netcard Allocation Mobile App Transaction list',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '005g') {
    /*
         *  Runs e-Netcard 
         *
         *  List count Allocation mobile app reverse order history list
         */
    $nt = new Netcard\NetcardTrans();
    $wardid = 1;
    $data = $nt->GetAllocationReverseHistoryList($wardid);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'e-Netcard Allocation Mobile App reverse order history list',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '006') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Count by Location
         */

    $nt = new Netcard\NetcardTrans();
    // for stock count
    /*
          *     LGA count
          *     $dd = $nt->CountLocationLga();
          *
          *     Ward count
          *     $dd = $nt->CountLocationWard();
          *
          *     Db count
          *     $dd = $nt->CountLocationDp();
          
         echo "Stock Location: $dd";
         */

    #
    #  #
    $state = $nt->ThisCountStateBalance();
    $lga = $nt->ThisCountLgaBalance("526");
} elseif (CleanData('qid') == '006aa') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Count Total active cards
         */

    $nt = new Netcard\NetcardTrans();
    $data = $nt->CountTotalNetcard();
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Total Active Netcard existing',
        'data' => $data
    ));
} elseif (CleanData('qid') == '006a') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Gat balances State
         */

    $nt = new Netcard\NetcardTrans();
    $data = $nt->ThisCountStateBalance();
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'State Balance',
        'data' => $data
    ));
} elseif (CleanData('qid') == '006b') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Gat balances LGA
         */

    $nt = new Netcard\NetcardTrans();
    $data = $nt->ThisCountLgaBalance("526");
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'LGA Akwanga Balance',
        'data' => $data
    ));
} elseif (CleanData('qid') == '006c') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Gat balances Ward
         */

    $nt = new Netcard\NetcardTrans();
    $data = $nt->ThisCountWardBalance(1);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Ward Balance',
        'data' => $data
    ));
} elseif (CleanData('qid') == '006d') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Gat balances HH Mobilizer
         */

    $nt = new Netcard\NetcardTrans();
    $userid = 1;
    $data = $nt->ThisCountHHMobilizerBalance($userid);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'HH Mobilizer by userid Balance',
        'data' => $data
    ));
}
#   Netcard movement from state to LGA (A1)
elseif (CleanData('qid') == '007') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard movement from state to LGA
         */
    $nt = new Netcard\NetcardTrans();
    // for stock count
    $total = 2;
    $stateid = 32;
    $lgaid = 666;
    $userid = 1;
    $nt->StateToLgaMovement($total, $stateid, $lgaid, $userid);

    //echo "$total Netcards has been moved from State to LGA successfully";
}
#   Netcard movement from LGA to Ward (A2)
elseif (CleanData('qid') == '007b') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard movement from LGA to Ward
         */
    $nt = new Netcard\NetcardTrans();
    $total = 30;
    $lgaid = 526;
    $wardid = 5;
    $userid = 1;
    #
    $nt->LgaToWardMovement($total, $lgaid, $wardid, $userid);
    echo "$total Netcards has been moved from LGA to Ward successfully";
}
#   Netcard reverse movement from LGA to State (A3)
elseif (CleanData('qid') == '007c') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard reverse movement from LGA to State
         */
    $nt = new Netcard\NetcardTrans();
    $total = 5;
    $lgaid = 526;
    $stateid = 26;
    $userid = 1;
    #
    $nt->LgaToStateMovement($total, $lgaid, $stateid, $userid);
    echo "$total Netcards has been moved from LGA to State in a reverse movement successfully";
}
#   Netcard reverse movement from Ward to lga (A5)
elseif (CleanData('qid') == '007d') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard reverse movement from Ward to lga
         */
    $nt = new Netcard\NetcardTrans();
    $total = 30;
    $lgaid = 526;
    $wardid = 5;
    $userid = 1;
    #
    $nt->WardToLgaMovement($total, $wardid, $lgaid, $userid);
    echo "$total Netcards has been moved from Ward to LGA in a reverse movement successfully";
}
#   Netcard Allocation Ward to mobilizer (A4)
elseif (CleanData('qid') == '007h') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard forward movement Ward to HH Mobilizer 
         */
    $nt = new Netcard\NetcardTrans();
    $total = 10;
    $mobilizerid = 3;
    $wardid = 1;
    $userid = 2;
    #
    $nt->WardToHHMobilizer($total, $wardid, $mobilizerid, $userid);
    echo "$total e-Netcards has been transferred from Ward to HH Mobilizer successfully";
}
#   Bulk e-Netcard Allocation Ward to mobilizer (BULK A4)
elseif (CleanData('qid') == '007hb') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Bulk e-Netcard Allocation Ward to mobilizer
         */
    $nt = new Netcard\NetcardTrans();
    # ['total'=>$total, 'wardid'=>$wardid, 'mobilizerid'=>$mobilizerid, 'userid'=>$userid]
    $bulk_data = [
        array('total' => 10, 'wardid' => 1, 'mobilizerid' => 3, 'userid' => 2),
        array('total' => 10, 'wardid' => 1, 'mobilizerid' => 4, 'userid' => 2),
        array('total' => 10, 'wardid' => 1, 'mobilizerid' => 5, 'userid' => 2)
    ];
    #
    $total = $nt->BulkAllocationTransfer($bulk_data);
    echo "$total e-Netcards allocation transfer has been performed from Ward to HH Mobilizers successfully";
}
#   Netcard Allocation reverse order (DISABLED)
elseif (CleanData('qid') == '007i') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard reverse order HHM back to ward 
         */
    $nt = new Netcard\NetcardTrans();
    $order_total = 5;
    $mobilizerid = 3;
    $wardid = 1;
    $userid = 2;
    $device_serial = "JMA003";
    #
    if ($nt->ReverseAllocationOrder($mobilizerid, $userid, $order_total, $device_serial)) {
        echo "$order_total e-Netcard reversal order has been placed successfully";
    } else {
        echo "Unable to place e-Netcard reversal order at the moment, please try again later";
    }
}
#   Netcard Allocation reverse order fulfilment
elseif (CleanData('qid') == '007j') {
    #
    #   Netcard Allocation reverse order fulfilment
    $nt = new Netcard\NetcardTrans();
    #
    $orderid = 2;
    $netcard_list = array(
        '7khh4t0s-ljhz-a7zm-o3lo-wzo3px7tfsmc',
        'fiofptns-ts73-ierg-085m-shas60b5eq5j',
        'kot0af87-v3t9-4alu-f7fc-j5r5xwdyym0b'
    );
    $mobilizerid = 3;
    $wardid = 1;
    $userid = 3;
    #
    $total = $nt->HHMobilizerToWardFulfulment($orderid, $netcard_list, $mobilizerid, $wardid, $userid);
    echo "$total e-Netcard was fulfilled successfully";
} elseif (CleanData('qid') == '009') {
    /*
         *  Runs e-Netcard Samples
         *
         *  Netcard movement from Ward to DP
         
         $nt = new Netcard\NetcardTrans();
         // for stock count
         $total = 30;
         $wardid = 322;
         $nt->MovementToDp($total,$wardid,1);
         echo "$total Netcards has been moved from Ward to DP successfully";
         */
}
#
#
#   e-Netcard Get combined mobilizer's balance
elseif (CleanData('qid') == '010') {
    #
    $nt = new Netcard\NetcardTrans();
    $wardid = 1;
    echo json_encode($nt->GetCombinedMobilizerBalance($wardid));
}
#   e-Netcard Get Offline mobilizer's balance
elseif (CleanData('qid') == '010a') {
    #
    $nt = new Netcard\NetcardTrans();
    $wardid = 1;
    echo json_encode($nt->GetOfflineMobilizerBalance($wardid));
}
#   e-Netcard Get Online mobilizer's balance
elseif (CleanData('qid') == '010aa') {
    #
    $nt = new Netcard\NetcardTrans();
    $wardid = 1;
    echo json_encode($nt->GetOnlineMobilizerBalance($wardid));
}
#   e-Netcard Direct (online) reverse allocation
elseif (CleanData('qid') == '010b') {
    #
    $total = 10;
    $userid = 1;
    $requester_id = 2;
    $nt = new Netcard\NetcardTrans();
    $total_reverse = $nt->DirectReverseAllocation($total, $userid, $requester_id);
    #
    echo "Total $total_reverse has been reverse to the LGA successfully";
}
#   e-Netcard Super User Unlock
elseif (CleanData('qid') == '010c') {
    #   
    $nt = new Netcard\NetcardTrans();
    $userid = 1;
    $device_serial = "";
    $requester_id = 2;
    $total = $nt->SuperUserUnlockNetcard($userid, $device_serial, $requester_id);
    echo "Total $total has been unlocked";
}
#   e-Netcard Mobilizer push netcard online
elseif (CleanData('qid') == '010d') {
    #
    $nt = new Netcard\NetcardTrans();
    $userid = 1;
    $netcard_list = array(
        '7khh4t0s-ljhz-a7zm-o3lo-wzo3px7tfsmc',
        'fiofptns-ts73-ierg-085m-shas60b5eq5j',
        'kot0af87-v3t9-4alu-f7fc-j5r5xwdyym0b'
    );
    $total = $nt->PushNetcardOnline($netcard_list, $hhm_id, $device_serial);
    echo "Total $total has been unlocked";
}
#   direct reverse transaction history
elseif (CleanData('qid') == '010e') {
    $nt = new Netcard\NetcardTrans();
    $wardid = 1;
    $data = $nt->GetAllocationDirectReverseList($ward);
    #
    echo json_encode($data);
}
#----   Movement mobile app --------
#
#----   Get mobilizers balances @ Ward Level
elseif (CleanData('qid') == '011a') {
    $nt = new Netcard\NetcardTrans();
    $lgaid = 124;
    $data = $nt->GetWardLevelMobilizersBalances($lgaid);
    #
    echo json_encode($data);
}
#----   Get ward list and e-Netcard balances
elseif (CleanData('qid') == '011b') {
    $nt = new Netcard\NetcardTrans();
    $lgaid = 124;
    $data = $nt->GetWardListAndBalances($lgaid);
    #
    echo json_encode($data);
}
#----   Get movement mobile app top history
elseif (CleanData('qid') == '011c') {
    $nt = new Netcard\NetcardTrans();
    $lgaid = 124;
    $data = $nt->GetMovementTopHistory($lgaid); # You can add option of count
    #
    echo json_encode($data);
}
#----   Get movement mobile app top history
elseif (CleanData('qid') == '011d') {
    $nt = new Netcard\NetcardTrans();
    $lgaid = 124;
    $data = $nt->GetMovementListHistory($lgaid); # You can add option of count
    #
    echo json_encode($data);
}
#----   Get movement mobile app dashboard balances
elseif (CleanData('qid') == '011e') {
    $nt = new Netcard\NetcardTrans();
    $lgaid = 124;
    # data structure - ['balance']['received']['disbursed']
    # balance: LGA balance
    # received: total LGA received 
    # disbursed: total LGA disbursed
    $data = $nt->GetMovementDashboardBalances($lgaid);
    #
    echo json_encode($data);
}
#
#
#   Users
#
elseif (CleanData("qid") == '020') {
    #   Init suer manage class
    $us = new Users\UserManage();
    #   Pad login ID if needed, if not, It will auto generate 3 cap padding
    #   $us->AddLoginPadding("ITN");
    #   Create user with the following
    $id = $us->CreateUser("Kolajo", "kolajo", 1, "ÏCT4D Admin");
    #   Check if successful
    if ($id) {
        echo "New user created successfully. ID: $id <br>";
        echo "User Login ID: " . $us->GetUserLoginId($id);
    } else {
        echo "Create new user failed.";
    }
} elseif (CleanData("qid") == '021') {
    #   Update User information
    #   Init suer manage class
    $us = new Users\UserManage();
    #   Update User's Finance returns positive int on success
    $us->UpdateFinance("Stanbic IBTC", "041", "029920192", "Someone Account Name", 1);
    #   Update User's IDentity returns positive int on success
    $us->UpdateIdentity("Solomon", "Ademola", "James", "Male", "stonemark@mail.com", "08039939393", 1);
    #   Update User's Role returns positive int on success
    $us->UpdateRole(2, 1);
    #
    echo "User records updated successfully";
} elseif (CleanData("qid") == '022') {
    #   Get user informations (Details)
    #   Init User manage class
    $us = new Users\UserManage();
    #   Get User's Base info returns array result single row
    $base = $us->GetUserBaseInfo(1);
    #   Get User's Finance returns array result single row
    $finance = $us->GetUserFinance(1);
    #   Get User's Identity returns array result single row
    $identity = $us->GetUserIdentity(1);
    #   Get User's role structure returns array result single row
    $role = $us->GetUserRoleStructure(1);
    #
    $data = array(
        "base" => $base,
        "finance" => $finance,
        "identity" => $identity,
        "role" => $role
    );
    #
    echo json_encode($data);
} elseif (CleanData("qid") == '023') {
    #   Toggle user status
    #   Init User manage class
    $us = new Users\UserManage();
    #   
    if ($us->ToggleUserStatus(1)) {
        echo "User status updated successfully";
    } else {
        echo "User status update failed, kindly contact system admin for support";
    }
} elseif (CleanData("qid") == '024') {
    #
    #   User login sample
    #
    #   Login using user ID and password
    #
    $login = new Users\Login();
    #   Set login id
    $login->SetLoginId('YC01009', 'demo123');
    $device_serial = 'OWS004';
    #   Run login
    if ($login->RunLogin($device_serial)) {
        #   login successful
        //echo "Login successful";
        #   Get login Data
        $loginData = $login->GetLoginData();
        echo json_encode($loginData);
    } else {
        #   login failed
        echo "Login failed. Error: " . $login->LastError;
    }
} elseif (CleanData("qid") == '025') {
    #
    #   User login sample
    #
    #   Login using badge
    #
    $login = new Users\Login('badge');
    #   Set badge by data - 
    $badge_data = "JTV00002|79mzhz79-u4h9-8df8-a9o8-9vr3b0zkttxi";
    $device_serial = 'OWS004';
    $login->SetBadge($badge_data);
    #   Run login
    if ($login->RunLogin($device_serial)) {
        #   login successful
        echo "Login successful";
        #   Get login Data
        $loginData = $login->GetLoginData();
        echo "<pre>";
        print_r($loginData);
        echo "</pre>";
    } else {
        #   login failed
        echo "Login failed. Error: " . $login->LastError;
        //echo "Login ID: ".$login->GetLoginId();
    }
} elseif (CleanData("qid") == '025b') {
    #
    #   User login sample
    #
    #   Change user password using login ID
    #
    $mg = new Users\UserManage();
    #
    $loginid = "SRJ00020";
    $old = "DEmo2021";
    $new = "DEmo2022";
    if ($mg->ChangePassword($loginid, $old, $new)) {
        echo "Password for <b>$loginid</b> changed successfully";
    } else {
        echo "Unable to change password, maybe use does not exist, or incorrect old password, please try again later";
    }
} elseif (CleanData("qid") == '025c') {
    #
    #   User login sample
    #
    #   Reset user password using login ID
    #
    $mg = new Users\UserManage();
    #
    $loginid = "SRJ00020";
    $new = "DEmo2021";
    if ($mg->ResetPassword($loginid, $new)) {
        echo "Password for <b>$loginid</b> has been reset successfully";
    } else {
        echo "Unable to reset password, maybe use does not exist or system error, please try again later";
    }
} elseif (CleanData("qid") == '026') {
    #
    #   Create Bulk Users
    #
    $usr = new Users\BulkUser('bulk_beta', 'demo123', 'state', 7, 7);
    $total = $usr->CreateBulkUser(2);
    if ($total) {
        echo "<br>Total Created was $total";
    } else {
        echo "<br>Unable to create new record--$total";
    }
} elseif (CleanData("qid") == '027') {
    #
    #   Activate/Deavtivate bulk users
    #
    $usr = new Users\UserManage();
    #   users list
    $users = array('1', '2', '3', '4', '5', '6', '7', '8');
    $total = $usr->BulkToggleUserStatus($users);
    echo "Total: $total";
} elseif (CleanData("qid") == '028') {
    #
    #   Bulk user update
    #
    $usr = new Users\UserManage();
    #
    # userid, roleid, first, middle, last, gender, email, phone, bank_name, account_name, account_no, bank_code, bio_feature
    #
    $userData = array(
        array(
            'userid' => '4450', 'roleid' => '4', 'first' => 'Bennet', 'middle' => 'Solomon', 'last' => 'Omale', 'gender' => 'Male', 'email' => 'someone@live.com',
            'phone' => '08099399393', 'bank_name' => '', 'account_name' => 'Bennet Solomon', 'account_no' => '002992929', 'bank_code' => '033', 'bio_feature' => ''
        ),
        array(
            'userid' => '4451', 'roleid' => '4', 'first' => 'Bennet', 'middle' => 'Solomon', 'last' => 'Omale', 'gender' => 'Male', 'email' => 'someone@live.com',
            'phone' => '08099399393', 'bank_name' => '', 'account_name' => 'Bennet Solomon', 'account_no' => '002992929', 'bank_code' => '035', 'bio_feature' => ''
        ),
        array(
            'userid' => '4452', 'roleid' => '4', 'first' => 'Bennet', 'middle' => 'Solomon', 'last' => 'Omale', 'gender' => 'Male', 'email' => 'someone@live.com',
            'phone' => '08099399393', 'bank_name' => '', 'account_name' => 'Bennet Solomon', 'account_no' => '002992929', 'bank_code' => '040', 'bio_feature' => ''
        ),
    );
    $total = $usr->BulkUserUpdate($userData);
    echo "Total: $total";
} elseif (CleanData("qid") == '029') {
    #
    #   User List for bulk consumption
    #   Sample DATA
    $usr = new Users\UserManage();
    $data = $usr->ListUserFull();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '030') {
    #
    #   Deavtivate users by Group
    #
    $usr = new Users\UserManage();
    #   users list
    $group = "TTAas";
    if ($usr->DeavtivateUserByGroup($group)) {
        echo "$group user group has been deactivated successfully";
    } else {
        echo "Unable to deactivate $group at the moment please try again later.";
    }
} elseif (CleanData("qid") == '031') {
    #
    #   Avtivate users by Group
    #
    $usr = new Users\UserManage();
    #   users list
    $group = "TTA";
    if ($usr->ActivateUserByGroup($group)) {
        echo "$group user group has been activated successfully";
    } else {
        echo "Unable to activate $group at the moment please try again later.";
    }
} elseif (CleanData("qid") == '032') {
    #
    #   Get role list
    #
    $usr = new Users\UserManage();
    #   users list
    $data = $usr->GetRoleList();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '033') {
    #
    #   update user role
    #
    $usr = new Users\UserManage();
    #   users list
    #
    #   UpdateUserRole($role_id, $user_id)
    $data = $usr->UpdateUserRole(3, 1);
    #
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#
#   Users work hours
#
elseif (CleanData("qid") == '034') {
    #
    #   Get default users work hour
    $usr = new Users\UserManage();
    #   
    $data = $usr->GetDefaultWorkHours();
    #
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Default System Working hours',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '035') {
    #
    #   Add Working additional working hours to user
    $usr = new Users\UserManage();
    #   
    $userid = '1';
    $extension_hours = '2';
    $extension_date = '2022-04-26';
    $created_userid = '1';
    #
    if ($usr->AddUserWorkHour($userid, $extension_hours, $extension_date, $created_userid)) {
        echo "{$extension_hours}hrs Working hours added to user successfully.";
    } else {
        echo "Unable to add working hour, please try again later.";
    }
} elseif (CleanData("qid") == '036') {
    #
    #   Get User working hours by days
    $usr = new Users\UserManage();
    #   
    $userid = 1;
    #
    $data = $usr->GetUserWorkingHours($userid);
    #
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Specific User working hours',
        'message' => 'success',
        'data' => $data
    ));
}
#   Log users activity
elseif (CleanData("qid") == '037') {
    #sample
    $userid = 1;
    $platform = "web";
    $module = "Users management";
    $description = "Update user data: ";
    $result = "success";
    $logid = System\General::LogActivity($userid, $platform, $module, $description, $result);
    if ($logid) {
        echo "Created log for the activity successfully";
    } else {
        echo "Unable to create log at the moment, please try again later";
    }
} elseif (CleanData("qid") == '040') {
    #
    #   Run User bank account validation 
    $usr = new Users\UserManage();
    #   
    $userid = 12277;
    $data = $usr->RunBankVerification($userid);
    #
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => "User Bank account validation for User ID: $userid",
        'message' => 'success',
        'data' => $data
    ));
}
#
#   User excel export
#
elseif (CleanData("qid") == '041') {
    #
    #   Count user list to export
    session_start();
    $v_g_geo_level = $_SESSION[$instance_token . '_geo_level'] ? $_SESSION[$instance_token . '_geo_level'] : 'state';
    $v_g_geo_level_id = $_SESSION[$instance_token . '_geo_level_id'] ? $_SESSION[$instance_token . '_geo_level_id'] : '532';
    #
    $us = new Users\UserManage();
    //  The first 2 parameters are required, the users geo-level & geo-level-id, the remaining are optional for filter
    $total = $us->ExcelCountUsers($v_g_geo_level, $v_g_geo_level_id); ##other parameters are optional for filter
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Count Users to download',
        'message' => 'success',
        'total' => $total
    ));
} elseif (CleanData("qid") == '041b') {
    #
    #   Export user list
    session_start();
    $v_g_geo_level = $_SESSION[$instance_token . '_geo_level'] ? $_SESSION[$instance_token . '_geo_level'] : 'state';
    $v_g_geo_level_id = $_SESSION[$instance_token . '_geo_level_id'] ? $_SESSION[$instance_token . '_geo_level_id'] : '532';
    #
    $us = new Users\UserManage();
    //  The first 2 parameters are required, the users geo-level & geo-level-id, the remaining are optional for filter
    $data = $us->ExcelDownloadUsers($v_g_geo_level, $v_g_geo_level_id);  #   Other parameters are optional useful for filters
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Download user list',
        'message' => 'success',
        'data' => $data
    ));
}
#
#   General
#
elseif (CleanData("qid") == '050') {
    #
    #   Get Bank List
    $gn = new System\General();
    $data = $gn->GetBankList();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '051') {
    #
    #   Get State List
    $gn = new System\General();
    $data = $gn->GetStateList();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '052') {
    #
    #   Get LGA List
    $gn = new System\General();
    $data = $gn->GetLgaList(26);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '053') {
    #
    #   Get Cluster List
    $gn = new System\General();
    $data = $gn->GetClusterList(526);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '054') {
    #
    #   Get Ward List
    $gn = new System\General();
    $data = $gn->GetWardList(526);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '055') {
    #
    #   Get Dp List
    $gn = new System\General();
    $data = $gn->GetDpList(1);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get Mobilier's List
elseif (CleanData("qid") == '055b') {
    #
    #   Get Mobilier List
    $gn = new System\General();
    $wardid = 1;
    $data = $gn->GetMobilizerList($wardid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get user List by Geo-Level by role code
elseif (CleanData("qid") == '055c') {
    #
    #   Get Mobilier List
    $gn = new System\General();
    $geo_level = 'ward';
    $geo_level_id = 1;
    $role_code = 'AB021';
    $data = $gn->GetUserByRoleInLevel($geo_level, $geo_level_id, $role_code);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '056') {
    #
    #   Sample Get Geo level List
    $gn = new System\General();
    $data = $gn->GetGeoLevel();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '057') {
    #
    #   Sample Get System Default List
    $gn = new System\General();
    $data = $gn->GetDefaultSettings();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '058') {
    #
    #   Sample Get Cluster list
    $gn = new System\General();
    $data = $gn->GetClusterList(530);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
/*
     *      General Get all sample
     */ elseif (CleanData("qid") == '059') {
    #
    #   Sample Get all LGa
    $gn = new System\General();
    $data = $gn->GetAllLga();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '060') {
    #
    #   Sample Get all Cluster
    $gn = new System\General();
    $data = $gn->GetAllCluster();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '061') {
    #
    #   Sample Get all Ward
    $gn = new System\General();
    $data = $gn->GetAllWard();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '062') {
    #
    #   Sample Get all DP
    $gn = new System\General();
    $data = $gn->GetAllDp();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
/*
     *      User badges options
     */ elseif (CleanData("qid") == '063') {
    #
    #   Get Badge data list by group
    $us = new Users\UserManage();
    $data = $us->GetBadgeByGroup("TTA");
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '064') {
    #
    #   Get Badge data list by User ID
    $us = new Users\UserManage();
    $data = $us->GetBadgeByUserID(2330);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '065') {
    #
    #   Get Badge data list by Login ID
    $us = new Users\UserManage();
    $data = $us->GetBadgeByLoginId('IGC04024');
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '066') {
    #
    #   Get Badge data list by User ID list
    $us = new Users\UserManage();
    $userid_list = array(1, 2678, 300, 41, 534, 2330);
    $data = $us->GetBadgeByUserIdList($userid_list);
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
/*
     *      User dashboard basic options
     */ elseif (CleanData("qid") == '067') {
    #
    #   Get Total users counts
    $us = new Users\UserManage();
    $data = $us->DashCountUser();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'total user' => $data
    ));
} elseif (CleanData("qid") == '068') {
    #
    #   Get total active and inavtive users
    $us = new Users\UserManage();
    $data = $us->DashCountActive();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '069') {
    #
    #   Get count by geo level
    $us = new Users\UserManage();
    $data = $us->DashCountGeoLevel();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '070') {
    #
    #   Get users count by group
    $us = new Users\UserManage();
    $data = $us->DashCountUserGroup();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '071') {
    #
    #   Get count total users group
    $us = new Users\UserManage();
    $data = $us->DashCountTotalGroup();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData("qid") == '072') {
    #
    #   Get users by gender distributions
    $us = new Users\UserManage();
    $data = $us->DashCountGender();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Update user level
elseif (CleanData("qid") == '073') {
    #
    #   Update user level
    $us = new Users\UserManage();
    #
    $userid = 2;
    $geo_level = "cluster";
    $geo_level_id = "10";
    if ($us->ChangeUserLevel($userid, $geo_level, $geo_level_id)) {
        echo "User Geo Level updated successfully";
    } else {
        echo "Unable to update the geo leve at the moment please try again later.";
    }
}
#   Get group list
elseif (CleanData("qid") == '074') {
    #
    #   Get group list
    $us = new Users\UserManage();
    #
    $data = $us->GetUserGroupList();
    #
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#
#   User FCm Register
elseif (CleanData('qid') == '075') {
    $us = new Users\UserManage();
    $userid = "1";
    $device_serial = "SN3126";
    $fcm_token = "SPAQ903MMu8849IO40409";
    $result = $us->RegisterUserFcm($userid,$device_serial,$fcm_token);
    //
    echo $result;
}
/*
     *
     *      Training Section
     * 
     */ elseif (CleanData("qid") == '080') {
    #
    #   Create Training
    $tr = new Training\Training();
    #   data
    #   $tr->CreateTraining('Training Tite', 'Geo location', 'Geo location id(int)', 'Training description', 'start date', 'end date');
    $trainingid = $tr->CreateTraining('Training TOT State Team', 'state', 26, "Training of the trainers for the state executive", '2022-03-21', '2022-03-23');
    #
    if ($trainingid) {
        echo "Training created successfully. Training ID: $trainingid";
    } else {
        echo "Create new training failed, unable to create at the moment, please try again later.";
    }
} elseif (CleanData("qid") == '081') {
    #
    #   Update Training
    $tr = new Training\Training();
    #
    #   $tr->UpdateTraining('Training Tite', 'Geo location', 'Geo location id(int)', 'Training description', 'start date', 'end date','training id');
    if ($tr->UpdateTraining('State level cordinator training', 'state', '26', 'State training for state team', '2022-03-22', '2022-03-24', 2)) {
        echo "Training updated successfully";
    } else {
        echo "unable to update training at the moment, please try gain later.";
    }
} elseif (CleanData('qid') == '082') {
    #
    #   Toggle training activation
    $tr = new Training\Training();
    if ($tr->ToggleTraining(3)) {
        echo "Training De/Activated successfully.";
    } else {
        echo "unable to update training status at the moment, please try again later.";
    }
} elseif (CleanData('qid') == '083') {
    #
    #   Add Participants by ID list
    #
    $tr = new Training\Training();
    #   participants array list
    $participants = array(11, 12, 13, 14, 15);
    #   $tr->AddParticipants($training_id,$list_of_user_id)
    $total = $tr->AddParticipants(4, $participants);
    if ($total) {
        echo "participant(s) added successfully, total $total";
    } else {
        echo "unable to add participants at the moment, please try again.";
    }
} elseif (CleanData('qid') == '084') {
    #
    #   Add Participants by group name
    #
    $tr = new Training\Training();
    #   $total = $tr->AddParticipantsByGroup($training_id, $group_name);
    $total = $tr->AddParticipantsByGroup(4, 'ICT4D_Team');
    if ($total) {
        echo "participant(s) added successfully, total $total";
    } else {
        echo "unable to add participants at the moment, please try again.";
    }
} elseif (CleanData('qid') == '085') {
    #
    #   Delete/Remove Participants 
    #
    $tr = new Training\Training();
    #   $total = $tr->AddParticipantsByGroup($training_id, $group_name);
    $training_id = 1;
    $participant_id_list = array(26, 27);     //  Users List
    #
    $total = $tr->RemoveParticipant($training_id, $participant_id_list);
    if ($total) {
        echo "$total participant(s) removed successfully";
    } else {
        echo "Unable to remove participant at the moment, Maybe the users has already been added to the list.";
    }
} elseif (CleanData('qid') == '086') {
    #
    #
    #   Create Session
    $tr = new Training\Training();
    #
    $training_id = 1;
    $session_title = "State TOT Day 3";
    $session_date = '2022-03-23';
    $id = $tr->CreateSession($training_id, $session_title, $session_date);
    if ($id) {
        echo "Session created successfully. Session ID: $id";
    } else {
        echo "Unable to create session at the moment, please try again later";
    }
} elseif (CleanData('qid') == '087') {
    #
    #
    #   Update Session
    $tr = new Training\Training();
    #
    $session_id = 1;
    $training_id = 1;
    $session_title = "State TOT Day 1 updated";
    $session_date = '2022-03-21';
    #
    if ($tr->UpdateSession($training_id, $session_title, $session_date, $session_date)) {
        #   successful
        echo "Session updated successfully";
    } else {
        #   failed
        echo  "Unable to update session at the moment please try again later.";
    }
} elseif (CleanData('qid') == '088') {
    #
    #
    #   Delete Session
    $tr = new Training\Training();
    #
    $session_id = 4;
    if ($tr->DeleteSession($session_id)) {
        echo "Session deleted successfully";
    } else {
        echo "Unable to delete session at the moment, please try again later";
    }
} elseif (CleanData('qid') == '089') {
    #
    #
    #   Add attendance single
    $tr = new Training\Training();
    #
    $session_id = 1;
    $user_id = 1;
    $participant_id = 12;
    $attendance_type = 'Clock-in';
    $biometrics_authentication = true;
    $longitude = '5.6789';
    $latitude = '8.6890';
    $date_collected = '2021-03-16';
    $app_version = '14.0.15';
    #
    $att_id = $tr->AddAttendance($session_id, $participant_id, $attendance_type, $biometrics_authentication, $date_collected, $longitude, $latitude, $user_id, $app_version);
    if ($att_id) {
        echo "Attendance created successfully";
    } else {
        echo "Unable to create attendance at the moment, please try  again later.";
    }
} elseif (CleanData('qid') == '090') {
    #
    #
    #   Add attendance bulk
    $tr = new Training\Training();
    #   Data structure
    ### [session_id, participant_id, at_type, bio_auth, collected,longitude,latitude, userid, app_version]
    $bulk_data = array(
        array('session_id' => 1, 'participant_id' => 12, 'at_type' => 'ClOCK-OUT', 'bio_auth' => true, 'collected' => '2022-03-16 16:00', 'longitude' => '8.0027', 'latitude' => '5.67822', 'userid' => 1, 'app_version' => '14.0.5'),
        array('session_id' => 1, 'participant_id' => 13, 'at_type' => 'ClOCK-in', 'bio_auth' => true, 'collected' => '2022-03-16 08:00', 'longitude' => '8.0027', 'latitude' => '5.67822', 'userid' => 1, 'app_version' => '14.0.5'),
        array('session_id' => 1, 'participant_id' => 14, 'at_type' => 'ClOCK-in', 'bio_auth' => false, 'collected' => '2022-03-16 08:34', 'longitude' => '8.0027', 'latitude' => '5.67822', 'userid' => 1, 'app_version' => '14.0.5'),
        array('session_id' => 1, 'participant_id' => 15, 'at_type' => 'ClOCK-in', 'bio_auth' => true, 'collected' => '2022-03-16 08:46', 'longitude' => '8.0027', 'latitude' => '5.67822', 'userid' => 1, 'app_version' => '14.0.5'),
        array('session_id' => 1, 'participant_id' => 16, 'at_type' => 'ClOCK-in', 'bio_auth' => true, 'collected' => '2022-03-16 08:57', 'longitude' => '8.0027', 'latitude' => '5.67822', 'userid' => 1, 'app_version' => '14.0.5')
    );
    $total = $tr->AddAttendancebulk($bulk_data);
    if ($total) {
        echo "Total of $total attendance was uploaded successfully";
    } else {
        echo "Unable to upload attendance at the moment.";
    }
} elseif (CleanData('qid') == '091') {
    #
    #
    #   Get list duplicates for traininng
    $tr = new Training\Training();
    $data = $tr->getParticipantDuplicate();
    echo json_encode(array(
        'status_code' => 200,
        'message' => 'success',
        'data' => $data
    ));
}
#   Get generic Training list (training list without privilege)
elseif (CleanData('qid') == '092') {
    #
    #
    #   Get generic Training list (training list without privilege)
    $tr = new Training\Training();
    $geo_level = "state";
    $geo_level_id = 26;
    $data = $tr->getGenericTraining($geo_level, $geo_level_id);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Generic Trainig List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get generic Training Session list (training Session without privilege)
elseif (CleanData('qid') == '093') {
    #
    #
    #   Get generic Training Session list (training Session without privilege)
    $tr = new Training\Training();
    $training_id = 1;
    $data = $tr->getGenericSession($training_id);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Generic Session List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get participants list for a particular training
elseif (CleanData('qid') == '094') {
    #
    #
    #   Get participants list for a particular training
    $tr = new Training\Training();
    $training_id = 5;
    $geo_level = "ward";
    $geo_level_id = "1";
    $data = $tr->getParticipantsList($training_id, $geo_level, $geo_level_id);
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Participants List',
        'message' => 'success',
        'data' => $data
    ));
}
#   Dashboard count total Training
elseif (CleanData('qid') == '095') {
    #
    #
    #   Get count total Training
    $us = new Training\Training();
    $data = $us->DashCountTraining();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Count Total Training',
        'message' => 'success',
        'data' => $data
    ));
}
#   Dashboard count Training active & inactive
elseif (CleanData('qid') == '096') {
    #
    #
    #   Dashboard count Training active & inactive
    $us = new Training\Training();
    $data = $us->DashCountActive();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Count Active/inactive training',
        'message' => 'success',
        'data' => $data
    ));
}
#   Dashboard count Session
elseif (CleanData('qid') == '097') {
    #
    #
    #   Dashboard count Session
    $us = new Training\Training();
    $data = $us->DashCountSession();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Count Session',
        'message' => 'success',
        'data' => $data
    ));
}
#
#
#   Excel Export Count
#
elseif (CleanData('qid') == '101') {
    #
    #
    #   Excel Export Count Participants in the training (Active participants only)
    $us = new Training\Training();
    $training_id = 4;
    $total = $us->ExcelCountParticipantList($training_id);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Count Participant List',
        'message' => 'success',
        'total' => $total
    ));
} elseif (CleanData('qid') == '102') {
    #
    #
    #   Excel Export Count Attendance list in a session
    $us = new Training\Training();
    $sessionid = 1;
    $total = $us->ExcelCountAttendanceList($sessionid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Count Attendance List',
        'message' => 'success',
        'total' => $total
    ));
}
#   Web get the list of the attendance in a session
elseif (CleanData('qid') == '103') {
    #
    #
    #   Web get the list of the attendance in a session
    $us = new Training\Training();
    $sessionid = 1;
    $data = $us->getAttendanceList($sessionid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Attendance List',
        'message' => 'success',
        'data' => $data
    ));
}
/*
     *  Utility code
     *  *************
     *  
     *  How to verify user privilge
     * 
     */ elseif (CleanData('qid') == '120') {
    #
    #   Check the privilege availability
    session_start();
    $privilege = $_SESSION[$instance_token . 'privileges'];
    $privi = 'mobilization';
    if (IsPrivilegeInArray(json_decode($privilege, true), $privi)) {
        echo "<p>Testing Privilege for $privi is available</p>";
    } else {
        echo "<p>Testing Privilege for <b>$privi</b> is not available</p>";
    }
    echo $privilege;
} elseif (CleanData('qid') == '121') {
    #
    #   Check the Platform availability
    session_start();
    $platform = $_SESSION[$instance_token . 'platform_priv'];
    $platf = 'web';
    if (IsPlatformInArray(json_decode($platform, true), $platf)) {
        echo "<p>Testing Platform for <b>$platf</b> is available</p>";
    } else {
        echo "<p>Testing Platform for <b>$platf</b> is not available</p>";
    }
}
/*
     *
     *  Mobilization
     * 
     */ elseif (CleanData('qid') == '201') {
    #
    #   Get DP list
    $us = new System\General();
    $wardid = 1;
    $data = $us->GetDpList($wardid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'DP list in a ward',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '202') {
    #
    #   Get location category list
    $us = new Mobilization\Mobilization();
    $data = $us->GetLocationCategories();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Location Category List',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '203') {
    #
    #   Download e-netcard
    $us = new Mob();
    $mobilizerid = 1052; //1009
    $device_id = 'TEST-DEVICE';
    $data = $us->DownloadEnetcard($mobilizerid, $device_id);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Download e-Netcard (Precious payload)',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '204') {
    #
    #   Check for pending reverse order
    #
    $us = new Mobilization\Mobilization();
    $mobilizerid = 3;
    $data = $us->GetPendingReverseOrder($mobilizerid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get pending reverse order',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '205') {
    #
    #   Generate e-Token list
    #
    $device_id = 'KM00299292';
    $total = 10;
    $tk = new Netcard\Etoken($device_id, $total);
    #   Generate the e-token
    $data = $tk->Generate();
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Generate e-Token',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '208') {
    #
    #
    #   Bulk Posting mobilization data
    $mo = new Mobilization\Mobilization();
    # === mobilization data structure ===
    #   [dp_id, comid, hm_id, co_hm_id, hoh_first, hoh_last, hoh_phone, hoh_gender, 
    #   family_size, hod_mother, sleeping_space, adult_female, adult_male, children, allocated_net, location_description, longitude, 
    #   latitude, netcards, etoken_id, etoken_serial, etoken_pin, collected_date, device_serial, app_version]
    /*$bulk_data = [array('dp_id'=>1,'comid'=>4001,'hm_id'=>5,'co_hm_id'=>13,'hoh_first'=>'Kanzambili','hoh_last'=>'Samuel','hoh_phone'=>'08023456789',
            'hoh_gender'=>'Male','family_size'=>4, 
            'hod_mother'=>'Omowumi Salewa','sleeping_space'=>'12','adult_female'=>'4','adult_male'=>'4','children'=>'4',
            'allocated_net'=>2,'location_description'=>'Household',
            'longitude'=>'5.67890','latitude'=>'7.2339038',
            'netcards'=>'h24h55kb-id4n-f5nf-9rgm-z3u9f1r663ow,q7e9idm1-3ggr-diwv-idfa-zmpemocb3lob',
            'etoken_id'=>'51','etoken_serial'=>'WO00056','etoken_pin'=>'12345','device_serial'=>'XA001','app_version'=>'21.032','collected_date'=>'2022-04-23')];
        */
    $json_data = '[{"adult_female":"1","adult_male":"1","allocated_net":1,"app_version":"2.0.39","children":"","co_hm_id":7201,"collected_date":"2023-11-22 10:38:07","comid":8070,"device_serial":"VNY12374","dp_id":2411,"etoken_id":19976,"etoken_pin":"29668","etoken_serial":"CN19977","family_size":2,"hm_id":4145,"hoh_first":"Terfa","hoh_gender":"MALE","hoh_last":"Nanen","hod_mother":"Joy","hoh_phone":"08023568974","latitude":"7.6986033","location_description":"Barracks","longitude":"9.3170133","netcards":"vvtxy3am-925b-bmtb-dk4c-wj7kht0el59w,","sleeping_space":"5"},{"adult_female":"2","adult_male":"2","allocated_net":3,"app_version":"2.0.39","children":"2","co_hm_id":7201,"collected_date":"2023-11-22 10:56:06","comid":8070,"device_serial":"VNY12375","dp_id":2411,"etoken_id":19977,"etoken_pin":"78684","etoken_serial":"PC19978","family_size":6,"hm_id":4145,"hoh_first":"Fanen","hoh_gender":"MALE","hoh_last":"Sunny","hod_mother":"Sandra","hoh_phone":"","latitude":"7.705196666666666","location_description":"Barracks","longitude":"9.316421666666667","netcards":"t5kk1uj9-q23z-8dt9-nmmy-owc2mn4xm6r3,6jyxodg0-ik25-wbo4-43pb-p19a5wvo8anq,igixg5q1-o1a4-eoiu-hyha-k0i6aq07ky9e,","sleeping_space":"2"},{"adult_female":"3","adult_male":"2","allocated_net":4,"app_version":"2.0.39","children":"3","co_hm_id":7201,"collected_date":"2023-11-22 11:36:05","comid":8070,"device_serial":"VNY12373","dp_id":2411,"etoken_id":19978,"etoken_pin":"52044","etoken_serial":"UL19972","family_size":8,"hm_id":4145,"hoh_first":"Kanen","hoh_gender":"MALE","hoh_last":"Dooga","hod_mother":"Kur","hoh_phone":"00000000000","latitude":"7.705241666666666","location_description":"Barracks","longitude":"9.316373333333333","netcards":"0jb1k1b5-ovj6-a8sl-q95r-jkk2b5z11moq,qytngjy5-48kb-hxgk-4mbs-2kxsroilhu2x,89eh3rqb-lzia-hvpm-3qyg-7lpwjdj5n0id,2piphwbr-ut94-si02-l9j2-53gb7df64mh8,","sleeping_space":"3"},{"adult_female":"2","adult_male":"2","allocated_net":4,"app_version":"2.0.39","children":"3","co_hm_id":7201,"collected_date":"2023-11-22 11:38:19","comid":8070,"device_serial":"VNY12373","dp_id":2411,"etoken_id":19979,"etoken_pin":"85850","etoken_serial":"GN19973","family_size":7,"hm_id":4145,"hoh_first":"Tom","hoh_gender":"FEMALE","hoh_last":"Soonen","hod_mother":"Nor","hoh_phone":"08023236584","latitude":"7.705236666666667","location_description":"Barracks","longitude":"9.316368333333333","netcards":"mu6xigev-6bzy-oms5-axcs-n2afr48qcbz1,gaie0v5q-kvsd-uqt3-mqzx-sewc4vlnoudq,el3bugvu-ekks-2jp5-b2pd-yrkmptcv9lad,ha5laokw-dt6z-gqv6-28wb-wywxr1kxam9e,","sleeping_space":"4"},{"adult_female":"3","adult_male":"4","allocated_net":4,"app_version":"2.0.39","children":"2","co_hm_id":7201,"collected_date":"2023-11-22 12:34:32","comid":8070,"device_serial":"VNY12373","dp_id":2411,"etoken_id":19980,"etoken_pin":"65130","etoken_serial":"GC19984","family_size":9,"hm_id":4145,"hoh_first":"Tiza","hoh_gender":"MALE","hoh_last":"Doove","hod_mother":"Mwuese","hoh_phone":"00000000000","latitude":"7.70505","location_description":"Barracks","longitude":"9.316491666666668","netcards":"e5lsfyv8-4bym-qebv-dcsy-07n370epyfif,fpyzp5uf-mrve-4px4-izgb-30t24bmfsf3p,6lr844ua-qt99-i3sb-tmxh-ojmsy5ogc8xz,e66wuf3g-hw9n-zzbx-sgkw-946r87tb87ho,","sleeping_space":"3"}]';
    $bulk_data = json_decode($json_data, true);
    $total = $mo->BulkMobilization($bulk_data);
    if ($total) {
        echo "Total $total bulk mobilization has been submitted successfully";
    } else {
        echo "Unable to submit the mobilization  bulk data";
    }
} elseif (CleanData('qid') == '210') {
    #
    #   Get Mobilization Details
    #
    $device_id = 'KM00299292';
    $total = 10;
    $mo = new Mobilization\Mobilization();
    #   Get details
    $mobilization_id = 2;
    $data = $mo->GetMobilizationDetails($mobilization_id);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Mobilization Details',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '211') {
    #
    #   Get Goe location codex
    #
    $mo = new System\General();
    #   parameter options ['dp','ward','lga','state','all'] default is dp (i.e without any parameter)
    #   Get details
    $data = $mo->GetGeoLocationCodex();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Geo Location Codex',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '212') {
    #
    #
    #   Excel Export Count Mobilization
    $loginid = CleanData('lgid');
    #   Filtered by mobilized date
    $mob_date = CleanData('mdt');
    #   Filtered by Geo-Level
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    $ex = new Mobilization\Mobilization();
    $total = $ex->ExcelCountMobilization($loginid, $mob_date, $geo_level, $geo_level_id);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Excel Export Count Mobilization',
        'message' => 'success',
        'total' => $total
    ));
} elseif (CleanData('qid') == '213') {
    #
    #
    #   Dashboard summary with options
    #
    #   Filtered by mobilized date
    $mob_date = CleanData('mdt');
    #   Filtered by Geo-Level
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    $ex = new Mobilization\Mobilization();
    $total = $ex->DashSummary($mob_date, $geo_level, $geo_level_id);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Dashboard summary',
        'message' => 'success',
        'total' => $total
    ));
} elseif (CleanData('qid') == '214') {
    #
    #
    #   Mobilization Master
    #
    #   Get receipt header
    $ex = new Mobilization\Mobilization();
    $data = $ex->GetReceiptHeader();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Receipt header, logo & header',
        'message' => 'success',
        'total' => $data
    ));
}
#   Get micro-palnning by LGA
elseif (CleanData('qid') == '215') {
    #
    #
    #   Mobilization Master
    #
    #   Get micro-palnning by LGA
    $ex = new Mobilization\Mobilization();
    $lgaid = 526;
    $data = $ex->GetMicroPosition($lgaid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get micro-palnning by LGA',
        'message' => 'success',
        'total' => $data
    ));
}
#   Get Excel count micro-palnning by LGA
elseif (CleanData('qid') == '216a') {
    #
    #
    #   Mobilization Master
    #
    $ex = new Mobilization\Mobilization();
    $lgaid = 526;
    $data = $ex->ExcelGetMicroPosition($lgaid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get micro-palnning Excel by LGA',
        'message' => 'success',
        'total' => $data
    ));
}
#   Get Excel Get Data micro-palnning by LGA
elseif (CleanData('qid') == '216b') {
    #
    #
    #   Mobilization Master
    #
    #   Get micro-palnning by LGA
    $ex = new Mobilization\Mobilization();
    $lgaid = 526;
    $data = $ex->ExcelCountMicroPosition($lgaid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get count micro-palnning by LGA',
        'message' => 'success',
        'total' => $data
    ));
}
#   Adding Community
elseif (CleanData('qid') == '220') {
    #
    #   Get Community list
    $us = new System\General();
    $dpid = 2027;
    $data = $us->GetCommunityList($dpid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Community list in DP',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '221') {
    #
    #   Get Community list
    $us = new System\General();
    $wardid = 1007;
    $data = $us->GetCommunityLitByWard($wardid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Community list in Ward',
        'message' => 'success',
        'data' => $data
    ));
}
/*
     *
     *  Distribution
     * 
     */ elseif (CleanData('qid') == '301') {
    #
    #   Distribution 
    #
    #   Get DP Locations details with DP ID
    $ex = new Distribution\Distribution();
    $wardid = 1;
    $total = $ex->GetDpLocationMaster($wardid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Distribution Point List for Badge Printing',
        'message' => 'success',
        'total' => $total
    ));
} elseif (CleanData('qid') == '302') {
    #
    #   Distribution 
    #
    #   Download Mobilization Data
    $ex = new Distribution\Distribution();
    $dpid = 2113;
    $total = $ex->DownloadMobilizationData($dpid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Download Mobilization Dataset for distribution',
        'message' => 'success',
        'total' => $total
    ));
} elseif (CleanData('qid') == '303') {
    #
    #   Distribution 
    #
    #   Get 
    $ex = new Distribution\Distribution();
    $guid = 'up0ddwj9-wu8y-vg3q-ey4g-nrv9igokyxxh';
    $data = $ex->GetGeoCodexDetails($guid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Geo location codex by guid',
        'message' => 'success',
        'data' => $data
    ));
} elseif (CleanData('qid') == '304') {
    #
    #   Distribution 
    #
    #   Get DP Locations list with DP ID
    $ex = new Distribution\Distribution();
    $dp_list = array(1, 2, 3, 4);
    $total = $ex->GetDpLocationMasterList($dp_list);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Distribution Point List with dp List',
        'message' => 'success',
        'total' => $total
    ));
} elseif (CleanData('qid') == '305') {
    #
    #   Distribution  Bulk distribution data upload
    #
    #   Bulk upload
    $ex = new Distribution\Distribution();
    ###  updated bulk data structure
    ##   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, etoken_id, etoken_serial, longitude, latitude, device_serial, app_version]
    $bulk_data = [
        [
            'dp_id' => 1, 'mobilization_id' => 1, 'recorder_id' => 21, 'distributor_id' => 25, 'collected_nets' => 4, 'is_gs_net' => 1,
            'gs_net_serial' => '992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912',
            'collected_date' => '2022-04-22', 'etoken_id' => 42
        ]
    ];
    $total = $ex->BulkDistibution($bulk_data);
    #
    if ($total) {
        echo "Total $total distribution data uploaded successfully";
    } else {
        echo "Unable to upload bulk distribution data at the moment";
    }
} elseif (CleanData('qid') == '306') {
    #
    #   Distribution  Bulk distribution data upload
    #
    #   Bulk upload
    $ex = new Distribution\Distribution();
    ##   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, 
    ##  etoken_id, etoken_serial, longitude, latitude, device_serial, app_version]
    $bulk_data = [
        [
            'dp_id' => 1, 'mobilization_id' => 1, 'recorder_id' => 21, 'distributor_id' => 25, 'collected_nets' => 4, 'is_gs_net' => 1,
            'gs_net_serial' => '992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912',
            'collected_date' => '2022-04-22', 'etoken_id' => 45, 'etoken_serial' => 'DK83933', 'longitude' => '', 'latitude' => '', 'device_serial' => 'RF8839', 'app_version' => 'v0.0.01'
        ],
        [
            'dp_id' => 1, 'mobilization_id' => 1, 'recorder_id' => 21, 'distributor_id' => 25, 'collected_nets' => 4, 'is_gs_net' => 1,
            'gs_net_serial' => '992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912',
            'collected_date' => '2022-04-22', 'etoken_id' => 46, 'etoken_serial' => 'DK83930', 'longitude' => '', 'latitude' => '', 'device_serial' => 'RF8839', 'app_version' => 'v0.0.01'
        ],
        [
            'dp_id' => 1, 'mobilization_id' => 1, 'recorder_id' => 21, 'distributor_id' => 25, 'collected_nets' => 4, 'is_gs_net' => 1,
            'gs_net_serial' => '992019291292012920129928,2881921029912021022,192992192912928122,188281929928182912',
            'collected_date' => '2022-04-22', 'etoken_id' => 44, 'etoken_serial' => 'DK83932', 'longitude' => '', 'latitude' => '', 'device_serial' => 'RF8839', 'app_version' => 'v0.0.01'
        ]
    ];
    $data = $ex->BulkDistibutionWithReturns($bulk_data);
    #
    if (is_array($data) && count($data)) {
        echo json_encode($data);
    } else {
        echo "Unable to upload bulk distribution data at the moment";
    }
}
/*
     *
     *  Device Management
     * 
     */ elseif (CleanData('qid') == '501') {
    #
    #   register Device
    #
    $ex = new System\Devices();
    #
    $device_name = "TCAN T2 POS Andrid 6";
    $device_id = "KSTC-0021-LITT";
    $device_type = "POS";
    #
    $device_data = $ex->RegisterDevice($device_name, $device_id, $device_type);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Register Device on System',
        'message' => 'success',
        'data' => $device_data
    ));
} elseif (CleanData('qid') == '502') {
    #
    #   Toggle Device Acivation
    #
    $ex = new System\Devices();
    #
    #
    if ($ex->ToggleActive('OWS004')) {
        echo "Device active toggle successful";
    } else {
        echo "Unable to active toggle device at the moment, please try again later";
    }
} elseif (CleanData('qid') == '503') {
    #
    #   Check Device
    #
    $ex = new System\Devices();
    #
    $device_id = 'KKSL-0039-LKSL';
    #
    $device_data = $ex->CheckDevice($device_id);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Check Device',
        'Device ID' => $device_id,
        'data' => $device_data
    ));
} elseif (CleanData('qid') == '504') {
    #
    #   Bulk device toggle activation
    #
    $ex = new System\Devices();
    #
    #
    $devices = array('KVZ001', 'OWS004', 'SZX006');
    $total = $ex->BulkToggleActive($devices);
    if ($total) {
        echo "Device active toggle successful, total $total affected";
    } else {
        echo "Unable to active toggle device at the moment, please try again later";
    }
} elseif (CleanData('qid') == '504') {
    #
    #   Bulk delete device 
    $ex = new System\Devices();
    #
    #
    $devices = array('KVZ001', 'OWS004', 'SZX006');
    $total = $ex->BulkDelete($devices);
    if ($total) {
        echo "Device(s) deleted successful, total $total affected";
    } else {
        echo "Unable to delete device at the moment, please try again later";
    }
}
#   Single device detail update with serial
elseif (CleanData('qid') == '505') {
    #
    #   Single update
    $ex = new System\Devices();
    #
    $imei1 = "98983294384023423";
    $imei2 = "34324932749343243";
    $phone_serial = "323JWW300KWJW839";
    $sim_network = "MTN";
    $sim_serial = "88292029";
    $device_serial = "OWS004"; #Device Unique identifier
    #
    if ($ex->UpdateDeviceWithSerial($imei1, $imei2, $phone_serial, $sim_network, $sim_serial, $device_serial)) {
        echo "Devise details was updated successfully";
    } else {
        echo "Error: unable to update the device details at the moment please try again later";
    }
}
#   Bulk device detail update with serial
elseif (CleanData('qid') == '506') {
    #
    #   Bulk update
    $ex = new System\Devices();
    #
    $data = array(
        array('imei1' => "98983294384023423", 'imei2' => "34324932749343243", 'phone_serial' => "323JWW300KWJW839", 'sim_network' => "MTN", 'sim_serial' => "88292029", 'device_serial' => "SZX006"),
        array('imei1' => "19809294384023423", 'imei2' => "09824932749343243", 'phone_serial' => "123JWW300KWJW839", 'sim_network' => "AIRTEL", 'sim_serial' => "01022029", 'device_serial' => "RXT007")
    );
    $count = $ex->BulkUpdateDeviceWithSerial($data);
    if ($count) {
        echo "Bulk device detail update was successful, updated total of $count";
    } else {
        echo "Bulk update failed, please try again later";
    }
}
/*
     *
     *  MAP Data
     * 
     */
#   Mobilizer Dataset (3 options)
elseif (CleanData('qid') == '601') {
    #
    #   Mobilizer Data
    #
    $ex = new Mobilization\MapData();
    #
    $mobilizerid = "CGF00003";      #   compulsory field to get all mobilizer
    $wardid = "1";                  #   required data as well
    $start_date = "2022-05-16";
    $end_date = "2022-05-17";
    #
    $data = $ex->GetMobilizationData($wardid, $mobilizerid, $start_date, $end_date);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get mobilizer mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get DP Dataset (3 options)
elseif (CleanData('qid') == '602') {
    #
    #   Get DP Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $dpid = "7";      #   compulsory field to get dp data
    $wardid = "1";                  #   required data as well
    $start_date = "2022-06-01";
    $end_date = "2022-06-02";

    #
    $data = $ex->GetDpData($wardid, $dpid, $start_date, $end_date);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get DP mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get WARD daily Dataset
elseif (CleanData('qid') == '603') {
    #
    #   Get WARD daily Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $wardid = "1";                  #   required data as well
    $date = "2022-06-01";

    #
    $data = $ex->GetWardData($wardid, $date);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get Ward daily mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get LGA daily Dataset
elseif (CleanData('qid') == '604') {
    #
    #   Get LGA daily Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $lgaid = "526";                  #   required data as well
    $date = "2022-06-02";
    #
    $data = $ex->GetLgaData($lgaid, $date);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get LGA Daily mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get State daily Dataset
elseif (CleanData('qid') == '605') {
    #
    #   Get State daily Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $stateid = "26";                  #   required data as well
    $date = "2022-06-01";
    #
    $data = $ex->GetStateData($stateid, $date);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get State Daily mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get map data per item Dataset
elseif (CleanData('qid') == '606') {
    #
    #   Get map data per item Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $hhid = "41";
    #
    $data = $ex->GetPerItemData($hhid);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get per item mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#   Get map data test all 
elseif (CleanData('qid') == '610') {
    #
    #   Get map data test all  Dataset
    #
    $ex = new Mobilization\MapData();
    #
    $hhid = "41";
    #
    $data = $ex->GetTestAllData();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Get test all mapping data',
        'message' => 'success',
        'data' => $data
    ));
}
#
#
#   GS1 - Traceability
elseif (CleanData('qid') == '700') {
    # Traceability search
    $ex = new Distribution\GsVerification();
    $gtin = "8906126051976";
    $sgtin = "ZWZW9BXHL0ST";
    $data = $ex->TraceabilitySearch($gtin, $sgtin);
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'traceability search using GTIN & SGTIN',
        'message' => 'success',
        'data' => $data
    ));
}
#
#
#   Monitoring Tool Region
#
#   Get list of form and count
elseif (CleanData('qid') == '800') {
    $fm = new Monitor\Monitor();
    $data = $fm->GetFormStatusList();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Monitoring tool list',
        'message' => 'success',
        'data' => $data
    ));
}
#
#   Download form I-9a
elseif (CleanData('qid') == '801') {
    $fm = new Monitor\Monitor();
    $data = $fm->EeFormInineA();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Form I-9a',
        'message' => 'success',
        'data' => $data
    ));
}
#
#   Download form I-9b
elseif (CleanData('qid') == '802') {
    $fm = new Monitor\Monitor();
    $data = $fm->EeFormInineB();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Form I-9b',
        'message' => 'success',
        'data' => $data
    ));
}
#   Download form I-9c
elseif (CleanData('qid') == '803') {
    $fm = new Monitor\Monitor();
    $data = $fm->EeFormInineC();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Form I-9c',
        'message' => 'success',
        'data' => $data
    ));
}
#   Download form 5% Revisit
elseif (CleanData('qid') == '804') {
    $fm = new Monitor\Monitor();
    $data = $fm->EeFormFiveRevisit();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Form 5% Revisit form export',
        'message' => 'success',
        'data' => $data
    ));
}
#   Download form End process 1
elseif (CleanData('qid') == '805') {
    $fm = new Monitor\Monitor();
    $data = $fm->EeFormEndProOne();
    #
    echo json_encode(array(
        'status_code' => 200,
        'dataset' => 'Form End process 1 form export',
        'message' => 'success',
        'data' => $data
    ));
}
#
#
#
#   Five % revisit supervisory data structure
#
elseif (CleanData('qid') == '806') {
    $data = [
        'uid' => '123e4567-e89b-12d3-a456-426614174000',
        'wardid' => 1007,
        'lgaid' => 526,
        'dpid' => 2027,
        'comid' => 3001,
        'userid' => 21,
        'visit_date' => '2022-06-01',   //
        'latitude' => '9.0765',
        'longitude' => '7.3986',
        'name_of_collector' => 'John Doe Something something [ Name of 5% Data Collector]', //[ Name of 5% Data Collector]
        'aa' => 'Confirm if the community has been mobilized [ Yes | No ]',
        'ab' => 'Did the 5% data collector visit this community? [ Yes | No ]',
        'ac' => 'Is the household marked as having been visited by a 5% data collector? (Note that this is filled-in based on observation by supervisor) (RVT 1-10) [ Yes | No ]',
        'ad' => 'Name of HH Head [ Text ]',
        'ae' => 'Was the Household Registered and issued token slip(s)? [ Yes | No ]',
        'af' => 'Did the 5% data collector adhere to the HHs randomization plan? [ Yes | No ]',
        'comments' => 'This is a comment for the revisit form data point',
        'etoken_serial' => 'DK83932',
        'app_version' => 'v0.0.01',
        'domain' => '5% Revisit'
    ];

    $fm = new Form\FiveRevisitSupervisor();
    $result = $fm->BulkSave($data);

    echo json_encode(array(
        'status_code' => 200,
        'dataset' => '5% Revisit supervisory data structure',
        'message' => 'success',
        'data' => $result
    ));
}
#
#   Reporting
#
#   Get json export for participants
elseif (CleanData('qid') == '901') {
    $rp = new Reporting\Reporting();
    $trainingId = 1;
    $geo_level = "state";
    $geo_level_id = "";
    //
    echo $rp->ListParticipants($trainingId, $geo_level, $geo_level_id);
}
#   Activity Management - Get json export for participants
elseif (CleanData('qid') == '901') {
    $rp = new Reporting\Reporting();
    $trainingId = 1;
    $geo_level = "state";
    $geo_level_id = "";
    //
    echo $rp->ListParticipants($trainingId, $geo_level, $geo_level_id);
}
#   Activity Management - Get json export for Bank verification status
elseif (CleanData('qid') == '902') {
    $rp = new Reporting\Reporting();
    $trainingId = 1;
    $geo_level = "state";
    $geo_level_id = "";
    //
    echo $rp->ListBankVerification($trainingId, $geo_level, $geo_level_id);
}
#   Activity Management - Get json export for Uncaptured Users
elseif (CleanData('qid') == '903') {
    $rp = new Reporting\Reporting();
    $trainingId = 1;
    $geo_level = "state";
    $geo_level_id = "";
    //
    echo $rp->ListUncapturedUsers($trainingId, $geo_level, $geo_level_id);
}
#   Mobilization - Get json export for Overall Mobilization by LGA
elseif (CleanData('qid') == '904') {
    $rp = new Reporting\Reporting();

    $geo_level = "state";   //  [ state or lga]
    $geo_level_id = 7;
    //
    echo $rp->ListMobilizationByLga($geo_level, $geo_level_id);
}
#   Mobilization -  Get json export mobilization DP level
elseif (CleanData('qid') == '905') {
    $rp = new Reporting\Reporting();

    $geo_level = "state";   //  [ state or lga]
    $geo_level_id = 7;
    //
    echo $rp->ListMobilizationByDp($geo_level, $geo_level_id);
}
#   Mobilization -  Get json export mobilization with date parameter LGA level
elseif (CleanData('qid') == '906') {
    $rp = new Reporting\Reporting();

    $geo_level = "lga";   //  [ state or lga]
    $geo_level_id = 119;
    $date = "2023-11-29";
    //
    echo $rp->ListDateMobilizationByLga($date, $geo_level, $geo_level_id);
}
#   Mobilization -  Get json export mobilization with date parameter DP level
elseif (CleanData('qid') == '907') {
    $rp = new Reporting\Reporting();

    $geo_level = "state";   //  [ state or lga]
    $geo_level_id = 119;
    $date = "2023-11-29";
    //
    echo $rp->ListDateMobilizationByDp($date, $geo_level, $geo_level_id);
}
#   Mobilization -  Get json export mobilization with date range parameter DP level
elseif (CleanData('qid') == '908') {
    $rp = new Reporting\Reporting();

    $geo_level = "lga";   //  [ state or lga]
    $geo_level_id = 119;
    $start_date = "2023-11-27";
    $end_date = "2023-11-29";
    //
    echo $rp->ListDateRangeMobilizationByLga($start_date, $end_date, $geo_level, $geo_level_id);
}
#   Distribution -  Get json export Distribution LGA level
elseif (CleanData('qid') == '909') {
    $rp = new Reporting\Reporting();

    $geo_level = "state";   //  [ state or lga]
    $geo_level_id = 119;
    //
    echo $rp->ListDistributionByLga($geo_level, $geo_level_id);
}
#   Distribution -  Get json export Distribution DP level
elseif (CleanData('qid') == '910') {
    $rp = new Reporting\Reporting();

    $geo_level = "state";   //  [ state or lga]
    $geo_level_id = 119;
    //
    echo $rp->ListDistributionByDp($geo_level, $geo_level_id);
}
#   Distribution -   Get json export specific date distribution by LGA level
elseif (CleanData('qid') == '911') {
    $rp = new Reporting\Reporting();

    $geo_level = "lga";   //  [ state or lga]
    $geo_level_id = 119;
    $date = "2023-12-16";
    //
    echo $rp->ListDateDistributionByLga($date, $geo_level, $geo_level_id);
}
#    Distribution - Get json export date range distribution by LGA level
elseif (CleanData('qid') == '912') {
    $rp = new Reporting\Reporting();

    $geo_level = "lga";   //  [ state or lga]
    $geo_level_id = 119;
    $start_date = "2023-12-16";
    $end_date = "2023-12-17";
    //
    echo $rp->ListDateRangeDistributionByLga($start_date, $end_date, $geo_level, $geo_level_id);
}
#   Distribution -   Get json export specific date distribution by DP level
elseif (CleanData('qid') == '913') {
    $rp = new Reporting\Reporting();

    $geo_level = "lga";   //  [ state or lga]
    $geo_level_id = 119;
    $date = "2023-12-16";
    //
    echo $rp->ListDateDistributionByDp($date, $geo_level, $geo_level_id);
}
#    Distribution - Get json export specific date distribution by DP level
elseif (CleanData('qid') == '914') {
    $rp = new Reporting\Reporting();

    $geo_level = "lga";   //  [ state or lga]
    $geo_level_id = 119;
    $start_date = "2023-12-16";
    $end_date = "2023-12-17";
    //
    echo $rp->ListDateRangeDistributionByDp($start_date, $end_date, $geo_level, $geo_level_id);
}
/*
     *
     * ====     DASHBOARD   =====
     * 
     */
# Top level fields [households, netcards, family_size]
elseif (CleanData('qid') == '950') {
    $dhb = new Mobilization();
    $data = $dhb->TopSummary();
    echo json_encode(array('info' => 'Top Summary dataset', 'data' => $data));
}
/*
     *      Aggregate by date
     *
     */
# Top level aggregated summary by date [date, households, netcards, family_size]
elseif (CleanData('qid') == '951') {
    $dhb = new Mobilization();
    $data = $dhb->TopSummaryByDate();
    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Household', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
# Drill level 1 - [lga, households, netcards, family_size, lgaid] Mobilization @ selected date
elseif (CleanData('qid') == '952') {
    $dhb = new Mobilization();
    $date = '2024-03-20';
    $data = $dhb->LgaAggregateByDate($date);
    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $household = DataLib::Column($data, 'households');
    $netcards = DataLib::Column($data, 'netcards');
    $family_size = DataLib::Column($data, 'family_size');
    $chart_data = array(
        array(
            array('name' => 'Household', 'data' => $household),
            array('name' => 'e-Netcards', 'data' => $netcards),
            array('name' => 'Family size', 'data' => $family_size)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
# Drill level 2 - [ward, households, netcards, family_size,wardid] List Ward Mobilization @ selected LGA and date
elseif (CleanData('qid') == '953') {
    $dhb = new Mobilization();
    $date = '2024-03-20';
    $lgaid = '665';
    $data = $dhb->WardAggregateByDate($date, $lgaid);
    echo json_encode(array('info' => 'Dataset', 'data' => $data));
}
# Drill level 3 - [dp, households, netcards, family_size] List Ward Mobilization @ selected ward and date
elseif (CleanData('qid') == '954') {
    $dhb = new Mobilization();
    $date = '2024-03-20';
    $wardid = '2001';
    $data = $dhb->DpAggregateByDate($date, $wardid);
    echo json_encode(array('info' => 'Dataset', 'data' => $data));
}
/*
     *      Aggregate by Location
     * 
     */
# Top level aggregated summary by date [lga, households, netcards, family_size, lgaid]
elseif (CleanData('qid') == '955') {
    $dhb = new Mobilization();
    $data = $dhb->TopSummaryByLocation();
    echo json_encode(array('info' => 'Dataset', 'data' => $data));
}
# Drill level 1 -  [ward, households, netcards, family_size, wardid]
elseif (CleanData('qid') == '956') {
    $dhb = new Mobilization();
    $lgaid = '670';
    $data = $dhb->WardAggregateByLocation($lgaid);
    echo json_encode(array('info' => 'Dataset', 'data' => $data));
}
# Drill level 2 -  [date, households, netcards, family_size, dpid]
elseif (CleanData('qid') == '957') {
    $dhb = new Mobilization();
    $wardid = '2001';
    $data = $dhb->DpAggregateByLocation($wardid);
    echo json_encode(array('info' => 'Dataset', 'data' => $data));
}
#
#
#
elseif (CleanData('qid') == '1001') {
    session_start();
    $data = $_SESSION[$instance_token . 'privileges'];
    $arr = json_decode($data, true);
    $selected = GetPrivilegeInArray($arr, 'training');

    echo "<pre>";
    print_r($selected);
    echo "</pre>";
}
#
#
#==============================================
#   SMC FEATURES 
#==============================================
#
#
#
#   Create new
elseif (CleanData('qid') == '1101') {
    $pr = new Smc\Period();
    $name = "2024 - Cycle 2";
    $start_date = '2024-07-01';
    $end_date = '2024-07-30';
    #
    $id = $pr->Create($name, $start_date, $end_date);
    #
    if ($id) {
        echo "Period created successfull with ID: $id";
    } else {
        echo "Period creation failed at this time, please try again later.";
    }
}
#   update period
elseif (CleanData('qid') == '1102') {
    $pr = new Smc\Period();
    $name = "2023 - Cycle 2";
    $start_date = '2023-06-01';
    $end_date = '2023-06-30';
    $period_id = 2;
    #
    if ($pr->Update($name, $start_date, $end_date, $period_id)) {
        echo "Period updated successfully.";
    } else {
        echo "Unable to update Period at the moment please try again later.";
    }
}
#   Delete period
elseif (CleanData('qid') == '1103') {
    $pr = new Smc\Period();
    $period_id = 2;
    #
    if ($pr->Delete($period_id)) {
        echo "Period deleted successfully";
    } else {
        echo "Unable to delete period at the moment, please try again later.";
    }
}
#   Activate period (only one per time)
elseif (CleanData('qid') == '1104') {
    $pr = new Smc\Period();
    $period_id = 3;
    #
    if ($pr->Activate($period_id)) {
        echo "Period activated successfully";
    } else {
        echo "Unable to activate period at the moment, please try again later.";
    }
}
#   Get period list
elseif (CleanData('qid') == '1105') {
    $pr = new Smc\Period();
    #
    $data = $pr->GetList();
    echo json_encode($data);
}
#   Create Bulk Household
#   ['dpid','hh_token','hoh','phone','longitude','latitude','user_id','created']
elseif (CleanData('qid') == '1106') {
    $hh = new Smc\Registration();
    $data = array(
        array('dpid' => 5001, 'hh_token' => 'EDG0023', 'hoh' => 'Samuel Perry', 'phone' => '08088282828', 'longitude' => '7.98030', 'latitude' => '9.809069', 'user_id' => 4, 'created' => '2024-05-02 13:44:55'),
        array('dpid' => 5001, 'hh_token' => 'ECG0024', 'hoh' => 'Jumaid Salam', 'phone' => '08062358989', 'longitude' => '7.98030', 'latitude' => '9.809069', 'user_id' => 4, 'created' => '2024-05-02 13:43:55'),
        array('dpid' => 5001, 'hh_token' => 'EDO0025', 'hoh' => 'Hammed Hammed', 'phone' => '09023568989', 'longitude' => '7.98030', 'latitude' => '9.809069', 'user_id' => 4, 'created' => '2024-05-02 13:55:55')
    );
    //
    $result = $hh->CreateHouseholdBulk($data);
    if ($result != false) {
        //  Successful
        echo json_encode($result);
    } else {
        echo "Failed, unable to create household at the moment, please try again later later.";
    }
}
#   Create Bulk Child record
#   ['hh_token','beneficiary_id','dpid','name','gender','dob','longitude','latitude','user_id','created']
elseif (CleanData('qid') == '1107') {
    $hh = new Smc\Registration();
    $data = array(
        array('hh_token' => 'EDG0024', 'beneficiary_id' => 'EE01023', 'dpid' => '5001', 'name' => 'James Bennet', 'gender' => 'Male', 'dob' => '2023-01-01', 'longitude' => '7.98030', 'latitude' => '9.809069', 'user_id' => 4, 'created' => '2024-05-03 13:44:55'),
        array('hh_token' => 'EDG0025', 'beneficiary_id' => 'EE01026', 'dpid' => '5001', 'name' => 'Samuel Bennet', 'gender' => 'Femal', 'dob' => '2022-01-01', 'longitude' => '7.98030', 'latitude' => '9.809069', 'user_id' => 4, 'created' => '2024-05-03 13:44:55'),
        array('hh_token' => 'EDG0026', 'beneficiary_id' => 'EE01027', 'dpid' => '5001', 'name' => 'Hakeem Bennet', 'gender' => 'Male', 'dob' => '2021-01-01', 'longitude' => '7.98030', 'latitude' => '9.809069', 'user_id' => 4, 'created' => '2024-05-03 13:44:55')
    );
    //
    $result = $hh->CreateChildBulk($data);
    if ($result != false) {
        //  Successful
        echo json_encode($result);
    } else {
        echo "Failed, unable to create bulk child record at the moment, please try again later later.";
    }
}
#   Drug Administration
#   [periodid, uid, dpid, beneficiary_id, is_eligible, not_eligible_reason, is_refer, drug, drug_qty, redose_count, redose_reason, user_id, longitude, latitude, collected_date]
elseif (CleanData('qid') == '1111') {
    $hh = new Smc\DrugAdmin();
    $data = array(
        array('periodid' => 3, 'uid' => 'BMN0029-02931', 'dpid' => 3001, 'beneficiary_id' => 'DM3009', 'is_eligible' => 1, 'not_eligible_reason' => 'just test', 'is_refer' => 1, 'drug' => 'SPAQ 1', 'drug_qty' => 1, 'redose_count' => 1, 'redose_reason' => 'redose reason', 'user_id' => 3456, 'longitude' => '8.738390', 'latitude' => '9.049940', 'collected_date' => '2024-05-22 13:02:33'),
        array('periodid' => 3, 'uid' => 'BMN0029-02932', 'dpid' => 3001, 'beneficiary_id' => 'DM30010', 'is_eligible' => 1, 'not_eligible_reason' => 'just test', 'is_refer' => 1, 'drug' => 'SPAQ 1', 'drug_qty' => 1, 'redose_count' => 1, 'redose_reason' => 'redose reason', 'user_id' => 3456, 'longitude' => '8.738390', 'latitude' => '9.049940', 'collected_date' => '2024-05-22 13:02:33'),
        array('periodid' => 3, 'uid' => 'BMN0029-02933', 'dpid' => 3001, 'beneficiary_id' => 'DM3041', 'is_eligible' => 1, 'not_eligible_reason' => 'just test', 'is_refer' => 1, 'drug' => 'SPAQ 1', 'drug_qty' => 1, 'redose_count' => 1, 'redose_reason' => 'redose reason', 'user_id' => 3456, 'longitude' => '8.738390', 'latitude' => '9.049940', 'collected_date' => '2024-05-22 13:02:33'),
        array('periodid' => 2, 'uid' => 'BMN0029-02927', 'dpid' => 3001, 'beneficiary_id' => 'DM30540', 'is_eligible' => 1, 'not_eligible_reason' => 'just test', 'is_refer' => 1, 'drug' => 'SPAQ 1', 'drug_qty' => 1, 'redose_count' => 1, 'redose_reason' => 'redose reason', 'user_id' => 3456, 'longitude' => '8.738390', 'latitude' => '9.049940', 'collected_date' => '2024-05-16 13:02:33')
    );
    #
    $result = $hh->BulkSave($data);
    if ($result != false) {
        //  successful
        echo json_encode($result);
    } else {
        echo "Failed, unable to upload bulk drug administration at the moment, please try again later.";
    }
}
#   Drug Administration: Redose update
#   [uid,redose_count,redose_reason]
elseif (CleanData('qid') == '1111b') {
    $hh = new Smc\DrugAdmin();
    $data = array(
        array('uid' => 'BMN0029-02920', 'redose_count' => 2, 'redose_reason' => 'check testing 01'),
        array('uid' => 'BMN0029-02921', 'redose_count' => 2, 'redose_reason' => 'check testing 02'),
        array('uid' => 'BMN0029-02922', 'redose_count' => 2, 'redose_reason' => 'check testing 03')
    );
    #
    $result = $hh->BulkRedose($data);
    if ($result != false) {
        //  successful
        echo json_encode($result);
    } else {
        echo "Failed, unable to upload bulk drug administration at the moment, please try again later.";
    }
}
#   ICC - Issue: Inventory Control Administration (ICC)
#   [uid, dpid, issuer_id, cdd_lead_id, cdd_team_code, periodid, issue_date, issue_day, issue_drug, drug_qty]
elseif (CleanData('qid') == '1112') {
    $ic = new Smc\Icc();
    $data = array(
        array('uid' => 'WE099-2002920', 'dpid' => 3001, 'issuer_id' => 2001, 'cdd_lead_id' => 2501, 'cdd_team_code' => 'NB/4664/ABU/002', 'periodid' => 1, 'issue_date' => '2024-05-09 08:32:14', 'issue_day' => 'Day 1', 'issue_drug' => 'SPAQ 1', 'drug_qty' => 20),
        array('uid' => 'WE099-2002921', 'dpid' => 3001, 'issuer_id' => 2001, 'cdd_lead_id' => 2502, 'cdd_team_code' => 'NB/4664/ABU/012', 'periodid' => 1, 'issue_date' => '2024-05-09 08:32:14', 'issue_day' => 'Day 1', 'issue_drug' => 'SPAQ 1', 'drug_qty' => 30),
        array('uid' => 'WE099-2002922', 'dpid' => 3001, 'issuer_id' => 2001, 'cdd_lead_id' => 2503, 'cdd_team_code' => 'NB/4664/ABU/004', 'periodid' => 1, 'issue_date' => '2024-05-09 08:32:14', 'issue_day' => 'Day 1', 'issue_drug' => 'SPAQ 1', 'drug_qty' => 30),
        array('uid' => 'WE099-2002923', 'dpid' => 3001, 'issuer_id' => 2001, 'cdd_lead_id' => 2501, 'cdd_team_code' => 'NB/4664/ABU/002', 'periodid' => 1, 'issue_date' => '2024-05-09 08:32:14', 'issue_day' => 'Day 1', 'issue_drug' => 'SPAQ 2', 'drug_qty' => 20),
        array('uid' => 'WE099-2002924', 'dpid' => 3001, 'issuer_id' => 2001, 'cdd_lead_id' => 2502, 'cdd_team_code' => 'NB/4664/ABU/012', 'periodid' => 1, 'issue_date' => '2024-05-09 08:32:14', 'issue_day' => 'Day 1', 'issue_drug' => 'SPAQ 2', 'drug_qty' => 30),
        array('uid' => 'WE099-2002925', 'dpid' => 3001, 'issuer_id' => 2001, 'cdd_lead_id' => 2503, 'cdd_team_code' => 'NB/4664/ABU/004', 'periodid' => 1, 'issue_date' => '2024-05-09 08:32:14', 'issue_day' => 'Day 1', 'issue_drug' => 'SPAQ 2', 'drug_qty' => 40)
    );
    #
    $result = $ic->BulkIccIssue($data);
    if ($result != false) {
        //  successful
        echo json_encode($result);
    } else {
        echo "Failed, unable to upload bulk ICC records at the moment, please try again later.";
    }
}
#   ICC - Receive: Inventory Control Administration (ICC)
#   [uid, dpid, receiver_id, cdd_lead_id, cdd_team_code, periodid, received_date, received_day, received_drug, total_qty, full_dose_qty, partial_qty, wasted_qty]
elseif (CleanData('qid') == '1113') {
    $ic = new Smc\Icc();
    $data = array(
        array('uid' => 'WE099-2002920', 'dpid' => 3001, 'receiver_id' => 2001, 'cdd_lead_id' => 2501, 'cdd_team_code' => 'NB/4664/ABU/002', 'periodid' => 1, 'received_date' => '2024-05-09 08:32:14', 'received_day' => 'Day 1', 'received_drug' => 'SPAQ 1', 'total_qty' => 5, 'full_dose_qty' => 3, 'partial_qty' => 5, 'wasted_qty' => 1),
        array('uid' => 'WE099-2002921', 'dpid' => 3001, 'receiver_id' => 2001, 'cdd_lead_id' => 2502, 'cdd_team_code' => 'NB/4664/ABU/012', 'periodid' => 1, 'received_date' => '2024-05-09 08:32:14', 'received_day' => 'Day 1', 'received_drug' => 'SPAQ 1', 'total_qty' => 4, 'full_dose_qty' => 2, 'partial_qty' => 2, 'wasted_qty' => 0),
        array('uid' => 'WE099-2002922', 'dpid' => 3001, 'receiver_id' => 2001, 'cdd_lead_id' => 2503, 'cdd_team_code' => 'NB/4664/ABU/004', 'periodid' => 1, 'received_date' => '2024-05-09 08:32:14', 'received_day' => 'Day 1', 'received_drug' => 'SPAQ 1', 'total_qty' => 2, 'full_dose_qty' => 0, 'partial_qty' => 2, 'wasted_qty' => 0),
        array('uid' => 'WE099-2002923', 'dpid' => 3001, 'receiver_id' => 2001, 'cdd_lead_id' => 2501, 'cdd_team_code' => 'NB/4664/ABU/002', 'periodid' => 1, 'received_date' => '2024-05-09 08:32:14', 'received_day' => 'Day 1', 'received_drug' => 'SPAQ 2', 'total_qty' => 6, 'full_dose_qty' => 5, 'partial_qty' => 1, 'wasted_qty' => 2),
        array('uid' => 'WE099-2002924', 'dpid' => 3001, 'receiver_id' => 2001, 'cdd_lead_id' => 2502, 'cdd_team_code' => 'NB/4664/ABU/012', 'periodid' => 1, 'received_date' => '2024-05-09 08:32:14', 'received_day' => 'Day 1', 'received_drug' => 'SPAQ 2', 'total_qty' => 8, 'full_dose_qty' => 3, 'partial_qty' => 5, 'wasted_qty' => 5),
        array('uid' => 'WE099-2002925', 'dpid' => 3001, 'receiver_id' => 2001, 'cdd_lead_id' => 2503, 'cdd_team_code' => 'NB/4664/ABU/004', 'periodid' => 1, 'received_date' => '2024-05-09 08:32:14', 'received_day' => 'Day 1', 'received_drug' => 'SPAQ 2', 'total_qty' => 4, 'full_dose_qty' => 3, 'partial_qty' => 1, 'wasted_qty' => 4)
    );
    #
    $result = $ic->BulkIccReceive($data);
    if ($result != false) {
        //  successful
        echo json_encode($result);
    } else {
        echo "Failed, unable to upload bulk ICC records at the moment, please try again later.";
    }
}
# Download Icc Balance for CDD
elseif (CleanData('qid') == '1113ab') {
    $ic = new Smc\Icc();
    #
    $cdd_id = 1000;
    $device_id = "NM0098";
    $version = "1.0.9";
    #
    $result = $ic->IccDownloadBalance($cdd_id, $device_id, $version);
    if ($result != false) {
        //  successful
        echo json_encode($result);
    } else {
        echo "Failed, unable to upload bulk ICC records at the moment, please try again later.";
    }
}
# Download Reconcile master data CDD
elseif (CleanData('qid') == '1113ac') {
    $ic = new Smc\Icc();
    #
    $dpid = 3354;
    #
    $result = $ic->GetReconciliationMaster($dpid);
    if (count($result)) {
        //  successful
        echo json_encode($result);
    } else {
        print_r($result);// echo "Failed, unable to upload bulk ICC records at the moment, please try again later.";
    }
}
#   Get ICC Administration Record List
elseif (CleanData('qid') == '1114') {
    $pr = new Smc\Icc();
    $dpid = 3354;
    #
    $data = $pr->GetAdministrationRecord($dpid);
    echo json_encode($data);
}
#   Get MasterHousehold using dpid
elseif (CleanData('qid') == 'gen009') {
    #   ['hhid','dpid','hh_token','hoh_name','hoh_phone']                #
    #   Get Master Household list
    $us = new Smc\SmcMaster();
    // $inputData = json_decode(file_get_contents('php://input'), true);
    $dpid = 3354;

    $data = $us->GetMasterHousehold($dpid);

    http_response_code(200);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Household Master Data',
        'message' => 'success',
        'data' => $data
    ));
} 
#   Get Referral summary card data
elseif (CleanData('qid') == '1120') {
    $pr = new Smc\DrugAdmin();
    $periodid = 0;      # 0 is default will filter to all periods. Period is comma seperated list eg 1,2,3
    $geo_id = 0;        # 0 is default will filter to all geo-level
    $geo_level = '';     # empty is the default filter to all
    $attended = 'no';   # yes, no or empty, cap incensitive
    #
    $data = $pr->GetReferralCount($periodid,$geo_id,$geo_level,$attended);
    echo json_encode($data);
}
#
#      SMC DASHBOARD SAMPLE
#
#   Child LGA list
elseif (CleanData('qid') == '1130') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $period_list = CleanData('period_from_ui');  // sample 1,2,3 or 1,3 not in use here
    #implement list LGA DATA [id, title, total, male, female]
    $data = $dhb->ChildListLgaSummary();
    //  Transform chart
    $label = DataLib::Column($data, 'title');
    $male = DataLib::Column($data, 'male');
    $female = DataLib::Column($data, 'female');
    $chart_data = array(
        array(
            array('name' => 'male', 'data' => $male),
            array('name' => 'female', 'data' => $female)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Child Ward list
elseif (CleanData('qid') == '1131') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = CleanData('id_from_ui');
    $period_list = CleanData('period_from_ui');  // sample 1,2,3 or 1,3 not in use here
    #implement list Ward data [id, title, total, male, female]
    $data = $dhb->ChildListWardSummary($id);
    //  Transform chart 
    $label = DataLib::Column($data, 'title');
    $male = DataLib::Column($data, 'male');
    $female = DataLib::Column($data, 'female');
    $chart_data = array(
        array(
            array('name' => 'male', 'data' => $male),
            array('name' => 'female', 'data' => $female)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Child DP list
elseif (CleanData('qid') == '1132') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id= CleanData('id_from_ui');
    $period_list = CleanData('period_from_ui');  // sample 1,2,3 or 1,3 not in use here
    #implement list Ward data [id, title, total, male, female]
    $data = $dhb->ChildListDpSummary($id);
    //  Transform chart 
    $label = DataLib::Column($data, 'title');
    $male = DataLib::Column($data, 'male');
    $female = DataLib::Column($data, 'female');
    $chart_data = array(
        array(
            array('name' => 'male', 'data' => $male),
            array('name' => 'female', 'data' => $female)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Drug Administration LGA list
elseif (CleanData('qid') == '1134') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $period_list = CleanData('period_from_ui');  // sample 1,2,3 or 1,3 
    #implement list Ward data [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    $data = $dhb->DrugAdminListLga($period_list);
    //  Transform chart part (eligible, spaq1 & spaq2)
    $label = DataLib::Column($data, 'title');
    $eligible = DataLib::Column($data, 'eligible');
    $spaq1 = DataLib::Column($data, 'spaq1');
    $spaq2 = DataLib::Column($data, 'spaq2');
    $chart_data = array(
        array(
            array('name' => 'Eligible', 'data' => $eligible),
            array('name' => 'SPAQ 1', 'data' => $spaq1),
            array('name' => 'SPAQ 2', 'data' => $spaq2)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Drug Administration Ward list
elseif (CleanData('qid') == '1135') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id= CleanData('id_from_ui');
    $period_list = CleanData('period_from_ui');  // period id if any sample 1,2,3 or 1,3 
    #implement list Ward data [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    $data = $dhb->DrugAdminListWard($id,$period_list);
    //  Transform chart part (eligible, spaq1 & spaq2)
    $label = DataLib::Column($data, 'title');
    $eligible = DataLib::Column($data, 'eligible');
    $spaq1 = DataLib::Column($data, 'spaq1');
    $spaq2 = DataLib::Column($data, 'spaq2');
    $chart_data = array(
        array(
            array('name' => 'Eligible', 'data' => $eligible),
            array('name' => 'SPAQ 1', 'data' => $spaq1),
            array('name' => 'SPAQ 2', 'data' => $spaq2)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Drug Administration DP list
elseif (CleanData('qid') == '1136') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id= CleanData('id_from_ui');
    $period_list = CleanData('period_from_ui');  // period id if any sample 1,2,3 or 1,3 
    #implement list Ward data [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    $data = $dhb->DrugAdminListDp($id,$period_list);
    //  Transform chart part (eligible, spaq1 & spaq2)
    $label = DataLib::Column($data, 'title');
    $eligible = DataLib::Column($data, 'eligible');
    $spaq1 = DataLib::Column($data, 'spaq1');
    $spaq2 = DataLib::Column($data, 'spaq2');
    $chart_data = array(
        array(
            array('name' => 'Eligible', 'data' => $eligible),
            array('name' => 'SPAQ 1', 'data' => $spaq1),
            array('name' => 'SPAQ 2', 'data' => $spaq2)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Referral LGA list
elseif (CleanData('qid') == '1138') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $period_list = CleanData('period_from_ui');  // period id if any sample 1,2,3 or 1,3 
    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->ReferralListLga($period_list);
    //  Transform chart part (referred, attended)
    $label = DataLib::Column($data, 'title');
    $referred = DataLib::Column($data, 'referred');
    $attended = DataLib::Column($data, 'attended');
    $chart_data = array(
        array(
            array('name' => 'Referred', 'data' => $referred),
            array('name' => 'Attended', 'data' => $attended)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Referral Ward list
elseif (CleanData('qid') == '1139') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id= CleanData('id_from_ui');
    $period_list = CleanData('period_from_ui');  // period id if any sample 1,2,3 or 1,3 
    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->ReferralListWard($id,$period_list);
    //  Transform chart part (referred, attended)
    $label = DataLib::Column($data, 'title');
    $referred = DataLib::Column($data, 'referred');
    $attended = DataLib::Column($data, 'attended');
    $chart_data = array(
        array(
            array('name' => 'Referred', 'data' => $referred),
            array('name' => 'Attended', 'data' => $attended)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#   Referral DP list
elseif (CleanData('qid') == '1140') {
    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id= CleanData('id_from_ui');
    $period_list = CleanData('period_from_ui');  // period id if any sample 1,2,3 or 1,3 
    #implement list Ward data [id, title, total, referred, attended]
    $data = $dhb->ReferralListDp($id,$period_list);
    //  Transform chart part (referred, attended)
    $label = DataLib::Column($data, 'title');
    $referred = DataLib::Column($data, 'referred');
    $attended = DataLib::Column($data, 'attended');
    $chart_data = array(
        array(
            array('name' => 'Referred', 'data' => $referred),
            array('name' => 'Attended', 'data' => $attended)
        ),
        $label
    );
    echo json_encode(array('table' => $data, 'chart' => $chart_data));
}
#
#   Reconcile balance
elseif (CleanData('qid') == '1145') {
    $icc = new Smc\Icc();
    $cdd_id = "1033";
    $dpid = "3126";
    $drug = "SPAQ 1";
    $qty = "3";
    $device_id = "U0000";
    $app_version = "v1.2.test";
    $result = $icc->ReconcileBalance($cdd_id,$dpid,$drug,$qty,$device_id,$app_version);
    //
    echo $result;
}
#
#   daownload Icc Balance
elseif (CleanData('qid') == '1146') {
    $icc = new Smc\Icc();
    $cdd_id = "1033";
    $device_id = "U0000";
    $app_version = "v1.2.test";
    $result = $icc->IccDownloadBalance($cdd_id,$device_id,$app_version);
    //
    echo json_encode($result);
}
#
#   Detail Icc Isued and reconcile
#   To replace the previous multiple call
elseif (CleanData('qid') == '1147') {
    $icc = new Smc\Icc();
    $cddid = '1033';
    $result = $icc->GetIccFlowDetailByCdd($cddid);
    //
    echo json_encode($result);
}
#   
#   Unlock balance
elseif (CleanData('qid') == '1148') {
    $icc = new Smc\Icc();
    $dpid = 3006;
    $cddid = 1066;
    $drug = 'SPAQ 1';
    $qty = 20;
    $userid = 1;
    $result = $icc->UnlockBalance($dpid,$cddid,$drug,$qty,$userid);
    //
    if($result){
        echo "unlock done successfully";
    }else{
        echo "Unlock failed";
    }
}
#
#
#       Ipolongo Update endpoint
#
#
#   Bulk reset password
elseif (CleanData('qid') == '1200') {
    $us = new Users\UserManage();
    $user_id_list = array(1000,1001,1002,1003,1004,1005,1006,1007,1008,1009);
    $default_password = 'demo123'; // default password
    #   Call reset
    $result = $us->BulkPasswordReset($user_id_list, $default_password);
    if ($result) {
        echo "Password reset successfully.";
    } else {
        echo "Unable to reset password at the moment, please try again later.";
    }
}
#
#   Bulk change geo localtion
elseif (CleanData('qid') == '1201') {
    $us = new Users\UserManage();
    $user_id_list = array(1234, 23456, 6789, 1223, 1224, 1235, 1236, 1237, 1238, 1239);
    $geo_level = 'ward'; // default password
    $geo_id = 2345; // default password
    #   Call reset
    $result = $us->BulkChangeGeoLocation($user_id_list, $geo_level, $geo_id);
    if ($result) {
        echo "Password reset successfully.";
    } else {
        echo "Unable to reset password at the moment, please try again later.";
    }
}
#
#
#
#
#           UPDATE VERSION FOR SMC ICC
#
#
#
#
#   Bulk Issue
elseif(CleanData('qid') == '1250') {
    $ic = new Smc\Icc();
    #   Sample data structure for BulkIccIssue
    #   [uid, dpid, issuer_id, cdd_lead_id, periodid, issue_date, issue_day, issue_drug, drug_qty, device_serial, app_version]
    $data = array(
        array(
            'uid' => 'ISSUE001',
            'dpid' => 3001,
            'issuer_id' => 1021,
            'cdd_lead_id' => 1081,
            'periodid' => 1,
            'issue_date' => '2024-05-09 08:32:14',
            'issue_day' => 'Day 1',
            'issue_drug' => 'SPAQ 1',
            'drug_qty' => 20,
            'device_serial' => 'DEV001',
            'app_version' => 'v1.0.0'
        ),
        array(
            'uid' => 'ISSUE002',
            'dpid' => 3002,
            'issuer_id' => 1021,
            'cdd_lead_id' => 1081,
            'periodid' => 1,
            'issue_date' => '2024-05-10 09:15:00',
            'issue_day' => 'Day 2',
            'issue_drug' => 'SPAQ 2',
            'drug_qty' => 30,
            'device_serial' => 'DEV002',
            'app_version' => 'v1.0.1'
        ),
        array(
            'uid' => 'ISSUE003',
            'dpid' => 3003,
            'issuer_id' => 1021,
            'cdd_lead_id' => 1082,
            'periodid' => 1,
            'issue_date' => '2024-05-11 10:45:30',
            'issue_day' => 'Day 3',
            'issue_drug' => 'SPAQ 1',
            'drug_qty' => 25,
            'device_serial' => 'DEV003',
            'app_version' => 'v1.0.2'
        ),
        array(
            'uid' => 'ISSUE004',
            'dpid' => 3003,
            'issuer_id' => 1021,
            'cdd_lead_id' => 1082,
            'periodid' => 1,
            'issue_date' => '2024-05-11 10:45:30',
            'issue_day' => 'Day 3',
            'issue_drug' => 'SPAQ 2',
            'drug_qty' => 25,
            'device_serial' => 'DEV003',
            'app_version' => 'v1.0.2'
        )
    );
    #   Call reset
    $result = $ic->BulkIccIssue($data);
    if (count($result)) {
        echo json_encode($result);
    } else {
        echo "Unable to reset password at the moment, please try again later.";
    }
}
#   Download ICC
elseif (CleanData('qid') == '1251') {
    $ic = new Smc\Icc();
    $cdd_id = 1081;
    $device_id = "NM0098";
    $version = "1.0.9";
    $period_id = 1;
    #   Call reset
    $result = $ic->IccDownloadBalance($period_id,$cdd_id, $device_id, $version);
    if (count($result)) {
        echo json_encode($result);
    } else {
        echo "Unable to reset password at the moment, please try again later.";
    }
}
#
#   ICC download confirmation
elseif (CleanData('qid') == '1252') {
    $ic = new Smc\Icc();
    $cdd_id = 1081;
    $issue_id = 9;
    $download_id = 'gdsjekn2-wmi6-za03-ujjm-nw3t7h2w4i6d';
    #   Call reset
    $result = $ic->ConfirmDownload($download_id, $cdd_id, $issue_id);
    if ($result) {
        echo "Download confirmation done successfully.";
    } else {
        echo "Unable to confirm download at the moment, please try again later.";
    }
}
#
#   ICC Acceptance accept
elseif (CleanData('qid') == '1253') {
    $ic = new Smc\Icc();
    $issue_id = 9;
    #   Call reset
    $result = $ic->AcceptanceAccept($issue_id);
    if ($result) {
        echo "ICC issued downloaded was accepted successfully";
    } else {
        echo "Failed to accept download acceptance, please try again later.";
    }
}
#
#   ICC Acceptance reject
elseif (CleanData('qid') == '1254') {
    $ic = new Smc\Icc();
    $issue_id = 1;
    $reasons = 'sample reason for rejection';
    #   Call reset
    $result = $ic->AcceptanceReject($issue_id, $reasons);
    if ($result) {
        echo "ICC issued downloaded was rejected successfully";
    } else {
        echo "Failed to reject download acceptance, please try again later.";
    }
}
#
#   Return ICC Issue
elseif (CleanData('qid') == '1255') {
    $ic = new Smc\Icc();
    $bulk_data = [['returned_qty'=>5, 'returned_partial'=>2, 'issue_id'=>9]];// sample - [['returned_qty'=>4, 'returned_partial'=>2, 'issue_id'=>1],['returned_qty'=>2,  'returned_partial'=>2, 'issue_id'=>2]]
    #   Call reset
    $result = $ic->BulkIccReturn($bulk_data);
    //  returns arrays of issue_id
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to return ICC issue, please try again later.";
    }
}
#
#   Save Reconciliation
elseif (CleanData('qid') == '1256') {
    $ic = new Smc\Icc();
    #   data structure
    #   ['issue_id', 'cdd_lead_id', 'drug', 'used_qty', 'full_qty', 'partial_qty', 'wasted_qty', 'loss_qty', 'loss_reason', 'receiver_id', 'device_serial', 'app_version', 'reconcile_date']
    $bulk_data = [['issue_id'=>9, 'cdd_lead_id'=>1081, 'drug'=>'SPAQ 1', 'used_qty'=>5, 'full_qty'=>3, 'partial_qty'=>2, 'wasted_qty'=>0, 'loss_qty'=>0, 'loss_reason'=>'none', 'receiver_id'=>2001, 'device_serial'=>'NM0098', 'app_version'=>'v1.0.9', 'reconcile_date'=>'2024-05-09 08:32:14']];
    
    #   Call reset
    $result = $ic->BulkSaveRconciliation($bulk_data);
    //  returns arrays of issue_id
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to return ICC issue, please try again later.";
    }
}
elseif (CleanData('qid') == '3001') {
    $nt = new Netcard\NetcardTrans();
    $mobilizer = 1052;
    $device_id = 'TEST-DEVICE';
    $result = $nt->SuperUserUnlockNetcard($mobilizer,$device_id,1);
    //
    echo $result;
}
#   CMS inventory in 
elseif (CleanData('qid') == '3002') {
    $nt = new Smc\Inventory();
    $product_code = 'SPAQ2';
    $product_name = 'SPAQ 2';
    $location_type = 'CMS';
    $location_id = 1;
    $batch = 'TEST-BATCH-2';
    $expiry_date = '2024-12-31';
    $rate = 100;
    $unit = '1X50';
    $primary_qty = 1000;
    $secondary_qty = 20;
    $userid = 1000;
    //  ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty','userid']
    $data = [array(
        'product_code' => $product_code,
        'product_name' => $product_name,
        'location_id' => $location_id,
        'batch' => $batch,
        'expiry_date' => $expiry_date,
        'rate' => $rate,
        'unit' => $unit,
        'primary_qty' => $primary_qty,
        'secondary_qty' => $secondary_qty,
        'userid' => $userid
    )];
    //
    #   ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty']
    $result = $nt->CmsInboundShipment($data);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}   
#   CMS inventory out 
elseif (CleanData('qid') == '3003') {
    $nt = new Smc\Inventory();
    $product_code = 'SPAQ1';
    $product_name = 'SPAQ 1';
    $location_type = 'CMS';
    $location_id = 1;
    $batch = 'TEST-BATCH';
    $expiry_date = '2024-12-31';
    $rate = 100;
    $unit = '1X50';
    $primary_qty = 3000;
    $secondary_qty = 60;
    $userid = 1000;
    //  ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty','userid']
    $data = [array(
        'product_code' => $product_code,
        'product_name' => $product_name,
        'location_id' => $location_id,
        'batch' => $batch,
        'expiry_date' => $expiry_date,
        'rate' => $rate,
        'unit' => $unit,
        'primary_qty' => $primary_qty,
        'secondary_qty' => $secondary_qty,
        'userid' => $userid
    )];
    //
    $result = $nt->CmsOutboundShipment($data);
    if ($result) {
        echo $result;
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   Facility inventory in 
elseif (CleanData('qid') == '3004') {
    $nt = new Smc\Inventory();
    $product_code = 'SPAQ1';
    $product_name = 'SPAQ 1';
    $location_type = 'CMS';
    $location_id = 3001;
    $batch = 'TEST-BATCH';
    $expiry_date = '2024-12-31';
    $rate = 100;
    $unit = '1X50';
    $primary_qty = 800;
    $secondary_qty = 16;
    $userid = 1000;
    //
    $result = $nt->FacilityInboundShipment($product_code, $product_name, $location_id, $batch, $expiry_date, $rate, $unit, $primary_qty, $secondary_qty, $userid);
    if ($result) {
        echo $result;
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   Facility inventory out 
elseif (CleanData('qid') == '3005') {
    $nt = new Smc\Inventory();
    $product_code = 'SPAQ1';
    $product_name = 'SPAQ 1';
    $location_type = 'CMS';
    $location_id = 3001;
    $batch = 'TEST-BATCH';
    $expiry_date = '2024-12-31';
    $rate = 100;
    $unit = '1X50';
    $primary_qty = 800;
    $secondary_qty = 16;
    $userid = 1000;
    //
    $result = $nt->FacilityOutboundShipment($product_code, $product_name, $location_id, $batch, $expiry_date, $rate, $unit, $primary_qty, $secondary_qty, $userid);
    if ($result) {
        echo $result;
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   CMS In-Bound shipment
elseif (CleanData('qid') == '3006') {
    $nt = new Smc\Inventory();
    //
    $product_code = 'SPAQ1';
    $period_id = 1;
    $result  = $nt->ProcessProductValidityCheck($period_id, $product_code);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   CMS In-Bound shipment
elseif (CleanData('qid') == '3006') {
    $nt = new Smc\Inventory();
    //
    $product_code = 'SPAQ1';
    $period_id = 1;
    $result  = $nt->ProcessProductValidityCheck($period_id, $product_code);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   Availability check
elseif (CleanData('qid') == '3007') {
    $nt = new Smc\Inventory();
    //
    $period_id = 1;
    $result  = $nt->ProcessTopinventoryToValidate($period_id);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   test
elseif (CleanData('qid') == '3008') {
    $nt = new Smc\Logistics();
    //
    $period_id = 1;
    $result  = $nt->executeShipmentSample($period_id);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   Generate Inventory shipment allocation sorting
elseif (CleanData('qid') == '3009') {
    $nt = new Smc\Logistics();
    //
    $period_id = 1;
    $result  = $nt->generateInventoryAllocations($period_id);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   Get Inventory shipment allocation sorting
elseif (CleanData('qid') == '3009b') {
    $nt = new Smc\Logistics();
    //
    $period_id = 1;
    $result  = $nt->getInventoryAllocations($period_id);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to create inventory transaction, please try again later.";
    }
}
#   execute shipment with allocated sorted items
elseif (CleanData('qid') == '3010') {
    $nt = new Smc\Logistics();
    //
    $period_id = 1;
    $result  = $nt->executeForwardShipment($period_id);
    //
    echo $result;
}
#  Get shipment
elseif (CleanData('qid') == '3010b') {
    $nt = new Smc\Logistics();
    //
    $period_id = 1;
    $result  = $nt->getShipmentList($period_id);
    //
    echo json_encode($result);
}
#   execute movement
elseif (CleanData('qid') == '3011') {
    $nt = new Smc\Logistics();
    //
    $period_id = 1;
    $transporter_id = 1;
    $movement_title = "Test movement from CMS to Gboko LGA";
    $shipment_list = [9,10];
    $conveyor_id = 1001;
    $userid = 1000;
    $result  = $nt->createMovementWithShipments($period_id, $transporter_id, $movement_title, $shipment_list,$conveyor_id, $userid);
    //
    echo $result;
}
#   execute approval
elseif (CleanData('qid') == '3011') {
    $nt = new Smc\Logistics();
    //[$movementid, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion]
    $movementid = 1;
    $locationString = 'PLATEAU > BARKIN LADI > GASSA > PHC GASSA';
    $signature = ""; // base64 string
    $approveDate = "2024-05-09";
    $conveyor_id = 1001;
    $userid = 1000;
    $latitude = 8.76688;
    $longitude = 9.09930;
    $deviceSerial = "TEST-DEVICE";
    $appVersion = "v1.0.0";
    $result  = $nt->OriginApproval($movementid, $userid, $locationString, $signature, $approveDate, $conveyor_id, $latitude, $longitude, $deviceSerial, $appVersion);
    //
    echo $result;
}
#   Get shipment details
elseif(CleanData('qid') == '3012') {
    $nt = new Smc\Logistics();
    //
    $shipment_id = 30;
    $result  = $nt->getShipmentDetails($shipment_id);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "No Shipment details found, please try again later.";
    }
}
elseif(CleanData('qid') == '3015') {
    $key = System\General::GetIdBadgeKey();
    //
    echo $key;
}
#   App api get movement
elseif (CleanData('qid') == '3012') {
    $nt = new Smc\Logistics();
    //
    $movementid = 1;
    $conveyour_id = 1005;
    $result  = $nt->getAppMovementList($movementid, $conveyour_id);
    if ($result) {
        echo json_encode($result);
    } else {
        echo "Failed to get movement, please try again later.";
    }
}

#
else {
    echo "Nothing to show";
}
