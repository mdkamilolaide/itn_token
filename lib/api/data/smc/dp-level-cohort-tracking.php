<?php


if (getPermission($user_priviledge, 'smc') >= 1) {
    $wardid = CleanData('filterId');
    $pr = new Smc\DrugAdmin();
    $data = $pr->GetCohortDpLevel($wardid);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All DP Level Cohort Tracking in the Ward with ID: $wardid Cohort Tracking Data',
        'message' => 'success',
        'level' => 4,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Cohort Tracking per Ward'
    );
    echo json_encode($json_data);
}
