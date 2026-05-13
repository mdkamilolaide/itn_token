<?php

#
#
#
#   Filtered by mobilized date
$mob_date = CleanData('mdt');
#   Filtered by Geo-Level
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
$ex = new Mobilization\Mobilization();
$total = $ex->DashSummary($mob_date, $geo_level, $geo_level_id);
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Dashboard summary',
    'message' => 'success',
    'total' => $total
));
