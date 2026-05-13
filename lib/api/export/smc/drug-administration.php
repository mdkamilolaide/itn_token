<?php

#    Distribution - Get json export specific date distribution by DP level
$rp = new Smc\Reporting();

$periodid = CleanData("pid");     #  period ID
$is_eligible = CleanData("ise");    #  The child is eligible must be yes
$is_redose = CleanData("isr");    #  Redose must be yes
$reg_date = CleanData("rda");    #   Registration date
$geo_id = CleanData("gid");    #   Geo_level_id
$geo_level = CleanData("glv");    #   Geo-Level
$beneficiary_id = CleanData("bid");    #   Beneficiary ID

$filter = [
    'periodid' => $periodid,
    'is_eligible' => $is_eligible,
    'is_redose' => $is_redose,
    'reg_date' => $reg_date,
    'geo_id' => $geo_id,
    'geo_level' => $geo_level,
    'beneficiaryid' => $beneficiary_id
];

echo $rp->DrugAdminBase($filter);
