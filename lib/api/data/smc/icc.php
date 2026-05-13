<?php

if (getPermission($user_priviledge, 'smc') >= 1) {

    $dhb = new Smc\Icc();
    #
    #   Filter 
    $cddid = CleanData('cddid');
    $period_filter = CleanData('pid');

    $data1 = $dhb->GetIccIssueByCdd($cddid, $period_filter);
    $data2 = $dhb->GetIccReceiveByCdd($cddid, $period_filter);
    //  Transform chart 
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get CDD ICC Issued and Received by CDD',
        'message' => 'success',
        'data' => array($data1, $data2)
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Child Cohort Details'
    );
    echo json_encode($json_data);
}
