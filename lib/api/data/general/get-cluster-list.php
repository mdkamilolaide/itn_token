<?php

#
#   Get Cluster List
$lgaid = CleanData('e');
$gn = new System\General();
$data = $gn->GetClusterList($lgaid);
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
