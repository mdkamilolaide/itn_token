<?php

/*
*
*  EOLIN Distribution  WARD Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Eolin();
$result = $nt->WardSummaryDistribution($lgaid = CleanData('lgaId'));
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'EOLIN Distribution Ward Top Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
