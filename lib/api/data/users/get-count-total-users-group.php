<?php

#
#   Get count total users group
$us = new Users\UserManage();
$data = $us->DashCountTotalGroup();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
