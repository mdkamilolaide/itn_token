<?php

#
#   Get LGA List
$stateid = json_decode(file_get_contents('php://input'), true);
$gn = new System\General();
$data = $gn->GetLgaList($stateid);
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
