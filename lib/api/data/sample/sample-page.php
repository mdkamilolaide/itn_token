<?php

$uc = new Users\UserManage();
$data = $uc->TableTestList();

echo json_encode(array('id' => 200, 'data' => $data, 'message' => 'success'));
