<?php

#
#   Get total active and inavtive users
$us = new Users\UserManage();
$data = $us->DashCountActive();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
