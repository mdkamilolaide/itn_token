<?php

#
#
#   Get Excel count micro-palnning by LGA
#
$ex = new Mobilization\Mobilization();
$lgaid = CleanData("lgaid");
$data = $ex->ExcelGetMicroPosition($lgaid);
#
echo  $data;
