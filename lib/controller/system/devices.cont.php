<?php

namespace System;

use DbHelper;
use Exception;

#
include_once('lib/common.php');
include_once('lib/mysql.min.php');

#
#   Happens upon login
#   CheckDevice availability (if empty(register device) else (continue login))
#   On login log device with user
#
#
class Devices{
    private $db;
    #
    #
    public function __construct(){
        # Declare db;
        $this->db = GetMysqlDatabase();
    }

    #
    #
    public function RegisterDevice($device_name, $device_id, $device_type){
        $check_data = $this->CheckDevice($device_id);
        if(count($check_data)){
            return $check_data;
        }
        $guid = generateUUID();
        $date = getNowDbDate();
        try {
            #
            $this->db->beginTransaction();
            #   Insert new device
            $insert_query = "INSERT INTO `sys_device_registry` (`device_name`,`device_id`,`guid`,`device_type`,`created`,`updated`) VALUES (?,?,?,?,?,?)";
            $inser_data = array($device_name, $device_id, $guid, $device_type, $date, $date);
            $this->db->executeTransaction($insert_query, $inser_data);
            #   Get the last inserted transaction id
            $id = $this->db->executeTransactionLastId();
            #   Update serial number
            $pre_pad = GenerateCodeAlphabet(3);
            $serial_num = $pre_pad.str_pad($id, 3, '0', STR_PAD_LEFT);
            $this->db->executeTransaction("UPDATE `sys_device_registry` SET `serial_no`= ? WHERE `id`=? LIMIT 1",array($serial_num,$id));
            #   Finalized the transaction
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
            #
            return DbHelper::Table("SELECT * FROM `sys_device_registry` WHERE `id`=$id");
        }
        catch(Exception $e){
            return "Error: Invalid device ID supplied";
        }
    }
    #
    #   Check device status if it has been register in the system before
    public function CheckDevice($device_id)
    {
        return DbHelper::Table("SELECT
            sys_device_registry.id,
            sys_device_registry.device_name,
            sys_device_registry.device_id,
            sys_device_registry.guid,
            sys_device_registry.serial_no,
            sys_device_registry.active,
            sys_device_registry.connected,
            sys_device_registry.created,
            sys_device_registry.updated
            FROM
            sys_device_registry
            WHERE
            sys_device_registry.device_id = '$device_id'");
    }
    #
    #
    #   Activate and deactivate devices
    public function ToggleActive($serial_num){
        # code...
        $date = getNowDbDate();
        if($this->ActiveStatus($serial_num)){
            #   Deactivate device
            return DbHelper::Update("sys_device_registry",array('active'=>0,'updated'=>$date),'serial_no',$serial_num);
        }
        else{
            #   Activate device
            return DbHelper::Update("sys_device_registry",array('active'=>1,'updated'=>$date),'serial_no',$serial_num);
        }
    }
    public function BulkToggleActive($serial_array){
        $counter = 0;
        if(count($serial_array) > 0){
            $this->db->beginTransaction();
            #
            for($a=0;$a<count($serial_array);$a++){
                #   Get device status
                if($this->db->executeTransactionScalar("SELECT active FROM sys_device_registry WHERE serial_no = '".$serial_array[$a]."'")){
                    #   Deactivate
                    $this->db->executeTransaction("UPDATE sys_device_registry SET `active`=0, `updated`=? WHERE serial_no=?",array(getNowDbDate(),$serial_array[$a]));
                }
                else{
                    #   Activate
                    $this->db->executeTransaction("UPDATE sys_device_registry SET `active`=1, `updated`=? WHERE serial_no=?",array(getNowDbDate(),$serial_array[$a]));
                }
                $counter++;
            }
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
        }
        return $counter;
    }
    public function BulkDelete($serial_array){
        $counter = 0;
        if(count($serial_array) > 0){
            $this->db->beginTransaction();
            for($a=0;$a<count($serial_array);$a++){
                #   delete 
                $this->db->executeTransaction("DELETE FROM `sys_device_registry` WHERE `sys_device_registry`.`serial_no` = ?",array($serial_array[$a]));
                $counter++;
            }
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
        }
        return $counter;
    }
    #
    public function UpdateDeviceWithSerial($imei1, $imei2, $phone_serial, $sim_network, $sim_serial, $device_serial){
        $date = getNowDbDate();
        #
        return DbHelper::Update("sys_device_registry",array(
            "imei1"=>$imei1,
            "imei2"=>$imei2,
            "phone_serial"=>$phone_serial,
            "sim_network"=>$sim_network,
            "sim_serial"=>$sim_serial
        ),"serial_no",$device_serial);
    }
    #
    public function BulkUpdateDeviceWithSerial($bulk_data){
        $counter = 0;
        if(count($bulk_data)){
            $this->db->beginTransaction();
            # "(imei1, imei2, phone_serial, sim_network, sim_serial, serial_no)
            foreach($bulk_data as $v){
                $que = "UPDATE `sys_device_registry` SET `imei1`=?, `imei2`=?, `phone_serial`=?, `sim_network`=?, `sim_serial`=? WHERE `serial_no`=?";
                $this->db->executeTransaction($que,
                array(
                    GetSafeArrayValue($v,'imei1'),
                    GetSafeArrayValue($v,'imei2'),
                    GetSafeArrayValue($v,'phone_serial'),
                    GetSafeArrayValue($v,'sim_network'),
                    GetSafeArrayValue($v,'sim_serial'),
                    GetSafeArrayValue($v,'serial_no'),
                ));
                $counter++;
            }
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
        }
        echo $this->db->ErrorMessage;
        return $counter;
    }
    #
    #
    #
    private function ActiveStatus($serial_num)
    {
        return DbHelper::GetScalar("SELECT
        sys_device_registry.active
        FROM
        sys_device_registry
        WHERE
        sys_device_registry.serial_no = '$serial_num'");
    }
}
?>