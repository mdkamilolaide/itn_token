<?php

$dhb = new Dashboard\Mobilization();
$data = $dhb->TopSummary();
echo json_encode(array('info' => 'Top Summary dataset', 'data' => $data));
