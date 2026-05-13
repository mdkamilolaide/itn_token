<?php

/*
*
*  e-Netcard WARD Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Enetcard();
$result = $nt->TopWardSummary($lgaid = CleanData('lgaId'));
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard Ward Top Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
