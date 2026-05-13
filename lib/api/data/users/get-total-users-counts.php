<?php

#
#   Get Total users counts
$us = new Users\UserManage();
$data = $us->DashCountUser();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'total_user' => $data
));
