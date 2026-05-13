<?php

#
#   Get CMS Location Data
#
$master = new Smc\SmcMaster();

#   Get details
$data = $master->GetCmsLocations();
#
echo json_encode(array(
    'status_code' => 200,
    'dataset' => 'CMS Master List',
    'message' => 'success',
    'data' => $data
));
