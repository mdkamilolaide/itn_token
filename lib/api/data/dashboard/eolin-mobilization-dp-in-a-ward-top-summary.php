<?php

/*
*
*  EOLIN Mobilization DP in a Ward Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Eolin();
$result = $nt->DpSummaryMobilization(CleanData('wardId'));
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'EOLIN Mobilization Top DP in a Ward Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
