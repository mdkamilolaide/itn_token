<?php
namespace Smc;
use DbHelper;
use Exception;

#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Registration {
    private $db;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    #
    #
    #       HOUSEHOLD
    #
    #
    public function CreateHousehold($hh_token,$name, $phone){
        $date = getNowDbDate();
        return DbHelper::Insert("smc_child_household",array(
            'hh_token'=>$hh_token,
            'hoh_name'=>$name,
            'hoh_phone'=>$phone,
            'created'=>$date,
            'updated'=>$date
        ));
    }
    public function UpdateHousehold($hh_token,$name,$phone,$householdId){
        $date = getNowDbDate();
        return DbHelper::Update("smc_child_household",array(
            'hh_token'=>$hh_token,
            'hoh_name'=>$name,
            'hoh_phone'=>$phone,
            'updated'=>$date
        ),"hhid",$householdId);
    }
    public  function DeleteHousehold($householdId){
        return DbHelper::Delete("smc_child_household","hhid",$householdId);
    }
    #   ['dpid','hh_token','hoh','phone','longitude','latitude','user_id','device_serial','app_version','created']
    #   return false if failed and arrays of hh_token if successful
    public function CreateHouseholdBulk($bulk_data){
        if (is_array($bulk_data) && count($bulk_data) > 0) {
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $row = [];
            try{
                $this->db->beginTransaction();
                foreach($bulk_data as $row){
                    $device_serial = isset($row['device_serial']) ? $row['device_serial'] : '';
                    $app_version = isset($row['app_version']) ? $row['app_version'] : '';
                    $qu = "INSERT INTO `smc_child_household` (`dpid`,`hh_token`,`hoh_name`,`hoh_phone`,`longitude`,`latitude`,`user_id`,`device_serial`,`app_version`,`created`,`updated`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                    $this->db->executeTransaction($qu,array($row['dpid'],$row['hh_token'],$row['hoh'],$row['phone'],$row['longitude'],$row['latitude'],$row['user_id'],$device_serial,$app_version,$row['created'],$date));
                    $retarray[] = $row['hh_token'];
                }
                $this->db->commitTransaction();
            }catch(Exception $e){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "----Create Household - DB error message: ".$this->db->ErrorMessage."\r\nData:".json_encode($row)."\r\nDate: $date \r\n";
                WriteToFile($error_file_name, $error_to_write);
            }
            /*
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nCreate Household - DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            */
            # Completed
            return $retarray;
        }else{
            return false;
        }
    }
    # ['hh_token','hoh','phone']
    public function UpdateHouseholdBulk($bulk_data){
        if (is_array($bulk_data) && count($bulk_data) > 0) {
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $row = [];
            try{
                $this->db->beginTransaction();
                foreach($bulk_data as $row){
                
                    $qu = "UPDATE `smc_child_household` SET `hoh_name`=?,`hoh_phone`=?,`updated`=? WHERE `hh_token`=?";
                    $this->db->executeTransaction($qu,array($row['hoh'],$row['phone'],$date,$row['hh_token']));
                    $retarray[] = $row['hh_token'];
                }
                $this->db->commitTransaction();
            }catch(Exception $e){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "----Update Household - DB error message: ".$this->db->ErrorMessage."\r\nData:".json_encode($row)."\r\nDate: $date \r\n";
                WriteToFile($error_file_name, $error_to_write);
            }
            /*
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nUpdate household DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            */
            # Completed
            return $retarray;
        }else{
            return false;
        }
    }
    #
    #
    #       CHILD INFO
    #
    #   ['hh_token','beneficiary_id','dpid','name','gender','dob','longitude','latitude','user_id','device_serial','app_version','created']
    public function CreateChildBulk($bulk_data){
        if (is_array($bulk_data) && count($bulk_data) > 0) {
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            try{
                $this->db->beginTransaction();
                foreach($bulk_data as $row){
                    $device_serial = isset($row['device_serial']) ? $row['device_serial'] : '';
                    $app_version = isset($row['app_version']) ? $row['app_version'] : '';
                    $qu = "INSERT INTO `smc_child` (`hh_token`,`beneficiary_id`,`dpid`,`name`,`gender`,`dob`,`longitude`,`latitude`,`user_id`,`device_serial`,`app_version`,`created`,`updated`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
                    $this->db->executeTransaction($qu,array($row['hh_token'],$row['beneficiary_id'],$row['dpid'],$row['name'],$row['gender'],$row['dob'],$row['longitude'],$row['latitude'],$row['user_id'],$device_serial,$app_version,$row['created'],$date));
                    $retarray[] = $row['beneficiary_id'];
                }
                # Completed
                $this->db->commitTransaction();
            }catch(Exception $ex){
                //return array();
            }
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nCreate Child DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            
            
            return $retarray;
        }else{
            return false;
        }
    }
    #   ['beneficiary_id','name','gender','dob']
    public function UpdateChildBulk($bulk_data){
        if (is_array($bulk_data) && count($bulk_data) > 0) {
            $retarray = array();
            $date = getNowDbDate();
            # Start transactions
            $row = [];
            try{
                $this->db->beginTransaction();
                foreach($bulk_data as $row){
                    // corrected UPDATE (was malformed and caused bound-parameter mismatch)
                    $qu = "UPDATE `smc_child` SET `name` = ?, `gender` = ?, `dob` = ?, `updated` = ? WHERE `beneficiary_id` = ?";
                    $this->db->executeTransaction($qu, array($row['name'], $row['gender'], $row['dob'], $date, $row['beneficiary_id']));
                    $retarray[] = $row['beneficiary_id'];
                }
                # Completed
                $this->db->commitTransaction();
            }catch(Exception $e){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "----Update child - DB error message: ".$this->db->ErrorMessage."\r\nData:".json_encode($row)."\r\nDate: $date \r\n";
                WriteToFile($error_file_name, $error_to_write);
            }
            /*
            #   log error if any
            $error_message = $this->db->ErrorMessage;
            if(strlen($error_message)>0){
                #   Write to file
                $error_file_name = "error-report.txt";
                $error_to_write = "\r\nUpdate child DB error message: $error_message\r\nData:".json_encode($bulk_data)."\r\nDate: $date";
                WriteToFile($error_file_name, $error_to_write);
            }
            */
            
            return $retarray;
        }else{
            return false;
        }
    }

}
?>