<?php


if (getPermission($user_priviledge, 'smc') >= 1) {
    $lgaid = CleanData('filterId');
    $pr = new Smc\DrugAdmin();
    $data = $pr->GetCohortWardLevel($lgaid);
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All Ward Level in an LGA with ID: $lgaid Cohort Tracking Data',
        'message' => 'success',
        'level' => 3,
        'data' => $data
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Cohort Tracking per Ward'
    );
    echo json_encode($json_data);
}
