<?php

/*
     *  Runs e-Netcard Samples
     *
     *  Count Total active cards
     */

$nt = new Netcard\NetcardTrans();
$data = $nt->CountTotalNetcard();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Total Active Netcard existing',
    'data' => $data
));
