<?php

/**
 * Check User Priviledge 
 * For Netcard Allocation
 */
#
$wardid = CleanData('wardid');
$nt = new Netcard\NetcardTrans();
// VErsion 1
$data = $nt->GetCombinedMobilizerBalance($wardid);

//Version 2
// $data = $nt->GetcAllMobilizerBalance($wardid);


http_response_code(200);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Combined Mobilizers Balance List',
    'message' => 'success',
    'data' => $data
));
