<?php

#   Mobilization - Get json export for Overall Mobilization by LGA
$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
//
echo $rp->ListMobilizationByLga($geo_level, $geo_level_id);
