<?php

#
#   Get State List
$gn = new System\General();
$data = $gn->GetStateList();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
