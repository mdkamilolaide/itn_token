<?php

/*
*
*  e-Netcard HHM in a Ward Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Enetcard();
$result = $nt->TopMobilizerSummary(CleanData('wardId'));
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard Top Mobilizer in a Ward Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
