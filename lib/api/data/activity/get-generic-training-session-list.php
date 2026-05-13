<?php

#
#
#   Get generic Training Session list (training Session without privilege)
$tr = new Training\Training();

$training_id = CleanData("e");
$data = $tr->getGenericSession($training_id);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Generic Session List',
    'message' => 'success',
    'data' => $data
));
