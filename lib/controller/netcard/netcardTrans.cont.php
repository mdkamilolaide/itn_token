<?php

    namespace Netcard;

    use DbHelper;
    use PDOException;
    use Exception;
    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');
    /*
     *      Netcard transaction class
     */
    class NetcardTrans
    {
        private $db;
        public $LastError;
        public $LastErrorCode;

        public function __construct()
        {
            #
            $this->db = GetMysqlDatabase();
        }
        #
        #   Transaction by Location
        #
        public function GetCountByLocation()
        {
            #
            #   Return list of location and count in array list
            #
            return DbHelper::Table("SELECT
            Count(nc_netcard.ncid) AS total,
            nc_netcard.location
            FROM
            nc_netcard
            GROUP BY
            nc_netcard.location");
        }
        public function CountLocationState()
        {
            #
            #   Return count of State location
            #
            return $this->intCountLocation('state');
        }
        public function CountLocationLga()
        {
            #
            #   Return count of lga location
            #
            return $this->intCountLocation('lga');
        }
        public function CountLocationWard()
        {
            #
            #   Return count of ward location
            #
            return $this->intCountLocation('ward');
        }
        public function CountAllNetcard()
        {
            return $this->intCountLocation();
        }
        #
        #
        #   This Balances Geo-Hierachy
        #
        #   Netcard Status/Location
        #   ************************
        #   100 - state | 80 - lga | 60 - ward | 40 - mobilizer | 35 - pending (pending download confirmation) | 30 - downloaded | 20 - beneficiary
        #
        public function CountTotalNetcard(){
            return DbHelper::Table("SELECT COUNT(*) AS total FROM `nc_netcard`
                    WHERE
                    nc_netcard.active = 1");
        }
        public function ThisCountStateBalance(){
            return DbHelper::Table("SELECT COUNT(*) AS total FROM `nc_netcard`
                    WHERE
                    nc_netcard.location_value = 100");
        }
        public function ThisCountLgaBalance($lgaid){
            return DbHelper::Table("SELECT COUNT(*) AS total FROM `nc_netcard`
                    WHERE
                    nc_netcard.location_value = 80
                    AND nc_netcard.lgaid = $lgaid");
        }
        public function ThisCountWardBalance($wardid){
            return DbHelper::Table("SELECT COUNT(*) AS total FROM `nc_netcard`
                    WHERE
                    nc_netcard.location_value = 60
                    AND nc_netcard.wardid = $wardid");
        }

        #-----------------------    NEED UPDATE UPON Pending Implementation --------------------------#
        public function ThisCountHHMobilizerBalance($userid){
            return DbHelper::Table("SELECT COUNT(*) AS total FROM `nc_netcard`
                    WHERE
                    nc_netcard.location_value = 40 OR nc_netcard.location_value = 30
                    AND nc_netcard.mobilizer_userid = $userid");
        }
        #   Balance for allocation mobile app 
        public function CombinedBalanceForApp($wardid){
            return DbHelper::Table("SELECT
            IFNULL(balance.total_balance, 0) AS balance,
                IFNULL(received.total_received, 0) AS received,
            ROUND(IFNULL(received.total_received, 0) - IFNULL(balance.total_balance, 0), 0) AS disbursed
            FROM
            (SELECT COUNT(*) AS total_balance
            FROM nc_netcard
            WHERE location_value = 60 AND wardid = $wardid) AS balance,
            (SELECT SUM(total) AS total_received
            FROM nc_netcard_movement
            WHERE destination_level = 'ward' AND destination_level_id = $wardid) AS received");
        }
        #
        #
        #
        #   Count LGA List
        public function GetCountLgaList(){
            return DbHelper::Table("SELECT
                Count(nc_netcard.ncid) AS total,
                nc_netcard.location,
                nc_netcard.geo_level_id AS lgaid,
                (SELECT sys_geo_codex.title FROM sys_geo_codex WHERE sys_geo_codex.geo_level=nc_netcard.geo_level AND sys_geo_codex.geo_level_id=nc_netcard.geo_level_id) AS lga,
                (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE sys_geo_codex.geo_level=nc_netcard.geo_level AND sys_geo_codex.geo_level_id=nc_netcard.geo_level_id) AS lga_string
                FROM
                nc_netcard
                WHERE
                nc_netcard.location_value = 80
                GROUP BY
                nc_netcard.geo_level_id");
        }
        #   Count Ward List with LGA parameter
        public function GetCountWardList($lgaid){
            return DbHelper::Table("SELECT
                Count(nc_netcard.ncid) AS total,
                nc_netcard.location,
                nc_netcard.geo_level_id AS wardid,
                (SELECT sys_geo_codex.title FROM sys_geo_codex WHERE sys_geo_codex.geo_level=nc_netcard.geo_level AND sys_geo_codex.geo_level_id=nc_netcard.geo_level_id) AS ward,
                (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE sys_geo_codex.geo_level=nc_netcard.geo_level AND sys_geo_codex.geo_level_id=nc_netcard.geo_level_id) AS ward_string
                FROM
                nc_netcard
                WHERE
                nc_netcard.location_value = 60 AND 
                nc_netcard.lgaid = $lgaid
                GROUP BY
                nc_netcard.geo_level_id");
        }
        public function GetCountHhmList($wardid){
            return DbHelper::Table("SELECT
                usr_login.userid,
                CONCAT_WS(usr_identity.`first`,usr_identity.middle,usr_identity.last) AS fullname,
                usr_login.loginid
                FROM
                usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
                WHERE
                usr_login.geo_level = 'ward' AND
                usr_login.geo_level_id = $wardid");
        }
        #
        #   Mobile App Movement process
        #
        # Get mobilizers combined balances @ LGA
        public function GetLgaLevelMobilizersBalances(){
            return DbHelper::Table("SELECT
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.Fullname AS lga,
            Sum(if(nc_netcard.location_value = 40, 1,0)) AS `online`,
            Sum(if(nc_netcard.location_value = 35, 1,0)) AS `pending`,
            Sum(if(nc_netcard.location_value = 30, 1,0)) AS `wallet`
            FROM
            nc_netcard
            INNER JOIN ms_geo_lga ON nc_netcard.lgaid = ms_geo_lga.LgaId
            GROUP BY nc_netcard.lgaid");
        }
        #
        # Get Ward level mobilizers balances
        public function GetWardLevelMobilizersBalances($lgaid){
            return DbHelper::Table("SELECT
            ms_geo_ward.wardid,
            ms_geo_ward.ward,
            Sum(if(nc_netcard.location_value = 40, 1,0)) AS `online`,
            Sum(if(nc_netcard.location_value = 35, 1,0)) AS `pending`,
            Sum(if(nc_netcard.location_value = 30, 1,0)) AS `wallet`
            FROM
            nc_netcard
            INNER JOIN ms_geo_ward ON nc_netcard.wardid = ms_geo_ward.wardid
            WHERE nc_netcard.lgaid = $lgaid
            GROUP BY
            nc_netcard.wardid");
        }
        #
        # Get Ward list and balances
        public function GetWardListAndBalances($lgaid){
            return DbHelper::Table("SELECT 
            ms_geo_ward.wardid,
            ms_geo_ward.ward,
            if(a.balance,a.balance,0) AS balance
            FROM ms_geo_ward 
            LEFT JOIN (SELECT
            nc_netcard.wardid,
            COUNT(nc_netcard.ncid) AS balance
            FROM nc_netcard
            WHERE nc_netcard.location_value = 60 AND nc_netcard.lgaid = $lgaid
			GROUP BY nc_netcard.wardid)a ON ms_geo_ward.wardid = a.wardid
            WHERE ms_geo_ward.lgaid = $lgaid");
        }
        #   Movement mobile app top
        public function GetMovementTopHistory($lgaid, $count = 5){
            return $this->GetMovementHistory($lgaid,$count);
        }
        #   Movement mobile app all history
        public function GetMovementListHistory($lgaid,$count = 30){
            return $this->GetMovementHistory($lgaid,$count);
        }
        #   Core generator
        private function GetMovementHistory($lgaid, $count){
            return DbHelper::Table("SELECT
            nc_netcard_movement.mtid,
            a.wardid,
            a.ward,
            nc_netcard_movement.total,
            nc_netcard_movement.move_type,
            nc_netcard_movement.destination_level,
            nc_netcard_movement.created
            FROM
            nc_netcard_movement
            INNER JOIN (SELECT ms_geo_ward.wardid, ms_geo_ward.ward
            FROM ms_geo_ward
            WHERE ms_geo_ward.lgaid = $lgaid)a ON nc_netcard_movement.destination_level_id = a.wardid OR nc_netcard_movement.origin_level_id = a.wardid
            ORDER BY nc_netcard_movement.mtid DESC
            LIMIT $count");
        }
        #   dashboard balance
        public function GetMovementDashboardBalances($lgaid){
            # data structure - ['balance']['received']['disbursed']
            return DbHelper::Table("SELECT 
                                    @balance := (SELECT COUNT(*) AS total FROM `nc_netcard` WHERE nc_netcard.location_value = 80 AND nc_netcard.lgaid = $lgaid ) AS balance,
                                    @received := (SELECT (SUM(IF(nc_netcard_movement.origin_level='state' AND nc_netcard_movement.move_type='forward', nc_netcard_movement.total,0))-SUM(IF(nc_netcard_movement.origin_level='lga' AND nc_netcard_movement.move_type='reverse', nc_netcard_movement.total,0))) AS total FROM nc_netcard_movement WHERE nc_netcard_movement.destination_level_id = $lgaid OR nc_netcard_movement.origin_level_id = $lgaid) AS received,ROUND(( @received - @balance ), 0 ) AS disbursed");
        }
        #
        #
        #   Get Mobilizers balance
        public function GetMobilizersList($wardid){
            #   loginid, userid, fullname, netcard balance
            return DbHelper::Table("SELECT
            if(a.total,a.total,0) AS `balance`,
            a.device_serial,
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname,
            sys_geo_codex.geo_level,
            sys_geo_codex.geo_level_id,
            sys_geo_codex.title,
            sys_geo_codex.geo_string,
            '' AS pick
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            LEFT JOIN (SELECT
            Count(nc_netcard.ncid) AS total,
            nc_netcard.mobilizer_userid,
            nc_netcard.device_serial
            FROM
            nc_netcard
            WHERE
            nc_netcard.location_value = 40 OR 
			nc_netcard.location_value = 30
            GROUP BY
            nc_netcard.mobilizer_userid,
			nc_netcard.device_serial) a ON usr_login.userid = a.mobilizer_userid 
            WHERE
            usr_login.geo_level = 'ward'
            AND usr_login.roleid = 1
            AND usr_login.geo_level_id = $wardid 
            AND usr_login.userid != 100");
        }
        #   Get combined mobilizer balance
        public function GetCombinedMobilizerBalance($wardid){
            return DbHelper::Table("SELECT
            if(a.total,a.total,0) AS `balance`,
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname,
            sys_geo_codex.geo_level,
            sys_geo_codex.geo_level_id,
            sys_geo_codex.title,
            sys_geo_codex.geo_string,
            '' AS pick
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            LEFT JOIN (SELECT
            Count(nc_netcard.ncid) AS total,
            nc_netcard.mobilizer_userid,
            nc_netcard.device_serial
            FROM
            nc_netcard
            WHERE
            nc_netcard.location_value = 40 OR 
			nc_netcard.location_value = 30
            GROUP BY
            nc_netcard.mobilizer_userid) a ON usr_login.userid = a.mobilizer_userid 
            WHERE
            usr_login.geo_level = 'ward'
            AND usr_login.roleid = 1
            AND usr_login.geo_level_id = $wardid");
        }
        #   Get Offline mobilizer balance
        public function GetOfflineMobilizerBalance($wardid){
            #   loginid, userid, fullname, netcard balance
            return DbHelper::Table("SELECT
            if(a.total,a.total,0) AS `balance`,
            a.device_serial,
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname,
            sys_geo_codex.geo_level,
            sys_geo_codex.geo_level_id,
            sys_geo_codex.title,
            sys_geo_codex.geo_string,
            '' AS pick
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            INNER JOIN (SELECT
            Count(nc_netcard.ncid) AS total,
            nc_netcard.mobilizer_userid,
            nc_netcard.device_serial
            FROM
            nc_netcard
            WHERE 
			nc_netcard.location_value = 30
            GROUP BY
            nc_netcard.mobilizer_userid,
			nc_netcard.device_serial) a ON usr_login.userid = a.mobilizer_userid 
            WHERE
            usr_login.geo_level = 'ward'
            AND usr_login.roleid = 1
            AND usr_login.geo_level_id = $wardid
            GROUP BY a.device_serial");
        }
        #   Get Online mobilizer balance
        public function GetOnlineMobilizerBalance($wardid){
            return DbHelper::Table("SELECT
            if(a.total,a.total,0) AS `balance`,
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname,
            sys_geo_codex.geo_level,
            sys_geo_codex.geo_level_id,
            sys_geo_codex.title,
            sys_geo_codex.geo_string,
            '' AS pick
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            LEFT JOIN (SELECT
            Count(nc_netcard.ncid) AS total,
            nc_netcard.mobilizer_userid,
            nc_netcard.device_serial
            FROM
            nc_netcard
            WHERE
            nc_netcard.location_value = 40 
            GROUP BY
            nc_netcard.mobilizer_userid) a ON usr_login.userid = a.mobilizer_userid 
            WHERE
            usr_login.geo_level = 'ward'
            AND usr_login.roleid = 1
            AND usr_login.geo_level_id = $wardid");
        }
        #   Get all seperate mobilizers balances
        public function GetcAllMobilizerBalance($wardid){
            return DbHelper::Table("SELECT ul.userid, CONCAT_WS(' ',ui.first,ui.last) fullname, ul.loginid, SUM(nc.location_value=40) online, SUM(nc.location_value=35) pending, SUM(nc.location_value=30) wallet, '' AS pick FROM nc_netcard nc JOIN usr_login ul ON nc.mobilizer_userid=ul.userid JOIN usr_identity ui ON nc.mobilizer_userid=ui.userid WHERE nc.wardid=$wardid GROUP BY nc.mobilizer_userid");
        }
        #   Get mobilizers balances in seperate form, - online, pending, wallet
        #   ['userid','loginid','fullname','online','pending','wallet']
        public function GetMobilizerBalanceBySupervisor($wardid){
            return DbHelper::Table("SELECT usr_login.userid, usr_login.loginid, CONCAT_WS(' ', usr_identity.`first`, usr_identity.last) AS fullname, IF(a.online, a.online, 0) AS online, IF(a.pending, a.pending, 0) AS pending, IF(a.wallet, a.wallet, 0) AS wallet FROM usr_login INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id LEFT JOIN (SELECT SUM(nc_netcard.location_value = 40) AS online, SUM(nc_netcard.location_value = 35) AS pending, SUM(nc_netcard.location_value = 30) AS wallet, COUNT(nc_netcard.ncid) AS total, nc_netcard.mobilizer_userid, nc_netcard.device_serial FROM nc_netcard WHERE nc_netcard.location_value IN (30, 35, 40) GROUP BY nc_netcard.mobilizer_userid) a ON usr_login.userid = a.mobilizer_userid WHERE usr_login.geo_level = 'ward' AND usr_login.roleid = 1 AND usr_login.geo_level_id = $wardid AND usr_login.userid != 100");
        }
        #
        #   Allocation Mobile App
        public function GetAllocationTransferHistoryList($wardid){
            return DbHelper::Table("SELECT
            nc_netcard_allocation.atid,
            CONCAT(b.fullname,' (',b.loginid,')') AS performed_by,
            nc_netcard_allocation.total,
            nc_netcard_allocation.a_type,
            nc_netcard_allocation.destination_userid AS hhm_userid,
            CONCAT(a.fullname,' (',a.loginid,')') AS hhm,
            nc_netcard_allocation.created
            FROM
            nc_netcard_allocation
            LEFT JOIN (
                SELECT
                    usr_login.userid,
                    usr_login.loginid,
                    CONCAT_WS( ' ', usr_identity.`first`, usr_identity.last ) AS fullname 
                FROM
                    usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
                ) AS a ON nc_netcard_allocation.destination_userid = a.userid
            LEFT JOIN (
                SELECT
                    usr_login.userid,
                    usr_login.loginid,
                    CONCAT_WS( ' ', usr_identity.`first`, usr_identity.last ) AS fullname 
                FROM
                    usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
                ) AS b ON nc_netcard_allocation.userid = b.userid
            WHERE
            nc_netcard_allocation.a_type = 'forward' AND
            nc_netcard_allocation.origin = 'ward' AND
            nc_netcard_allocation.origin_id = $wardid
            ORDER BY
            nc_netcard_allocation.atid DESC
            LIMIT 50");
        }
        public function GetAllocationReverseHistoryList($wardid){
            return DbHelper::Table("SELECT
            nc_netcard_allocation_order.orderid,
            CONCAT(a.fullname,' (',a.loginid,')') AS hhm,
            CONCAT(b.fullname,' (',b.loginid,')') AS requester,
            nc_netcard_allocation_order.total_order,
            nc_netcard_allocation_order.device_serial,
            nc_netcard_allocation_order.total_fulfilment,
            nc_netcard_allocation_order.`status`,
            nc_netcard_allocation_order.created,
            nc_netcard_allocation_order.fulfilled_date
            FROM
            nc_netcard_allocation_order
            LEFT JOIN (
                SELECT
                    usr_login.userid,
                    usr_login.loginid,
                    CONCAT_WS( ' ', usr_identity.`first`, usr_identity.last ) AS fullname,
                    usr_login.geo_level,
                    usr_login.geo_level_id
                FROM
                    usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
                ) AS a ON nc_netcard_allocation_order.hhm_id = a.userid
            LEFT JOIN (
                SELECT
                    usr_login.userid,
                    usr_login.loginid,
                    CONCAT_WS( ' ', usr_identity.`first`, usr_identity.last ) AS fullname 
                FROM
                    usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
                ) AS b ON nc_netcard_allocation_order.requester_id = b.userid
            WHERE a.geo_level = 'ward'
            AND a.geo_level_id = $wardid
            ORDER BY
            nc_netcard_allocation_order.orderid DESC
            LIMIT 50");
        }
        public function GetAllocationDirectReverseList($wardid){
            return DbHelper::Table("SELECT
            nc_netcard_allocation_online.id,
            CONCAT(a.fullname,' (',a.loginid,')') AS hhm,
            CONCAT(b.fullname,' (',b.loginid,')') AS requester,
            nc_netcard_allocation_online.hhm_id,
            nc_netcard_allocation_online.requester_id,
            nc_netcard_allocation_online.amount,
            nc_netcard_allocation_online.created
            FROM
            nc_netcard_allocation_online
            LEFT JOIN (
                SELECT
                usr_login.userid,
                usr_login.loginid,
                CONCAT_WS( ' ', usr_identity.`first`, usr_identity.last ) AS fullname,
                usr_login.geo_level,
                usr_login.geo_level_id
                FROM
                usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
            ) AS a ON nc_netcard_allocation_online.hhm_id = a.userid
            LEFT JOIN (
                SELECT
                usr_login.userid,
                usr_login.loginid,
                CONCAT_WS( ' ', usr_identity.`first`, usr_identity.last ) AS fullname 
                FROM
                usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
            ) AS b ON nc_netcard_allocation_online.requester_id = b.userid
            WHERE a.geo_level = 'ward'
            AND a.geo_level_id = $wardid
            ORDER BY
            nc_netcard_allocation_online.id DESC
            LIMIT 50");
        }
        #
        #   movement Flow Netcard to another
        #
        #   Forward State to LGA (A1)
        public function StateToLgaMovement($total, $stateid, $lgaid, $userid){
            $origin = "state";
            $destination = "lga";
            $move_type = "forward";
            // Create movement record
            $mtid = $this->CreateMovement($total,$move_type,$origin,$stateid,$destination,$lgaid,$userid);
            $date = getNowDbDate();
            if(!$mtid){
                return false;
            }
            $pdo = $this->db->Conn;
            try{
                $pdo->beginTransaction();
                /*
                STEP 1: Select rows and lock them (skip already locked rows)
                */
                $selectQuery = "
                    SELECT ncid
                    FROM nc_netcard
                    WHERE geo_level = 'state'
                    AND geo_level_id = :stateid
                    AND location_value = 100
                    ORDER BY ncid ASC
                    LIMIT :total
                    FOR UPDATE SKIP LOCKED
                ";

                $stmt = $pdo->prepare($selectQuery);
                $stmt->bindValue(':stateid', $stateid, \PDO::PARAM_INT);
                $stmt->bindValue(':total', (int)$total, \PDO::PARAM_INT);
                $stmt->execute();

                $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if(!$rows){
                    $pdo->rollBack();
                    return 0;
                }
                /*
                STEP 2: Build ID list
                */
                $ids = implode(',', array_map('intval', $rows));
                /*
                STEP 3: Update locked rows
                */
                $updateQuery = "
                    UPDATE nc_netcard SET
                        location = 'lga',
                        location_value = 80,
                        geo_level = 'lga',
                        geo_level_id = :lgaid,
                        lgaid = :lgaid,
                        state_mtid = :mtid,
                        status = CONCAT(status, ' > lga($lgaid)'),
                        updated = :date
                    WHERE ncid IN ($ids)
                ";
                $stmt = $pdo->prepare($updateQuery);
                $stmt->bindValue(':lgaid', $lgaid, \PDO::PARAM_INT);
                $stmt->bindValue(':mtid', $mtid, \PDO::PARAM_INT);
                $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
                $pdo->commit();
                // Return the total affected rows
                return $affectedRows;

            } catch (\PDOException $e){

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $this->LastError = "Error: " . $e->getMessage();
                $this->LastErrorCode = $e->getCode();

                $logMessage = "-- A1-eNetcard Movement Error --\n";
                $logMessage .= "MITD: $mtid, Total: $total, State ID: $stateid, LGA ID: $lgaid, User ID: $userid\n";
                $logMessage .= "Error: " . $e->getMessage() . "\n";

                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);

                return false;
            }
        }
        #   Reverse LGA to State movement (A3)
        public function LgaToStateMovement($total, $lgaid, $stateid, $userid){
            $origin = "lga";
            $destination = "state";
            $move_type = "reverse";

            $mtid = $this->CreateMovement($total,$move_type,$origin,$lgaid,$destination,$stateid,$userid);
            $date = getNowDbDate();

            if(!$mtid){
                return false;
            }

            $pdo = $this->db->Conn;

            try{

                $pdo->beginTransaction();

                /*
                STEP 1: Select rows and lock them (skip locked rows)
                */
                $selectQuery = "
                    SELECT ncid
                    FROM nc_netcard
                    WHERE geo_level = 'lga'
                    AND geo_level_id = :lgaid
                    AND location_value = 80
                    ORDER BY ncid ASC
                    LIMIT :total
                    FOR UPDATE SKIP LOCKED
                ";

                $stmt = $pdo->prepare($selectQuery);
                $stmt->bindValue(':lgaid', $lgaid, \PDO::PARAM_INT);
                $stmt->bindValue(':total', (int)$total, \PDO::PARAM_INT);
                $stmt->execute();

                $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if(!$rows){
                    $pdo->rollBack();
                    return 0;
                }

                /*
                STEP 2: Build ID list
                */
                $ids = implode(',', array_map('intval', $rows));

                /*
                STEP 3: Update locked rows
                */
                $updateQuery = "
                    UPDATE nc_netcard SET
                        location = 'state',
                        location_value = 100,
                        geo_level = 'state',
                        geo_level_id = :stateid,
                        state_mtid = :mtid,
                        lgaid = NULL,
                        status = CONCAT(status, ' > state($stateid)'),
                        updated = :date
                    WHERE ncid IN ($ids)
                ";

                $stmt = $pdo->prepare($updateQuery);
                $stmt->bindValue(':stateid', $stateid, \PDO::PARAM_INT);
                $stmt->bindValue(':mtid', $mtid, \PDO::PARAM_INT);
                $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
                $stmt->execute();

                $affectedRows = $stmt->rowCount();

                $pdo->commit();

                return $affectedRows;

            } catch (\PDOException $e){

                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }

                $this->LastError = "Error: " . $e->getMessage();
                $this->LastErrorCode = $e->getCode();

                $logMessage = "-- A1-eNetcard Movement Error --\n";
                $logMessage .= "MITD: $mtid, Total: $total, State ID: $stateid, LGA ID: $lgaid, User ID: $userid\n";
                $logMessage .= "Error: " . $e->getMessage() . "\n";

                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);

                return false;
            }
        }
        #   Forward LGA to Ward (A2)
        public function LgaToWardMovement($total, $lgaid, $wardid, $userid){

            $origin = "lga";
            $destination = "ward";
            $move_type = "forward";

            $mtid = $this->CreateMovement($total,$move_type,$origin,$lgaid,$destination,$wardid,$userid);
            $date = getNowDbDate();

            if(!$mtid){
                return false;
            }

            $pdo = $this->db->Conn;

            try{

                $pdo->beginTransaction();

                /*
                STEP 1: Select rows and lock them while skipping locked rows
                */
                $selectQuery = "
                    SELECT ncid
                    FROM nc_netcard
                    WHERE geo_level = 'lga'
                    AND geo_level_id = :lgaid
                    AND location_value = 80
                    ORDER BY ncid ASC
                    LIMIT :total
                    FOR UPDATE SKIP LOCKED
                ";

                $stmt = $pdo->prepare($selectQuery);
                $stmt->bindValue(':lgaid', $lgaid, \PDO::PARAM_INT);
                $stmt->bindValue(':total', (int)$total, \PDO::PARAM_INT);
                $stmt->execute();

                $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if(!$rows){
                    $pdo->rollBack();
                    return 0;
                }

                /*
                STEP 2: Build ID list
                */
                $ids = implode(',', array_map('intval', $rows));

                /*
                STEP 3: Update locked rows
                */
                $updateQuery = "
                    UPDATE nc_netcard SET
                        location = 'ward',
                        location_value = 60,
                        geo_level = 'ward',
                        geo_level_id = :wardid,
                        ward_mtid = :mtid,
                        wardid = :wardid,
                        status = CONCAT(status, ' > ward($wardid)'),
                        updated = :date
                    WHERE ncid IN ($ids)
                ";

                $stmt = $pdo->prepare($updateQuery);
                $stmt->bindValue(':wardid', $wardid, \PDO::PARAM_INT);
                $stmt->bindValue(':mtid', $mtid, \PDO::PARAM_INT);
                $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
                $stmt->execute();

                $affectedRows = $stmt->rowCount();

                $pdo->commit();

                return $affectedRows;

            }catch (\PDOException $e){

                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }

                $this->LastError = "Error: " . $e->getMessage();
                $this->LastErrorCode = $e->getCode();

                $logMessage = "-- A1-eNetcard Movement Error --\n";
                $logMessage .= "MITD: $mtid, Total: $total, LGA ID: $lgaid, Ward ID: $wardid, User ID: $userid\n";
                $logMessage .= "Error: " . $e->getMessage() . "\n";

                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);

                return false;
            }
        }
        #   Reverse Ward to Lga (A5)
        public function WardToLgaMovement($total, $wardid, $lgaid, $userid){
            $origin = "ward";
            $destination = "lga";
            $move_type = "reverse";
            $mtid = $this->CreateMovement($total,$move_type,$origin,$wardid,$destination,$lgaid,$userid);
            $date = getNowDbDate();
            if(!$mtid){
                return false;
            }
            $pdo = $this->db->Conn;
            try{
                $pdo->beginTransaction();
                /*
                STEP 1: Select rows and lock them (skip locked rows)
                */
                $selectQuery = "
                    SELECT ncid
                    FROM nc_netcard
                    WHERE geo_level = 'ward'
                    AND geo_level_id = :wardid
                    AND location_value = 60
                    ORDER BY ncid ASC
                    LIMIT :total
                    FOR UPDATE SKIP LOCKED
                ";
                $stmt = $pdo->prepare($selectQuery);
                $stmt->bindValue(':wardid', $wardid, \PDO::PARAM_INT);
                $stmt->bindValue(':total', (int)$total, \PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                if(!$rows){
                    $pdo->rollBack();
                    return 0;
                }
                /*
                STEP 2: Build ID list
                */
                $ids = implode(',', array_map('intval', $rows));
                /*
                STEP 3: Update locked rows
                */
                $updateQuery = "
                    UPDATE nc_netcard SET
                        location = 'lga',
                        location_value = 80,
                        geo_level = 'lga',
                        geo_level_id = :lgaid,
                        lga_mtid = :mtid,
                        wardid = NULL,
                        status = CONCAT(status, ' > lga($lgaid)'),
                        updated = :date
                    WHERE ncid IN ($ids)
                ";
                $stmt = $pdo->prepare($updateQuery);
                $stmt->bindValue(':lgaid', $lgaid, \PDO::PARAM_INT);
                $stmt->bindValue(':mtid', $mtid, \PDO::PARAM_INT);
                $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
                $pdo->commit();
                return $affectedRows;

            } catch (\PDOException $e){

                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }

                $this->LastError = "Error: " . $e->getMessage();
                $this->LastErrorCode = $e->getCode();

                $logMessage = "-- A1-eNetcard Movement Error --\n";
                $logMessage .= "MITD: $mtid, Total: $total, LGA ID: $lgaid, Ward ID: $wardid, User ID: $userid\n";
                $logMessage .= "Error: " . $e->getMessage() . "\n";

                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);

                return false;
            }
        }
        #
        #
        #   Allocation flow e-Netcard
        #
        public function WardToHHMobilizerTemp($total, $wardid, $mobilizer_id, $userid){
            $allocation_type = "forward";
            $origin = "ward";
            $destination = "Mobilizer";
            $atid = $this->CreateAllocation($total,$allocation_type,$origin,$wardid,$destination,$mobilizer_id,$userid);
            $date = getNowDbDate();
            #   init transaction
            $this->db->beginTransaction();
            #   lock the table
            $this->db->executeTransaction("LOCK TABLE nc_netcard WRITE",array()); 
            #
            $this->db->executeTransaction("UPDATE nc_netcard SET nc_netcard.location = 'mobilizer', 
                nc_netcard.location_value = 40,
                nc_netcard.mobilizer_userid = $mobilizer_id,
                nc_netcard.atid = $atid,
                nc_netcard.updated = '$date',
                nc_netcard.status = CONCAT(nc_netcard.status,' > Mobilizer($mobilizer_id)')
                WHERE nc_netcard.geo_level = 'ward' AND nc_netcard.geo_level_id = $wardid
                AND nc_netcard.location_value = 60
                ORDER BY nc_netcard.ncid ASC
                LIMIT $total",array());
            #   unlock the table
            $this->db->executeTransaction("UNLOCK TABLES",array());              
            #   Complete transaction
            #   php 8  pdo upgrade
            #return $this->db->commitTransaction();
        }
        #   Forward e-Netcard allocation (A4)
        public function WardToHHMobilizer($total, $wardid, $mobilizerid, $userid){
            $type = "forward";
            $origin = "ward";
            $destination = "mobilizer";
            //
            $atid = $this->CreateAllocation($total,$type,$origin,$wardid,$destination,$mobilizerid,$userid);
            $date = getNowDbDate();
            //
            if(!$atid){
                return false;
            }
            //
            $pdo = $this->db->Conn;
            try{
                $pdo->beginTransaction();
                /*
                STEP 1: Select rows and lock them (skip already locked rows)
                */
                $selectQuery = "
                    SELECT ncid
                    FROM nc_netcard
                    WHERE geo_level = 'ward'
                    AND geo_level_id = :wardid
                    AND location_value = 60
                    ORDER BY ncid ASC
                    LIMIT :total
                    FOR UPDATE SKIP LOCKED
                ";
                $stmt = $pdo->prepare($selectQuery);
                $stmt->bindValue(':wardid', $wardid, \PDO::PARAM_INT);
                $stmt->bindValue(':total', (int)$total, \PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                if(!$rows){
                    $pdo->rollBack();
                    return 0;
                }
                /*
                STEP 2: Build ID list
                */
                $ids = implode(',', array_map('intval', $rows));
                /*
                STEP 3: Update locked rows
                */
                $updateQuery = "
                    UPDATE nc_netcard SET
                        location = 'mobilizer',
                        location_value = 40,
                        mobilizer_userid = :mobilizerid,
                        atid = :atid,
                        updated = :date,
                        status = CONCAT(status, ' > Mobilizer($mobilizerid)')
                    WHERE ncid IN ($ids)
                ";
                $stmt = $pdo->prepare($updateQuery);
                $stmt->bindValue(':mobilizerid', $mobilizerid, \PDO::PARAM_INT);
                $stmt->bindValue(':atid', $atid, \PDO::PARAM_INT);
                $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
                $stmt->execute();
                $affectedRows = $stmt->rowCount();
                $pdo->commit();
                return $affectedRows;
            } catch (\PDOException $e){
                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }
                $this->LastError = "Error: ".$e->getMessage();
                $this->LastErrorCode = $e->getCode();

                $logMessage = "-- A1-eNetcard Allocation Error --\n";
                $logMessage .= "ATID: $atid, Total: $total, Ward ID: $wardid, Mobilizer ID: $mobilizerid, User ID: $userid\n";
                $logMessage .= "Error: ".$e->getMessage()."\n";

                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);

                return false;
            }
        }
        #  (DISABLED) Reverse allocation Order (DISABLED)
        public function ReverseAllocationOrder($hhmid, $requester_id, $order, $device_serial){
            /*
             *  Proper reverse order implementation
            return DbHelper::Insert('nc_netcard_allocation_order',array(
                'hhm_id'=>$hhmid,
                'device_serial'=>$device_serial,
                'requester_id'=>$requester_id,
                'total_order'=>$order,
                'status'=>'pending'
            ));
            */
            //  Cancel order
            $order = 0;
            $status = 'fulfilled';
            return DbHelper::Insert('nc_netcard_allocation_order',array(
                'hhm_id'=>$hhmid,
                'device_serial'=>$device_serial,
                'requester_id'=>$requester_id,
                'total_order'=>$order,
                'total_fulfilment'=>$order,
                'status'=>$status
            ));
        }
        #   Mobilizer Online -> Ward - Reverse(A7)
        public function DirectReverseAllocation($total, $mobilizer_id, $requester_id){
            $date = getNowDbDate();
            $total = $total < 0 ? 0 : $total;
            if($total <= 0){
                return 0;
            }

            $pdo = $this->db->Conn;
            try{
                $pdo->beginTransaction();
                /*
                STEP 1: Lock rows belonging to the mobilizer
                */
                $selectQuery = "
                    SELECT ncid
                    FROM nc_netcard
                    WHERE mobilizer_userid = :mobilizer_id
                    AND location_value = 40
                    ORDER BY ncid ASC
                    LIMIT :total
                    FOR UPDATE SKIP LOCKED
                ";

                $stmt = $pdo->prepare($selectQuery);
                $stmt->bindValue(':mobilizer_id', $mobilizer_id, \PDO::PARAM_INT);
                $stmt->bindValue(':total', (int)$total, \PDO::PARAM_INT);
                $stmt->execute();

                $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if(!$rows){
                    $pdo->rollBack();
                    return 0;
                }

                /*
                STEP 2: Build ID list
                */
                $ids = implode(',', array_map('intval', $rows));
                /*
                STEP 3: Update locked rows
                */
                $updateQuery = "
                    UPDATE nc_netcard SET
                        location = 'ward',
                        location_value = 60,
                        mobilizer_userid = NULL,
                        atid = NULL,
                        device_serial = NULL,
                        updated = :date,
                        status = CONCAT(status,' > ward(D)')
                    WHERE ncid IN ($ids)
                ";

                $stmt = $pdo->prepare($updateQuery);
                $stmt->bindValue(':date', $date, \PDO::PARAM_STR);
                $stmt->execute();
                //
                $affectedRows = $stmt->rowCount();
                $pdo->commit();
                /*
                Record the reverse operation
                */
                $this->DirectReverseRecord($mobilizer_id, $requester_id, $affectedRows);

                return $affectedRows;

            } catch (\PDOException $e){

                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }

                $this->LastError = "Error: ".$e->getMessage();
                $this->LastErrorCode = $e->getCode();

                $logMessage = "-- Direct Reverse Netcard Error --\n";
                $logMessage .= "Mobilizer ID: $mobilizer_id, Requester ID: $requester_id, Total: $total\n";
                $logMessage .= "Error: ".$e->getMessage()."\n";

                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);

                return false;
            }
        }
        #
        private function DirectReverseRecord($mobilizer_id, $requester_id, $amount){
            return DbHelper::Insert('nc_netcard_allocation_online',array('hhm_id'=>$mobilizer_id,'requester_id'=>$requester_id,'amount'=>$amount,'created'=>getNowDbDate()));
        }
        #   (DISABLED) Reverse allocation fulfilment transfer (DISABLED) (Wallet -> ward)
        public function HHMobilizerToWardFulfulment($orderid, $netcat_list, $mobilizer_id, $wardid, $userid){
            $allocation_type = "reverse";
            $total = count($netcat_list);
            $origin = "mobilizer";
            $destination = "ward";
            $date = getNowDbDate();
            if($total){
                $atid = $this->CreateAllocation($total,$allocation_type,$origin,$mobilizer_id,$destination,$wardid,$userid);
                //
                #   init transaction
                $this->db->beginTransaction();
                $counter = 0;
                #
                #   Temporary disabled reverse order fulfilment
                #
                /*
                foreach($netcat_list as $v){
                    #   Update individual netcards
                    $this->db->executeTransaction("UPDATE nc_netcard SET nc_netcard.location = 'ward',
                     nc_netcard.location_value = 60,
                     nc_netcard.mobilizer_userid = NULL,
                     nc_netcard.device_serial = NULL,
                     nc_netcard.atid = NULL,
                     nc_netcard.updated = '$date',
                     nc_netcard.status = CONCAT(nc_netcard.status,' > Ward')
                     WHERE 
                     nc_netcard.uuid = '$v'
                     ",array());
                    $counter++;
                }
                */
                #
                # --    End disable
                #   Update fulifilment
                $counter = 0;
                $this->db->executeTransaction("UPDATE nc_netcard_allocation_order SET 
                    `total_fulfilment` = ?,
                    `status` = ?,
                    `fulfilled_date` = ?
                    WHERE `orderid`=$orderid LIMIT 1",array($counter,'fulfilled',getNowDbDate()));
                #   Complete transaction
                #   php 8  pdo upgrade
                #$this->db->commitTransaction();
                return $counter;
            }
        }
        #   Forward Bulk allocation transfer (BULK A4)
        public function BulkAllocationTransfer($bulk_list_data){
            $counter = 0;
            if(!count($bulk_list_data)){
                return 0;
            }
            $type = "forward";
            $origin = "ward";
            $destination = "mobilizer";
            $date = getNowDbDate();
            //
            $pdo = $this->db->Conn;
            //
            try{
                $pdo->beginTransaction();

                foreach($bulk_list_data as $val){

                    $total       = GetSafeArrayValue($val, 'total', 0);
                    $wardid      = GetSafeArrayValue($val, 'wardid', 0);
                    $mobilizerid = GetSafeArrayValue($val, 'mobilizerid', null);
                    $userid      = GetSafeArrayValue($val, 'userid', null);

                    if($total <= 0){
                        continue;
                    }
                    /*
                    STEP 1: Create allocation record
                    */
                    $insertQuery = "
                        INSERT INTO nc_netcard_allocation
                        (`userid`,`total`,`a_type`,`origin`,`origin_id`,`destination`,`destination_userid`)
                        VALUES (?,?,?,?,?,?,?)
                    ";
                    $stmt = $pdo->prepare($insertQuery);
                    $stmt->execute([
                        $userid,
                        $total,
                        $type,
                        $origin,
                        $wardid,
                        $destination,
                        $mobilizerid
                    ]);
                    $atid = $pdo->lastInsertId();
                    /*
                    STEP 2: Lock rows to allocate
                    */
                    $selectQuery = "
                        SELECT ncid
                        FROM nc_netcard
                        WHERE geo_level = 'ward'
                        AND geo_level_id = :wardid
                        AND location_value = 60
                        ORDER BY ncid ASC
                        LIMIT :total
                        FOR UPDATE SKIP LOCKED
                    ";
                    $stmt = $pdo->prepare($selectQuery);
                    $stmt->bindValue(':wardid', $wardid, \PDO::PARAM_INT);
                    $stmt->bindValue(':total', (int)$total, \PDO::PARAM_INT);
                    $stmt->execute();

                    $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                    if(!$rows){
                        continue;
                    }

                    $ids = implode(',', array_map('intval',$rows));

                    /*
                    STEP 3: Update locked rows
                    */
                    $updateQuery = "
                        UPDATE nc_netcard SET
                            location = 'mobilizer',
                            location_value = 40,
                            mobilizer_userid = :mobilizerid,
                            atid = :atid,
                            updated = :date,
                            status = CONCAT(status,' > Mobilizer($mobilizerid)')
                        WHERE ncid IN ($ids)
                    ";

                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->bindValue(':mobilizerid',$mobilizerid,\PDO::PARAM_INT);
                    $stmt->bindValue(':atid',$atid,\PDO::PARAM_INT);
                    $stmt->bindValue(':date',$date,\PDO::PARAM_STR);
                    $stmt->execute();

                    $counter++;

                }

                $pdo->commit();

                return $counter;

            }
            catch(PDOException $e){

                if($pdo->inTransaction()){
                    $pdo->rollBack();
                }

                $this->LastError = "Error: ".$e->getMessage();
                $this->LastErrorCode = $e->getCode();

                $logMessage = "-- Bulk Netcard Allocation Error --\n";
                $logMessage .= "Error: ".$e->getMessage()."\n";

                file_put_contents('error-report-netcards.txt',$logMessage,FILE_APPEND);

                return false;
            }
        }
        #
        #   
        #   Super users to unlock lock e-Netcard for the 
        #   returns total number of netcard unlocked (A10)
        public function SuperUserUnlockNetcard($userid, $device_serial, $requester_id){
            $pdo = $this->db->Conn;
            #   Get list of netcard in the list
            $query = "SELECT nc_netcard.ncid
                      FROM nc_netcard
                      WHERE nc_netcard.mobilizer_userid = :userid 
                      AND nc_netcard.device_serial = :device_serial 
                      AND nc_netcard.location_value = 30";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':userid', $userid, \PDO::PARAM_INT);
            $stmt->bindParam(':device_serial', $device_serial, \PDO::PARAM_STR);
            $stmt->execute();
            $netcards = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            #   Total number of found netcards
            $total_netcards = count($netcards);
            
            #   Update list
            if ($total_netcards > 0) {
                try {
                    $pdo->beginTransaction();

                    foreach ($netcards as $v) {
                        $updateQuery = "UPDATE nc_netcard SET 
                            nc_netcard.location = 'ward',
                            nc_netcard.location_value = 60,
                            nc_netcard.mobilizer_userid = NULL,
                            nc_netcard.atid = NULL,
                            nc_netcard.device_serial = NULL,
                            nc_netcard.status = CONCAT(nc_netcard.status, ' - (unlock) > Ward')
                            WHERE nc_netcard.ncid = :ncid";
                        $stmt = $pdo->prepare($updateQuery);
                        $stmt->bindParam(':ncid', $v['ncid'], \PDO::PARAM_INT);
                        $stmt->execute();
                    }

                    # Update unused pushed log
                    $date = getNowDbDate();
                    $insertQuery = "INSERT INTO nc_netcard_unlocked_log 
                        (`hhm_id`, `requester_id`, `device_serial`, `amount`, `created`) 
                        VALUES (:hhm_id, :requester_id, :device_serial, :amount, :created)";
                    $stmt = $pdo->prepare($insertQuery);
                    $stmt->bindParam(':hhm_id', $userid, \PDO::PARAM_INT);
                    $stmt->bindParam(':requester_id', $requester_id, \PDO::PARAM_INT);
                    $stmt->bindParam(':device_serial', $device_serial, \PDO::PARAM_STR);
                    $stmt->bindParam(':amount', $total_netcards, \PDO::PARAM_INT);
                    $stmt->bindParam(':created', $date, \PDO::PARAM_STR);
                    $stmt->execute();

                    $pdo->commit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $this->LastError = "Error: " . $e->getMessage();
                    $this->LastErrorCode = $e->getCode();
                    $logMessage = "-- Unlock Netcard Error --\n User ID: $userid, Device Serial: $device_serial, Requester ID: $requester_id\n";
                    $logMessage .= "Error: " . $e->getMessage() . "\n";
                    file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);
                    return false; // or handle the error as needed
                }
            }
            return $total_netcards;
        }
        #
        #   Push e-Netcard online (A9)
        public function PushNetcardOnline($list_of_netcard_uuid, $hhm_id, $device_serial) {
            $logMessage = "";
            $hasError = false;
            $date = getNowDbDate();
            $counter = 0;
            if (!is_array($list_of_netcard_uuid) || empty($list_of_netcard_uuid)) {
                $hasError = true;
                $logMessage .= "[" . date('Y-m-d H:i:s') . "] (A9) Push e-Netcard online - No UUIDs provided\n";
                $logMessage .= "HHM ID: $hhm_id | Device Serial: $device_serial\n";
            }
            if ($hasError) {
                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);
                return $counter;
            }
            try {
                $pdo = $this->db->Conn;
                $pdo->beginTransaction();
                $updateSql = "UPDATE nc_netcard 
                                SET 
                                    location_value = 40,
                                    device_serial = NULL,
                                    status = CONCAT(status, ' > Push-Online')
                                WHERE uuid = ?";
                $stmt = $pdo->prepare($updateSql);
                foreach ($list_of_netcard_uuid as $uuid) {
                    if (empty($uuid)) {
                        $hasError = true;
                        $logMessage .= "Skipped empty UUID\n";
                        continue;
                    }
                    try {
                        $executed = $stmt->execute([$uuid]);
                        if ($executed) {
                            $counter += $stmt->rowCount();
                        } else {
                            $hasError = true;
                            $logMessage .= "Execute failed for UUID: $uuid\n";
                        }
                    } catch (PDOException $e) {
                        $hasError = true;
                        $logMessage .= "PDOException for UUID $uuid: " . $e->getMessage() . "\n";
                    }
                }
                try {
                    $insertSql = "INSERT INTO nc_netcard_unused_pushed 
                                    (hhm_id, device_serial, amount, created) 
                                VALUES (?, ?, ?, ?)";
                    $stmt2 = $pdo->prepare($insertSql);
                    $stmt2->execute([$hhm_id, $device_serial, $counter, $date]);
                } catch (Exception $e) {
                    $hasError = true;
                    $logMessage .= "Insert log error: " . $e->getMessage() . "\n";
                    $pdo->rollBack();
                    if ($hasError) {
                        $logMessage = "[" . date('Y-m-d H:i:s') . "] (A9) Push e-Netcard online ERROR\n" . $logMessage;
                        file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);
                    }
                    return 0;
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $hasError = true;
                $logMessage .= "General exception: " . $e->getMessage() . "\nTransaction rolled back\n";
            }
            if ($hasError) {
                $logMessage = "[" . date('Y-m-d H:i:s') . "] (A9) Push e-Netcard online ERROR\n" . $logMessage;
                file_put_contents('error-report-netcards.txt', $logMessage, FILE_APPEND);
            }
            return $counter;
        }
        #
        #   create internal transaction
        #   create external transaction
        private function intCountLocation($value="")
        {
            $query = "";
            if($value == "")
            {
                $query = "SELECT
                    Count(nc_netcard.ncid)
                    FROM
                    nc_netcard";
            }
            else
            {
                $query = "SELECT
                    Count(nc_netcard.ncid)
                    FROM
                    nc_netcard
                    WHERE
                    nc_netcard.location = '$value'";
            }
            return DbHelper::GetScalar($query);
        }
        private function CreateAllocation($total,$allocate_type,$origin,$originid,$destination,$destinationid,$userid){
            return DbHelper::Insert('nc_netcard_allocation',array(
                "userid"=>$userid,
                "total"=>$total,
                "a_type"=>$allocate_type,
                "origin"=>$origin,
                "origin_id"=>$originid,
                "destination"=>$destination,
                "destination_userid"=>$destinationid,
                "created"=>getNowDbDate()
            ));
        }
        private function CreateMovement($total,$move_type,$origin,$originid,$destination,$destinationid,$userid){
            #
            return DbHelper::Insert('nc_netcard_movement',array(
                "userid"=>$userid,
                "total"=>$total,
                "move_type"=>$move_type,
                "origin_level"=>$origin,
                "origin_level_id"=>$originid,
                "destination_level"=>$destination,
                "destination_level_id"=>$destinationid,
                "created"=>getNowDbDate()
            ));
        }
    }
?>
