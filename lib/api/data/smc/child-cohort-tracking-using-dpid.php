<?php


if (getPermission($user_priviledge, 'smc') >= 1) {
    $dpid = CleanData('filterId');
    $pr = new Smc\DrugAdmin();
    $data = $pr->GetCohortChildLevel($dpid);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All Child Cohort Tracking in the DP with ID: $dpid  Data',
        'message' => 'success',
        'level' => 5,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Cohort Tracking per Ward'
    );
    echo json_encode($json_data);
}
