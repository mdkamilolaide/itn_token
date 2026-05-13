<?php

#
#
#   Excel Export Count Mobilization
$loginid = CleanData('lgid');
#   Filtered by mobilized data
$mob_date = CleanData('mdt');
#   Filtered by Geo-Level
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
$ex = new Mobilization\Mobilization();
$total = $ex->ExcelCountMobilization($loginid, $mob_date, $geo_level, $geo_level_id);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Excel Export Count Mobilization',
    'message' => 'success',
    'total' => $total
));
