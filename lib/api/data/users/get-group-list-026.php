<?php

#
#   Get group list
$us = new Users\UserManage();
#
$data = $us->GetUserGroupList();
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
