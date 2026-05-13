<?php

#   Online reverse transaction history
$nt = new Netcard\NetcardTrans();

$wardid = CleanData('wardid');
$data = $nt->GetAllocationDirectReverseList($ward);
#
http_response_code(200);
echo json_encode(array(
    'result_code' => 200,
    'message' => 'e-Netcard Online Reverse Transaction List',
    'data' => $data
));
