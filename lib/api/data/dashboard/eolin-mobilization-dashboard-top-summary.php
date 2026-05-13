<?php

/*
*
*  EOLIN Mobilization Dashboard Top Summary
*/
#  All NetcardTop Summary
$nt = new Dashboard\Eolin();
$dd = $nt->TopSummaryMobilization();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'EOLIN Dashboard Top Summary',
    'message' => 'success',
    'data' => $dd
));
