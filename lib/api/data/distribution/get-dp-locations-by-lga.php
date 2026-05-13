<?php

#
#   Distribution 
#
#   Get DP Locations details with DP ID
$ex = new Distribution\Distribution();
$lgaid = CleanData('lgaid');
$data = $ex->GetDpLocationMasterByLga($lgaid);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get Distribution Point List for Badge Printing',
    'message' => 'success',
    'data' => $data
));
