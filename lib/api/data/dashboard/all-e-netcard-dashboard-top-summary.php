<?php

/*
*
*  e-Necard Dashboard Top Summary
*/
#  All NetcardTop Summary
$nt = new Dashboard\Enetcard();
$dd = $nt->TopSummary();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard Dashboard Top Summary',
    'message' => 'success',
    'data' => $dd
));
