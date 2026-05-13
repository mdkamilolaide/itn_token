<?php

#
#
#   Export Mobilization Data
$loginid = CleanData('lgid');
#   Filtered by mobilized data
$mob_date = CleanData('mdt');
#   Filtered by Geo-Level
$geo_level = CleanData('gl');
$geo_level_id = CleanData('glid');
#
$ex = new Mobilization\Mobilization();
echo $ex->ExcelGetMobilization($loginid, $mob_date, $geo_level, $geo_level_id);
