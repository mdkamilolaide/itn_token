<?php

#
#   Sample Get System Default List
$gn = new System\General();
$data = $gn->GetDefaultSettings();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
