<?php

#
#   Get Distribution Top Level Dashboard Summary
$ex = new Dashboard\Distribution();
$data = $ex->TopSummary();
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get Distribution Top Level Dashboard Summary',
    'message' => 'success',
    'data' => $data
));
