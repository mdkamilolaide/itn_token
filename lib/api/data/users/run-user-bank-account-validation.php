<?php

#
$usr = new Users\UserManage();
#   
$userid = CleanData('userid');
$data = $usr->RunBankVerification($userid);
#
#
echo json_encode(array(
    'result_code' => 200,
    'message' => 'success',
    'data' => $data
));
