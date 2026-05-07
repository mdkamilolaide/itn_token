<?php

    namespace Mobilization;
    use DbHelper;
    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');

    class Mobilization {
        #
        private $db;
        #
        public function __construct(){
            $this->db = GetMysqlDatabase();
        }
        #
        #
        #
        #
        public function BulkMobilization($mobilization_bulk){
            $counter = 0;
            $duplicate_count = 0;
            $total_items = count($mobilization_bulk);
            $date = getNowDbDate();
            if($total_items){
                #   init transaction
                $this->db->beginTransaction();
                foreach($mobilization_bulk as $a){
                    #   get data
                    #   [dp_id, comid, hm_id, co_hm_id, hoh_first, hoh_last, hoh_phone, hoh_gender, 
                    #   family_size, hod_mother, sleeping_space, adult_female, adult_male, children, allocated_net, location_description, longitude, 
                    #   latitude, netcards, etoken_id, etoken_serial, etoken_pin, collected_date, device_serial, app_version, eolin_have_old_net, eolin_total_old_net]
                    #   
                    
                    #
                    # - Insert mobilization
                    # - Check if exist
                    $count_existing_data = $this->db->executeTransactionScalar("SELECT COUNT(*) FROM hhm_mobilization
                    WHERE hhm_mobilization.etoken_serial = '".$a['etoken_serial']."'");
                    if($count_existing_data < 1){
                        $device_serial = array_key_exists("device_serial",$a)? $a['device_serial']:'';"";
                        $app_version = array_key_exists("app_version",$a)? $a['app_version']:'';"";
                        #   Safe addition of new fields
                        $hod_mother = array_key_exists("hod_mother",$a)? $a['hod_mother']:'';
                        $sleeping_space = array_key_exists("sleeping_space",$a)? intval($a['sleeping_space']):0;
                        $sleeping_space = intval($sleeping_space) >= 0 || $sleeping_space != ""? $sleeping_space: 0;
                        $adult_female = array_key_exists("adult_female",$a)? intval($a['adult_female']):0;
                        $adult_female = intval($adult_female) >= 0 || $adult_female != ""? $adult_female: 0;
                        $adult_male = array_key_exists("adult_male",$a)? intval($a['adult_male']):0;
                        $adult_male = intval($adult_male) >= 0 || $adult_male != ""? $adult_male: 0;
                        $children = array_key_exists("children",$a)? intval($a['children']):0;
                        $children = intval($children) >= 0 || $children != ""? $children: 0;
                        //  upgrade data collection
                        $eolin_have_old_net = array_key_exists("eolin_have_old_net",$a)? $a['eolin_have_old_net']:'';
                        $eolin_have_old_net = intval($eolin_have_old_net) >= 0 || $eolin_have_old_net != ""? $eolin_have_old_net: 0;
                        $eolin_total_old_net = array_key_exists("eolin_total_old_net",$a)? intval($a['eolin_total_old_net']):0;
                        $eolin_total_old_net = intval($eolin_total_old_net) >= 0 || $eolin_total_old_net != ""? $eolin_total_old_net: 0;
                        #
                        $this->db->executeTransaction("INSERT INTO hhm_mobilization (`dp_id`,`comid`,`hhm_id`,`co_hhm_id`,`hoh_first`,`hoh_last`,`hoh_phone`,`hoh_gender`,`family_size`,
                           `hod_mother`,`sleeping_space`,`adult_female`,`adult_male`,`children`,`allocated_net`,`location_description`,`longitude`,
                            `Latitude`,`eolin_have_old_net`,`eolin_total_old_net`,`netcards`,`etoken_id`,`etoken_serial`,`etoken_pin`,`device_serial`,`app_version`,`collected_date`,`created`) VALUES 
                            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",array($a['dp_id'],$a['comid'],$a['hm_id'],$a['co_hm_id'],$a['hoh_first'],$a['hoh_last'],$a['hoh_phone'],$a['hoh_gender'],
                            $a['family_size'],$hod_mother,$sleeping_space,$adult_female,$adult_male,$children,
                            $a['allocated_net'],$a['location_description'],$a['longitude'],$a['latitude'],$eolin_have_old_net,$eolin_total_old_net,
                            $a['netcards'],$a['etoken_id'],$a['etoken_serial'],$a['etoken_pin'],$device_serial,$app_version,$a['collected_date'], $date));
                        #   get ID
                        $utid = $this->db->executeTransactionLastId();
                        #
                        $netcard_list = preg_split('@,@', $a['netcards'], -1, PREG_SPLIT_NO_EMPTY);
                        #
                        #  - Update e-Netcard
                        if(count($netcard_list)){
                            foreach($netcard_list as $uid){
                                $uuid = trim($uid);
                                $this->db->executeTransaction("UPDATE nc_netcard SET `location`='beneficiary',
                                `location_value`=20,`utid`=?,`beneficiaryid`=?, 
                                `updated`=?, `status`= CONCAT(`status`,' > beneficiary($utid)') 
                                WHERE `uuid` = '$uuid'",array($utid,$utid,$date));
                            }
                        }
                        #
                        #  - Update eToken usage
                        $this->db->executeTransaction("UPDATE nc_token SET `status`='used', `status_code`=5,`updated`='$date' WHERE `tokenid`=?",
                            array($a['etoken_id']));
                        $counter++;
                    }
                    else{
                        $duplicate_count++;
                    }
                }
                #   Complete transaction
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
                #   log error if any
                $error_message = $this->db->ErrorMessage;
                if(strlen($error_message)>0){
                    #   Write to file
                    $error_file_name = "error-report.txt";
                    $error_to_write = "\r\nMobilization DB error message: $error_message\r\nData:".json_encode($mobilization_bulk)."\r\nDate: $date";
                    WriteToFile($error_file_name, $error_to_write);
                }
                
                #
                #   Create room for duplicate flip
                #   Increase return counter if all incoming records are duplicates
                if($total_items == $duplicate_count)
                {
                    $counter = 1;
                }
            }
            return $counter;
        }
        #
        #
        #
        #
        public function GetLocationCategories(){
            return DbHelper::Table("SELECT
                hhm_location_categories.id,
                hhm_location_categories.location
                FROM
                hhm_location_categories
                ORDER BY
                hhm_location_categories.location ASC");
        }
        #   Mobilizer download enetcard A8 - e-Netcard download
        public function DownloadEnetcard($mobilizer_userid,$device_serial){
            #   Get list of net in the list
            #   
            #   Updated to implement pending process
            $pdo = $this->db->Conn;
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT
                nc_netcard.ncid,
                nc_netcard.uuid
                FROM
                nc_netcard
                WHERE
                nc_netcard.active = 1 AND
                nc_netcard.location_value = 40 AND
                nc_netcard.mobilizer_userid = $mobilizer_userid");
            $stmt->execute();
            $netcards = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $download_id = generateUUID();
            $capping = [];
            if(count($netcards)){
                #   Update downloaded list
                
                #   Update netcard location status to pending
                $netcard_ncid_list = [];
                foreach($netcards as $v){
                    $stmt2 = $pdo->prepare("UPDATE nc_netcard SET nc_netcard.location_value = 35,
                    nc_netcard.device_serial = ?, updated = ?
                    WHERE nc_netcard.ncid =?");
                    $stmt2->execute(array($device_serial,$v['ncid'],getNowDbDate()));
                    $netcard_ncid_list[] = $v['ncid'];
                }
                #   Create and upgrade download batch
                $stmt3 = $pdo->prepare("INSERT INTO nc_netcard_download (`download_id`,`device_id`,`userid`,`total`, `netcard_list`,`created`,`updated`)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt3->execute(array($download_id,$device_serial,$mobilizer_userid,count($netcards),json_encode($netcard_ncid_list),getNowDbDate(),getNowDbDate()));
                
            }
            #   Get netcapping for user
            $stmt4 = $pdo->prepare("SELECT sys_geo_codex.net_capping
            FROM usr_login INNER JOIN sys_geo_codex ON usr_login.geo_level_id = sys_geo_codex.geo_level_id AND usr_login.geo_level = sys_geo_codex.geo_level
            WHERE usr_login.userid = $mobilizer_userid");
            $stmt4->execute();
            $capping = $stmt4->fetchAll(\PDO::FETCH_ASSOC);
            // Commit transaction
            $pdo->commit();
            # check capping value if found and integer valied
            if(count($capping) && is_numeric($capping[0]['net_capping'])){
                $capping[0]['net_capping'] = intval($capping[0]['net_capping']);
            }
            else{
                $capping[0]['net_capping'] = 4; // default capping value//
            }
            #
            return array(
                'net_capping' => $capping[0]['net_capping'],
                'download_id' => $download_id,
                'netcard' => $netcards
            );
        }
        #   Mobilizer confirm download
        public function ConfirmDownload($mobilizer_userid, $device_serial, $download_id){
            #   Get list details of $downloaded
            $dwn = $this->db->DataTable("SELECT * FROM nc_netcard_download WHERE download_id = '$download_id' AND device_id = '$device_serial' AND userid = $mobilizer_userid");
            #   Check if exist
            if(count($dwn)){
                #   Check if already confirmed
                $dwn = $dwn[0];
                if($dwn['status'] == 'pending' && $dwn['is_confirmed'] == 0 && $dwn['is_destroyed'] == 0){
                    #   Flip the netcard to downloaded and update status to downloaded
                    $enetcard_list = json_decode($dwn['netcard_list'], true);
                    $this->db->beginTransaction();
                    foreach($enetcard_list as $v){
                        $u_q = "UPDATE nc_netcard SET nc_netcard.location_value = 30,
                        nc_netcard.device_serial = ? 
                        WHERE nc_netcard.ncid =? ";
                        $this->db->executeTransaction($u_q, array($device_serial,$v));
                    }
                    #   Update download status to downloaded
                    $dwn_query = "UPDATE nc_netcard_download SET `status` = 'downloaded', `is_confirmed` = 1, `updated` = ? WHERE download_id = ?";
                    $this->db->executeTransaction($dwn_query, array(getNowDbDate(),$download_id));
                    $this->db->commitTransaction();
                    #   return success message
                    return array(
                        'status' => 'success',
                        'message' => 'Download confirmed successfully'
                    );
                }
                else if($dwn['status'] == 'downloaded' && $dwn['is_confirmed'] == 1 && $dwn['is_destroyed'] == 0){
                    return array(
                        'status' => 'success',
                        'message' => 'Download already confirmed'
                    );
                }
                else if($dwn['status'] == 'destroyed'){
                    return array(
                        'status' => 'destroyed',
                        'message' => 'Download destroyed'
                    );
                }
                else{
                    return array(
                        'status' => 'error',
                        'message' => 'Download unknown error'
                    );
                }
            }
            else{
                return array(
                    'status' => 'error',
                    'message' => 'Download not found'
                );
            }
            #   if exist continue
            #   if status is pending and is_confirmed and is_destroyed is 0, flip to downloaded and send success
            #   if status is downloaded send success message
            #   if status id destroyed send error message
            
        }
        #   (DISABLED) Mobilizer reverse order  (DISABLED)
        public function GetPendingReverseOrder($mobilizer_userid, $device_serial){
            return DbHelper::Table("SELECT
                nc_netcard_allocation_order.orderid,
                nc_netcard_allocation_order.total_order,
                nc_netcard_allocation_order.created
                FROM
                nc_netcard_allocation_order
                WHERE
                nc_netcard_allocation_order.`status` = 'pending' AND
                nc_netcard_allocation_order.hhm_id = $mobilizer_userid AND 
                nc_netcard_allocation_order.device_serial = '$device_serial'");
        }
        public function GetMobilizationDetails($hh_id){
            return DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.dp_id,
            hhm_mobilization.hhm_id,
            hhm_mobilization.hoh_first,
            hhm_mobilization.hoh_last,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.location_description,
            hhm_mobilization.longitude,
            hhm_mobilization.Latitude,
            hhm_mobilization.netcards,
            hhm_mobilization.etoken_id,
            hhm_mobilization.etoken_serial,
            hhm_mobilization.etoken_pin,
            hhm_mobilization.collected_date,
            hhm_mobilization.created,
            sys_geo_codex.title AS dp,
            sys_geo_codex.geo_string,
            a.fullname AS mobilizer,
            a.loginid AS mobilizer_loginid
            FROM
            hhm_mobilization
            LEFT JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
            LEFT JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            WHERE
            hhm_mobilization.hhid = $hh_id");
        }
        public function GetReceiptHeader(){
            return DbHelper::Table("SELECT
                sys_default_settings.logo,
                sys_default_settings.receipt_header
                FROM
                sys_default_settings
                WHERE
                sys_default_settings.id = 1");
        }
        #
        #
        #   Data Export
        #
        public function ExcelGetMobilization($loginid = "", $mobilization_date = "", $geo_level = "", $geo_level_id = ""){
            #  Require variable
            $date_format = '%d/%m/%Y';
            $datetime_format = '%d/%m/%Y %r';
            #
            #  Where condition
            $where_condition = "   ";
            $seed = 0;
            #
            #
            if($loginid){
                if($seed == 0){
                    $where_condition = " WHERE a.loginid = '$loginid' ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND a.loginid = '$loginid' ";
            }
            if($mobilization_date){
                if($seed == 0){
                    $where_condition = " WHERE hhm_mobilization.collected_date = '$mobilization_date' ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND hhm_mobilization.collected_date = '$mobilization_date' ";
            }
            if($geo_level && $geo_level_id){
                if($seed == 0){
                    $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
            }
            #
            $query = "SELECT
            hhm_mobilization.hhid AS `Mobilization ID`,
            hhm_mobilization.hoh_last AS `HOH Surname`,
            hhm_mobilization.hoh_first AS `HOH Other Name`,
            hhm_mobilization.hoh_phone AS `HOH Phone`,
            hhm_mobilization.hoh_gender AS `HOH Gender`,
            hhm_mobilization.family_size AS `Family Size`,
            hhm_mobilization.allocated_net AS `Allocated Net`,
            hhm_mobilization.location_description AS `Location Category`,
            hhm_mobilization.longitude,
            hhm_mobilization.Latitude,
            hhm_mobilization.netcards AS `Allocated e-Netcard ID`,
            hhm_mobilization.etoken_serial AS `e-Token Serial`,
            DATE_FORMAT(hhm_mobilization.collected_date,'$datetime_format') AS `Collected Date`,
            sys_geo_codex.geo_string AS `Geo Location`,
            a.fullname AS mobilizer,
            a.phone AS `mobilizer phone`
            FROM
            hhm_mobilization
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
            LEFT JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname,
            usr_identity.phone
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            ) AS a ON hhm_mobilization.hhm_id = a.userid 
            $where_condition";
            #   Get payload
            $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
            $json_data = array(array(
                "sheetName" => "Mobilization",
                "data" => $data
            ));
            #   return payload
            return json_encode($json_data);
        }
        public function ExcelCountMobilization($loginid = "", $mobilization_date = "", $geo_level = "", $geo_level_id = ""){
            #
            #  Where condition
            $where_condition = "   ";
            $seed = 0;
            #
            #
            if($loginid){
                if($seed == 0){
                    $where_condition = " WHERE a.loginid = '$loginid' ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND a.loginid = '$loginid' ";
            }
            if($mobilization_date){
                if($seed == 0){
                    $where_condition = " WHERE hhm_mobilization.collected_date = '$mobilization_date' ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND hhm_mobilization.collected_date = '$mobilization_date' ";
            }
            if($geo_level && $geo_level_id){
                if($seed == 0){
                    $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
            }
            #
            #
            return DbHelper::GetScalar("SELECT
                    COUNT(hhm_mobilization.hhid) FROM
                    hhm_mobilization
                    INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
                    LEFT JOIN (SELECT
                    usr_login.userid,
                    usr_login.loginid,
                    CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname,
                    usr_identity.phone
                    FROM
                    usr_login
                    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
                    ) AS a ON hhm_mobilization.hhm_id = a.userid 
                    $where_condition");
        }
        #
        #
        #   Dashboard Count
        #
        public function DashSummary($mobilization_date = "", $geo_level = "", $geo_level_id = ""){

            #
            #  Where condition
            $where_condition_netcard = "   ";
            $seed_netcard = 1;
            $where_condition_mobilization = "   ";
            $seed_mobilization = 0;
            #
            #
            if($mobilization_date){
                if($seed_mobilization == 0){
                    $where_condition_mobilization = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$mobilization_date') ";
                    $seed_mobilization = 1;
                }
                else
                $where_condition_mobilization .= " AND DATE(hhm_mobilization.collected_date) = DATE('$mobilization_date') ";
            }
            if($geo_level && $geo_level_id){
                if($geo_level == 'state'){
                    if($seed_netcard == 0){
                        $where_condition_netcard  = " WHERE nc_netcard.location_value <= 100 AND nc_netcard.stateid = $geo_level_id ";
                        $seed_netcard = 1;
                    }
                    else{
                        $where_condition_netcard .= " AND nc_netcard.location_value <= 100 AND nc_netcard.stateid = $geo_level_id ";
                    }
                    if($seed_mobilization == 0){
                        $where_condition_mobilization = " WHERE sys_geo_codex.stateid = $geo_level_id ";
                        $seed_mobilization = 1;
                    }
                    else{
                        $where_condition_mobilization = " AND sys_geo_codex.stateid = $geo_level_id ";
                    }
                }
                elseif($geo_level == 'lga'){
                    if($seed_netcard == 0){
                        $where_condition_netcard  = " WHERE nc_netcard.location_value <= 80 AND nc_netcard.lgaid = $geo_level_id ";
                        
                        $seed_netcard = 1;
                    }
                    else{
                        $where_condition_netcard .= " AND nc_netcard.location_value <= 80 AND nc_netcard.lgaid = $geo_level_id ";
                        
                    }
                    if($seed_mobilization == 0){
                        $where_condition_mobilization = " WHERE sys_geo_codex.lgaid = $geo_level_id ";
                        $seed_mobilization = 1;
                    }
                    else{
                        $where_condition_mobilization = " AND sys_geo_codex.lgaid = $geo_level_id ";
                    }
                }
                elseif($geo_level == 'ward'){
                    if($seed_netcard == 0){
                        $where_condition_netcard  = " WHERE nc_netcard.location_value <= 60 AND nc_netcard.wardid = $geo_level_id ";
                        
                        $seed_netcard = 1;
                    }
                    else{
                        $where_condition_netcard .= " AND nc_netcard.location_value <= 60 AND nc_netcard.wardid = $geo_level_id ";
                        
                    }
                    if($seed_mobilization == 0){
                        $where_condition_mobilization = " WHERE sys_geo_codex.wardid = $geo_level_id ";
                        $seed_mobilization = 1;
                    }
                    else{
                        $where_condition_mobilization = " AND sys_geo_codex.wardid = $geo_level_id ";
                    }
                }
            }
            #
            #
            #
            return DbHelper::Table("SELECT
            --	Total netcard
            (SELECT COUNT(nc_netcard.ncid)
            FROM nc_netcard
            WHERE nc_netcard.active = 1)  AS total_netcard,
            --
            --	Total netcards with mobilizers
            (SELECT COUNT(nc_netcard.ncid)
            FROM nc_netcard
            WHERE nc_netcard.active = 1 AND nc_netcard.location_value IN (40,30) 
            $where_condition_netcard) AS total_netcard_with_mobilizers,
            --
            --	Total netcards used
            (SELECT COUNT(nc_netcard.ncid)
            FROM nc_netcard
            WHERE nc_netcard.active = 1 AND nc_netcard.location_value = 20 
            $where_condition_netcard) AS total_netcard_distributed,
            --
            --	Total mobilization
            (SELECT COUNT(hhm_mobilization.hhid)
            FROM hhm_mobilization 
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            $where_condition_mobilization) AS total_mobilization,
            --
            -- 	Total family mobilized
            IFNULL((SELECT SUM(hhm_mobilization.family_size)
            FROM hhm_mobilization 
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            $where_condition_mobilization),0) AS total_family_mobilized,
            --
            -- 	Total allocated netcard
            IFNULL((SELECT SUM(hhm_mobilization.allocated_net)
            FROM hhm_mobilization 
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            $where_condition_mobilization),0) AS total_allocated_netcard"); 
        }
        #
        #
        #
        #   Micro - Positioning
        public function GetMicroPosition($lgaid){
            $out_put = array();
            $data = DbHelper::Table("SELECT
            ms_geo_lga.Fullname AS lga,
            ms_geo_ward.ward,
            ms_geo_dp.dp,
            Count(hhm_mobilization.hhid) AS mobilization,
            Sum(hhm_mobilization.family_size) AS family_size,
            Sum(hhm_mobilization.allocated_net) AS allocated_net
            FROM
            hhm_mobilization
            INNER JOIN ms_geo_dp ON hhm_mobilization.dp_id = ms_geo_dp.dpid
            INNER JOIN ms_geo_ward ON ms_geo_ward.wardid = ms_geo_dp.wardid
            INNER JOIN ms_geo_lga ON ms_geo_lga.LgaId = ms_geo_ward.lgaid
            WHERE
            ms_geo_lga.LgaId = $lgaid
            GROUP BY
            ms_geo_dp.dp");
            #
            if(count($data)){
                foreach($data as $d){
                    #   add more
                    $in_bales = ceil($d['allocated_net']/50);
                    $d['in_bales'] = $in_bales;
                    $d['difference'] = $d['allocated_net'] - ($in_bales*50);
                    #   assign
                    $out_put[] = $d;
                }
            }
            return $out_put;
        }
        public function ExcelGetMicroPosition($lgaid){
            $out_put = array();
            $query = "SELECT
            ms_geo_lga.Fullname AS lga,
            ms_geo_ward.ward,
            ms_geo_dp.dp,
            Count(hhm_mobilization.hhid) AS `mobilization`,
            Sum(hhm_mobilization.family_size) AS `family size`,
            Sum(hhm_mobilization.allocated_net) AS `allocated net`,
            CEIL(Sum(hhm_mobilization.allocated_net)/50) AS `bales`,
            (Sum(hhm_mobilization.allocated_net) - CEIL(Sum(hhm_mobilization.allocated_net)/50) * 50) AS `difference`
            FROM
            hhm_mobilization
            INNER JOIN ms_geo_dp ON hhm_mobilization.dp_id = ms_geo_dp.dpid
            INNER JOIN ms_geo_ward ON ms_geo_ward.wardid = ms_geo_dp.wardid
            INNER JOIN ms_geo_lga ON ms_geo_lga.LgaId = ms_geo_ward.lgaid
            WHERE
            ms_geo_lga.LgaId = $lgaid
            GROUP BY
            ms_geo_dp.dp";
            #   Get payload
            $data = $this->db->ExcelDataTable($query);
            
            #   Prep Payload
            $json_data = array(array(
                "sheetName" => "Micro-Positioning",
                "data" => $data
            ));
            #   return payload
            return json_encode($json_data);
        }
        public function ExcelCountMicroPosition($lgaid){
            $data = DbHelper::Table("SELECT
            COUNT(*)
            FROM
            hhm_mobilization
            INNER JOIN ms_geo_dp ON hhm_mobilization.dp_id = ms_geo_dp.dpid
            INNER JOIN ms_geo_ward ON ms_geo_ward.wardid = ms_geo_dp.wardid
            INNER JOIN ms_geo_lga ON ms_geo_lga.LgaId = ms_geo_ward.lgaid
            WHERE
            ms_geo_lga.LgaId = $lgaid
            GROUP BY
            ms_geo_dp.dp");
            #   return
            return count($data);
        }
    }

?>