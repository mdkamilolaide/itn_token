<?php

#
#
#   Get micro-palnning by LGA
#
$ex = new Mobilization\Mobilization();
$lgaid = CleanData("lgaid");
$data = $ex->GetMicroPosition($lgaid);

#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get micro-palnning by LGA',
    'message' => 'success',
    'data' => $data
));
