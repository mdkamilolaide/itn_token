<?php

#    Distribution - Get json export specific date distribution by DP level
$rp = new Smc\Reporting();

$periodid = CleanData("pid");       #  period ID
$geo_id = CleanData("gid");         #   Geo_level_id
$geo_level = CleanData("glv");      #   Geo-Level

$filter = ['periodid' => $periodid, 'geo_id' => $geo_id, 'geo_level' => $geo_level];

echo $rp->IccCddBase($filter);
