<?php


if (getPermission($user_priviledge, 'smc') >= 1) {
    $pr = new Smc\DrugAdmin();
    $data = $pr->GetCohortLgaLevel();
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All LGA Cohort Tracking Data',
        'message' => 'success',
        'level' => 2,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Cohort Tracking per LGA'
    );
    echo json_encode($json_data);
}
