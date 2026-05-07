<?php
ini_set("pcre.backtrack_limit", "10000000");

//  Start session
//    session_start();
include_once('lib/autoload.php');
include("lib/config.php");
include_once('lib/common.php');
require('lib/vendor/autoload.php');    //MPDF Autoload
ob_start();

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

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    $data = json_decode(file_get_contents('php://input'));
    $header = getallheaders();

    $mpdf = new \Mpdf\Mpdf([

        'showBarcodeNumbers' => false
    ]);

    $html = '
                    <style type="text/css">
                        *{
                            padding: 0;
                            margin: 0;
                            font-family: arial,helvetica,sans-serif;
                        }
                        #id-card{
                            padding:0 !important;
                            margin: 0 auto;
                            display: block !important;
                            /* 210 x 297 mm */
                            width: 212mm !important;
                            height: 266mm !important;
                            text-align: left !important;
                            float: left;
                        }
                        .page{
                            width: 212mm !important;
                            height: 297mm !important;
                        }
                        .id-grid{
                            border-radius: 4px;
                            width: 76.8mm !important;
                            height: 46.2mm !important;
                            min-height: 46.2mm !important;
                            max-height: 46.4mm !important;
                            margin-bottom: 8px !important;
                            padding: 2.5mm !important;
                            border: 1px solid #000;
                            display: inline-block !important;
                            float: left !important;
                        }
                        .ml{
                            margin-left: 12px !important;
                        }
                        .passport{
                            text-align: center;
                        }
                        .qrcode-box{
                            display: inline-block;
                            width: 99px !important;
                            float: right;
                            text-align: center !important;
                        }
                        .qrcode-box div{
                            text-align: center !important;
                            width: 89px !important;
                            display: block;
                            color: #fff !important;
                            font-size: 12px;
                            float: left;
                            font-weight: bold;
                            background-color: #000;
                            padding: 2px !important;
                            margin: 0 !important;
                            margin-left: 3px !important;
                            font-family: arial,helvetica,sans-serif;
                        }
                        .qrcode-box img{
                            width: 95px !important;
                            height: 95px !important;
                            display: block;
                            position: relative;
                            float: right;
                            margin: 0 !important;
                            border-radius: 4px 4px 0 0;
                        }
                        .logo{
                            display: inline-block;
                            width: 100px !important;
                            height: 100px !important;
                            margin-left: 0 !important;
                            float: left;

                        }
                        .logo img{
                            width: 120px;
                            height: auto;
                        }
                        .id-details{
                            text-align: center;
                            display: block;
                            float: left;
                            width: 100% !important;


                        }
                        .id-details h2{
                            padding: 0 !important;
                            margin: 0 !important;
                            font-size: 12px;
                            float: left !important;
                            font-family: arial,helvetica,sans-serif;

                        } 
                        .id-details h4{
                            color: #f00;
                            font-family: arial,helvetica,sans-serif;
                            font-weight: bold;
                            padding: 0 !important;
                            margin: 0 !important;
                            width: 100% !important;
                            float: left !important;
                            font-size: 12.8px !important;

                        }
                        .id-details h6{
                            padding: 0 !important;
                            margin: 0 !important;
                            font-family: arial,helvetica,sans-serif;
                            font-weight: normal !important;
                            float: left !important;
                            word-wrap: break-word !important;
                            line-height: 1;
                        }
                        .barcode {
                            padding: 1mm !important;
                            margin: 0;
                            vertical-align: top;
                            color: #000;
                            border: 1px solid #000;
                        }
                        .pt-10{
                            padding-top: 15px !important;
                        }
                        .role_border{
                            width: 120px !important
                            border-bottom: 1px dotted #000 !important;
                            padding-top: 6px !important;
                            padding-bottom: 2px solid !important;
                            font-weight: normal !important;
                        }
                        .color_blue{
                            color: #1f11b5 !important;
                        }
                    </style>

                    <div id="id-card">
                ';
    $badge_data = array();
    $filename = "";

    $receipt_data = new Mobilization\Mobilization();
    $receipt = $receipt_data->GetReceiptHeader();

    $endpointId = CleanData("qid");

    $us = new Users\UserManage();

    switch ($endpointId) {
        case '001':
            #   Get Badge data list by group
            $user_group = CleanData("e");
            $badge_data = $us->GetBadgeByGroup($user_group);
            $filename = CleanData("e");
            break;
        case '002':
            #   Get Badge data list by User ID
            $userid = CleanData("e");
            $badge_data = $us->GetBadgeByUserID($userid);
            $filename = $badge_data[0]['loginid'];
            break;
        case '003':
            #   Get Badge data list by User ID list
            $userids = CleanData("e");
            $userid_list = array($userids);
            $badge_data = $us->GetBadgeByUserIdList($userid_list);
            $filename = count($badge_data) . ' badges downloads';
            break;
        case '004':
            #   Get Badge data list by Login ID
            $badge_data = $us->GetBadgeByLoginId('IGC04024');
            break;
        default:
            // handle unsupported qid
            break;
    }


    if (count($badge_data)) {
        if (count($badge_data) <= 300) {
            $i = 1;
            foreach ($badge_data as $user_data) {
                # code...
                $i++;
                $margin = ($i % 2 == 1) ? 'ml' : '';
                $name = empty($user_data['fullname']) ? $user_data['username'] : $user_data['fullname'];
                $role = empty($user_data['role']) ? '<span class="color_blue">Role:</span> <span class="role_border" style="color: #7367f0 !important">.........................................</span>' : $user_data['role'];
                $html .= '
                        <div class="id-grid ' . $margin . '">
                            <div class="qrcode-box">
                                <barcode code="' . $user_data['loginid'] . '|' . $user_data['guid'] . '" size="0.88" type="QR" error="M"  disableborder="1" class="barcode" />
                                <div>' . $user_data['loginid'] . '</div>
                            </div>
                            <div class="logo">
                                <img src="data:image/jpeg;base64,' . $receipt[0]['logo'] . '" alt="' . $receipt[0]['receipt_header'] . '">
                            </div>
                            <div class="id-details">
                                <h2 style="color: #900;">' . $name . '</h2>
                                <h4>' . $role . '</h4>
                                <h6 class="pt-10">' . $user_data['geo_string']  . '</h6>
                            </div>
                        </div>
                        ';
            }
        } else {
            $html .= '<div style="color: #f00; fornt-size: 16pt !important">Error:  You cann\'t download more than 300 badges</div>';
        }
    }

    $html .= '</div>';



    $mpdf->WriteHTML($html);
    // echo $html;

    // Output a PDF file directly to the browser
    $mpdf->Output($filename . ' - ' . time() . '.pdf', 'D');
}
