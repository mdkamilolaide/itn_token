<?php

#    Distribution - Get json export specific date distribution by DP level
$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
$start_date = CleanData('startDate');
$end_date = CleanData('endDate');
//
echo $rp->ListDateRangeDistributionByDp($start_date, $end_date, $geo_level, $geo_level_id);
