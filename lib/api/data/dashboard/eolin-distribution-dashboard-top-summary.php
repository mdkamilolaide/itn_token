<?php

/*
*
*  e-Netcard LGA Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Eolin();
$result = $nt->TopSummaryDistribution();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'EOLIN Diatribution LGA Top Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
