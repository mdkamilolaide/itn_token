<?php

#
#   Get LGA Distribution per date aggregate Dashboard Data

$date = CleanData('date');
$ex = new Dashboard\Distribution();
$data = $ex->LgaAggregateByDate($date);
#

$label = DataLib::Column($data, 'title');
$household = DataLib::Column($data, 'household_redeemed');
$netcards = DataLib::Column($data, 'net_redeemed');
$family_size = DataLib::Column($data, 'familysize_redeemed');

$chart_data = array(
    array(
        array('name' => 'Households', 'data' => $household),
        array('name' => 'Net Redeemed', 'data' => $netcards),
        array('name' => 'Family size', 'data' => $family_size)
    ),
    $label
);
echo json_encode(
    array(
        'result_code' => 200,
        'dataset' => 'Get LGA Distribution per date aggregate',
        'message' => 'success',
        'data' => $data,
        'chart' => $chart_data
    )
);
return;
