<?php

#
#   Get count total Training
$us = new Training\Training();
$data = $us->DashCountTraining();
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Count Total Training',
    'message' => 'success',
    'data' => $data
));
