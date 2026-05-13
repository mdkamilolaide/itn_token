<?php


#
#   Get generic Training list (training list without privilege)
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');

$tr = new Training\Training();
$data = $tr->getGenericTraining($geo_level, $geo_level_id);
http_response_code(200);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Generic Activity List',
    'message' => 'success',
    'data' => $data
));
