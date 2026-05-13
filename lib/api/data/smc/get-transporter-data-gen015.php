<?php

#
#   Get CMS Location Data
#
$master = new Smc\SmcMaster();

#   Get details
$data = $master->GetConveyors();
#
echo json_encode(array(
    'status_code' => 200,
    'dataset' => 'Get Conveyor Master List',
    'message' => 'success',
    'data' => $data
));
