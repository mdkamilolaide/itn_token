<?php

#
#   Get receipt header
$ex = new Mobilization\Mobilization();
$data = $ex->GetReceiptHeader();
#
http_response_code(200);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get Receipt Header',
    'message' => 'success',
    'data' => $data
));
