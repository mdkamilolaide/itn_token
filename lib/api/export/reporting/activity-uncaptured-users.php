<?php

#   Activity Management - Get json export for Uncaptured Users
$rp = new Reporting\Reporting();
$trainingId = CleanData('tid');
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
//
echo $rp->ListUncapturedUsers($trainingId, $geo_level, $geo_level_id);
