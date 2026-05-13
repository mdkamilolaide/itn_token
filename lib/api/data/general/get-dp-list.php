<?php

#
#   Get Dp List
$gn = new System\General();
$wardid = CleanData('wardid');
$data = $gn->GetDpList($wardid);
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
