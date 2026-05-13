<?php

#
#   Get LGA daily Dataset
#
$ex = new Mobilization\MapData();
#
$lgaid = CleanData("lgaid");                  #   required data as well
$date = CleanData("s_date");

#
$data = $ex->GetLgaData($lgaid, $date);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get LGA Daily mapping data',
    'message' => 'success',
    'data' => $data
));
