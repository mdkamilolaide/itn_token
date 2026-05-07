<?php

namespace Distribution;
use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');

class Distribution {
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
    public function GetDpLocationMaster($wardid){
        return DbHelper::Table("SELECT
        sys_geo_codex.id,
        sys_geo_codex.guid,
        sys_geo_codex.stateid,
        sys_geo_codex.lgaid,
        sys_geo_codex.wardid,
        sys_geo_codex.dpid,
        sys_geo_codex.geo_level_id,
        sys_geo_codex.geo_level,
        sys_geo_codex.geo_value,
        sys_geo_codex.title,
        sys_geo_codex.geo_string,
        '' AS pick
        FROM
        sys_geo_codex
        WHERE
        sys_geo_codex.wardid = $wardid AND sys_geo_codex.geo_level = 'dp'");
    }
    public function GetDpLocationMasterByLga($lgaid){
        return DbHelper::Table("SELECT
        sys_geo_codex.id,
        sys_geo_codex.guid,
        sys_geo_codex.stateid,
        sys_geo_codex.lgaid,
        sys_geo_codex.wardid,
        sys_geo_codex.dpid,
        sys_geo_codex.geo_level_id,
        sys_geo_codex.geo_level,
        sys_geo_codex.geo_value,
        sys_geo_codex.title,
        sys_geo_codex.geo_string,
        '' AS pick
        FROM
        sys_geo_codex
        WHERE
        sys_geo_codex.lgaid = $lgaid AND sys_geo_codex.geo_level = 'dp'");
    }
    public function GetDpLocationMasterList($dpid_array){
        $dp_list = ArrayToCsv($dpid_array);
        #
        return DbHelper::Table("SELECT
        sys_geo_codex.id,
        sys_geo_codex.guid,
        sys_geo_codex.stateid,
        sys_geo_codex.lgaid,
        sys_geo_codex.wardid,
        sys_geo_codex.dpid,
        sys_geo_codex.geo_level_id,
        sys_geo_codex.geo_level,
        sys_geo_codex.geo_value,
        sys_geo_codex.title,
        sys_geo_codex.geo_string,
        '' AS pick
        FROM
        sys_geo_codex
        WHERE
        sys_geo_codex.dpid IN ($dp_list)
        AND sys_geo_codex.geo_level = 'dp'");
    }
    public function GetGeoCodexDetails($guid){
        return DbHelper::Table("SELECT
        sys_geo_codex.id,
        sys_geo_codex.guid,
        sys_geo_codex.stateid,
        sys_geo_codex.lgaid,
        sys_geo_codex.wardid,
        sys_geo_codex.dpid,
        sys_geo_codex.geo_level_id,
        sys_geo_codex.geo_level,
        sys_geo_codex.geo_value,
        sys_geo_codex.title AS geo_title,
        sys_geo_codex.geo_string,
        sys_geo_codex.is_gsone,
        ms_geo_ward.ward,
        ms_geo_lga.Fullname AS lga,
        ms_geo_state.Fullname AS state
        FROM
        sys_geo_codex
        LEFT JOIN ms_geo_ward ON sys_geo_codex.wardid = ms_geo_ward.wardid
        LEFT JOIN ms_geo_lga ON sys_geo_codex.lgaid = ms_geo_lga.LgaId
        LEFT JOIN ms_geo_state ON sys_geo_codex.stateid = ms_geo_state.StateId
        WHERE
        sys_geo_codex.guid = '$guid'");
    }
    public function DownloadMobilizationData($dp){
        return DbHelper::Table("SELECT
        hhm_mobilization.hhid,
        hhm_mobilization.hoh_first,
        hhm_mobilization.hoh_last,
        hhm_mobilization.hoh_phone,
        hhm_mobilization.hoh_gender,
        hhm_mobilization.family_size,
        hhm_mobilization.allocated_net,
        hhm_mobilization.location_description,
        hhm_mobilization.netcards,
        hhm_mobilization.etoken_id,
        hhm_mobilization.etoken_serial,
        hhm_mobilization.etoken_pin,
        hhm_mobilization.collected_date,
        nc_token.uuid 
        FROM
            hhm_mobilization
            INNER JOIN nc_token ON hhm_mobilization.etoken_id = nc_token.tokenid 
        WHERE
            hhm_mobilization.dp_id = $dp
            AND hhm_mobilization.hhid NOT IN (SELECT
        hhm_distribution.hhid
        FROM
        hhm_distribution
        WHERE hhm_distribution.dp_id = $dp)");
    }
    public function DownloadMobilizationDataBackup($dp){
        return DbHelper::Table("SELECT
        hhm_mobilization.hhid,
        hhm_mobilization.hoh_first,
        hhm_mobilization.hoh_last,
        hhm_mobilization.hoh_phone,
        hhm_mobilization.hoh_gender,
        hhm_mobilization.family_size,
        hhm_mobilization.allocated_net,
        hhm_mobilization.location_description,
        hhm_mobilization.netcards,
        hhm_mobilization.etoken_id,
        hhm_mobilization.etoken_serial,
        hhm_mobilization.etoken_pin,
        hhm_mobilization.collected_date,
        a.fullname AS mobilizer_fullname,
        a.loginid AS mobilizer_loginid,
        nc_token.uuid AS etoken_uuid,
        if(hhm_distribution.dis_id,1,0) AS issued
        FROM
        hhm_mobilization
        LEFT JOIN nc_token ON hhm_mobilization.etoken_id = nc_token.tokenid
        LEFT JOIN (
            SELECT
                usr_login.userid,
                usr_login.loginid,
                CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
            FROM
                usr_login
                INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
            ) AS a ON hhm_mobilization.hhm_id = a.userid
        LEFT JOIN hhm_distribution ON hhm_mobilization.hhid = hhm_distribution.hhid
        WHERE
        hhm_mobilization.dp_id = $dp");
    }
    #
    #
    #
    #
    public function BulkDistibution($bulk_distribution){
        $counter = 0;
        $date = getNowDbDate();
        if(count($bulk_distribution)){
            #   init transaction
            $this->db->beginTransaction();
            foreach($bulk_distribution as $a){
                #   get data
                #   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, etoken_id, etoken_serial, longitude, latitude, device_serial, app_version]
                #   [eolin_bring_old_net, eolin_total_old_net]
                #
                # - Check if exist
                $count_existing_data = $this->db->executeTransactionScalar("SELECT COUNT(*) FROM hhm_distribution WHERE hhm_distribution.etoken_serial = '".$a['etoken_serial']."'");
                #   perform insert operation on item if not found
                if($count_existing_data < 1){
                    #   Safe EOLIN updated data
                    //  upgrade data collection
                    $eolin_bring_old_net = array_key_exists("eolin_bring_old_net",$a)? $a['eolin_bring_old_net']:'';
                    $eolin_bring_old_net = intval($eolin_bring_old_net) >= 0 || $eolin_bring_old_net != ""? $eolin_bring_old_net: 0;
                    $eolin_total_old_net = array_key_exists("eolin_total_old_net",$a)? intval($a['eolin_total_old_net']):0;
                    $eolin_total_old_net = intval($eolin_total_old_net) >= 0 || $eolin_total_old_net != ""? $eolin_total_old_net: 0;
                    #   insert distribution
                    $this->db->executeTransaction("INSERT INTO hhm_distribution (`dp_id`,`hhid`,`etoken_id`,`etoken_serial`,`recorder_id`,`distributor_id`,`collected_nets`,`is_gs_net`,`gs_net_serial`,`longitude`,`latitude`,`device_serial`,`app_version`,`eolin_bring_old_net`,`eolin_total_old_net`,`collected_date`,`created`) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        array($a['dp_id'],$a['mobilization_id'],$a['etoken_id'],$a['etoken_serial'],$a['recorder_id'],$a['distributor_id'],$a['collected_nets'],$a['is_gs_net'],$a['gs_net_serial'],$a['longitude'],$a['latitude'],$a['device_serial'],$a['app_version'],$eolin_bring_old_net,$eolin_total_old_net,$a['collected_date'],$date));
                        #   get ID
                    $dis_id = $this->db->executeTransactionLastId();
                    #   insert GS1 physical net if available
                    $net_data_list = json_decode($a['gs_net_serial'], true);
                    if(is_array($net_data_list)){
                        if(count($net_data_list)){
                            foreach($net_data_list as $v){
                                $batch_no = array_key_exists("batchNumber",$v)? $v['batchNumber']:'';
                                $exp_date = array_key_exists("expDate",$v)? $v['expDate']:'';
                                $gtin = array_key_exists("gtin",$v)? $v['gtin']:'';
                                $netdata = array_key_exists("netData",$v)? $v['netData']:'';
                                $serial =  array_key_exists("serialNumber",$v)? $v['serialNumber']:'';
                                $prod = array_key_exists("prodDate",$v)? $v['prodDate']:'';
                                #
                                $netQuery = "INSERT INTO hhm_gs_net_serial (`dis_id`,`hhid`,`etoken_id`,`net_serial`,`gtin`,`sgtin`,`batch`,`expiry`) 
                                VALUES (?,?,?,?,?,?,?,?)";
                                $this->db->executeTransaction($netQuery,array($dis_id, $a['mobilization_id'], $a['etoken_id'],$netdata,$gtin,$serial,$batch_no,$exp_date));
                            }
                        }
                    }
                    #
                    $counter++;
                }
            }
            #   Complete transaction
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
            
        }
        return $counter;
    }
    public function BulkDistibutionWithReturns($bulk_distribution){
        $counter = 0;
        $success = array();
        $failed = array();
        $returnData = [];
        $date = getNowDbDate();
        if(count($bulk_distribution)){
            #   init transaction
            $this->db->beginTransaction();
            #   filter duplicates
            $bulk_filtered = $this->filterDuplicate($bulk_distribution, 'etoken_serial');
            foreach($bulk_filtered as $a){
                #   get data
                #   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, etoken_id, etoken_serial, longitude, latitude, device_serial, app_version]
                #   [eolin_bring_old_net, eolin_total_old_net]

                # - Check if exist
                $count_existing_data = $this->db->executeTransactionScalar("SELECT COUNT(*) FROM hhm_distribution WHERE hhm_distribution.etoken_serial = '".$a['etoken_serial']."'");
                #   perform insert operation on item if not found
                if($count_existing_data < 1){
                    #   Safe EOLIN updated data
                    //  upgrade data collection
                    $eolin_bring_old_net = array_key_exists("eolin_bring_old_net",$a)? $a['eolin_bring_old_net']:'';
                    $eolin_bring_old_net = intval($eolin_bring_old_net) >= 0 || $eolin_bring_old_net != ""? $eolin_bring_old_net: 0;
                    $eolin_total_old_net = array_key_exists("eolin_total_old_net",$a)? intval($a['eolin_total_old_net']):0;
                    $eolin_total_old_net = intval($eolin_total_old_net) >= 0 || $eolin_total_old_net != ""? $eolin_total_old_net: 0;
                    #   insert distribution
                    $this->db->executeTransaction("INSERT INTO hhm_distribution (`dp_id`,`hhid`,`etoken_id`,`etoken_serial`,`recorder_id`,`distributor_id`,`collected_nets`,`is_gs_net`,`gs_net_serial`,`longitude`,`latitude`,`device_serial`,`app_version`,`eolin_bring_old_net`,`eolin_total_old_net`,`collected_date`,`created`) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        array($a['dp_id'],$a['mobilization_id'],$a['etoken_id'],$a['etoken_serial'],$a['recorder_id'],$a['distributor_id'],$a['collected_nets'],$a['is_gs_net'],$a['gs_net_serial'],$a['longitude'],$a['latitude'],$a['device_serial'],$a['app_version'],$eolin_bring_old_net,$eolin_total_old_net,$a['collected_date'],$date));
                        #   get ID
                    $dis_id = $this->db->executeTransactionLastId();
                    #   insert GS1 physical net if available
                    $net_data_list = json_decode($a['gs_net_serial'], true);
                    if(is_array($net_data_list)){
                        if(count($net_data_list)){
                            foreach($net_data_list as $v){
                                $batch_no = array_key_exists("batchNumber",$v)? $v['batchNumber']:'';
                                $exp_date = array_key_exists("expDate",$v)? $v['expDate']:'';
                                $gtin = array_key_exists("gtin",$v)? $v['gtin']:'';
                                $netdata = array_key_exists("netData",$v)? $v['netData']:'';
                                $serial =  array_key_exists("serialNumber",$v)? $v['serialNumber']:'';
                                $prod = array_key_exists("prodDate",$v)? $v['prodDate']:'';
                                #
                                $netQuery = "INSERT INTO hhm_gs_net_serial (`dis_id`,`hhid`,`etoken_id`,`net_serial`,`gtin`,`sgtin`,`batch`,`expiry`) 
                                VALUES (?,?,?,?,?,?,?,?)";
                                $this->db->executeTransaction($netQuery,array($dis_id, $a['mobilization_id'], $a['etoken_id'],$netdata,$gtin,$serial,$batch_no,$exp_date));
                            }
                        }
                    }
                    #
                    $success[] = $a['etoken_serial'];
                    $counter++;
                }
                else{
                    #   failed
                    $failed[] = $a['etoken_serial'];
                }
            }
            #   Complete transaction
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
        }
        return array('success'=>$success, 'failed'=>$failed);
    }
    public function BulkDistibutionStatus($bulk_distribution){
        $counter = 0;
        $failCounter = 0;
        $date = getNowDbDate();
        if(count($bulk_distribution)){
            #   init transaction
            $this->db->beginTransaction();
            foreach($bulk_distribution as $a){
                #   get data
                #   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, etoken_id]
                #   [eolin_bring_old_net, eolin_total_old_net]
                #
                # - Check if exist
                $count_existing_data = $this->db->executeTransactionScalar("SELECT COUNT(*) FROM hhm_distribution WHERE hhm_distribution.etoken_id = '".$a['etoken_id']."'");
                #   perform insert operation on item if not found
                if($count_existing_data < 1){
                    #   Safe EOLIN updated data
                    //  upgrade data collection
                    $eolin_bring_old_net = array_key_exists("eolin_bring_old_net",$a)? $a['eolin_bring_old_net']:'';
                    $eolin_bring_old_net = intval($eolin_bring_old_net) >= 0 || $eolin_bring_old_net != ""? $eolin_bring_old_net: 0;
                    $eolin_total_old_net = array_key_exists("eolin_total_old_net",$a)? intval($a['eolin_total_old_net']):0;
                    $eolin_total_old_net = intval($eolin_total_old_net) >= 0 || $eolin_total_old_net != ""? $eolin_total_old_net: 0;
                    #   insert distribution
                    $this->db->executeTransaction("INSERT INTO hhm_distribution (`dp_id`,`hhid`,`etoken_id`,`recorder_id`,`distributor_id`,`collected_nets`,`is_gs_net`,`gs_net_serial`,`eolin_bring_old_net`,`eolin_total_old_net`,`collected_date`,`created`) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                        array($a['dp_id'],$a['mobilization_id'],$a['etoken_id'],$a['recorder_id'],$a['distributor_id'],$a['collected_nets'],$a['is_gs_net'],$a['gs_net_serial'],$eolin_bring_old_net,$eolin_total_old_net,$a['collected_date'],$date));
                        #   get ID
                    $dis_id = $this->db->executeTransactionLastId();
                    #   insert GS1 physical net if available
                    $net_data_list = json_decode($a['gs_net_serial'], true);
                    if(is_array($net_data_list)){
                        if(count($net_data_list)){
                            foreach($net_data_list as $v){
                                $batch_no = array_key_exists("batchNumber",$v)? $v['batchNumber']:'';
                                $exp_date = array_key_exists("expDate",$v)? $v['expDate']:'';
                                $gtin = array_key_exists("gtin",$v)? $v['gtin']:'';
                                $netdata = array_key_exists("netData",$v)? $v['netData']:'';
                                $serial =  array_key_exists("serialNumber",$v)? $v['serialNumber']:'';
                                $prod = array_key_exists("prodDate",$v)? $v['prodDate']:'';
                                #
                                $netQuery = "INSERT INTO hhm_gs_net_serial (`dis_id`,`hhid`,`etoken_id`,`net_serial`,`gtin`,`sgtin`,`batch`,`expiry`) 
                                VALUES (?,?,?,?,?,?,?,?)";
                                $this->db->executeTransaction($netQuery,array($dis_id, $a['mobilization_id'], $a['etoken_id'],$netdata,$gtin,$serial,$batch_no,$exp_date));
                            }
                        }
                    }
                    #
                    $counter++;
                }
                else{
                    $failCounter++;
                }
            }
            #   Complete transaction
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
            
        }
        return array('success'=>$counter,'fail'=>$failCounter);
    }
    private function filterDuplicate($data, $field){
        $uniqueArray = array();
        $uniqueSerial = array();
        #
        foreach ($data as $item) {
            $serial = $item[$field];
            if (!in_array($serial, $uniqueSerial)) {
                $uniqueSerial[] = $serial;
                $uniqueArray[] = $item;
            }
        }
        #
        return $uniqueArray;
    }
}

?>