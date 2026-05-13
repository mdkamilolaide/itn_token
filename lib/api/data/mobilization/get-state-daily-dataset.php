<?php

#
#   Get State daily Dataset
#
$ex = new Mobilization\MapData();
#
$stateid = CleanData("stateid");                  #   required data as well
$date = CleanData("s_date");

#
$data = $ex->GetStateData($stateid, $date);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get State Daily mapping data',
    'message' => 'success',
    'data' => $data
));
