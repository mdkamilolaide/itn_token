<?php

if (getPermission($user_priviledge, 'smc') >= 1) {

    #
    #
    #   Excel Export Count Drug Refferal
    $periodid = CleanData("pid");       #  period ID
    $geo_id = CleanData("gid");         #   Geo_level_id
    $geo_level = CleanData("glv");      #   Geo-Level

    $filter = ['periodid' => $periodid, 'geo_id' => $geo_id, 'geo_level' => $geo_level];

    $ex = new Smc\Reporting();
    $total = $ex->CountIccCddBase($filter);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Excel Export Count ICC',
        'message' => 'success',
        'total' => $total
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load ICC Records'
    );
    echo json_encode($json_data);
}
