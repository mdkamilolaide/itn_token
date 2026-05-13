<?php

#
#
#   Mobilization Master
#
#   Get micro-palnning by LGA
$ex = new Mobilization\Mobilization();
$lgaid = CleanData("lgaid");
$total = $ex->ExcelCountMicroPosition($lgaid);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get count micro-palnning by LGA',
    'message' => 'success',
    'total' => $total
));
