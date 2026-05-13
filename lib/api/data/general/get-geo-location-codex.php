<?php

#
#   Get Goe location codex
#
$mo = new System\General();
#   parameter options ['dp','ward','lga','state'] default is dp (i.e without any parameter)
#   Get details
$data = $mo->GetGeoLocationCodex("all");
#
echo json_encode(array(
    'status_code' => 200,
    'dataset' => 'Get Geo Location Codex',
    'message' => 'success',
    'data' => $data
));
