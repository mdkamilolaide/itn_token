<?php

/*
*
*  EOLIN Mobilization  WARD Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Eolin();
$result = $nt->WardSummaryMobilization($lgaid = CleanData('lgaId'));
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'EOLIN Mobilization Ward Top Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
