<?php

$dhb = new Smc\Dashboard();
#
#   Filter 
$period_list = CleanData('pid');  // period id if any sample 1,2,3 or 1,3 
$startDate = CleanData('sdate');
$endDate = CleanData('edate');
$endDate = $endDate === '' && $startDate !== '' ? $startDate : $endDate;

#implement list Ward data [id, title, total, referred, attended]
$data = $dhb->IccListLga($period_list, $startDate, $endDate);
//  Transform chart part (referred, attended)
echo json_encode(array(
    'result_code' => 200,
    'dataset' => 'Get All LGA ICC Dashboard Data',
    'message' => 'success',
    'level' => 2,
    'data' => $data
));
