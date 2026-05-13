<?php

if (getPermission($user_priviledge, 'smc') >= 1) {

    $dhb = new Smc\Icc();
    #
    #   Filter 
    $cddid = CleanData('cddid');
    $period_filter = CleanData('pid');

    $data1 = $dhb->GetIccFlowDetailByCdd($cddid);
    //  Transform chart 
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get Detail Icc Isued and reconcile',
        'message' => 'success',
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to view ICC Details'
    );
    echo json_encode($json_data);
}
