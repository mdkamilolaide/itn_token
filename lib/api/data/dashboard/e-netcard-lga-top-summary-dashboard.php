<?php

/*
*
*  e-Netcard LGA Top Summary Dashboard
*/
#  All NetcardTop Summary
$nt = new Dashboard\Enetcard();
$result = $nt->TopLgaSummary();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard LGA Top Summary Dashboard',
    'message' => 'success',
    'data' => $result
));
