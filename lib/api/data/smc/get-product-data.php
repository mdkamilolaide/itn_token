<?php

#
#   Get Product Data
#

$master = new Smc\SmcMaster();
$lgaId = json_decode(file_get_contents('php://input'), true);

#   Get details
$data = $master->GetCommodity();
#
echo json_encode(array(
    'status_code' => 200,
    'dataset' => 'Get Product List',
    'message' => 'success',
    'data' => $data
));
