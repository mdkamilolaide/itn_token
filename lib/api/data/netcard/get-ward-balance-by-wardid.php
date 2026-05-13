<?php


/*
    *  Runs e-Netcard 
    *
    *  Get a single Ward Balance using the waardid
    */
$nt = new Netcard\NetcardTrans();
$wardid = CleanData('wardid');

$data = $nt->CombinedBalanceForApp($wardid);
http_response_code(200);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard Allocation Mobile App Balances',
    'message' => 'success',
    'data' => $data
));
