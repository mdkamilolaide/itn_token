<?php
include_once('../lib/common.php');
include_once('../lib/mysql.min.php');
#
#   
#   Security check
if(true){
    CleanPendingToDestroyed(10);
}
###  
function CleanPendingToDestroyed($min = 10){
    $db = GetMysqlDatabase();
    $pdo = $db->Conn;
    try {
        // 
        $stmt = $pdo->prepare("UPDATE nc_netcard SET nc_netcard.location_value = 40, nc_netcard.device_serial = NULL,
	nc_netcard.`status` = CONCAT(nc_netcard.`status`, ' > ', '(REVERSED)')
	WHERE nc_netcard.location_value = 35 AND nc_netcard.updated >= (NOW() - INTERVAL 10 MINUTE)
	ORDER BY nc_netcard.ncid ASC LIMIT 10000");
        $stmt->execute();
        //
        $affectedB = $stmt->rowCount();
        // log success
		$logMessage = "[" . date('Y-m-d H:i:s') . "] - $affectedB Pending cleaned successfully  \n";
        file_put_contents('pending-cleaner-log.txt', $logMessage, FILE_APPEND);
		//
		echo $affectedB;
        return;
    } catch (PDOException $e) {
        // Log connection error
        file_put_contents('pending-cleaner-log.txt', "[" . date('Y-m-d H:i:s') . "] Database Connection Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return;
    }
}
?>