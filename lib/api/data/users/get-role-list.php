<?php

#
#   Get role list
#
$usr = new Users\UserManage();
#   users list
$data = $usr->GetRoleList($priority);
// $data = $usr->GetRoleList(3);
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
