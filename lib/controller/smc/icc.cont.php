<?php
namespace Smc;
use DbHelper;
use System\Fcm;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
include_once('lib/autoload.php');
class Icc {
    private $db;
    private $pdo;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
        $this->pdo = $this->db->Conn;
    }
    
    #
    #
    #   [uid, dpid, issuer_id, cdd_lead_id, periodid, issue_date, issue_day, issue_drug, drug_qty, device_serial, app_version]
    public function BulkIccIssue($bulk_data){
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $retarray = array();
            $this->pdo->beginTransaction();
                //  insert into smc_icc_issue
                foreach($bulk_data as $row){
                    $uid = isset($row['uid']) ? $row['uid'] : '';
                    $dpid = isset($row['dpid']) ? $row['dpid'] : '';
                    $issuer_id = isset($row['issuer_id']) ? $row['issuer_id'] : '';
                    $cdd_lead_id = isset($row['cdd_lead_id']) ? $row['cdd_lead_id'] : '';
                    $periodid = isset($row['periodid']) ? $row['periodid'] : '';
                    $issue_date = isset($row['issue_date']) ? $row['issue_date'] : '';
                    $issue_drug = isset($row['issue_drug']) ? $row['issue_drug'] : '';
                    $drug_qty = isset($row['drug_qty']) ? $row['drug_qty'] : '';
                    $device_serial = isset($row['device_serial']) ? $row['device_serial'] : '';
                    $app_version = isset($row['app_version']) ? $row['app_version'] : '';
                    //  log success
                    $retarray[] = array('uid' => $uid);
                    try{
                        $issue_id = $this->createIssue($uid, $dpid, $issuer_id, $cdd_lead_id, $periodid, $issue_date, $issue_drug, $drug_qty, $device_serial, $app_version);
                        if ($issue_id) {
                            //  insert into smc_icc_collection
                            $this->createCollection($periodid, $issue_id, $dpid, $issue_drug, $drug_qty, $issue_date, $cdd_lead_id);
                        }
                    } catch (\Exception $e) {
                        //  log error
                        $error_message = $e->getMessage();
                        $error_file_name = "error-report.txt";
                        $error_to_write = "\r\nICC Issue DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: ".getNowDbDate();
                        WriteToFile($error_file_name, $error_to_write);
                        return $retarray;
                    }
                }
            //  commit transaction
            $this->pdo->commit();
            //
            return $retarray;
        }else{
            return false;
        }
    }
    #   Issue dependencies
    private function createIssue($uid, $dpid, $issuer_id, $cdd_lead_id, $periodid, $issue_date, $issue_drug, $drug_qty,$device_serial, $app_version){
        $sql = "INSERT INTO smc_icc_issue (`uid`, `dpid`, `issuer_id`, `cdd_lead_id`, `periodid`, `issue_date`, `issue_drug`, `drug_qty`, `device_serial`, `app_version`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$uid, $dpid, $issuer_id, $cdd_lead_id, $periodid, $issue_date, $issue_drug, $drug_qty, $device_serial, $app_version]);
        if($stmt->errorCode() != '00000'){
            //  log error
            $error_message = $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        return $this->pdo->lastInsertId(); // returns issue_id
    }
    private function createCollection($periodid, $issue_id, $dpid, $drug, $qty, $issue_date, $cdd_lead_id){
        //  Generate download_id
        $download_id = generateUUID();
        $sql = "INSERT INTO smc_icc_collection (`periodid`,`issue_id`, `dpid`, `download_id`, `drug`, `qty`,`total_qty`, `issue_date`, `cdd_lead_id`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$periodid, $issue_id, $dpid, $download_id, $drug, $qty, $qty, $issue_date, $cdd_lead_id]);
    }
    #
    #   Download ICC
    public function IccDownloadBalance($periodid,$cdd_lead_id, $device_id, $app_version){
        $pdo = $this->pdo;
        $pdo->beginTransaction();
        $date = getNowDbDate();
        $stmt = $pdo->prepare("SELECT periodid,issue_id,drug,qty,issue_date,if(	is_accepted,'Yes','No') AS is_accepted FROM smc_icc_collection
                    WHERE periodid = $periodid AND cdd_lead_id = $cdd_lead_id AND status_code = 10");
        $stmt->execute();
        $balance = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($balance)) {
            $combined_balance = array_map(function($row) {
            $row['download_id'] = generateUUID();
            return $row;
            }, $balance);
        } else {
            $combined_balance = [];
        }
        //
        if(count($balance)){
            try{
                foreach($combined_balance as $row){
                    #   Update the download
                    $query = "UPDATE smc_icc_collection SET download_id = ?, download_date = ?, `status` = 'pending', `status_code` = 20,  updated = ? WHERE issue_id = ?";
                    $stmt2 = $pdo->prepare($query);
                    $stmt2->execute([$row['download_id'], $date, $date, $row['issue_id']]);
                    #   Log download process
                    $qin = "INSERT INTO smc_icc_download_log (`download_id`, `issue_id`, `cdd_lead_id`, `drug`, `qty`, `device_id`, `version`, `created`) VALUES (?,?,?,?,?,?,?,?)";
                    $stmt3 = $pdo->prepare($qin);
                    $stmt3->execute([$row['download_id'], $row['issue_id'],$cdd_lead_id, $row['drug'], $row['qty'], $device_id, $app_version, getNowDbDate()]);
                }
                $pdo->commit();
                return $combined_balance;
            }
            catch (\Exception $e) {
                //  log error
                $error_message = $e->getMessage();
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nICC Issue DB error message: $error_message\r\nData:".json_encode($balance)."\r\nDate: ".getNowDbDate();
                WriteToFile($error_file_name, $error_to_write);
                return false;
            }
            
        }
        return false;
    }
    #
    #
    #   ICC download confirmation
    public function ConfirmDownload($download_id, $cdd_lead_id, $issue_id){
        $pdo = $this->pdo;
        $date = getNowDbDate();
        //  check if it has be been previously confirmed
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM smc_icc_collection WHERE download_id = ? AND cdd_lead_id = ? AND issue_id = ? AND is_download_confirm = 1"); // *** to be used in production
        $stmt->execute([$download_id, $cdd_lead_id, $issue_id]); //, use for production
        $count = $stmt->fetchColumn();
        if($count > 0){
            return true; // already confirmed
        }
        //  update the download confirmation
        $query = "UPDATE smc_icc_collection SET `is_download_confirm` = 1, `download_confirm_date`=?, `status` = 'confirmed', `status_code` = 30,  updated = ? WHERE issue_id = ?";
        $stmt2 = $pdo->prepare($query);
        $stmt2->execute([$date, $date, $issue_id]);
        if($stmt2->errorCode() != '00000'){
            //  log error
            $error_message = "ICC Confirm Download Error \r\n".$stmt2->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        return true;
    }
    #
    #   ICC Acceptance Accept
    public function AcceptanceAccept($issue_id){
        $date = getNowDbDate();
        #  update the collection for acceptance
        #  Update issue status to accepted
        $pdo = $this->pdo;
        //  check if it has be been previously accepted
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM smc_icc_collection WHERE issue_id = ? AND is_accepted = 1");
        $stmt->execute([$issue_id]);
        $count = $stmt->fetchColumn();
        if($count > 0){
            return true; // already accepted
        }
        try {
            //  update the download confirmation
            $pdo->beginTransaction();
            //  if not start afresh
            $query = "UPDATE smc_icc_collection SET `is_accepted`=1,`accepted_date`=?, `status` = 'accepted', `status_code` = 40,  updated = ? WHERE issue_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$date,$date, $issue_id]);
            //  update the issue status to accepted
            $query = "UPDATE smc_icc_issue SET `confirmation` = '1', updated = ? WHERE issue_id = ?";
            $stmt2 = $this->pdo->prepare($query);
            $stmt2->execute([$date, $issue_id]);
            //  commit
            $pdo->commit();
            //
            return true;
        } catch (\Exception $e) {
            //  log error
            $error_message = $e->getMessage();
            $error_file_name = "error-report.txt";
            $error_to_write = "\r\nICC Acceptance ERROR: $error_message\r\nData:".json_encode($issue_id)."\r\nDate: ".getNowDbDate();
            WriteToFile($error_file_name, $error_to_write);
            return false;
        }
        
    }
    #
    #   ICC Acceptance Reject
    public function AcceptanceReject($issue_id, $reasons){
        $date = getNowDbDate();
        #  delete row from collection for acceptance
        #  Update issue confirmation to rejected
        $pdo = $this->pdo;
        //  check if it has be been previously rejected
        /*
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM smc_icc_collection WHERE issue_id = ? AND is_accepted = 0");
        $stmt->execute([$issue_id]);
        $count = $stmt->fetchColumn();
        
        if($count > 0){
            return true; // already rejected
        }
            */
        try {
            //  update the download confirmation
            $pdo->beginTransaction();
            //  if not start afresh
            $query = "DELETE FROM smc_icc_collection WHERE issue_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$issue_id]);
            //  update the issue status to rejected
            $query2 = "UPDATE smc_icc_issue SET `confirmation` = '-1', `confirmation_note` = :confirmation_note, `updated` = :updated WHERE `issue_id` = :issue_id";
            $stmt2 = $pdo->prepare($query2);
            $stmt2->bindValue(':confirmation_note', (string)$reasons, \PDO::PARAM_STR);
            $stmt2->bindValue(':updated', (string)$date, \PDO::PARAM_STR);
            $stmt2->bindValue(':issue_id', (int)$issue_id, \PDO::PARAM_INT);
            $stmt2->execute();
            //  commit
            $pdo->commit();
            //
            return true;
        } catch (\Exception $e) {
            //  log error
            $error_message = $e->getMessage();
            $error_file_name = "error-report.txt";
            $error_to_write = "\r\nICC Rejected ERROR: $error_message\r\nData:".json_encode($issue_id)."\r\nDate: ".getNowDbDate();
            WriteToFile($error_file_name, $error_to_write);
            return false;
        }
    }
    #
    #   Return ICC Issue
    #   ['returned_qty', 'issue_id']    Data structure
    #   sample parameter [['returned_qty'=>4, 'returned_partial'=>2, 'issue_id'=>1],['returned_qty'=>2,  'returned_partial'=>2, 'issue_id'=>2]]
    public function BulkIccReturn($bulk_data){
        #   return unused issue
        $pdo = $this->pdo;
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $pdo->beginTransaction();
            foreach($bulk_data as $row){
                $query = "UPDATE smc_icc_collection SET `is_returned` = '1', `returned_qty` = ?, `returned_partial` = ?, `returned_date` = ?,`status`='returned', status_code=50, updated = ? WHERE issue_id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$row['returned_qty'], $row['returned_partial'], $date, $date, $row['issue_id']]);
                $retarray[] = $row['issue_id'];
            }
            //  commit
            $pdo->commit();
            return $retarray;
        }else{
            return false;
        }
    }
    #
    #
    # Data structure
    #   ['issue_id', 'cdd_lead_id', 'drug', 'used_qty', 'full_qty', 'partial_qty', 'wasted_qty', 'loss_qty', 'loss_reason', 'receiver_id', 'device_serial', 'app_version', 'reconcile_date']
    public function BulkSaveRconciliation($bulk_data){
        $pdo = $this->pdo;
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $date = getNowDbDate();
            $retarray = array();
            # Start transactions
            $pdo->beginTransaction();
            foreach($bulk_data as $row){
                $retarray[] = $row['issue_id'];
                try{
                    $query = "INSERT INTO `smc_icc_reconcile` (`issue_id`,`cdd_lead_id`,`drug`,`used_qty`,`full_qty`,`partial_qty`,`wasted_qty`,`loss_qty`,`loss_reason`,`receiver_id`,`device_serial`,`app_version`,`reconcile_date`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$row['issue_id'],$row['cdd_lead_id'],$row['drug'],$row['used_qty'],$row['full_qty'],$row['partial_qty'],$row['wasted_qty'],$row['loss_qty'],$row['loss_reason'],$row['receiver_id'],$row['device_serial'],$row['app_version'],$row['reconcile_date']]);
                    $affected_rows = $stmt->rowCount();
                } catch (\Exception $e) {
                    //  log error (stmt may not be defined if prepare() failed)
                    $error_message = isset($stmt) ? ($stmt->errorInfo()[2] ?? $e->getMessage()) : $e->getMessage();
                    $error_file_name = "error-report.txt";
                    $error_to_write = "\r\nBulkSaveRconciliation() DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                    WriteToFile($error_file_name, $error_to_write);
                    return $retarray;
                }
                //  update the collection status to reconciled
                $query = "UPDATE smc_icc_collection SET `status` = 'reconciled', `status_code` = 60, updated = ? WHERE issue_id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$date, $row['issue_id']]);
                $affected_rows = $stmt->rowCount();
            }
            //  commit
            $pdo->commit();
            return $retarray;
        }else{
            return false;
        }
    }
    #
    #
    #   Get list of ICC issues for reconciliation
    #   [issue_id, loginid, fullname, phone, drug, qty, calculated_used, calculated_partial]
    public function GetIccListToReconcile($periodid, $dpid){
        return $this->db->DataTable("SELECT
                smc_icc_collection.issue_id,
                usr_login.loginid,
                CONCAT_WS(' ',usr_identity.`first`,usr_identity.last) AS fullname,
                usr_identity.phone,
                smc_icc_collection.drug,
                smc_icc_collection.total_qty AS qty,
                smc_icc_collection.calculated_used,
                smc_icc_collection.calculated_partial
                FROM
                smc_icc_collection
                INNER JOIN usr_login ON smc_icc_collection.cdd_lead_id = usr_login.userid
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
                WHERE
                smc_icc_collection.periodid = $periodid AND
                smc_icc_collection.status_code = 50 AND
                smc_icc_collection.dpid = $dpid");
    }
    #
    #   OLD reconverted list
    #
    #   2. Get list of reconciliation from your facility
    #   [issue_id, cdd_lead_id, loginid, cdd, drug, full, used, partial, wasted, issued]
    public function GetReconciliationMaster($periodid,$dpid){
        return $this->GetIccListToReconcile($periodid, $dpid);
    }
    #  
    #   4. Push balance online
    #   [periodid, issue_id, dpid, cdd_id, drug, qty, device_id, app_version]
    public function PushBalance($bulk_data){
        $date = getNowDbDate();
        $pdo = $this->pdo;
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $retarray = array();
            # Start transactions
            $pdo->beginTransaction();
            foreach($bulk_data as $row){
                //  update collection status to pushed
                $query = "UPDATE smc_icc_collection SET `qty` = ?, `status` = 'issued', `status_code` = 10, download_date = NULL, is_download_confirm = 0, download_confirm_date = NULL, updated = ? WHERE issue_id = ?";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$row['qty'], $date, $row['issue_id']]);
                $affected_rows = $stmt->rowCount();
                if($affected_rows > 0){
                    $query = "INSERT INTO `smc_icc_push` (`periodid`,`dpid`,`issue_id`,`cdd_lead_id`,`drug`,`qty`,`device_id`,`version`,`created`) VALUES (?,?,?,?,?,?,?,?,?)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([$row['periodid'],$row['dpid'],$row['issue_id'],$row['cdd_lead_id'],$row['drug'],$row['qty'],$row['device_id'],$row['app_version'],$date]);
                    $retarray[] = $row['issue_id'];
                }else{
                    //  log error
                    $error_message = $stmt->errorInfo()[2];
                    $error_file_name = "error-report.txt";
                    $error_to_write = "\r\nPushBalance() DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                    WriteToFile($error_file_name, $error_to_write);
                }
            }
            //  commit
            $pdo->commit();
            #   check is $retarray is empty return false else return $retarray
            if(count($retarray) == 0){
                return false;
            }
            return $retarray;
        }else{
            return false;
        }
    }
    #
    # [fullname, cdd_lead_id, loginid,geo_level_id,geo_level,geo_string,drug,balance,total_qty,downloaded,online]
    public function GetIccBalanceForDp($periodid,$dpid){
        return DbHelper::Table("SELECT
            smc_icc_collection.issue_id,
            smc_icc_collection.cdd_lead_id,
            usr_login.loginid, 
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.last) AS fullname,
            smc_icc_collection.drug,
            COALESCE(SUM(CASE WHEN status_code = 10 THEN qty ELSE 0 END), 0) AS issued,
            COALESCE(SUM(CASE WHEN status_code = 20 THEN qty ELSE 0 END), 0) AS pending,
            COALESCE(SUM(CASE WHEN status_code = 30 THEN qty ELSE 0 END), 0) AS confirmed,
            COALESCE(SUM(CASE WHEN status_code = 40 THEN qty ELSE 0 END), 0) AS accepted,
            COALESCE(SUM(CASE WHEN status_code = 50 THEN returned_qty ELSE 0 END), 0) AS returned,
            COALESCE(SUM(CASE WHEN status_code = 60 THEN reconciled_qty ELSE 0 END), 0) AS reconciled
            FROM smc_icc_collection
            INNER JOIN usr_login ON smc_icc_collection.cdd_lead_id = usr_login.userid
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            WHERE smc_icc_collection.periodid = $periodid
            AND smc_icc_collection.dpid = $dpid
            GROUP BY smc_icc_collection.cdd_lead_id, smc_icc_collection.issue_id");
    }
    # 
    #
    public function GetAdministrationRecord($dpid){
        return DbHelper::Table("SELECT
        smc_drug_administration.user_id,
        DATE(smc_drug_administration.collected_date) AS date,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS cdd_lead,
        if(smc_drug_administration.is_eligible = 0, 'Ineligible',smc_drug_administration.drug) AS drug,
        Sum(smc_drug_administration.drug_qty) AS qty,
        Sum(smc_drug_administration.redose_count) AS redose,
        Sum(if(smc_drug_administration.is_eligible = 0,1,0)) AS Ineligible
        FROM
        smc_drug_administration
        LEFT JOIN usr_login ON smc_drug_administration.user_id = usr_login.userid
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        WHERE smc_drug_administration.dpid = $dpid
        #AND smc_drug_administration.is_eligible = 1
        GROUP BY
        DATE(smc_drug_administration.collected_date),
        cdd_lead,
        usr_login.loginid,
        drug");
    }
    #
    #   5. Unlock balance
    #   parameters [issue_id, dpid, cdd_id, drug, qty, user_id]
    public function UnlockBalance($issue_id, $dpid, $cdd_id, $drug, $qty, $user_id){
        $date = getNowDbDate();
        $pdo = $this->pdo;
        # Start transactions
        $pdo->beginTransaction();;
        #   Update collection the balance ()
        $query = "UPDATE smc_icc_collection SET `qty` = ?, `status` = 'issued', `status_code` = 10, download_date = NULL, is_download_confirm = 0, download_confirm_date = NULL, updated = ? WHERE issue_id = ? LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$qty, $date, $issue_id]);
        $affected_rows = $stmt->rowCount();
        if($affected_rows > 0){
            $query = "INSERT INTO smc_icc_unlock (`issue_id`,`dpid`,`cdd_lead_id`,`drug`,`qty`,`user_id`,`created`) 
            VALUES (?,?,?,?,?,?,?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$issue_id,$dpid,$cdd_id,$drug,$qty,$user_id,$date]);
            //  commit
            $pdo->commit();
            return true;
        }else{
            //  log error
            $error_message = $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            $error_to_write = "\r\nUnlock Balance ERROR: $error_message\r\nData:issueid:$issue_id, dpid:$dpid, cdd_id:$cdd_id, drug:$drug, qty:$qty, user_id:$user_id\r\nDate: $date";
            WriteToFile($error_file_name, $error_to_write);
            return false;
        }
    }
    /*
     *
     * 
     * 
     * 
     * 
     * 
     * 
     */
    #   Bulk Receive 
    #   [uid, dpid, receiver_id, cdd_lead_id, cdd_team_code, periodid, received_date, received_day, received_drug, total_qty, full_dose_qty, partial_qty, wasted_qty]
    public function BulkIccReceive($bulk_data){
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $this->db->beginTransaction();
            foreach($bulk_data as $row){
                $query = "INSERT INTO `smc_icc_receive` (`uid`,`dpid`,`receiver_id`,`cdd_lead_id`,`cdd_team_code`,`periodid`,`received_date`,`received_day`,`received_drug`,`total_qty`,`full_dose_qty`,`partial_qty`,`wasted_qty`,`created`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $this->db->executeTransaction($query,array($row['uid'],$row['dpid'],$row['receiver_id'],$row['cdd_lead_id'],$row['cdd_team_code'],$row['periodid'],$row['received_date'],$row['received_day'],$row['received_drug'],$row['total_qty'],$row['full_dose_qty'],$row['partial_qty'],$row['wasted_qty'],$date));
                $retarray[] = $row['uid'];
            }
            $this->db->commitTransaction();
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nICC Received DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            # Completed
            return $retarray;
        }else{
            return false;
        }
    }
    #   Bulk Issue 
    #   [uid, dpid, issuer_id, cdd_lead_id, cdd_team_code, periodid, issue_date, issue_day, issue_drug, drug_qty, device_serial, app_version]
    public function BulkIccIssue_old($bulk_data){
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $this->db->beginTransaction();
            foreach($bulk_data as $row){
                $device_serial = isset($row['device_serial']) ? $row['device_serial'] : '';
                $app_version = isset($row['app_version']) ? $row['app_version'] : '';
                $query = "INSERT INTO `smc_icc_issue` (`uid`,`dpid`,`issuer_id`,`cdd_lead_id`,`cdd_team_code`,`periodid`,`issue_date`,`issue_day`,`issue_drug`,`drug_qty`,`device_serial`,`app_version`,`created`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $this->db->executeTransaction($query,array($row['uid'],$row['dpid'],$row['issuer_id'],$row['cdd_lead_id'],$row['cdd_team_code'],$row['periodid'],$row['issue_date'],$row['issue_day'],$row['issue_drug'],$row['drug_qty'],$device_serial,$app_version,$date));
                $retarray[] = $row['uid'];
                //  FCM IMPLEMENTATION DISABLED FOR NOW
                /*
                $fcm = new Fcm();
                $data = $this->IccDownloadBalanceSub($row['cdd_lead_id'],'FCM Channel','FCM');
                $user_token = $this->db->DataTable('SELECT device_fcm_token, loginid FROM usr_login WHERE userid = '.$row['cdd_lead_id']);
                $note = "Login ID: ".$user_token[0]['loginid'];
                $fcm->sendFCMDataMessage($user_token[0]['device_fcm_token'], $data, 'smc_icc_balance', $note);
                */
            }
            $this->db->commitTransaction();
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nICC Issue DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            # Completed
            return $retarray;
        }else{
            return false;
        }
    }
    #
    #   1. Call balance for CDD lead (balances on commodities)
    #   [id (aggregator_id, useless beyond this methods), drug, qty]
    public function IccDownloadBalance_old($cddid, $device_id, $app_version){
        //  Get Balance
        #   
        $date = getNowDbDate();
        $this->db->beginTransaction();
        $balance = $this->db->DataTable("select `smc_icc_aggragator`.`id` AS `id`,`smc_icc_aggragator`.`drug` AS `drug`,(`smc_icc_aggragator`.`total_qty` - `smc_icc_aggragator`.`downloaded`) AS `qty` from `smc_icc_aggragator` where (`smc_icc_aggragator`.`cdd_lead_id` = $cddid)");
        if(count($balance)){
            //  their is balance, update the balance
            $id_list = [];
            foreach($balance as $row){
                #   Carryout update data operations if the balance is not empty
                if($row['qty'] > 0){
                    #   Update the download
                    $query = "UPDATE smc_icc_aggragator SET smc_icc_aggragator.downloaded = smc_icc_aggragator.downloaded + ?, smc_icc_aggragator.device_id = ?, smc_icc_aggragator.version = ?, smc_icc_aggragator.updated = ? WHERE smc_icc_aggragator.id = ?";
                    $this->db->executeTransaction($query,array($row['qty'], $device_id, $app_version, $date, $row['id']));
                    #   Log download process
                    $qin = "INSERT INTO smc_icc_download_log (`aggr_id`, `cdd_lead_id`, `drug`, `qty`, `device_id`, `version`, `created`) VALUES (?,?,?,?,?,?,?)";
                    $this->db->executeTransaction($qin,array($row['id'],$cddid,$row['drug'],$row['qty'],$device_id,$app_version, $date));
                    $id_list[] = $this->db->executeTransactionLastId();
                }
            }            
        }
        $this->db->commitTransaction();
        return $balance;
    }
    #
    #   same downoad balance without db transaction
    private function IccDownloadBalanceSub($cddid, $device_id, $app_version){
        $date = getNowDbDate();
        #
        $balance = $this->db->DataTable("select `smc_icc_aggragator`.`id` AS `id`,`smc_icc_aggragator`.`drug` AS `drug`,(`smc_icc_aggragator`.`total_qty` - `smc_icc_aggragator`.`downloaded`) AS `qty` from `smc_icc_aggragator` where (`smc_icc_aggragator`.`cdd_lead_id` = $cddid)");
        if(count($balance)){
            //  their is balance, update the balance
            $id_list = [];
            foreach($balance as $row){
                #   Carryout update data operations if the balance is not empty
                if($row['qty'] > 0){
                    #   Update the download
                    $query = "UPDATE smc_icc_aggragator SET smc_icc_aggragator.downloaded = smc_icc_aggragator.downloaded + ?, smc_icc_aggragator.device_id = ?, smc_icc_aggragator.version = ?, smc_icc_aggragator.updated = ? WHERE smc_icc_aggragator.id = ?";
                    $this->db->executeTransaction($query,array($row['qty'], $device_id, $app_version, $date, $row['id']));
                    #   Log download process
                    $qin = "INSERT INTO smc_icc_download_log (`aggr_id`, `cdd_lead_id`, `drug`, `qty`, `device_id`, `version`, `created`) VALUES (?,?,?,?,?,?,?)";
                    $this->db->executeTransaction($qin,array($row['id'],$cddid,$row['drug'],$row['qty'],$device_id,$app_version, $date));
                    $id_list[] = $this->db->executeTransactionLastId();
                }
            }            
        }
        return $balance;
    }
    
    #
    #   3. Call BulkIccReconcile($bulk_data) Bulk save reconciliation
    #   [issue_id, wasted, loss, loss_reason, receiver_id, device_serial, app_version reconcile_date]
    public function BulkIccReconcile($bulk_data){
        #   
        $date = getNowDbDate();
        $this->db->beginTransaction();
        $ids = [];
        if(count($bulk_data)){
            foreach($bulk_data as $row){
                $loss = isset($row['loss'])? $row['loss']:0;
                $loss_reason = isset($row['loss_reason'])? $row['loss_reason']:'';
                $device_serial = isset($row['device_serial']) ? $row['device_serial'] : '';
                $app_version = isset($row['app_version']) ? $row['app_version'] : '';
                $query = "UPDATE smc_icc_reconcile SET `wasted` = ?, `loss` = ?, `loss_reason` = ?, `receiver_id` = ?, `returned` = ?, `device_serial` = ?, `app_version` = ?, `reconcile_date` = ? WHERE `issue_id` = ?";
                $this->db->executeTransaction($query,array($row['wasted'],$loss, $loss_reason, $row['receiver_id'],1,$device_serial,$app_version,$date,$row['issue_id']));
                $ids[] = $row['issue_id'];
            }
        }
        $this->db->commitTransaction();
        return $ids;
    }
    
    
   
    
    #
    #Get list of referrers
    public function GetReferrerList($dpid,$periodid) {
        return DbHelper::Table("SELECT
            smc_drug_administration.adm_id,
            smc_drug_administration.dpid,
            smc_drug_administration.periodid,
            smc_child.`name`,
            smc_drug_administration.beneficiary_id,
            smc_child.gender,
            smc_child.dob,
            smc_drug_administration.collected_date,
            smc_drug_administration.not_eligible_reason,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS referrer_cdd,
            usr_login.loginid AS referrer_cdd_loginid
            FROM
            smc_drug_administration
            INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
            INNER JOIN usr_identity ON smc_drug_administration.user_id = usr_identity.userid
            INNER JOIN usr_login ON usr_identity.userid = usr_login.userid
            WHERE
            smc_drug_administration.is_refer
            AND smc_drug_administration.dpid = $dpid
            AND smc_drug_administration.periodid = $periodid");
    }
    #
    # [adm_id,uid,beneficiary_id,userid,refer_type,ill_cause_of,ill_diagnosis,ill_child_treated,ill_dose_of_treatment,ill_admitted,fe_tested_for_malaria,fe_rdt_result,fe_admitted,fe_treated_with_act,fe_name_dose,fe_given_spaq,ad_child_evaluated,ad_pv_form_completed,ad_child_admitted,outcome,collected_date]
    public function BulkSaveReferrer($bulk_data){
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $retarray = array();
            $date = getNowDbDate();
            
            # Start transactions
            $this->db->beginTransaction();
            foreach($bulk_data as $row){
                #   Format data
                $ill_cause_of = intval($row['ill_cause_of']) === 1? 1:0;
                $ill_child_treated = intval($row['ill_child_treated']) === 1? 1:0;
                $ill_admitted = intval($row['ill_admitted']) === 1? 1:0;
                $fe_tested_for_malaria = intval($row['fe_tested_for_malaria']) === 1? 1:0;
                $fe_rdt_result = intval($row['fe_rdt_result']) === 1? 1:0;
                $fe_admitted = intval($row['fe_admitted']) === 1? 1:0;
                $fe_treated_with_act = intval($row['fe_treated_with_act']) === 1? 1:0;
                $fe_given_spaq = intval($row['fe_given_spaq']) === 1? 1:0;
                $ad_child_evaluated = intval($row['ad_child_evaluated']) === 1? 1:0;
                $ad_pv_form_completed = intval($row['ad_pv_form_completed']) === 1? 1:0;
                $ad_child_admitted = intval($row['ad_child_admitted']) === 1? 1:0;
                #
                $query = "INSERT INTO `smc_referer_record` (`adm_id`,`uid`,`beneficiary_id`,`userid`,`refer_type`,`ill_cause_of`,`ill_diagnosis`,`ill_child_treated`,`ill_dose_of_treatment`,`ill_admitted`,`fe_tested_for_malaria`,`fe_rdt_result`,`fe_admitted`,`fe_treated_with_act`,`fe_name_dose`,`fe_given_spaq`,`ad_child_evaluated`,`ad_pv_form_completed`,`ad_child_admitted`,`outcome`,`collected_date`,`created`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $this->db->executeTransaction($query,array($row['adm_id'],$row['uid'],$row['beneficiary_id'],$row['userid'],$row['refer_type'],$ill_cause_of,$row['ill_diagnosis'],$ill_child_treated,$row['ill_dose_of_treatment'],$ill_admitted,$fe_tested_for_malaria,$fe_rdt_result,$fe_admitted,$fe_treated_with_act,$row['fe_name_dose'],$fe_given_spaq,$ad_child_evaluated,$ad_pv_form_completed,$ad_child_admitted,$row['outcome'],$row['collected_date'],$date));
                $retarray[] = $row['uid'];
            }
            $this->db->commitTransaction();
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nMobilization DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nReferrer DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            # Completed
            return $retarray;
        }else{
            return false;
        }
    }
    #
    # ICC ISSUE AND RECEIVED WOULD BE MERGED
    #
    public function GetIccIssueByCdd($cddid,$period_filter=""){
        $where = "";
        if($period_filter){
            $where = " AND smc_icc_issue.periodid IN ($period_filter) ";
        }
        return DbHelper::Table("select `smc_icc_issue`.`issue_id` AS `issue_id`,`smc_period`.`title` AS `period`,`a`.`fullname` AS `issuer_name`, `a`.`loginid` AS `issuer_loginid`,`smc_icc_issue`.`issue_drug` AS `issue_drug`,`smc_icc_issue`.`drug_qty` AS `drug_qty`,cast(`smc_icc_issue`.`issue_date` as date) AS `issue_date`,`smc_icc_issue`.`created` AS `created` from ((`smc_icc_issue` join `smc_period` on((`smc_icc_issue`.`periodid` = `smc_period`.`periodid`))) join (select `usr_login`.`userid` AS `userid`,`usr_login`.`loginid` AS `loginid`,concat_ws(' ',`usr_identity`.`first`,`usr_identity`.`middle`,`usr_identity`.`last`) AS `fullname` from (`usr_login` join `usr_identity` on((`usr_login`.`userid` = `usr_identity`.`userid`)))) `a` on((`smc_icc_issue`.`issuer_id` = `a`.`userid`))) where (`smc_icc_issue`.`cdd_lead_id` = $cddid) $where");
    }
    public function GetIccReceiveByCdd($cddid,$period_filter=""){
        $where = "";
        if($period_filter){
            $where = " AND smc_icc_receive.periodid IN ($period_filter) ";
        }
        return DbHelper::Table("select `smc_icc_receive`.`receive_id` AS `receive_id`,`a`.`fullname` AS `receiver_name`, `a`.`loginid` AS `receiver_loginid`,`smc_period`.`title` AS `period`,`smc_icc_receive`.`received_drug` AS `received_drug`,`smc_icc_receive`.`full_dose_qty` AS `full_dose_qty`,`smc_icc_receive`.`partial_qty` AS `partial_qty`,`smc_icc_receive`.`wasted_qty` AS `wasted_qty`,`smc_icc_receive`.`received_date` AS `received_date`,`smc_icc_receive`.`created` AS `created` from ((`smc_icc_receive` join `smc_period` on((`smc_icc_receive`.`periodid` = `smc_period`.`periodid`))) join (select `usr_login`.`userid` AS `userid`,`usr_login`.`loginid` AS `loginid`,concat_ws(' ',`usr_identity`.`first`,`usr_identity`.`middle`,`usr_identity`.`last`) AS `fullname` from (`usr_login` join `usr_identity` on((`usr_login`.`userid` = `usr_identity`.`userid`)))) `a` on((`smc_icc_receive`.`receiver_id` = `a`.`userid`))) where (`smc_icc_receive`.`cdd_lead_id` = $cddid) $where");
    }
    #
    #   This is the merged version of ICC issued and reconcile
    #
    public function GetIccFlowDetailByCdd($cddid,$period_filter=""){
        $where = " where `smc_icc_issue`.`cdd_lead_id` = $cddid ";
        if($period_filter){
            $where .= " AND smc_icc_issue.periodid IN ($period_filter) ";
        }
        return DbHelper::Table("SELECT
            smc_icc_issue.issue_id,
            smc_icc_issue.issue_drug,
            smc_icc_issue.drug_qty AS issued_qty,
            COALESCE(smc_icc_reconcile.remaining, smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial)) AS unused,
            smc_icc_reconcile.`full` AS used_full,
            smc_icc_reconcile.partial AS used_partial,
            smc_icc_reconcile.wasted,
            DATE(smc_icc_issue.issue_date) AS issue_date,
            DATE(smc_icc_reconcile.reconcile_date) AS reconcile_date,
            if(smc_icc_reconcile.is_reconcile_ready, 'Yes','No') AS push_reconcile,
            if(smc_icc_reconcile.returned, 'Yas','No') AS has_reconciled,
            usr_login.loginid AS issuer_loginid,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS issuer_name,
            r.loginid AS receiver_loginid,
            r.fullname AS receiver_fullname
            FROM
            smc_icc_issue
            INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
            INNER JOIN usr_login ON smc_icc_issue.issuer_id = usr_login.userid
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            LEFT JOIN 
            (SELECT
            usr_login.userid,
            usr_login.loginid AS loginid,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) r ON smc_icc_reconcile.receiver_id = r.userid");
    }
    #
    #
    #   ICC Reconciliation
    #
    #   [uid, cdd_lead_id, dpid, drug, qty, device_id, app_version]
    public function ReconcileBalanceRun($bulk_data){
        $result = array();
        if(count($bulk_data)){
            foreach($bulk_data as $row){
                $result[] = $this->ReconcileBalance($row['uid'],$row['cdd_lead_id'],$row['dpid'],$row['drug'],$row['qty'],$row['device_id'],$row['app_version']);
            }
        }
        return $result;
    }
    private function ReconcileBalance($uid,$cdd_lead_id, $dpid, $drug, $qty, $device_id, $app_version){
        # logic part #
        $date = getNowDbDate();
        # Get list of the remaining
        $this->db->beginTransaction();
        //If saved successfully
        $target = $qty;
        if(true){
            
            # Get list of what to update
            $rem_list = $this->db->DataTable("SELECT
                    smc_icc_reconcile.recon_id,
                    smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial) AS balance
                    FROM
                    smc_icc_issue
                    INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
                    WHERE
                    smc_icc_reconcile.is_reconcile_ready = 0 AND
                    smc_icc_reconcile.returned = 0 AND
                    smc_icc_reconcile.cdd_lead_id = $cdd_lead_id AND
                    smc_icc_reconcile.drug = '$drug'
                    ORDER BY
                    smc_icc_reconcile.recon_id ASC");
            
            if(count($rem_list)){
                
                //  log reconciliation
                $r_q = "INSERT INTO smc_icc_reconcile_log (uid,cdd_lead_id, dpid, drug, qty, device_id, app_version, created) VALUE (?,?,?,?,?,?,?,?)";
                $this->db->executeTransaction($r_q,array($uid,$cdd_lead_id,$dpid,$drug,$qty,$device_id,$app_version,$date));
                //
                $reconciliation_id = $this->db->executeTransactionLastId();
                if($reconciliation_id){
                    //  effect the changes in the reconciliation calculation
                    foreach($rem_list as $row){
                        if($target > 0){
                            // Determine quantity to be used here
                            $use_qty = $target >= $row['balance'] ? $row['balance']: $target;
                            $query = "UPDATE smc_icc_reconcile SET smc_icc_reconcile.remaining = smc_icc_reconcile.remaining + ?, smc_icc_reconcile.is_reconcile_ready = 1 WHERE smc_icc_reconcile.recon_id = ?";
                            $this->db->executeTransaction($query,array($use_qty, $row['recon_id']));
                            # reduce target by the balance used
                            $target -= $use_qty;
                        }else{
                            break;  //  exit loop as no more balance to reconcile
                        }
                    }
                    //  Update the remaining balance for the user to download
                    $b_qu = "UPDATE smc_icc_aggragator SET smc_icc_aggragator.qty = smc_icc_aggragator.qty - ? WHERE smc_icc_aggragator.cdd_lead_id = ? AND smc_icc_aggragator.drug = ?";
                    $this->db->executeTransaction($b_qu,array($qty, $cdd_lead_id, $drug));
                }
            }
        }
        
        $this->db->commitTransaction();
        //$result = $target == 0? 'successful':'failed';    //  temporary sleeping
        $result = 'successful';
        //
        return array('uid'=>$uid,'status'=>$result);
    }
}
?>