<?php

#
#   Mobilizer Data
#
$ex = new Mobilization\MapData();
#
// $mobilizerid = "CGF00003";      #   compulsory field to get all mobilizer
// $start_date = "2022-05-16";
// $end_date = "2022-05-17";
// $wardid = "1";        

$mobilizerid = CleanData("mob");      #   compulsory field to get all mobilizer
$start_date = CleanData("s_date");
$end_date = CleanData("e_date");
$wardid = CleanData("wardid");
#
$data = $ex->GetMobilizationData($wardid, $mobilizerid, $start_date, $end_date);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get mobilizer mapping data',
    'message' => 'success',
    'data' => $data
));
