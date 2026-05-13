<?php

#   Mobilization -  Get json export mobilization with date parameter LGA level
$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
$date = CleanData('date');
//
echo $rp->ListDateMobilizationByLga($date, $geo_level, $geo_level_id);
