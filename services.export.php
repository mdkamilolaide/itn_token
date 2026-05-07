<?php
include_once('lib/autoload.php');
include_once('lib/common.php');
include_once('lib/config.php');
session_start();
//  Log actions before leaving
$default_home = $config_pre_append_link . 'login';
//
log_system_access();

# Detect and safe base directory
$system_base_directory = __DIR__;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = file_get_contents('lib/privateKey.pem');

require('lib/vendor/autoload.php');    //JWT Autoload
/*
     *  configure required protocol access
     */
$jwt_token = $_COOKIE[$secret_code_token];

$token = JWT::decode($jwt_token, new Key($secret_key, 'HS512'));



if ($token->iss !== $issuer_claim && $token->nbf > $issuedat_claim->getTimestamp() || $token->exp < $issuedat_claim->getTimestamp()) {
    //
    http_response_code(404);
    echo json_encode(array(
        'result_code' => 404,
        'message' => 'Error:  404, Page not Found'
    ));
} else {
    if (CleanData("qid") == '001') {
        #
        #   Export user list
        $v_g_geo_level = $token->geo_level;
        $v_g_geo_level_id = $token->geo_level_id;
        #
        #   Filter column
        #

        $geo_level = CleanData('gl');
        $geo_level_id = CleanData('gl_id');

        $loginid = CleanData('lo');
        $active = CleanData('ac');
        $phone = CleanData('ph');
        $user_group = CleanData('gr');
        $name = CleanData('na');
        $bank_verification_status = CleanData('bv');    // parameters['failed' | 'success' | 'none']
        $role_id = CleanData('ri');                     # user filter by role id

        #
        $us = new Users\UserManage();
        //  The first 2 parameters are required, the users geo-level & geo-level-id, the remaining are optional for filter
        // $data = $us->ExcelDownloadUsers($user_geo_level, $user_geo_level_id, $loginid='', $active='', $phone='', $user_group='', $name='', $geo_level='', $geo_level_id='', $bank_verification_status='', $role_id='');
        $data = $us->ExcelDownloadUsers($v_g_geo_level, $v_g_geo_level_id, $loginid, $active, $phone, $user_group, $name, $geo_level, $geo_level_id, $bank_verification_status, $role_id);
        #
        echo $data;
    } elseif (CleanData('qid') == '101') {
        #
        #
        #   Export participant list
        $training_id = CleanData('id');
        #
        $ex = new Training\Training();
        echo $ex->ExcelGetParticipantList($training_id);
    } elseif (CleanData('qid') == '102') {
        #
        #
        #   Export Attendance list
        $session_id = CleanData('id');
        $geo_level = CleanData('gl');
        $geo_level_id = CleanData('glid');
        #
        $ex = new Training\Training();
        echo $ex->ExcelGetAttendanceList($session_id, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '301') {
        #
        #
        #   Export Mobilization Data
        $loginid = CleanData('lgid');
        #   Filtered by mobilized data
        $mob_date = CleanData('mdt');
        #   Filtered by Geo-Level
        $geo_level = CleanData('gl');
        $geo_level_id = CleanData('glid');
        #
        $ex = new Mobilization\Mobilization();
        echo $ex->ExcelGetMobilization($loginid, $mob_date, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '401') {
        #
        #   Export Participants List
        $rp = new Reporting\Reporting();
        #   Filtered by Geo-Level
        $trainingId = CleanData('tid');
        $geo_level = CleanData('gl');
        $geo_level_id = CleanData('glid');
        //
        echo $rp->ListParticipants($trainingId, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '402') {
        #   Activity Management - Get json export for Bank verification status
        $rp = new Reporting\Reporting();
        $trainingId = CleanData('tid');
        $geo_level = CleanData('gl');
        $geo_level_id = CleanData('glid');
        //
        echo $rp->ListBankVerification($trainingId, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '403') {
        #   Activity Management - Get json export for Uncaptured Users
        $rp = new Reporting\Reporting();
        $trainingId = CleanData('tid');
        $geo_level = CleanData('gl');
        $geo_level_id = CleanData('glid');
        //
        echo $rp->ListUncapturedUsers($trainingId, $geo_level, $geo_level_id);
    }
    #
    #   Mobilization Report Export
    #
    elseif (CleanData('qid') == '501') {
        #   Mobilization - Get json export for Overall Mobilization by LGA
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        //
        echo $rp->ListMobilizationByLga($geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '502') {
        #   Mobilization -  Get json export mobilization DP level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        //
        echo $rp->ListMobilizationByDp($geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '503') {
        #   Mobilization -  Get json export mobilization with date parameter LGA level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        $date = CleanData('date');
        //
        echo $rp->ListDateMobilizationByLga($date, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '504') {
        #   Mobilization -  Get json export mobilization with date parameter DP level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        $date = CleanData('date');
        //
        echo $rp->ListDateMobilizationByDp($date, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '505') {
        #   Mobilization -  Get json export mobilization with date range parameter DP level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        $start_date = CleanData('startDate');
        $end_date = CleanData('endDate');
        //
        echo $rp->ListDateRangeMobilizationByLga($start_date, $end_date, $geo_level, $geo_level_id);
    }

    #
    #   Distribution Report
    #
    #   Distribution -  Get json export Distribution LGA level
    elseif (CleanData('qid') == '601') {
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        //
        echo $rp->ListDistributionByLga($geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '602') {
        #   Distribution -  Get json export Distribution DP level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        //
        echo $rp->ListDistributionByDp($geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '603') {
        #   Distribution -   Get json export specific date distribution by LGA level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        $date = CleanData('date');
        //
        echo $rp->ListDateDistributionByLga($date, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '604') {
        #    Distribution - Get json export date range distribution by LGA level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        $start_date = CleanData('startDate');
        $end_date = CleanData('endDate');
        //
        echo $rp->ListDateRangeDistributionByLga($start_date, $end_date, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '605') {
        #   Distribution -   Get json export specific date distribution by DP level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        $date = CleanData('date');
        //
        echo $rp->ListDateDistributionByDp($date, $geo_level, $geo_level_id);
    } elseif (CleanData('qid') == '606') {
        #    Distribution - Get json export specific date distribution by DP level
        $rp = new Reporting\Reporting();

        $geo_level = CleanData('gl');   //    [state | lga]
        $geo_level_id = CleanData('glid');
        $start_date = CleanData('startDate');
        $end_date = CleanData('endDate');
        //
        echo $rp->ListDateRangeDistributionByDp($start_date, $end_date, $geo_level, $geo_level_id);
    }


    #   Download form I-9a
    elseif (CleanData('qid') == '701') {
        $fm = new Monitor\Monitor();
        $data = $fm->EeFormInineA();
        #
        echo $data;
    }
    #   Download form I-9b
    elseif (CleanData('qid') == '702') {
        $fm = new Monitor\Monitor();
        $data = $fm->EeFormInineB();
        #
        echo $data;
    }
    #   Download form I-9c
    elseif (CleanData('qid') == '703') {
        $fm = new Monitor\Monitor();
        $data = $fm->EeFormInineC();
        #        
        echo $data;
    }
    #   Download form 5% Revisit
    elseif (CleanData('qid') == '704') {
        $fm = new Monitor\Monitor();
        $data = $fm->EeFormFiveRevisit();
        #
        echo $data;
    }
    #   Download form End process 1
    elseif (CleanData('qid') == '705') {
        $fm = new Monitor\Monitor();
        $data = $fm->EeFormEndProOne();
        #
        echo $data;
    }    #   Download CDD Forms
    elseif (CleanData('qid') == '706') {
        $fm = new Monitor\Monitor();
        $data = $fm->EeFormSmcSupervisoryCdd();
        #
        echo $data;
    }    #   Download HFW Form
    elseif (CleanData('qid') == '707') {
        $fm = new Monitor\Monitor();
        $data = $fm->EeFormSmcSupervisoryHfw();
        #
        echo $data;
    }
    #

    #
    #   Drug Administration Data Export
    #
    elseif (CleanData('qid') == '801') {
        #    Distribution - Get json export specific date distribution by DP level
        $rp = new Smc\Reporting();

        $periodid = CleanData("pid");     #  period ID
        $is_eligible = CleanData("ise");    #  The child is eligible must be yes
        $is_redose = CleanData("isr");    #  Redose must be yes
        $reg_date = CleanData("rda");    #   Registration date
        $geo_id = CleanData("gid");    #   Geo_level_id
        $geo_level = CleanData("glv");    #   Geo-Level
        $beneficiary_id = CleanData("bid");    #   Beneficiary ID

        $filter = [
            'periodid' => $periodid,
            'is_eligible' => $is_eligible,
            'is_redose' => $is_redose,
            'reg_date' => $reg_date,
            'geo_id' => $geo_id,
            'geo_level' => $geo_level,
            'beneficiaryid' => $beneficiary_id
        ];

        echo $rp->DrugAdminBase($filter);
    }

    #
    #   Refferal Data Export
    #
    elseif (CleanData('qid') == '802') {
        #    Distribution - Get json export specific date distribution by DP level
        $rp = new Smc\Reporting();

        $periodid = CleanData("pid");       #  period ID
        $geo_id = CleanData("gid");         #   Geo_level_id
        $geo_level = CleanData("glv");      #   Geo-Level
        $attended = CleanData('atd');       #   Attended filter

        $filter = [
            'periodid' => $periodid,
            'geo_id' => $geo_id,
            'geo_level' => $geo_level,
            'attended' => $attended
        ];
        echo $rp->ReferralBase($filter);
    }

    #
    #   ICC Data Export
    #
    elseif (CleanData('qid') == '803') {
        #    Distribution - Get json export specific date distribution by DP level
        $rp = new Smc\Reporting();

        $periodid = CleanData("pid");       #  period ID
        $geo_id = CleanData("gid");         #   Geo_level_id
        $geo_level = CleanData("glv");      #   Geo-Level

        $filter = ['periodid' => $periodid, 'geo_id' => $geo_id, 'geo_level' => $geo_level];

        echo $rp->IccCddBase($filter);
    }
}
