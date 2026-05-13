<?php

/*
     *  Runs e-Netcard 
     *
     *  List count Mobilizerd balances
     */
$nt = new Netcard\NetcardTrans();
$wardid = CleanData('wardid');
$data = $nt->GetMobilizersList($wardid);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard Mobilizers List',
    'message' => 'success',
    'data' => $data
));
