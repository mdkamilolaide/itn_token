<?php

#
#   Get Geo level List
#
$gn = new System\General();
$data = $gn->GetGeoLevel();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
