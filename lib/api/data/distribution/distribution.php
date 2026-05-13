<?php

#
#   Distribution 
#
#   Get DP Locations details with DP ID
$ex = new Distribution\Distribution();
$wardid = CleanData('wardid');
$data = $ex->GetDpLocationMaster($wardid);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get Distribution Point List for Badge Printing',
    'message' => 'success',
    'data' => $data
));
