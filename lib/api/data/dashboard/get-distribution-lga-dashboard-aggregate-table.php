<?php

#
#   Get Distribution LGA Dashboard Aggregate Table
$ex = new Dashboard\Distribution();
$data = $ex->LgaAggregateByLocation();
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get Distribution Top Level Dashboard Summary',
    'message' => 'success',
    'data' => $data
));
