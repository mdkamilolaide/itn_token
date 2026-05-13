<?php

#
#   Get Mobilier List
$gn = new System\General();
$wardid = CleanData("wardid");
$data = $gn->GetMobilizerList($wardid);
#
echo json_encode(array(
    'status_code' => 200,
    'message' => 'success',
    'data' => $data
));
