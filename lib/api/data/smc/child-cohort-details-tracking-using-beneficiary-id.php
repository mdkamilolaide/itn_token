<?php


if (getPermission($user_priviledge, 'smc') >= 1) {
    $beneficiary_id = CleanData('bid');
    $pr = new Smc\DrugAdmin();
    $data = $pr->GetCohortChildDetails($beneficiary_id);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get a Child Cohort Tracking Details',
        'message' => 'success',
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Child Cohort Details'
    );
    echo json_encode($json_data);
}
