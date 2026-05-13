<?php

$pr = new Smc\DrugAdmin();
#   Filters
$periodid = CleanData("pid");       #  period ID
$geo_id = CleanData("gid");         #   Geo_level_id
$geo_level = CleanData("glv");      #   Geo-Level
$attended = CleanData('atd');       #   Attended filter
#
$data = $pr->GetReferralCount($periodid, $geo_id, $geo_level, $attended);

#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get State Daily mapping data',
    'message' => 'success',
    'data' => $data
));
