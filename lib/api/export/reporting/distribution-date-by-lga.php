<?php

#   Distribution -   Get json export specific date distribution by LGA level
$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
$date = CleanData('date');
//
echo $rp->ListDateDistributionByLga($date, $geo_level, $geo_level_id);
