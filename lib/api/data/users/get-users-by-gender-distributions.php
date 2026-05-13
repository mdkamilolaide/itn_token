<?php

#
#   Get users by gender distributions
$us = new Users\UserManage();
$data = $us->DashCountGender();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
