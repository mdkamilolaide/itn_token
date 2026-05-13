<?php

#
#   Get users count by group
$us = new Users\UserManage();
$data = $us->DashCountUserGroup();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
