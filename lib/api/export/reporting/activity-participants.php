<?php

#
#   Export Participants List
$rp = new Reporting\Reporting();
#   Filtered by Geo-Level
$trainingId = CleanData('tid');
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
//
echo $rp->ListParticipants($trainingId, $geo_level, $geo_level_id);
