<?php
include_once('../lib/common.php');
include_once('../lib/mysql.min.php');

#
#   
#   Security check
if(true){
    CleanIccPendingToDestroyed(10);
}
#
#
#
function CleanIccPendingToDestroyed($min = 10){
    $db = GetMysqlDatabase();
    $pdo = $db->Conn;
    $logMessage = "--[ SMC ICC ]--\n";
    try {
        // Begin transaction
        $pdo->beginTransaction();
        // 1. Select from table a where status = 'pending' and created > 10 minutes ago
        $stmt = $pdo->prepare("SELECT issue_id, dpid, drug, qty, `status`, status_code FROM smc_icc_collection WHERE `status_code` = 20 AND created <= (NOW() - INTERVAL $min MINUTE)");
        $stmt->execute();
        #
        $records = $stmt->fetchAll();
        // echo  json_encode($records);
        // return;
        // Check if any records were found
        if (empty($records)) {
            # No eligible records found in table a
            // Log message
            $logMessage .= "[" . date('Y-m-d H:i:s') . "] No record found\n";
            file_put_contents('pending-cleaner-log.txt', $logMessage, FILE_APPEND);
            return;
        }
		$success_message = "";
        foreach ($records as $record) {
            // 2. Update table a set status = 'destroyed' where sn = $record['sn']
            
            $issue_id = $record['issue_id'];
            $dpid = $record['dpid'];
            $drug = $record['drug'];
            $qty = $record['qty'];
            $status = $record['status'];
            $status_code = $record['status_code'];
            //echo "Issue ID: $issue_id, DP ID:$dpid, Drug: $drug, Qty: $qty Record: $status, Status Code: $status_code <br>";
            #
            $sql = "UPDATE smc_icc_collection SET `status` = 'issued', `status_code` = 10, updated = NOW() WHERE issue_id = ?";
            $stmt2 = $pdo->prepare($sql);
            $stmt2->execute([$issue_id]);
            //
            $affectedB = $stmt2->rowCount();
            //
            if ($affectedB > 0) {
				// log success
				$success_message .= "[" . date('Y-m-d H:i:s') . "] Pending cleaned successfully - Issue ID: $issue_id, DP ID:$dpid, Drug: $drug, Qty: $qty Record updated: $affectedB \n";
            } else {
                // Log error message
                $logMessage .= "[" . date('Y-m-d H:i:s') . "] No updates performed on ICC Pending ID: $issue_id\n";
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
        file_put_contents('pending-cleaner-log.txt', $logMessage."[" . date('Y-m-d H:i:s') . "] Database Connection Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return;
    }
}
?>