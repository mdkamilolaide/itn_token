<?php

#
$wardid = CleanData('wardid');
$nt = new Netcard\NetcardTrans();
$data = $nt->GetOfflineMobilizerBalance($wardid);

http_response_code(200);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Offline Device Mobilizers Balance List',
    'message' => 'success',
    'data' => $data
));
