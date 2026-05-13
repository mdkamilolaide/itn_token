<?php

$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
//
echo $rp->ListDistributionByLga($geo_level, $geo_level_id);
