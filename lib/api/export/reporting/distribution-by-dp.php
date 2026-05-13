<?php

#   Distribution -  Get json export Distribution DP level
$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
//
echo $rp->ListDistributionByDp($geo_level, $geo_level_id);
