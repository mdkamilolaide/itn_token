<?php

/*
     *  Runs e-Netcard Samples
     *
     *  List count Ward balances
     */
$nt = new Netcard\NetcardTrans();
$lgaid = CleanData("lgaid");
$dd = $nt->GetCountWardList($lgaid);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard Ward List',
    'message' => 'success',
    'data' => $dd
));
