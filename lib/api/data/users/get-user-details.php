<?php

#   Get user informations (Details)
#   Init User manage class
$us = new Users\UserManage();
$userid = CleanData("e");
#   Get User's Base info returns array result single row
$base = $us->GetUserBaseInfo($userid);
#   Get User's Finance returns array result single row
$finance = $us->GetUserFinance($userid);
#   Get User's Identity returns array result single row
$identity = $us->GetUserIdentity($userid);
#   Get User's role structure returns array result single row
$role = $us->GetUserRoleStructure($userid);
#
$data = array(
    "result_code" => 201,
    "message" => "success",
    "base" => $base,
    "finance" => $finance,
    "identity" => $identity,
    "role" => $role
);

#
echo json_encode($data);
