<?php

if (getPermission($user_priviledge, 'smc') >= 1) {
    // $dpid, $cdd_id, $drug, $qty, $user_id
    $dhb = new Smc\Icc();
    #
    #   Filter 
    $dpid = CleanData('dpid');
    $cdd_id = CleanData('cddid');
    $drug = CleanData('drug');
    $qty = CleanData('qty');
    $user_id = CleanData('user_id');
    $issueId = CleanData('issueId');

    // $period_filter = CleanData('pid');

    $data = $dhb->UnlockBalance($issueId, $dpid, $cdd_id, $drug, $qty, $user_id);
    //  Transform chart 
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => $qty . ' ' . $drug . ' Unlocked',
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
