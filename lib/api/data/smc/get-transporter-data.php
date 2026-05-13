<?php

#
#   Get CMS Location Data
#
$master = new Smc\SmcMaster();

#   Get details
$data = $master->GetTransporter();
#
echo json_encode(array(
    'status_code' => 200,
    'dataset' => 'Transporter Master List',
    'message' => 'success',
    'data' => $data
));
