<?php

#
#   Get map data test all  Dataset
#
$ex = new Mobilization\MapData();
#
$hhid = "41";
#
$data = $ex->GetTestAllData();
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get test all mapping data',
    'message' => 'success',
    'data' => $data
));
