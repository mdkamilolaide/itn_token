<?php

#
#
#   Dashboard count Training active & inactive
$us = new Training\Training();
$data = $us->DashCountActive();
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Count Active/inactive training',
    'message' => 'success',
    'data' => $data
));
