<?php

#
#   Get WARD daily Dataset
#
$ex = new Mobilization\MapData();
#
$wardid = CleanData("wardid");                  #   required data as well
$date = CleanData("s_date");

#
$data = $ex->GetWardData($wardid, $date);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get Ward daily mapping data',
    'message' => 'success',
    'data' => $data
));
