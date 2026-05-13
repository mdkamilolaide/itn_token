<?php

#    Distribution - Get json export specific date distribution by DP level
$rp = new Smc\Reporting();

$periodid = CleanData("pid");       #  period ID
$geo_id = CleanData("gid");         #   Geo_level_id
$geo_level = CleanData("glv");      #   Geo-Level
$attended = CleanData('atd');       #   Attended filter

$filter = [
    'periodid' => $periodid,
    'geo_id' => $geo_id,
    'geo_level' => $geo_level,
    'attended' => $attended
];
echo $rp->ReferralBase($filter);
