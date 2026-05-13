<?php

#
#   Get Ward List
$lgaid = CleanData('e');
$gn = new System\General();
$data = $gn->GetWardList($lgaid);
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
