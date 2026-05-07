<?php
namespace Smc;

use DbHelper;

#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
include_once('lib/autoload.php');
class Inventory {
    private $db;
    private $pdo;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
        $this->pdo = $this->db->Conn;
    }
    #   [product_code, product_name, location_type, location_id, batch, expiry_date, rate, unit, primary_qty, secondary_qty]
    private function InboundMgt($product_code, $product_name, $location_type, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty, $userid){
        //  selectinventory balance with keys to check if the product exists and current balance
        $sql = "SELECT primary_qty, secondary_qty FROM smc_inventory_central WHERE product_code = :product_code AND location_type = :location_type AND location_id = :location_id AND batch = :batch AND expiry = :expiry_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':product_code', $product_code);
        $stmt->bindParam(':location_type', $location_type);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':batch', $batch);
        $stmt->bindParam(':expiry_date', $expiry_date);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $row_count = $stmt->rowCount();
        //
        $previous_primary_qty = 0;
        $previous_secondary_qty = 0;
        if($row_count > 0){
            //  update the inventory balance
            $previous_primary_qty = $result[0]['primary_qty'];
            $previous_secondary_qty = $result[0]['secondary_qty'];
        }
        //  insert new row into smc_inventory_inbound 
        $sql = "INSERT INTO smc_inventory_inbound (`product_code`, `product_name`, `location_type`, `location_id`, `batch`, `expiry`, `rate`, `unit`, `previous_primary_qty`, `previous_secondary_qty`, `current_primary_qty`, `current_secondary_qty`,`userid`)
        VALUES (:product_code, :product_name, :location_type, :location_id, :batch, :expiry_date, :rate, :unit, :previous_primary_qty, :previous_secondary_qty, :current_primary_qty, :current_secondary_qty,:userid)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':product_code', $product_code);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':location_type', $location_type);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':batch', $batch);
        $stmt->bindParam(':expiry_date', $expiry_date);
        $stmt->bindParam(':rate', $rate);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':previous_primary_qty', $previous_primary_qty);
        $stmt->bindParam(':previous_secondary_qty', $previous_secondary_qty);
        $stmt->bindParam(':current_primary_qty', $primary_qty);
        $stmt->bindParam(':current_secondary_qty', $secondary_qty);
        $stmt->bindParam(':userid', $userid);
        $stmt->execute();
        $result = $stmt->rowCount();
        // get last inserted id
        $last_id = $this->pdo->lastInsertId();
        if($stmt->errorCode() != '00000'){
            //  log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n";
            $error_message .= "-- INVENTORY INBOUND/OUTBOUND INSERT --\n";
            $error_message .= "Product Code: $product_code, Product: $product_name, Location: $location_type, Location ID: $location_id\n";
            $error_message .= $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        //
        return $last_id;
    }
    private function OutboundMgt($product_code, $product_name, $location_type, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty, $userid){
        //  selectinventory balance with keys to check if the product exists and current balance
        $sql = "SELECT primary_qty, secondary_qty FROM smc_inventory_central WHERE product_code = :product_code AND location_type = :location_type AND location_id = :location_id AND batch = :batch AND expiry = :expiry_date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':product_code', $product_code);
        $stmt->bindParam(':location_type', $location_type);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':batch', $batch);
        $stmt->bindParam(':expiry_date', $expiry_date);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $row_count = $stmt->rowCount();
        //
        $previous_primary_qty = 0;
        $previous_secondary_qty = 0;
        if($row_count > 0){
            //  update the inventory balance
            $previous_primary_qty = $result[0]['primary_qty'];
            $previous_secondary_qty = $result[0]['secondary_qty'];
        }
        //  insert new roww into smc_inventory_outbound 
        $sql = "INSERT INTO smc_inventory_outbound (`product_code`, `product_name`, `location_type`, `location_id`, `batch`, `expiry`, `rate`, `unit`, `previous_primary_qty`, `previous_secondary_qty`, `current_primary_qty`, `current_secondary_qty`,`userid`)
        VALUES (:product_code, :product_name, :location_type, :location_id, :batch, :expiry_date, :rate, :unit, :previous_primary_qty, :previous_secondary_qty, :current_primary_qty, :current_secondary_qty,:userid)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':product_code', $product_code);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':location_type', $location_type);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':batch', $batch);
        $stmt->bindParam(':expiry_date', $expiry_date);
        $stmt->bindParam(':rate', $rate);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':previous_primary_qty', $previous_primary_qty);
        $stmt->bindParam(':previous_secondary_qty', $previous_secondary_qty);
        $stmt->bindParam(':current_primary_qty', $primary_qty);
        $stmt->bindParam(':current_secondary_qty', $secondary_qty);
        $stmt->bindParam(':userid', $userid);
        $stmt->execute();
        $result = $stmt->rowCount();
        // get last inserted id
        $last_id = $this->pdo->lastInsertId();
        if($stmt->errorCode() != '00000'){
            //  log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n";
            $error_message .= "-- INVENTORY OUTBOUND INSERT --\n";
            $error_message .= "Product Code: $product_code, Product: $product_name, Location: $location_type, Location ID: $location_id\n";
            $error_message .= $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        //
        return $last_id;
    }
    #
    #   In-bound Shipment
    #   ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty','userid']
    public function CmsInboundShipment($bulk_data){
        $location_type = 'CMS';
        $ids = [];
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $this->pdo->beginTransaction();
            foreach($bulk_data as $data){
                // ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty','userid']
                $product_code = $data['product_code'];
                $product_name = $data['product_name'];
                $location_id = $data['location_id'];
                $batch = $data['batch'];
                $expiry_date = $data['expiry_date'];
                $rate = $data['rate'];
                $unit = $data['unit'];
                $primary_qty = $data['primary_qty'];
                $secondary_qty = $data['secondary_qty'];
                $userid = $data['userid'];
                //
                $id = $this->InboundMgt($product_code, $product_name, $location_type, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty,$userid);
                if($id){
                    //  success
                    array_push($ids, $id);
                }else{
                    //  failed to update
                    //array_push($ids, false);
                }
            }
            //  commit transaction
            $this->pdo->commit();
            return $ids;
        }
        return false;
    }
    #   Out-bound Shipment with identical function and data fields
    #   ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty','userid']
    public function CmsOutboundShipment($bulk_data){
        $location_type = 'CMS';
        $ids = [];
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $this->pdo->beginTransaction();
            foreach($bulk_data as $data){
                // ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty','userid']
                $product_code = $data['product_code'];
                $product_name = $data['product_name'];
                $location_id = $data['location_id'];
                $batch = $data['batch'];
                $expiry_date = $data['expiry_date'];
                $rate = $data['rate'];
                $unit = $data['unit'];
                $primary_qty = $data['primary_qty'];
                $secondary_qty = $data['secondary_qty'];
                $userid = $data['userid'];
                //
                $id = $this->OutboundMgt($product_code, $product_name, $location_type, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty,$userid);
                if($id){
                    //  success
                    array_push($ids, $id);
                }else{
                    //  failed to update
                    //array_push($ids, false);
                }
            }
            //  commit transaction
            $this->pdo->commit();
            return $ids;
        }
        return false;
    }
    #   In-bound Shipment
    #   ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty']
    public function FacilityInboundShipment($product_code, $product_name, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty, $userid){
        $location_type = 'Facility';
        return $this->InboundMgt($product_code, $product_name, $location_type, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty, $userid);
    }
    #   Out-bound Shipment with identical function and data fields
    #   ['product_code','product_name','location_id','batch','expiry_date','rate','unit','primary_qty','secondary_qty']
    public function FacilityOutboundShipment($product_code, $product_name, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty, $userid){
        $location_type = 'Facility';
        return $this->OutboundMgt($product_code, $product_name, $location_type, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty, $userid);
    }
    #
    #
    #
    public function GetCmsInventory(){
        return DbHelper::Table("SELECT
        smc_inventory_central.inventory_id,
        smc_inventory_central.product_code,
        smc_inventory_central.product_name,
        smc_inventory_central.batch,
        smc_cms_location.cms_name,
        smc_inventory_central.expiry,
        smc_inventory_central.rate,
        smc_inventory_central.primary_qty,
        smc_inventory_central.secondary_qty,
        smc_inventory_central.created,
        smc_inventory_central.updated
        FROM
        smc_inventory_central
        INNER JOIN smc_cms_location ON smc_inventory_central.location_id = smc_cms_location.location_id
        WHERE
        smc_inventory_central.location_type = 'cms'");
    }
    #
    #
    #       Availability check
    #   ['dpid','geo_string','product_code','product_name','primary_qty','secondary_qty']
    private function getFacilityIssue($periodid, $product_code){
        return DbHelper::Table("SELECT
            smc_logistics_issues.dpid,
            sys_geo_codex.geo_string,
            smc_logistics_issues.product_code,
            smc_logistics_issues.product_name,
            smc_logistics_issues.primary_qty,
            smc_logistics_issues.secondary_qty
            FROM
            smc_logistics_issues
            INNER JOIN sys_geo_codex ON smc_logistics_issues.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_value = 10
            WHERE
            smc_logistics_issues.periodid = $periodid AND
            smc_logistics_issues.product_code = '$product_code'");
    }
    #   ['cms_name','product_code','product_name','primary_qty','secondary_qty']
    private function getCmsInventoryProductTotal($product_code){
        return DbHelper::Table("SELECT
            smc_cms_location.cms_name,
            smc_inventory_central.product_code,
            SUM(smc_inventory_central.secondary_qty) AS total
            FROM
            smc_inventory_central
            INNER JOIN smc_cms_location ON smc_inventory_central.location_id = smc_cms_location.location_id
            WHERE
            smc_inventory_central.location_type = 'CMS' AND
            smc_inventory_central.product_code = '$product_code'
            GROUP BY smc_inventory_central.product_code");
    }
    #
    #
    private function getTopCmsInventoryTotal(){
        return DbHelper::Table("SELECT
            smc_cms_location.cms_name,
            smc_inventory_central.product_code,
            SUM(smc_inventory_central.secondary_qty) AS total
            FROM
            smc_inventory_central
            INNER JOIN smc_cms_location ON smc_inventory_central.location_id = smc_cms_location.location_id
            WHERE
            smc_inventory_central.location_type = 'CMS'
            GROUP BY smc_inventory_central.product_code");
    }
    private function getTopFacilityInventoryTotal($periodid){
        return DbHelper::Table("SELECT
            smc_logistics_issues.product_code,
            smc_logistics_issues.product_name,
            SUM(smc_logistics_issues.secondary_qty) AS total
            FROM
            smc_logistics_issues
            INNER JOIN sys_geo_codex ON smc_logistics_issues.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_value = 10
            WHERE
            smc_logistics_issues.periodid = $periodid 
            GROUP BY smc_logistics_issues.product_code");
    }
    #   Targeting individual product with detail result
    #   ['dpid','geo_string','product_code','product_name','primary_qty','secondary_qty']
    public function ProcessProductValidityCheck($periodid, $product_code){
        $facility_issue = $this->getFacilityIssue($periodid, $product_code);
        $cms_inventory = $this->getCmsInventoryProductTotal($product_code);
        //
        $results = [];
        // Create a modifiable map of product_code to cms inventory
        if(count($cms_inventory) > 0 && count($facility_issue) > 0){
            //  inventory data
            $cms_name = $cms_inventory[0]['cms_name'];
            $total_qty = $cms_inventory[0]['total'];
            $qty_to_distribute = $total_qty;
            //  loop through facility issue
            foreach($facility_issue as $issue){
                $dpid = $issue['dpid'];
                $product_code = $issue['product_code'];
                $geo_string = $issue['geo_string'];
                $allocated_qty = $issue['secondary_qty'];
                $product_name = $issue['product_name'];
                //
                if($qty_to_distribute > 0){
                    if($allocated_qty <= $qty_to_distribute){
                        $status = 'pass';
                        $available_qty = $allocated_qty;
                    }else{
                        $status = 'fail';
                         $available_qty = $qty_to_distribute;
                    }
                    $results[] = [
                        'dpid'          => $dpid,
                        'geo_string'    => $geo_string,
                        'cms_name'      => $cms_name,
                        'product_name'  => $product_name,
                        'product_code'  => $product_code,
                        'allocated_qty' => $allocated_qty,
                        'available_qty' => $available_qty,
                        'status'        => $status
                    ];
                    $qty_to_distribute -= $allocated_qty;
                }else{
                    $status = 'fail';
                    $available_qty = 0;
                    $results[] = [
                        'dpid'          => $dpid,
                        'geo_string'    => $geo_string,
                        'cms_name'      => $cms_name,
                        'product_name'  => $product_name,
                        'product_code'  => $product_code,
                        'allocated_qty' => $allocated_qty,
                        'available_qty' => $available_qty,
                        'status'        => $status
                    ];
                }
                
            }
            //
            return $results;
        }
        return false; 
    }
    #
    #   Targeting all product with top summary result
    public function ProcessTopinventoryToValidate($periodid) {
        $available = $this->getTopCmsInventoryTotal();
        $allocated = $this->getTopFacilityInventoryTotal($periodid); // Assuming periodid is 1 for this example
        
        $result = [];

        // Map available quantities by product_code for quick lookup
        $available_map = [];
        foreach ($available as $item) {
            $available_map[$item['product_code']] = $item['total'];
        }

        // Compare each allocated product
        foreach ($allocated as $item) {
            $product_code = $item['product_code'];
            $allocated_qty = $item['total'];
            $available_qty = isset($available_map[$product_code]) ? $available_map[$product_code] : 0;
            $status = ($available_qty >= $allocated_qty) ? 'pass' : 'fail';

            $result[] = [
                'product_code'   => $product_code,
                'available_qty'  => $available_qty,
                'allocated_qty'  => $allocated_qty,
                'status'         => $status
            ];
        }

        return $result;
    }
    #
    #
    #
    #   Get facility inventory Balance
    public function GetFacilityInventoryBalance($facilityId){
        //  [product_code, product_name, location_type, location_id, batch, expiry_date, rate, unit, primary_qty, secondary_qty]
        return DbHelper::Table("SELECT
                smc_inventory_central.inventory_id,
                smc_inventory_central.product_code,
                smc_inventory_central.product_name,
                smc_inventory_central.batch,
                smc_inventory_central.expiry,
                smc_inventory_central.rate,
                smc_inventory_central.unit,
                smc_inventory_central.primary_qty
                FROM
                smc_inventory_central
                WHERE
                location_type = 'facility'
                AND location_id = $facilityId");
    }
    #
    #
    #   
    #  [inventory_id, to_facility_id, secondary_qty, userid]
    private function saveTransfer($inventory_id, $from_falicity_id, $to_facility_id, $product_code,$product_name, $batch, $expiry, $rate,$unit,$primary_qty, $secondary_qty, $userid){
        //  log transfer into the transfer table
        $sql = "INSERT INTO smc_inventory_transfer (`inventory_id`, `source_facility_id`, `destination_facility_id`, `product_code`,`product_name`,`batch`,`expiry`,`rate`,`unit`,`primary_qty`, `secondary_qty`, `userid`) 
        VALUES (:inventory_id, :from_falicity_id, :to_facility_id, :product_code, :product_name, :batch, :expiry, :rate, :unit, :primary_qty, :secondary_qty, :userid)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':inventory_id', $inventory_id);
        $stmt->bindParam(':from_falicity_id', $from_falicity_id);
        $stmt->bindParam(':to_facility_id', $to_facility_id);
        $stmt->bindParam(':product_code', $product_code);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':batch', $batch);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':rate', $rate);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':primary_qty', $primary_qty);
        $stmt->bindParam(':secondary_qty', $secondary_qty);
        $stmt->bindParam(':userid', $userid);
        //  execute the query
        $result = $stmt->execute();
        //  return true if successful, log if error
        if($stmt->errorCode() != '00000'){
            //  log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n";
            $error_message .= "-- INVENTORY TRANSFER INSERT --\n";
            $error_message .= "Inventory ID: $inventory_id, From Facility ID: $from_falicity_id, To Facility ID: $to_facility_id\n";
            $error_message .= $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        //  return last inserted id
        $last_id = $this->pdo->lastInsertId();
        if($last_id > 0){
            //  return true if successful
            return $last_id;
        }else{
            //  return false if not successful
            return false;
        }
    }
    public function FacilityTransfer($inventory_id, $from_falicity_id, $to_facility_id, $primary_qty, $userid){
        $location_type = 'facility';
        //  select inventory balance with with inventory_id to check if the product exists and current balance
        $sql = "SELECT product_code, product_name, location_type, location_id, batch, expiry, rate, unit, primary_qty, secondary_qty FROM smc_inventory_central WHERE inventory_id = :inventory_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':inventory_id', $inventory_id);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if(count($result) > 0){
            //  conduct inbound management on source facility
            //  conduct outbound management on destination facility
            #   outbound
            $secondary_qty = $primary_qty*50;
            //  log transfer into the transfer table
            $saveResult = $this->saveTransfer($inventory_id, $from_falicity_id, $to_facility_id, $result[0]['product_code'], $result[0]['product_name'],
            $result[0]['batch'], $result[0]['expiry'], $result[0]['rate'], $result[0]['unit'], $primary_qty, $secondary_qty, $userid);
            if(!$saveResult){
                //  failed to save transfer
                $outbound = $this->OutboundMgt($result[0]['product_code'],$result[0]['product_name'],$result[0]['location_type'],$result[0]['location_id'],
                $result[0]['batch'],$result[0]['expiry'],$result[0]['rate'],$result[0]['unit'],$primary_qty, $secondary_qty, $userid);
                #
                $inbound = $this->InboundMgt($result[0]['product_code'],$result[0]['product_name'],'facility',$to_facility_id,$result[0]['batch'],
                $result[0]['expiry'],$result[0]['rate'],$result[0]['unit'], $primary_qty, $secondary_qty, $userid);
                //  
                return true;
            }
            
        }else{
            //  no inventory found
            return false;
        }

        
    }


}
?>