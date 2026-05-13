<?php

#
#
#   Export participant list
$training_id = CleanData('id');
#
$ex = new Training\Training();
echo $ex->ExcelGetParticipantList($training_id);
