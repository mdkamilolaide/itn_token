<?php

/*
     *  Runs e-Netcard Samples
     *
     *  List count of Location
     */
#  All Netcard count at State, LGA, and Ward
$nt = new Netcard\NetcardTrans();
$dd = $nt->GetCountByLocation();
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'e-Netcard Location List',
    'message' => 'success',
    'data' => $dd
));
