<?php

#    Distribution - Get json export date range distribution by LGA level
$rp = new Reporting\Reporting();

$geo_level = CleanData('gl');   //    [state | lga]
$geo_level_id = CleanData('glid');
$start_date = CleanData('startDate');
$end_date = CleanData('endDate');
//
echo $rp->ListDateRangeDistributionByLga($start_date, $end_date, $geo_level, $geo_level_id);
