<?php

/*
     *  Runs e-Netcard Samples
     *
     *  Bulk e-Netcard Allocation Ward to mobilizer
     */
$nt = new Netcard\NetcardTrans();

$inputData = json_decode(file_get_contents('php://input'), true);

# ['total'=>$total, 'wardid'=>$wardid, 'mobilizerid'=>$mobilizerid, 'userid'=>$userid]
//  $bulk_data = [array('total'=>10, 'wardid'=>1, 'mobilizerid'=>3, 'userid'=>2),
//  array('total'=>10, 'wardid'=>1, 'mobilizerid'=>4, 'userid'=>2),
//  array('total'=>10, 'wardid'=>1, 'mobilizerid'=>5, 'userid'=>2)];
$mobid = "";
for ($i = 0; $i < count($inputData); $i++) {
    # code...
    $mobid .= "," . $inputData[$i]['mobilizerid'];
}
$mobid = substr($mobid, 1);

$bulk_data = $inputData;
#
$total = $nt->BulkAllocationTransfer($bulk_data);
//User Log Activity
$result = "success";
logUserActivity($userid = $current_userid, $platform, $module = "enetcard", $description = "$total e-Netcard was Successfully Allocated to Household Mobilizers (" . $mobid . ") by user with the Login ID: " . $current_loginid . " :", $result);

echo json_encode(array(
    'result_code' => 200,
    'message' => "e-Netcard Successfully Allocated to HHM",
    'total' => $total
));
