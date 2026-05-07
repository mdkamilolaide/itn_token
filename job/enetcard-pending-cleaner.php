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
        // Begin transaction
        $pdo->beginTransaction();
        // 1. Select from table a where status = 'pending' and created > 10 minutes ago
        $stmt = $pdo->prepare("SELECT sn, device_id, download_id,netcard_list FROM nc_netcard_download WHERE `status` = 'pending' AND created <= (NOW() - INTERVAL $min MINUTE)");
        $stmt->execute();
        #
        $records = $stmt->fetchAll();
        if (empty($records)) {
            # No eligible records found in table a
            // Log message
            $logMessage = "[" . date('Y-m-d H:i:s') . "] No record found\n";
            file_put_contents('pending-cleaner-log.txt', $logMessage, FILE_APPEND);
            return;
        }
		$success_message = "";
        foreach ($records as $record) {
            // 2. Update table a set status = 'destroyed' where sn = $record['sn']
            $sn = $record['sn'];
            $device_id = $record['device_id'];
            $download_id = $record['download_id'];
            $json = $record['netcard_list'];
            $ncids = json_decode($json, true);
            if (!is_array($ncids)) {
                // log error message
                $logMessage = "[" . date('Y-m-d H:i:s') . "] Invalid JSON format for record ID: $sn\n Download ID: $download_id Device ID:$device_id Data: $json\n";
                file_put_contents('pending-cleaner-log.txt', $logMessage, FILE_APPEND);
                return;
            }
            $ncids = array_filter($ncids, fn($id) => is_int($id) || ctype_digit($id));
            if (empty($ncids)) {
                // log error message
                $logMessage = "[" . date('Y-m-d H:i:s') . "] No valid integer IDs found for record ID: $sn\n Download ID: $download_id Device ID:$device_id \n";
                file_put_contents('pending-cleaner-log.txt', $logMessage, FILE_APPEND);
                return;
            }
            $placeholders = implode(',', array_fill(0, count($ncids), '?'));
            $sql = "UPDATE nc_netcard SET location_value = 40, device_serial = NULL, updated = NOW() WHERE ncid IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ncids);
            //
            $affectedB = $stmt->rowCount();
            //
            if ($affectedB > 0) {
                // 3. Update table a
                $updateA = $pdo->prepare("UPDATE nc_netcard_download SET `status` = 'destroyed', `is_destroyed` = 1 updated = NOW() WHERE sn = ?");
                $updateA->execute([$sn]);
				// log success
				$success_message .= "[" . date('Y-m-d H:i:s') . "] Pending cleaned successfully - SN:$sn Download ID:$download_id Device Id: $device_id Record updated: $affectedB \n";
            } else {
                // Log error message
                $logMessage = "[" . date('Y-m-d H:i:s') . "] No updates performed on enetcard download table for record ID: $sn\n";
                file_put_contents('pending-cleaner-log.txt', $logMessage, FILE_APPEND);
                return;
            }
        }
        // Commit transaction
        $pdo->commit();
        // Log success message 
        file_put_contents('pending-cleaner-log.txt', $success_message, FILE_APPEND);
        return;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Log connection error
        file_put_contents('pending-cleaner-log.txt', "[" . date('Y-m-d H:i:s') . "] Database Connection Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return;
    }

}
?>