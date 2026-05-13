<?php

if (getPermission($user_priviledge, 'smc') >= 1) {

    #
    #
    #   Excel Export Count
    $periodid = CleanData("pid");     #  period ID
    $is_eligible = CleanData("ise");    #  The child is eligible must be yes
    $is_redose = CleanData("isr");    #  Redose must be yes
    $reg_date = CleanData("rda");    #   Registration date
    $geo_id = CleanData("gid");    #   Geo_level_id
    $geo_level = CleanData("glv");    #   Geo-Level
    $beneficiary_id = CleanData("bid");    #   Beneficiary ID

    $filter = [
        'periodid' => $periodid,
        'is_eligible' => $is_eligible,
        'is_redose' => $is_redose,
        'reg_date' => $reg_date,
        'geo_id' => $geo_id,
        'geo_level' => $geo_level,
        'beneficiaryid' => $beneficiary_id
    ];

    $ex = new Smc\Reporting();
    $total = $ex->CountDrugAdminBase($filter);
    #
    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Excel Export Count Drug Administration',
        'message' => 'success',
        'total' => $total
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Drug Administration'
    );
    echo json_encode($json_data);
}
