<?php

#
#
#   Dashboard count Session
$us = new Training\Training();
$data = $us->DashCountSession();
#
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Count Session',
    'message' => 'success',
    'data' => $data
));
