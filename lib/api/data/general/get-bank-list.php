<?php

#
#   Get Bank List
$gn = new System\General();
$data = $gn->GetBankList();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
