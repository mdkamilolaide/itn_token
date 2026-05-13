<?php

$dhb = new Smc\Dashboard();
#
#   Filter 
$id = (int) CleanData('filterId');
$period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
$startDate = CleanData('sdate');
$endDate = CleanData('edate');
$endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

#implement list Ward data [id, title, total, referred, attended]
$data = $dhb->ReferralListWard($id, $period_list, $startDate, $endDate);
//  Transform chart part (referred, attended)
$label = DataLib::Column($data, 'title');
$referred = DataLib::Column($data, 'referred');
$attended = DataLib::Column($data, 'attended');
$chart_data = array(
    array(
        array('name' => 'Referred', 'data' => $referred),
        array('name' => 'Attended', 'data' => $attended)
    ),
    $label
);
$allData = array('table' => $data, 'chart' => $chart_data);
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get All Ward Referral Dashboard Data',
    'message' => 'success',
    'level' => 3,
    'data' => $allData
));
