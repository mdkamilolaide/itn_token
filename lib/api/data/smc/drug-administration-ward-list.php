<?php

$dhb = new Smc\Dashboard();
#
#   Filter 
$id = (int) CleanData('filterId');
$period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
$startDate = CleanData('sdate');
$endDate = CleanData('edate');
$endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

#implement list Ward data [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
$data = $dhb->DrugAdminListWard($id, $period_list, $startDate, $endDate);
//  Transform chart part (eligible, spaq1 & spaq2)
$label = DataLib::Column($data, 'title');
$eligible = DataLib::Column($data, 'eligible');
$spaq1 = DataLib::Column($data, 'spaq1');
$spaq2 = DataLib::Column($data, 'spaq2');
$chart_data = array(
    array(
        array('name' => 'SPAQ 1', 'data' => $spaq1),
        array('name' => 'SPAQ 2', 'data' => $spaq2)
    ),
    $label
);
$allData = array('table' => $data, 'chart' => $chart_data);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get All Ward Drug Administration Dashboard Data in an LGA',
    'message' => 'success',
    'level' => 3,
    'data' => $allData
));
