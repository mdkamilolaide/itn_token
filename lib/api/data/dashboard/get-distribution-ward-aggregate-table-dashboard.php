<?php

#
#   Get Distribution WARD Dashboard Aggregate Table
$lgaid = CleanData('lgaId');
$ex = new Dashboard\Distribution();
$data = $ex->WardAggregateByLocation($lgaid);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get Ward Aggregate Table Data',
    'message' => 'success',
    'data' => $data
));
