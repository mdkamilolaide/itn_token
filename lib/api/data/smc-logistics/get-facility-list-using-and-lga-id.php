<?php

#
#   Get Facility List Using and LGA ID
#

$master = new Smc\Logistics();
$inputData = json_decode(file_get_contents('php://input'), true);

#   Get details
$data = $master->GetIssueByPeriod($inputData['periodId'], $inputData['lgaId']);
#
echo json_encode(array(
    'status_code' => 200,
    'dataset' => 'Get All Facility Data by LGA',
    'message' => 'success',
    'data' => $data
));
