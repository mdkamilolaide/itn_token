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
$data = $dhb->IccListDp($id, $period_list, $startDate, $endDate);
//  Transform chart part (referred, attended)
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get All DP ICC Dashboard Data',
    'message' => 'success',
    'level' => 4,
    'data' => $data
));
