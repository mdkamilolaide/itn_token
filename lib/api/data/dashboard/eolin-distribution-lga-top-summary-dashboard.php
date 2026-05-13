<?php

/*
*
*  EOLIN Distribution  LGA Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Eolin();
$result = $nt->LgaSummaryDistribution();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'EOLIN Mobilization LGA Top Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
