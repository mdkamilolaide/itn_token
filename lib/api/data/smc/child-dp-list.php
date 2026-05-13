<?php

if (getPermission($user_priviledge, 'smc') >= 1) {

    $dhb = new Smc\Dashboard();
    #
    #   Filter 
    $id = (int) CleanData('filterId');
    $period_list = CleanData('pid');  // sample 1,2,3 or 1,3 not in use here
    $startDate = CleanData('sdate');
    $endDate = CleanData('edate');
    $endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

    #implement list Ward data [id, title, total, male, female]
    $data = $dhb->ChildListDpSummary($id, $startDate, $endDate);
    //  Transform chart 
    $label = DataLib::Column($data, 'title');
    $male = DataLib::Column($data, 'male');
    $female = DataLib::Column($data, 'female');
    $chart_data = array(
        array(
            array(
                'name' => 'male',
                'data' => $male
            ),
            array('name' => 'female', 'data' => $female)
        ),
        $label
    );
    $allData = array('table' => $data, 'chart' => $chart_data);

    echo json_encode(array(
        'result_code' => 200,
        'dataset' => 'Get All DP Child Dashboard Data in a Ward',
        'message' => 'success',
        'level' => 4,
        'data' => $allData
    ));
} else {
    $json_data = array(
        "result_code" => 400,
        'message' => 'You don\'t have permission to Load Cohort Tracking per Ward Level'
    );
    echo json_encode($json_data);
}
