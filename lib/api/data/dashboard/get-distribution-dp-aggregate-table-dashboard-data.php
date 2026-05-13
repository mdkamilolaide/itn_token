<?php

#
#   Get Distribution WARD Dashboard Aggregate Table
$wardId = CleanData('wardId');
$ex = new Dashboard\Distribution();
$data = $ex->DpAggregateByLocation($wardId);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get DP Aggregate Table Data',
    'message' => 'success',
    'data' => $data
));
