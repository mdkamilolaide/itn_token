<?php

/*
*
*  EOLIN Distribution DP in a Ward Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Eolin();
$result = $nt->DpSummaryDistribution(CleanData('wardId'));
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'EOLIN Distribution Top DP in a Ward Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
