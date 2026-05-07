<?php
ini_set("pcre.backtrack_limit", "10000000");
//  Start session
// session_start();
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
    http_response_code(403);
    echo json_encode(array(
        'result_code' => 403,
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
                            margin-bottom: 10mm !important;
                            display: block !important;
                            /* 210 x 297 mm */
                            width: 210mm !important;
                            height: 297mm !important;
                            text-align: left !important;
                            float: left;
                        }
                        .page{
                            width: 210mm !important;
                            height: 297mm !important;
                        }
                        .id-grid{
                            border-radius: 4px;
                            width: 200mm !important;
                            height: 60mm !important;
                            padding: 5mm !important;
                            border: 2px solid #000;
                            display: inline-block !important;
                            float: left !important;
                            margin-left: 6px !important;
                        }
                        .passport{
                            text-align: center;
                        }
                        .qrcode-box{
                            display: inline-block;
                            width: 90mm !important;
                            float: right;
                            text-align: center !important;
                        }
                        .qrcode-box div{
                            text-align: center !important;
                            width: 80mm !important;
                            display: block;
                            color: #fff !important;
                            font-size: 14px;
                            font-weight: bold;
                            background-color: #000;
                            padding: 5px !important;
                            margin: 0 !important;
                            margin-left: 3.78mm !important;
                            font-family: arial,helvetica,sans-serif;
                        }
                        .logo{
                            display: inline-block;
                            width: 50mm !important;
                            height: 50mm !important;
                            margin-left: 0 !important;
                            float: left;
                        }
                        .logo img{
                            width: 45mm;
                            height: auto;
                        }
                        .id-details{
                            text-align: center;
                            display: block;
                            float: left;
                            padding-top: 5mm !important;
                            width: 100% !important;
                        }
                        .id-details h2{
                            padding: 0 !important;
                            margin: 0 !important;
                            font-size: 22px;
                            float: left !important;
                            font-family: arial,helvetica,sans-serif;
                            color: #f00;
                        } 
                        .id-details h6{
                            padding: 0 !important;
                            margin: 0 !important;
                            font-size: 16px !important;
                            font-family: arial,helvetica,sans-serif;
                            font-weight: normal !important;
                            float: left !important;
                            padding-top: 2mm !important;
                        }
                        .barcode {
                            padding: 1mm !important;
                            margin: 0;
                            vertical-align: top;
                            color: #000;
                            border: 1px solid #000;
                        }
                        .mb-1{
                            margin-bottom: 10mm !important;
                        }
                    </style>

                    <div id="id-card">
                ';
    $badge_data = array();

    // Header Receipt
    $receipt_data = new Mobilization\Mobilization();
    $receipt = $receipt_data->GetReceiptHeader();

    if (CleanData('qid') == '001') {
        #
        #   Distribution 
        #
        #   Get DP Locations list with DP ID
        $ex = new Distribution\Distribution();
        $dp_list = array(CleanData('e'));
        $data = $ex->GetDpLocationMasterList($dp_list);
        #
        $badge_data = $data;

        if (count($badge_data)) {
            if (count($badge_data) <= 1000) {
                $i = 1;
                foreach ($badge_data as $badge) {
                    $marg = ($i % 2) == 1 ? 'mb-1' : '';
                    # code...
                    $html .= '
                            <div class="id-grid  ' . $marg . '">
                                <div class="qrcode-box">
                                    <barcode code="' . $badge['guid'] . '" size="3.2" type="QR" error="M" disableborder="1" class="barcode"  />
                                    <div>' . $server_type . ' DP Badge</div>
                                </div>
                                <div class="logo">
                                    <img src="data:image/jpeg;base64,' . $receipt[0]['logo'] . '" alt="' . $receipt[0]['receipt_header'] . '">
                                </div>
                                <div class="id-details">
                                    <h2>' . $badge['title'] .  '</h2>
                                    <h6>' . $badge['geo_string'] .  '</h6>
                                </div>
                            </div>
                            ';

                    $filename = $i . ' DP Badges ';
                    $i++;
                }
            } else {
                $html .= '<div style="color: #f00; fornt-size: 16pt !important">Error:  You cann\'t download more than 1000 badges</div>';
            }
        }
    }
    if (CleanData('qid') == '002') {
        #
        #   Distribution 
        #
        #   Get DP Locations list with DP ID
        $filename = str_replace("&gt", ">", CleanData("title"));

        $html .= '
                <div class="id-grid">
                    <div class="qrcode-box">
                        <barcode code="' . CleanData("guid") . '" size="3.2" type="QR" error="M" disableborder="1" class="barcode"  />
                        <div>' . $server_type . ' DP Badge</div>
                    </div>
                    <div class="logo">
                        <img src="data:image/jpeg;base64,' . $receipt[0]['logo'] . '" alt="">
                    </div>
                    <div class="id-details">
                        <h2>' . str_replace("&gt", ">", CleanData("title")) . '</h2>
                        <h6>' . str_replace("&gt", ">", CleanData("geo_string")) . '</h6>
                    </div>
                </div>
                ';
    }
    // Download Provision Badge
    if (CleanData('qid') == '003') {
        #
        #   Distribution 
        #
        #   Get DP Locations list with DP ID
        $filename = str_replace("&gt", ">", $server_type);
        $date = CleanData("date") != '' ? date("d-m-Y", strtotime(CleanData("date"))) : 'Never Expire';

        $html .= '
                <div class="id-grid">
                    <div class="qrcode-box">
                        <barcode code="' . $server_type . '|' . $server_claim . '|' . CleanData("date") . '" size="3.2" type="QR" error="M" disableborder="1" class="barcode"  />
                        <div>' . $server_type . ' Badge</div>
                    </div>
                    <div class="logo">
                        <img src="data:image/jpeg;base64,' . $receipt[0]['logo'] . '" alt="">
                    </div>
                    <div class="id-details">
                        <h2>' . $server_type . ' Provisioning Badge</h2>
                        <h6 style="color: #0c0274 !important">' . $config_pre_append_link . '</h6>
                        <h6><b>Expiring Date:</b> ' . $date . '</h6>
                    </div>
                </div>
                ';
    }

    $html .= '</div>';

    $mpdf->WriteHTML($html);

    // $mpdf->Output();

    // Output a PDF file directly to the browser
    $mpdf->Output($filename . ' - ' . time() . '.pdf', 'D');
}
