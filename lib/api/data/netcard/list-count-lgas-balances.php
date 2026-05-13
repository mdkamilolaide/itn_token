<?php

/*
     *  Runs e-Netcard Samples
     *
     *  List count LGAs balances
     */
$nt = new Netcard\NetcardTrans();
$dd = $nt->GetCountLgaList();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard LGA List',
    'message' => 'success',
    'data' => $dd
));
