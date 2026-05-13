<?php

#
#   Get DP Dataset
#
$ex = new Mobilization\MapData();
#
$dpid = CleanData("dpid");      #   compulsory field to get dp data
$wardid = CleanData("wardid");                  #   required data as well
$start_date = CleanData("s_date");
$end_date = CleanData("e_date");

#
$data = $ex->GetDpData($wardid, $dpid, $start_date, $end_date);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get DP mapping data',
    'message' => 'success',
    'data' => $data
));
