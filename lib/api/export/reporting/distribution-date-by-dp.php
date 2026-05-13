<?php

#   Distribution -   Get json export specific date distribution by DP level
$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
$date = CleanData('date');
//
echo $rp->ListDateDistributionByDp($date, $geo_level, $geo_level_id);
