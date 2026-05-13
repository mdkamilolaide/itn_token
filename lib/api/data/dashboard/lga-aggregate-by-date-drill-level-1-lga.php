<?php

$dhb = new Dashboard\Mobilization();
$date = CleanData("date");
$data = $dhb->LgaAggregateByDate($date);
//  Transform chart
$label = DataLib::Column($data, 'title');
$household = DataLib::Column($data, 'households');
$netcards = DataLib::Column($data, 'netcards');
$family_size = DataLib::Column($data, 'family_size');
$chart_data = array(
    array(
        array('name' => 'Household', 'data' => $household),
        array('name' => 'e-Netcards', 'data' => $netcards),
        array('name' => 'Family size', 'data' => $family_size)
    ),
    $label
);
echo json_encode(array('table' => $data, 'chart' => $chart_data, 'level' => 1));
