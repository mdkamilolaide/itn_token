<?php

#
#   Get count by geo level
$us = new Users\UserManage();
$data = $us->DashCountGeoLevel();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
