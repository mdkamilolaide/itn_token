<?php

namespace Distribution;
use DbHelper;
use Exception;

#
include_once('lib/common.php');
include_once('lib/mysql.min.php');

class GsVerification {
    #
    private $db;
    #   Set batch limit
    private $limit = 500;   
    #
    #   Construct
    public function __construct($limit = 100){
        $this->db = GetMysqlDatabase();
        $this->limit = $limit;
    }
    #   
    #   Private Methods
    private function GetUnverified(){
        #   (snid, gtin, sgtin)
        $limit = " LIMIT ".$this->limit;
        return $this->db->DataTable("SELECT
        hhm_gs_net_serial.snid,
        hhm_gs_net_serial.gtin,
        hhm_gs_net_serial.sgtin
        FROM
        hhm_gs_net_serial
        WHERE
        hhm_gs_net_serial.is_verified = 0
        $limit");
    }
    private function VerifySgtin($value){
        $query = "SELECT ms_product_sgtin.sgtinid FROM ms_product_sgtin
        WHERE sgtin = '$value'";
        return $this->db->executeTransactionScalar($query);
    }
    #   
    #   Public methods
    public function RunVerification(){
        #   Getunverified list
        #   compare the list individually
        #   log result
        #   log verification task
        $date = getNowDbDate();
        $unverifiedList = $this->GetUnverified();
        if(count($unverifiedList)){
            #
            #
            #echo "<pre>";
            #print_r($unverifiedList);
            #echo "</pre>";
            #return;
            #
            #
            $this->db->beginTransaction();
            $counter = 0;
            $total_failed = 0;
            $total_success = 0;
            $error_note = "";
            foreach($unverifiedList as $item){
                //  (snid, gtin, sgtin)
                #   verify
                try{
                    if($item['sgtin']){
                        $sgtinid = $this->VerifySgtin($item['sgtin']);
                        if($sgtinid){
                            # successful or Value exist 
                            # Log success
                            $que = "INSERT INTO hhm_gs_net_verification (`sgtinid`, `snid`, `sgtin`, `status`, `note`, `created`) VALUES (?,?,?,?,?,?)";
                            $this->db->executeTransaction($que,array($sgtinid, $item['snid'], $item['sgtin'], 'success', "success", $date));
                            $counter++;
                            $total_success++;
                        }
                        else
                        {
                            # Failed or Value non-exist
                            # Log failed
                            $eque = "INSERT INTO hhm_gs_net_verification (`snid`, `sgtin`, `status`, `note`, `created`) VALUES (?,?,?,?,?)";
                            $this->db->executeTransaction($eque,array($item['snid'], $item['sgtin'], 'failed', "Not on master list", $date));
                            $counter++;
                            $total_failed++;
                        }
                    }
                    else{
                        $snid = $item['snid'];
                        $error_note .= "\n Record ID: $snid is null or empty";
                        $eque = "INSERT INTO hhm_gs_net_verification (`snid`, `sgtin`, `status`, `note`, `created`) VALUES (?,?,?,?,?)";
                        $this->db->executeTransaction($eque,array($item['snid'], $item['sgtin'], 'failed', "SGTIN not captured", $date));
                        $counter++;
                        $total_failed++;
                    }
                }
                catch(Exception $ex){
                    #   Unknown error
                    
                    $snid = $item['snid'];
                    $error_note .= "\n Record ID: $snid has unknown error";
                    $eque = "INSERT INTO hhm_gs_net_verification (`snid`, `sgtin`, `status`, `note`, `created`) VALUES (?,?,?,?,?)";
                    $this->db->executeTransaction($eque,array($item['snid'], $item['sgtin'], 'failed', "unknown error", $date));
                    $counter++;
                    $total_failed++;
                    
                    echo json_encode(array('proc_no'=>504,'message'=>"Record ID: $snid has unknown error\r\n"));
                }
                
                # update verification done
                $uque = "UPDATE hhm_gs_net_serial SET is_verified = 1 WHERE snid = ? LIMIT 1";
                $this->db->executeTransaction($uque,array($item['snid']));
            }
            #log varification activity
            $verif_note = "Total success: $total_success, total failed: $total_failed. $error_note";
            $logq = "INSERT INTO hhm_gs_net_verification_log (`total_verification`, `description`, `created`) VALUES (?,?,?)";
            $this->db->executeTransaction($logq, array($counter, $verif_note, $date));
            #   Commit
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
            echo json_encode(array('proc_no'=>200,'message'=>"$verif_note"));
        }
        else{
            echo json_encode(array('proc_no'=>404,'message'=>"Nothing found to verify"));
        }
    }
    public function ChangeLimit($limit){
        $this->limit = $limit;
    }
    #
    #
    #   Traceability search
    public function TraceabilitySearch($gtin, $sgtin){
        $manufacturer = $this->db->DataTable("SELECT
        ms_product_item.itemid,
        ms_product_item.brand_name,
        ms_product_item.product_description,
        ms_product_item.manufacturer_name,
        ms_product_item.gtin,
        ms_product_item.created
        FROM
        ms_product_item
        WHERE
        ms_product_item.gtin = '$gtin'");
        $logistics = $this->db->DataTable("SELECT
        hhm_gs_net_serial.gtin,
        hhm_gs_net_serial.sgtin,
        hhm_gs_net_serial.batch,
        hhm_distribution.collected_nets,
        hhm_distribution.collected_date,
        'Cross River Warehouse' AS `state_warehouse`,
        hhm_mobilization.hoh_first,
        hhm_mobilization.hoh_last,
        hhm_mobilization.hoh_phone,
        hhm_mobilization.family_size,
        hhm_mobilization.location_description,
        hhm_mobilization.longitude,
        hhm_mobilization.Latitude,
        hhm_mobilization.etoken_serial,
        sys_geo_codex.geo_string
        FROM
        hhm_gs_net_serial
        INNER JOIN hhm_distribution ON hhm_gs_net_serial.dis_id = hhm_distribution.dis_id
        INNER JOIN hhm_mobilization ON hhm_distribution.hhid = hhm_mobilization.hhid
        INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
        WHERE
        hhm_gs_net_serial.sgtin = '$sgtin'");
        #
        return array('manufacturer'=>$manufacturer, 'logistic'=>$logistics);
    }
}

?>