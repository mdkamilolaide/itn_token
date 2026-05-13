<?php


if (getPermission($user_priviledge, 'smc') >= 1) {

    $pr = new Smc\Period();
    #
    $data = $pr->GetList();

    #On User Creation
    echo json_encode(array(
        'result_code' => 200,
        'message' => 'success',
        'data' => $data,
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Create Period/Visit'
    );
    echo json_encode($json_data);
}
