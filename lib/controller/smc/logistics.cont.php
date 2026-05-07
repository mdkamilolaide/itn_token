<?php
namespace Smc;

use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Logistics {
    private $db;
    private $pdo;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
        $this->pdo = $this->db->Conn;
    } 
    #
    #
    #   ['periodid','dpid','product_code','product_name','primary_qty','secondary_qty']
    public function CreateBulkIssue($bulk_data){
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $counter = 0;
            $this->pdo->beginTransaction();
            foreach($bulk_data as $data){
                $periodid = $data['periodid'];
                $dpid = $data['dpid'];
                $product_code = $data['product_code'];
                $product_name = $data['product_name'];
                $primary_qty = $data['primary_qty'];
                $secondary_qty = $data['secondary_qty'];
                //
                $id = $this->InsertIssue($periodid,$dpid, $product_code, $product_name, $primary_qty, $secondary_qty);
                if($id === false){
                    // return false;
                    //  failed to insert
                }else{
                    //  success
                    $counter++;
                }
            }
            //  commit transaction
            $this->pdo->commit();
            return $counter;
        }
        return false;
    }
    public function CreateSingleIssue($periodid, $dpid, $product_code, $product_name, $primary_qty, $secondary_qty){
        if($dpid != '' && $product_code != '' && $product_name != '' && $primary_qty != '' && $secondary_qty != ''){
            return $this->InsertIssue($periodid, $dpid, $product_code, $product_name, $primary_qty, $secondary_qty);
        }
        return false;
    }
    private function InsertIssue($periodid, $dpid, $product_code, $product_name, $primary_qty, $secondary_qty){
        $sql = "INSERT INTO smc_logistics_issues (`periodid`,`dpid`, `product_code`, `product_name`, `primary_qty`, `secondary_qty`)
        VALUES (:periodid,:dpid, :product_code, :product_name, :primary_qty, :secondary_qty)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':periodid', $periodid);
        $stmt->bindParam(':dpid', $dpid);
        $stmt->bindParam(':product_code', $product_code);
        $stmt->bindParam(':product_name', $product_name);
        $stmt->bindParam(':primary_qty', $primary_qty);
        $stmt->bindParam(':secondary_qty', $secondary_qty);
        $stmt->execute();
        if($stmt->errorCode() != '00000'){
            //  log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n --- LOGISTICS ISSUE IN --\n";
            $error_message .= $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        return $this->pdo->lastInsertId(); // returns issue_id
    }
    #
    #   Update issue
    #
    #   ['issue_id','primary_qty','secondary_qty']
    public function UpdatebulkIssue($bulk_data){
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $counter = 0;
            $this->pdo->beginTransaction();
            foreach($bulk_data as $data){
                $issue_id = $data['issue_id'];
                $primary_qty = $data['primary_qty'];
                $secondary_qty = $data['secondary_qty'];
                //
                if($this->UpdateIssue($issue_id, $primary_qty, $secondary_qty)){
                    //  success
                    $counter++;
                }else{
                    //  failed to update
                }
            }
            //  commit transaction
            $this->pdo->commit();
            return $counter;
        }
        return 0;
    }
    public function UpdateSingleIssue($issue_id, $primary_qty, $secondary_qty){
        if($issue_id != '' && $primary_qty != '' && $secondary_qty != ''){
            return $this->UpdateIssue($issue_id, $primary_qty, $secondary_qty);
        }
        return false;
    }
    private function UpdateIssue($issue_id, $primary_qty, $secondary_qty){
        $sql = "UPDATE smc_logistics_issues SET
        primary_qty = :primary_qty,
        secondary_qty = :secondary_qty
        WHERE issue_id = :issue_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':issue_id', $issue_id);
        $stmt->bindParam(':primary_qty', $primary_qty);
        $stmt->bindParam(':secondary_qty', $secondary_qty);
        $stmt->execute();
        
        //
        if($stmt->errorCode() != '00000'){
            //  log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n --- LOGISTICS ISSUE UPDATE --\n";
            $error_message .= $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        //  check if row was updated
        if($stmt->rowCount() > 0){
            //  success
            return true;
        }
        //  no row was updated
        return false;
    }
    #
    #   Process bulk issue combined create and update to process bulk issue
    #   ['periodid','issue_id','dpid','product_code','product_name','primary_qty','secondary_qty']
    public function ProcessBulkIssue($bulk_data){
        #   check $bulk_data is not empty
        #   loop through $bulk_data
        #   check if issue_id is empty InsertIssue
        #   else UpdateIssue
        #   return count of success
        if(count($bulk_data) > 0 && is_array($bulk_data)){
            $counter = 0;
            $this->pdo->beginTransaction();
            foreach($bulk_data as $data){
                $periodid = $data['periodid'];
                $issue_id = $data['issue_id'];
                $dpid = $data['dpid'];
                $product_code = $data['product_code'];
                $product_name = $data['product_name'];
                $primary_qty = $data['primary_qty'];
                $secondary_qty = $data['secondary_qty'];
                //
                if($issue_id == ''){
                    //  insert new issue
                    if($this->InsertIssue($periodid, $dpid, $product_code, $product_name, $primary_qty, $secondary_qty)){
                        //  success
                        $counter++;
                    }else{
                        //  failed to insert
                    }
                }else{
                    //  update existing issue
                    if($this->UpdateIssue($issue_id, $primary_qty, $secondary_qty)){
                        //  success
                        $counter++;
                    }else{
                        //  failed to update
                    }
                }
            }
            //  commit transaction
            $this->pdo->commit();
            return $counter;
        }
    }
    #   single issue update
    #   Issue table
    #   Get issue record for certain LGA by period
    public function GetIssueByPeriod($periodid, $lgaid){
        return DbHelper::Table("SELECT
            sys_geo_codex.geo_string,
            sys_geo_codex.dpid,
            smc_logistics_issues.issue_id,
            smc_period.title AS period,
            smc_logistics_issues.product_code,
            smc_logistics_issues.product_name,
            smc_logistics_issues.primary_qty,
            smc_logistics_issues.secondary_qty,
            smc_logistics_issues.created
            FROM
            sys_geo_codex
            LEFT JOIN smc_logistics_issues 
            ON sys_geo_codex.dpid = smc_logistics_issues.dpid 
            AND smc_logistics_issues.periodid = $periodid
            LEFT JOIN smc_period 
            ON smc_logistics_issues.periodid = smc_period.periodid
            WHERE
            sys_geo_codex.lgaid = $lgaid
            AND sys_geo_codex.geo_value = 10");
    }
    #
    #
    #   Execute shipment process
    #
    public function getInvAvailableBalance(){
        return DbHelper::Table("SELECT
            smc_inventory_central.inventory_id,
            smc_inventory_central.product_code,
            smc_inventory_central.product_name,
            smc_inventory_central.location_type,
            smc_cms_location.cms_name,
            smc_inventory_central.location_id,
            smc_inventory_central.batch,
            smc_inventory_central.expiry,
            smc_inventory_central.rate,
            smc_inventory_central.unit,
            smc_inventory_central.primary_qty,
            smc_inventory_central.secondary_qty
            FROM
            smc_inventory_central
            INNER JOIN smc_cms_location ON smc_inventory_central.location_id = smc_cms_location.location_id
            WHERE
            smc_inventory_central.location_type = 'CMS' AND
            smc_inventory_central.secondary_qty > 0");
    }
    public function getBulkAllocation($periodid){
        return DbHelper::Table("SELECT
                    ms_geo_lga.LgaId AS lgaid,
                    ms_geo_lga.Fullname AS lga,
                    smc_logistics_issues.dpid,
                    sys_geo_codex.geo_string,
                    smc_logistics_issues.product_code,
                    smc_logistics_issues.product_name,
                    smc_logistics_issues.primary_qty,
                    smc_logistics_issues.secondary_qty
                    FROM
                    smc_logistics_issues
                    INNER JOIN sys_geo_codex ON smc_logistics_issues.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_value = 10
                    INNER JOIN ms_geo_lga ON sys_geo_codex.lgaid = ms_geo_lga.LgaId
                    WHERE
                    smc_logistics_issues.periodid = $periodid ");
    }
    #   Sample sorting now truncated for experimental purposes only
    public function executeShipmentSample($periodid){
        //  get all available balances
        $available_balance = $this->getInvAvailableBalance();
        //  get all bulk allocations    
        $bulk_allocation = $this->getBulkAllocation($periodid);
        //  
        if(count($available_balance) > 0 && count($bulk_allocation) > 0){
            $spaq1 = [];
            $spaq1_total_qty = 0;
            $spaq2 = [];
            $spaq2_total_qty = 0;
            //
            $allocate1 = [];
            $allocate2 = [];
            // Seperate available balances
            foreach ($available_balance as $item) {
                if ($item['product_code'] === 'SPAQ1') {
                    $spaq1[] = $item;
                    $spaq1_total_qty += $item['secondary_qty'];
                } elseif ($item['product_code'] === 'SPAQ2') {
                    $spaq2[] = $item;
                    $spaq2_total_qty += $item['secondary_qty'];
                }
            }
            //  seperate allocation
            foreach ($bulk_allocation as $item) {
                if ($item['product_code'] === 'SPAQ1') {
                    $allocate1[] = $item;
                } elseif ($item['product_code'] === 'SPAQ2') {
                    $allocate2[] = $item;
                }
            }
            //  loop through bulk allocation
            $spaq1_total = count($spaq1);
            $spaq2_total = count($spaq2);
            $allocate1_counter = count($allocate1);
            $allocate2_counter = count($allocate2);
            //
            $spaq1_balance = 0;
            $spaq1_pointer = 0;
            $spaq2_balance = 0;
            $spaq2_pointer = 0;
            //
            $spaq1_outbound_data = [];
            $spaq2_outbound_data = [];
            /*
            foreach ($bulk_allocation as $item) {
                
                //
                //
                //
                if ($item['product_code'] === 'SPAQ1') {
                    //  check if available balance is greater than allocation
                    if ($item['secondary_qty'] <= $spaq1[$spaq1_pointer]['secondary_qty']) {
                        //  allocate
                        $spaq1_outbound_data[] = array(
                            'origin_id' => $spaq1[$spaq1_pointer]['location_id'],
                            'origin_string' => $spaq1[$spaq1_pointer]['cms_name'],
                            'origin_type' => $spaq1[$spaq1_pointer]['location_type'],
                            'destination_id' => $item['dpid'],
                            'destination_string' => $item['geo_string'],
                            'destination_type' => 'Facility',
                            'periodid' => $periodid,
                            'product_code' => $spaq1[$spaq1_pointer]['product_code'],
                            'product_name' => $spaq1[$spaq1_pointer]['product_name'],
                            'expiry' => $spaq1[$spaq1_pointer]['expiry'],
                            'batch' => $spaq1[$spaq1_pointer]['batch'],
                            'rate' => $spaq1[$spaq1_pointer]['rate'],
                            'unit' => $spaq1[$spaq1_pointer]['unit'],
                            'primary_qty' => $item['primary_qty'],
                            'secondary_qty' => $item['secondary_qty']
                        );
                        //  Other calculations
                        $spaq1[$spaq1_pointer]['secondary_qty'] -= $item['secondary_qty'];
                        $spaq1[$spaq1_pointer]['primary_qty'] -= $item['primary_qty'];
                        
                        if($spaq1[$spaq1_pointer]['secondary_qty'] <= 0){
                            //  move to next pointer
                            //  if($spaq1_pointer > $spaq1_total){ increment pointer) else break;
                            if($spaq1_pointer >= $spaq1_total - 1){
                                //  break;
                                break;  
                            }
                            //  move to next pointer
                            $spaq1_pointer++;
                        }
                        
                    }
                    elseif($item['secondary_qty'] > $spaq1[$spaq1_pointer]['secondary_qty'] && $spaq1[$spaq1_pointer]['secondary_qty'] > 0){
                        //  It means there is not enough balance in the current pointer
                        //  get all the remaining balance for an item
                        //  move to next pointer
                        //  get the balance from the next pointer
                        //  allocate
                        $spaq1_outbound_data[] = array(
                            'origin_id' => $spaq1[$spaq1_pointer]['location_id'],
                            'origin_string' => $spaq1[$spaq1_pointer]['cms_name'],
                            'origin_type' => $spaq1[$spaq1_pointer]['location_type'],
                            'destination_id' => $item['dpid'],
                            'destination_string' => $item['geo_string'],
                            'destination_type' => 'Facility',
                            'periodid' => $periodid,
                            'product_code' => $spaq1[$spaq1_pointer]['product_code'],
                            'product_name' => $spaq1[$spaq1_pointer]['product_name'],
                            'expiry' => $spaq1[$spaq1_pointer]['expiry'],
                            'batch' => $spaq1[$spaq1_pointer]['batch'],
                            'rate' => $spaq1[$spaq1_pointer]['rate'],
                            'unit' => $spaq1[$spaq1_pointer]['unit'],
                            'primary_qty' => $spaq1[$spaq1_pointer]['primary_qty'],
                            'secondary_qty' => $spaq1[$spaq1_pointer]['secondary_qty']
                        );
                        $remaining_primary_qty =  $item['primary_qty'] - $spaq1[$spaq1_pointer]['primary_qty'];
                        $remaining_secondary_qty = $item['secondary_qty'] - $spaq1[$spaq1_pointer]['secondary_qty'];
                        //
                        if($spaq1_pointer >= $spaq1_total - 1){
                            //  break;
                            break;  
                        }
                        $spaq1_pointer++;
                        //  fultil remaining balance
                        if($remaining_secondary_qty <= $spaq1[$spaq1_pointer]['secondary_qty'] ){
                            //  allocate
                            $spaq1_outbound_data[] = array(
                                'origin_id' => $spaq1[$spaq1_pointer]['location_id'],
                                'origin_string' => $spaq1[$spaq1_pointer]['cms_name'],
                                'origin_type' => $spaq1[$spaq1_pointer]['location_type'],
                                'destination_id' => $item['dpid'],
                                'destination_string' => $item['geo_string'],
                                'destination_type' => 'Facility',
                                'periodid' => $periodid,
                                'product_code' => $spaq1[$spaq1_pointer]['product_code'],
                                'product_name' => $spaq1[$spaq1_pointer]['product_name'],
                                'expiry' => $spaq1[$spaq1_pointer]['expiry'],
                                'batch' => $spaq1[$spaq1_pointer]['batch'],
                                'rate' => $spaq1[$spaq1_pointer]['rate'],
                                'unit' => $spaq1[$spaq1_pointer]['unit'],
                                'primary_qty' => $remaining_primary_qty,
                                'secondary_qty' => $remaining_secondary_qty
                            );
                            //  Other calculations
                            $spaq1[$spaq1_pointer]['secondary_qty'] -= $remaining_primary_qty;
                            $spaq1[$spaq1_pointer]['primary_qty'] -= $remaining_secondary_qty;
                            if($spaq1[$spaq1_pointer]['secondary_qty'] <= 0){
                                if($spaq1_pointer >= $spaq1_total - 1){
                                    //  break;
                                    break;  
                                }
                                //  move to next pointer
                                $spaq1_pointer++;
                            }
                        }
                    }
                    else{
                        //  move to next pointer
                        if($spaq1_pointer >= $spaq1_total - 1){
                            //  break;
                            break;  
                        }
                        $spaq1_pointer++;
                    }
                } elseif ($item['product_code'] === 'SPAQ2') {
                    //  check if available balance is greater than allocation
                    if ($item['secondary_qty'] <= $spaq2[$spaq2_pointer]['secondary_qty']) {
                        //  allocate
                        $spaq2_outbound_data[] = array(
                            'origin_id' => $spaq2[$spaq2_pointer]['location_id'],
                            'origin_string' => $spaq2[$spaq2_pointer]['cms_name'],
                            'origin_type' => $spaq2[$spaq2_pointer]['location_type'],
                            'destination_id' => $item['dpid'],
                            'destination_string' => $item['geo_string'],
                            'destination_type' => 'Facility',
                            'periodid' => $periodid,
                            'product_code' => $spaq2[$spaq2_pointer]['product_code'],
                            'product_name' => $spaq2[$spaq2_pointer]['product_name'],
                            'expiry' => $spaq2[$spaq2_pointer]['expiry'],
                            'batch' => $spaq2[$spaq2_pointer]['batch'],
                            'rate' => $spaq2[$spaq2_pointer]['rate'],
                            'unit' => $spaq2[$spaq2_pointer]['unit'],
                            'primary_qty' => $item['primary_qty'],
                            'secondary_qty' => $item['secondary_qty']
                        );
                        //  Other calculations
                        $spaq2[$spaq2_pointer]['secondary_qty'] -= $item['secondary_qty'];
                        $spaq2[$spaq2_pointer]['primary_qty'] -= $item['primary_qty'];
                        if($spaq2[$spaq2_pointer]['secondary_qty'] <= 0){
                            //  move to next pointer
                            if($spaq2_pointer >= $spaq2_total - 1){
                                //  break;
                                break;  
                            }
                            //  move to next pointer
                            $spaq2_pointer++;
                        }
                    }
                    elseif($item['secondary_qty'] > $spaq2[$spaq2_pointer]['secondary_qty'] && $spaq2[$spaq2_pointer]['secondary_qty'] > 0){
                        //  It means there is not enough balance in the current pointer
                        //  get all the remaining balance for an item
                        //  move to next pointer
                        //  get the balance from the next pointer
                        //  allocate
                        $spaq2_outbound_data[] = array(
                            'origin_id' => $spaq2[$spaq2_pointer]['location_id'],
                            'origin_string' => $spaq2[$spaq2_pointer]['cms_name'],
                            'origin_type' => $spaq2[$spaq2_pointer]['location_type'],
                            'destination_id' => $item['dpid'],
                            'destination_string' => $item['geo_string'],
                            'destination_type' => 'Facility',
                            'periodid' => $periodid,
                            'product_code' => $spaq2[$spaq2_pointer]['product_code'],
                            'product_name' => $spaq2[$spaq2_pointer]['product_name'],
                            'expiry' => $spaq2[$spaq2_pointer]['expiry'],
                            'batch' => $spaq2[$spaq2_pointer]['batch'],
                            'rate' => $spaq2[$spaq2_pointer]['rate'],
                            'unit' => $spaq2[$spaq2_pointer]['unit'],
                            'primary_qty' => $spaq2[$spaq2_pointer]['primary_qty'],
                            'secondary_qty' => $spaq2[$spaq2_pointer]['secondary_qty']
                        );
                        $remaining_primary_qty = $item['primary_qty'] - $spaq2[$spaq2_pointer]['primary_qty'];
                        $remaining_secondary_qty = $item['secondary_qty'] - $spaq2[$spaq2_pointer]['secondary_qty'];
                        //
                        //  move to next pointer
                        if($spaq2_pointer >= $spaq2_total - 1){
                            //  break;
                            break;  
                        }
                        $spaq2_pointer++;
                        //  fultil remaining balance
                        if($remaining_secondary_qty <= $spaq2[$spaq2_pointer]['secondary_qty'] ){
                            //  allocate
                            $spaq2_outbound_data[] = array(
                                'origin_id' => $spaq2[$spaq2_pointer]['location_id'],
                                'origin_string' => $spaq2[$spaq2_pointer]['cms_name'],
                                'origin_type' => $spaq2[$spaq2_pointer]['location_type'],
                                'destination_id' => $item['dpid'],
                                'destination_string' => $item['geo_string'],
                                'destination_type' => 'Facility',
                                'periodid' => $periodid,
                                'product_code' => $spaq2[$spaq2_pointer]['product_code'],
                                'product_name' => $spaq2[$spaq2_pointer]['product_name'],
                                'expiry' => $spaq2[$spaq2_pointer]['expiry'],
                                'batch' => $spaq2[$spaq2_pointer]['batch'],
                                'rate' => $spaq2[$spaq2_pointer]['rate'],
                                'unit' => $spaq2[$spaq2_pointer]['unit'],
                                'primary_qty' => $remaining_primary_qty,
                                'secondary_qty' => $remaining_secondary_qty
                            );
                            //  Other calculations
                            $spaq2[$spaq2_pointer]['secondary_qty'] -= $remaining_primary_qty;
                            $spaq2[$spaq2_pointer]['primary_qty'] -= $remaining_secondary_qty;  
                            if($spaq2[$spaq2_pointer]['secondary_qty'] <= 0){
                                //  move to next pointer
                                //  move to next pointer
                                if($spaq2_pointer >= $spaq2_total - 1){
                                    //  break;
                                    break;  
                                }
                                $spaq2_pointer++;
                            }
                        }
                    }
                    else{
                        //  move to next pointer
                        if($spaq2_pointer >= $spaq2_total - 1){
                            //  break;
                            break;  
                        }
                        $spaq2_pointer++;
                    }
                }
            }
            
            for ($i = 0; $i < count($allocate1); $i++) {
                $item = $allocate1[$i];
                $remaining_primary_qty = $item['primary_qty'];
                $remaining_secondary_qty = $item['secondary_qty'];

                while ($remaining_secondary_qty > 0 && $spaq1_pointer < $spaq1_total) {
                    $current = &$spaq1[$spaq1_pointer];

                    $alloc_secondary = min($remaining_secondary_qty, $current['secondary_qty']);
                    $alloc_primary = min($remaining_primary_qty, $current['primary_qty']); // Adjust if needed proportionally

                    $spaq1_outbound_data[] = [
                        'origin_id' => $current['location_id'],
                        'origin_string' => $current['cms_name'],
                        'origin_type' => $current['location_type'],
                        'destination_id' => $item['dpid'],
                        'destination_string' => $item['geo_string'],
                        'destination_type' => 'Facility',
                        'periodid' => $periodid,
                        'product_code' => $current['product_code'],
                        'product_name' => $current['product_name'],
                        'expiry' => $current['expiry'],
                        'batch' => $current['batch'],
                        'rate' => $current['rate'],
                        'unit' => $current['unit'],
                        'primary_qty' => $alloc_primary,
                        'secondary_qty' => $alloc_secondary
                    ];

                    // Update available balance
                    $current['secondary_qty'] -= $alloc_secondary;
                    $current['primary_qty'] -= $alloc_primary;

                    // Update remaining to be allocated
                    $remaining_secondary_qty -= $alloc_secondary;
                    $remaining_primary_qty -= $alloc_primary;

                    // If current stock is exhausted, move to next
                    if ($current['secondary_qty'] <= 0) {
                        $spaq1_pointer++;
                    }
                }

                // If after the loop there's still quantity left, log or handle shortfall
                if ($remaining_secondary_qty > 0) {
                    // Could log or track unmet needs
                    break; // or continue depending on business rules
                }
            }
            for ($i = 0; $i < count($allocate2); $i++) {
                $item = $allocate2[$i];
                $remaining_primary_qty = $item['primary_qty'];
                $remaining_secondary_qty = $item['secondary_qty'];

                while ($remaining_secondary_qty > 0 && $spaq2_pointer < $spaq2_total) {
                    $current = &$spaq2[$spaq2_pointer];

                    $alloc_secondary = min($remaining_secondary_qty, $current['secondary_qty']);
                    $alloc_primary = min($remaining_primary_qty, $current['primary_qty']); // Adjust if needed proportionally

                    $spaq1_outbound_data[] = [
                        'origin_id' => $current['location_id'],
                        'origin_string' => $current['cms_name'],
                        'origin_type' => $current['location_type'],
                        'destination_id' => $item['dpid'],
                        'destination_string' => $item['geo_string'],
                        'destination_type' => 'Facility',
                        'periodid' => $periodid,
                        'product_code' => $current['product_code'],
                        'product_name' => $current['product_name'],
                        'expiry' => $current['expiry'],
                        'batch' => $current['batch'],
                        'rate' => $current['rate'],
                        'unit' => $current['unit'],
                        'primary_qty' => $alloc_primary,
                        'secondary_qty' => $alloc_secondary
                    ];

                    // Update available balance
                    $current['secondary_qty'] -= $alloc_secondary;
                    $current['primary_qty'] -= $alloc_primary;

                    // Update remaining to be allocated
                    $remaining_secondary_qty -= $alloc_secondary;
                    $remaining_primary_qty -= $alloc_primary;

                    // If current stock is exhausted, move to next
                    if ($current['secondary_qty'] <= 0) {
                        $spaq1_pointer++;
                    }
                }

                // If after the loop there's still quantity left, log or handle shortfall
                if ($remaining_secondary_qty > 0) {
                    // Could log or track unmet needs
                    break; // or continue depending on business rules
                }
            }
            */
            foreach ($bulk_allocation as $item) {
                if ($item['product_code'] === 'SPAQ1') {
                    $remaining_primary_qty = $item['primary_qty'];
                    $remaining_secondary_qty = $item['secondary_qty'];

                    while ($remaining_secondary_qty > 0 && $spaq1_pointer < $spaq1_total) {
                        $available = $spaq1[$spaq1_pointer];

                        $alloc_secondary_qty = min($remaining_secondary_qty, $available['secondary_qty']);
                        $alloc_primary_qty = min($remaining_primary_qty, $available['primary_qty']); // optional: more accurate conversion if needed

                        $spaq1_outbound_data[] = [
                            'origin_id' => $available['location_id'],
                            'origin_string' => $available['cms_name'],
                            'origin_type' => $available['location_type'],
                            'destination_id' => $item['dpid'],
                            'destination_string' => $item['geo_string'],
                            'destination_type' => 'Facility',
                            'periodid' => $periodid,
                            'product_code' => $available['product_code'],
                            'product_name' => $available['product_name'],
                            'expiry' => $available['expiry'],
                            'batch' => $available['batch'],
                            'rate' => $available['rate'],
                            'unit' => $available['unit'],
                            'primary_qty' => $alloc_primary_qty,
                            'secondary_qty' => $alloc_secondary_qty
                        ];
                        // insert into shipment sorting
                        $this->saveShipmentSorting(
                            $item['lgaid'],
                            $item['lga'],
                            $available['location_id'],
                            $available['cms_name'],
                            $available['location_type'],
                            $item['dpid'],
                            $item['geo_string'],
                            'Facility',
                            $periodid,
                            $available['product_code'],
                            $available['product_name'],
                            $available['expiry'],
                            $available['batch'],
                            $available['rate'],
                            $available['unit'],
                            $alloc_primary_qty,
                            $alloc_secondary_qty
                        );

                        // Deduct used quantities
                        $spaq1[$spaq1_pointer]['secondary_qty'] -= $alloc_secondary_qty;
                        $spaq1[$spaq1_pointer]['primary_qty'] -= $alloc_primary_qty;
                        $remaining_secondary_qty -= $alloc_secondary_qty;
                        $remaining_primary_qty -= $alloc_primary_qty;

                        if ($spaq1[$spaq1_pointer]['secondary_qty'] <= 0) {
                            $spaq1_pointer++;
                        }
                    }
                }

                if ($item['product_code'] === 'SPAQ2') {
                    $remaining_primary_qty = $item['primary_qty'];
                    $remaining_secondary_qty = $item['secondary_qty'];

                    while ($remaining_secondary_qty > 0 && $spaq2_pointer < $spaq2_total) {
                        $available = $spaq2[$spaq2_pointer];

                        $alloc_secondary_qty = min($remaining_secondary_qty, $available['secondary_qty']);
                        $alloc_primary_qty = min($remaining_primary_qty, $available['primary_qty']); // optional

                        $spaq2_outbound_data[] = [
                            'origin_id' => $available['location_id'],
                            'origin_string' => $available['cms_name'],
                            'origin_type' => $available['location_type'],
                            'destination_id' => $item['dpid'],
                            'destination_string' => $item['geo_string'],
                            'destination_type' => 'Facility',
                            'periodid' => $periodid,
                            'product_code' => $available['product_code'],
                            'product_name' => $available['product_name'],
                            'expiry' => $available['expiry'],
                            'batch' => $available['batch'],
                            'rate' => $available['rate'],
                            'unit' => $available['unit'],
                            'primary_qty' => $alloc_primary_qty,
                            'secondary_qty' => $alloc_secondary_qty
                        ];
                        // insert into shipment sorting
                        $this->saveShipmentSorting(
                            $item['lgaid'],
                            $item['lga'],
                            $available['location_id'],
                            $available['cms_name'],
                            $available['location_type'],
                            $item['dpid'],
                            $item['geo_string'],
                            'Facility',
                            $periodid,
                            $available['product_code'],
                            $available['product_name'],
                            $available['expiry'],
                            $available['batch'],
                            $available['rate'],
                            $available['unit'],
                            $alloc_primary_qty,
                            $alloc_secondary_qty
                        );

                        // Deduct used quantities
                        $spaq2[$spaq2_pointer]['secondary_qty'] -= $alloc_secondary_qty;
                        $spaq2[$spaq2_pointer]['primary_qty'] -= $alloc_primary_qty;
                        $remaining_secondary_qty -= $alloc_secondary_qty;
                        $remaining_primary_qty -= $alloc_primary_qty;

                        if ($spaq2[$spaq2_pointer]['secondary_qty'] <= 0) {
                            $spaq2_pointer++;
                        }
                    }
                }
            }

            //
            //  loop through bulk allocation
            return array(
                'spaq1_total_qty' => $spaq1_total_qty,
                'spaq2_total_qty' => $spaq2_total_qty,
                'spaq1_total' => $spaq1_total,
                'spaq2_total' => $spaq2_total,
                'allocate1_total' => $allocate1_counter,
                'allocate2_total' => $allocate2_counter,
                'spaq1' => $spaq1,
                'spaq2' => $spaq2,
                'available_balance' => $available_balance,
                'bulk_allocation' => $bulk_allocation,
                'spaq1_outbound_data' => $spaq1_outbound_data,
                'spaq2_outbound_data' => $spaq2_outbound_data
            );
        }
        return false;
    }
    #   Save sorting into the database
    private function saveShipmentSorting($lgaid,$lga,$origin_id, $origin_string, $origin_type, $destination_id, $destination_string, $destination_type, $periodid, $product_code, $product_name, $expiry, $batch, $rate, $unit, $primary_qty, $secondary_qty){
        try {
            $stmt = $this->pdo->prepare("INSERT INTO smc_logistics_shipment_sorting (lgaid, lga, origin_id, origin_string, origin_type, destination_id, destination_string, destination_type, periodid, product_code, product_name, expiry, batch, rate, unit, primary_qty, secondary_qty) VALUES (:lgaid, :lga, :origin_id, :origin_string, :origin_type, :destination_id, :destination_string, :destination_type, :periodid, :product_code, :product_name, :expiry, :batch, :rate, :unit, :primary_qty, :secondary_qty)");
            $stmt->bindParam(':lgaid', $lgaid);
            $stmt->bindParam(':lga', $lga);
            $stmt->bindParam(':origin_id', $origin_id);
            $stmt->bindParam(':origin_string', $origin_string);
            $stmt->bindParam(':origin_type', $origin_type);
            $stmt->bindParam(':destination_id', $destination_id);
            $stmt->bindParam(':destination_string', $destination_string);
            $stmt->bindParam(':destination_type', $destination_type);
            $stmt->bindParam(':periodid', $periodid);
            $stmt->bindParam(':product_code', $product_code);
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':batch', $batch);
            $stmt->bindParam(':rate', $rate);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':primary_qty', $primary_qty);
            $stmt->bindParam(':secondary_qty', $secondary_qty);
            if($stmt->execute()){
                //  return inserted id
                return $this->pdo->lastInsertId();
            }else{
                //  log error if any
                $error = $stmt->errorInfo();
                $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS SHIPMENT SORTING INSERT ERROR ---\n" . $error[2] . "\n";
                file_put_contents('error-report.txt', $error_message, FILE_APPEND);
                return false;
            }
        } catch (\PDOException $e) {
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS SHIPMENT SORTING PDO EXCEPTION ---\n" . $e->getMessage() . "\n";
            file_put_contents('error-report.txt', $error_message, FILE_APPEND);
            return false;
        }
    }
    //  [$product_code, $product_name, $location_type, $location_id, $batch, $expiry_date, $rate, $unit, $primary_qty, $secondary_qty, $user_id]
    private function saveOutboundMgt($shipment_id, $product_code, $product_name, $location_type, $location_id, $batch, $expiry_date,$rate,$unit,$primary_qty,$secondary_qty, $userid){
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
        $sql = "INSERT INTO smc_inventory_outbound (`shipment_id`,`product_code`, `product_name`, `location_type`, `location_id`, `batch`, `expiry`, `rate`, `unit`, `previous_primary_qty`, `previous_secondary_qty`, `current_primary_qty`, `current_secondary_qty`,`userid`)
        VALUES (:shipment_id, :product_code, :product_name, :location_type, :location_id, :batch, :expiry_date, :rate, :unit, :previous_primary_qty, :previous_secondary_qty, :current_primary_qty, :current_secondary_qty,:userid)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':shipment_id', $shipment_id);
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
    #
    #   Generate inventory allocations into sorting
    public function generateInventoryAllocations($periodid){
        $inventory = $this->getInvAvailableBalance();
        $allocations = $this->getBulkAllocation($periodid);
        $result = [];

        // Check if it has been previously generate and saved in the database
        $previousAllocations = $this->getInventoryAllocations($periodid);
        if (count($previousAllocations) > 0) {
            return $previousAllocations; // Return previous allocations if they exist
        }

        // Group inventory by product_code and sort by expiry ascending (FEFO)
        $invByProduct = [];
        foreach ($inventory as $inv) {
            $invByProduct[$inv['product_code']][] = $inv;
        }
        foreach ($invByProduct as &$batches) {
            usort($batches, fn($a, $b) => strcmp($a['expiry'], $b['expiry']));
        }
        // Start transaction
        $this->pdo->beginTransaction();
        // Process allocations per destination
        foreach ($allocations as $alloc) {
            $productCode = $alloc['product_code'];
            $requiredQty = $alloc['primary_qty'];  // Only using primary_qty for allocation
            $requiredSecondary = $alloc['secondary_qty'];

            if (!isset($invByProduct[$productCode])) {
                continue; // Skip if no inventory for this product
            }
            
            foreach ($invByProduct[$productCode] as &$batch) {
                if ($requiredQty <= 0) {
                    break; // Fully allocated
                }

                $availableQty = (int) $batch['primary_qty'];
                $availableSecondary = (int) $batch['secondary_qty'];

                if ($availableQty <= 0) {
                    continue;
                }

                $allocatedQty = min($availableQty, $requiredQty);
                $allocatedSecondary = ($batch['secondary_qty'] > 0 && $requiredSecondary > 0)
                    ? min($availableSecondary, $requiredSecondary)
                    : 0;

                // Append allocation record
                $result[] = [
                    'lgaid' => $alloc['lgaid'],
                    'lga' => $alloc['lga'],
                    'origin_id' => $batch['location_id'],
                    'origin_string' => $batch['cms_name'],
                    'origin_type' => $batch['location_type'],
                    'destination_id' => $alloc['dpid'],
                    'destination_string' => $alloc['geo_string'],
                    'destination_type' => 'Facility',
                    'periodid' => $periodid,
                    'product_code' => $productCode,
                    'product_name' => $alloc['product_name'],
                    'expiry' => $batch['expiry'],
                    'batch' => $batch['batch'],
                    'rate' => $batch['rate'],
                    'unit' => $batch['unit'],
                    'primary_qty' => $allocatedQty,
                    'secondary_qty' => $allocatedSecondary
                ];

                // Insert into shipment sorting
                $this->saveShipmentSorting(
                    $alloc['lgaid'],
                    $alloc['lga'],
                    $batch['location_id'],
                    $batch['cms_name'],
                    $batch['location_type'],
                    $alloc['dpid'],
                    $alloc['geo_string'],
                    'Facility',
                    $periodid,
                    $productCode,
                    $alloc['product_name'],
                    $batch['expiry'],
                    $batch['batch'],
                    $batch['rate'],
                    $batch['unit'],
                    $allocatedQty,
                    $allocatedSecondary
                );

                // Deduct from batch and remaining requirement
                $batch['primary_qty'] -= $allocatedQty;
                $batch['secondary_qty'] -= $allocatedSecondary;
                $requiredQty -= $allocatedQty;
                $requiredSecondary -= $allocatedSecondary;
            }
        }
        // Commit transaction
        $this->pdo->commit();

        return $result;
    }
    #   
    public function getInventoryAllocations($periodid){
        return DbHelper::Table("SELECT
                smc_logistics_shipment_sorting.lgaid,
                smc_logistics_shipment_sorting.lga,
                smc_logistics_shipment_sorting.origin_id,
                smc_logistics_shipment_sorting.origin_string,
                smc_logistics_shipment_sorting.origin_type,
                smc_logistics_shipment_sorting.destination_id,
                smc_logistics_shipment_sorting.destination_string,
                smc_logistics_shipment_sorting.destination_type,
                smc_logistics_shipment_sorting.periodid,
                smc_logistics_shipment_sorting.product_code,
                smc_logistics_shipment_sorting.product_name,
                smc_logistics_shipment_sorting.expiry,
                smc_logistics_shipment_sorting.batch,
                smc_logistics_shipment_sorting.rate,
                smc_logistics_shipment_sorting.unit,
                smc_logistics_shipment_sorting.primary_qty,
                smc_logistics_shipment_sorting.secondary_qty
                FROM
                smc_logistics_shipment_sorting
                WHERE
                smc_logistics_shipment_sorting.periodid = $periodid");
    }
    #
    #   
    #   execute shipment 
    public function executeForwardShipment($periodid, $userid){
        $pdo = $this->pdo;
        try {
            // Begin Transaction
            $pdo->beginTransaction();

            // Fetch unsorted items
            $stmt = $pdo->query("SELECT * FROM smc_logistics_shipment_sorting WHERE periodid=$periodid AND is_used = 0 ORDER BY destination_id");
            $sortingData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($sortingData)) {
                return "No items to process.";
            }

            // Group by destination_id
            $grouped = [];
            foreach ($sortingData as $item) {
                $grouped[$item['destination_id']][] = $item;
            }

            foreach ($grouped as $destinationId => $items) {
                // Use first item to gather header info
                $first = $items[0];

                $shipmentNo = 'SMC-BE-' . date('YmdHis') . '-' . rand(100, 999);
                $totalQty = 0;
                $totalValue = 0.0;

                foreach ($items as $item) {
                    $totalQty += (int)$item['secondary_qty'];   //  Assuming secondary_qty is the quantity to be shipped
                    $totalValue += ((float)$item['rate']) * (int)$item['secondary_qty'];    //  Assuming rate is per secondary unit
                    $totalValue = number_format($totalValue, 2, '.', '');
                }
                //  execute outbound before logging data
                
                // Insert into smc_logistics_shipment
                $stmtShipment = $pdo->prepare("
                    INSERT INTO smc_logistics_shipment (
                        shipment_no, shipment_type, origin_id, periodid, origin_location_type, origin_string,
                        destination_id, destination_location_type, destination_string,
                        total_qty, total_value, rate, unit, shipment_status,
                        created, updated
                    ) VALUES (
                        :shipment_no, 'Forward', :origin_id, :periodid, :origin_type, :origin_string,
                        :destination_id, :destination_type, :destination_string,
                        :total_qty, :total_value, :rate, :unit, 'Pending',
                        NOW(), NOW()
                    )
                ");
                
                $stmtShipment->execute([
                    ':shipment_no' => $shipmentNo,
                    ':origin_id' => $first['origin_id'],
                    ':periodid' => $first['periodid'],
                    ':origin_type' => $first['origin_type'],
                    ':origin_string' => $first['origin_string'],
                    ':destination_id' => $first['destination_id'],
                    ':destination_type' => $first['destination_type'],
                    ':destination_string' => $first['destination_string'],
                    ':total_qty' => $totalQty,
                    ':total_value' => $totalValue,
                    ':rate' => $first['rate'],
                    ':unit' => $first['unit'],
                ]);

                $shipmentId = $pdo->lastInsertId();

                // Insert shipment items
                $stmtItem = $pdo->prepare("
                    INSERT INTO smc_logistics_shipment_item (
                        shipment_id, product_code, product_name, batch, expiry,
                        rate, unit, primary_qty, secondary_qty, created
                    ) VALUES (
                        :shipment_id, :product_code, :product_name, :batch, :expiry,
                        :rate, :unit, :primary_qty, :secondary_qty, NOW()
                    )
                ");

                $updateIds = [];

                foreach ($items as $item) {
                    $stmtItem->execute([
                        ':shipment_id' => $shipmentId,
                        ':product_code' => $item['product_code'],
                        ':product_name' => $item['product_name'],
                        ':batch' => $item['batch'],
                        ':expiry' => $item['expiry'],
                        ':rate' => $item['rate'],
                        ':unit' => $item['unit'],
                        ':primary_qty' => $item['primary_qty'],
                        ':secondary_qty' => $item['secondary_qty'],
                    ]);

                    $updateIds[] = $item['sorting_id'];
                    //
                    //  [$product_code, $product_name, $location_type, $location_id, $batch, $expiry_date, $rate, $unit, $primary_qty, $secondary_qty, $user_id]
                    $inbound_id = $this->saveOutboundMgt(
                        $shipmentId,
                        $item['product_code'],
                        $item['product_name'],
                        $item['origin_type'],
                        $item['origin_id'],
                        $item['batch'],
                        $item['expiry'],
                        $item['rate'],
                        $item['unit'],
                        $item['primary_qty'],
                        $item['secondary_qty'],
                        $userid
                    );
                }

                // Mark items as used
                if (!empty($updateIds)) {
                    $inClause = implode(',', array_map('intval', $updateIds));
                    $pdo->exec("UPDATE smc_logistics_shipment_sorting SET is_used = 1 WHERE sorting_id IN ($inClause)");
                }
            }

            $pdo->commit();
            return "Shipments processed successfully.";
        } catch (\PDOException $e) {
            //$pdo->rollBack();
            //  log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS SHIPMENT PROCESSING ERROR ---\n" . $e->getMessage() . "\n";
            file_put_contents('error-report.txt', $error_message, FILE_APPEND);
            return "Error processing shipments: " . $e->getMessage();
        }

    }
    #   Create movement with shipment
    public function createMovementWithShipments($periodid, $transporter_id, $title, $shipmentIds, $conveyor_id, $userId){
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Insert into smc_logistics_movement
            $stmt = $this->pdo->prepare("
                INSERT INTO smc_logistics_movement (periodid, title, transporter_id, conveyor_id, userid)
                VALUES (:periodid, :title, :transporter_id, :conveyor_id, :userid)
            ");
            $stmt->execute([
                ':periodid' => $periodid,
                ':title' => $title,
                ':transporter_id' => $transporter_id,
                ':conveyor_id' => $conveyor_id,
                ':userid' => $userId
            ]);

            // Get the auto-generated movement ID
            $movementId = $this->pdo->lastInsertId();

            // Insert shipment items
            $itemStmt = $this->pdo->prepare("
                INSERT INTO smc_logistics_movement_items (movement_id, shipment_id)
                VALUES (:movement_id, :shipment_id)
            ");

            $updateStmt = $this->pdo->prepare("
                UPDATE smc_logistics_shipment
                SET shipment_status = 'processing', status_value = 15
                WHERE shipment_id = :shipment_id
            ");

            foreach ($shipmentIds as $shipmentId) {
                $itemStmt->execute([
                    ':movement_id' => $movementId,
                    ':shipment_id' => $shipmentId
                ]);
                $updateStmt->execute([
                    ':shipment_id' => $shipmentId
                ]);
            }

            // Commit transaction
            $this->pdo->commit();

            return $movementId;

        } catch (\PDOException $e) {
            // Rollback on error
            $this->pdo->rollBack();
            // Log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS MOVEMENT CREATION ERROR ---\n" . $e->getMessage() . "\n";
            file_put_contents('error-report.txt', $error_message, FILE_APPEND);
            return false;
        }
    }
    #
    #
    #
    #
    public function getShipmentList($periodid){
        return DbHelper::Table("SELECT
                smc_logistics_shipment.shipment_id,
                smc_logistics_shipment.shipment_no,
                smc_logistics_shipment.shipment_type,
                smc_logistics_shipment.origin_id,
                smc_logistics_shipment.origin_location_type,
                smc_logistics_shipment.origin_string,
                smc_logistics_shipment.destination_id,
                smc_logistics_shipment.destination_location_type,
                smc_logistics_shipment.destination_string,
                smc_logistics_shipment.total_qty,
                smc_logistics_shipment.total_value,
                smc_logistics_shipment.rate,
                smc_logistics_shipment.unit,
                smc_logistics_shipment.shipment_status,
                '' AS pick
                FROM
                smc_logistics_shipment
                WHERE
                smc_logistics_shipment.status_value = 10 OR
                smc_logistics_shipment.status_value = 15 AND
                smc_logistics_shipment.periodid = $periodid");
    }
    #   Get dettail list of commodity in the shipment
    public function getShipmentItems($shipment_id){
        return DbHelper::Table("SELECT
                item_id,
                shipment_id,
                product_code,
                product_name,
                batch,
                expiry,
                rate,
                unit,
                primary_qty,
                secondary_qty,
                created
                FROM
                smc_logistics_shipment_item
                WHERE
                shipment_id = $shipment_id");
    }
    public  function getShipmentDetails($shipment_id){
        //  get shipment info
        //  get shipment items
        //  get shipment approvals
        //  combine all into one array and return
        $shipment = DbHelper::Table("SELECT
                shipment_id,
                shipment_no,
                shipment_type,
                origin_id,
                origin_location_type,
                origin_string,
                destination_id,
                destination_location_type,
                destination_string,
                total_qty,
                total_value,
                rate,
                unit,
                shipment_status,
                created
            FROM
                smc_logistics_shipment
            WHERE
                shipment_id = $shipment_id");
        $items = $this->getShipmentItems($shipment_id);
        $approvals = DbHelper::Table("SELECT 
                    s.shipment_id,
                    
                    sa.approval_id AS source_approval_id,
                    sa.approval_type AS source_approval_type,
                    sa.user_id AS source_user_id,
                    sa.approval_name AS source_name,
                    sa.approval_designation AS source_designation,
                    sa.approval_phone AS source_phone,
                    sa.location_string AS source_location,
                    sa.signature AS source_signature,
                    sa.approve_date AS source_approve_date,
                    sa.latitude AS source_latitude,
                    sa.longitude AS source_longitude,
                    sa.device_serial AS source_device_serial,
                    sa.app_version AS source_app_version,
                    sa.created AS source_created,
                    sa.updated AS source_updated,

                    ca.approval_id AS conveyor_approval_id,
                    ca.approval_type AS conveyor_approval_type,
                    ca.user_id AS conveyor_user_id,
                    ca.approval_name AS conveyor_name,
                    ca.approval_designation AS conveyor_designation,
                    ca.approval_phone AS conveyor_phone,
                    ca.location_string AS conveyor_location,
                    ca.signature AS conveyor_signature,
                    ca.approve_date AS conveyor_approve_date,
                    ca.latitude AS conveyor_latitude,
                    ca.longitude AS conveyor_longitude,
                    ca.device_serial AS conveyor_device_serial,
                    ca.app_version AS conveyor_app_version,
                    ca.created AS conveyor_created,
                    ca.updated AS conveyor_updated,

                    da.approval_id AS destination_approval_id,
                    da.approval_type AS destination_approval_type,
                    da.user_id AS destination_user_id,
                    da.approval_name AS destination_name,
                    da.approval_designation AS destination_designation,
                    da.approval_phone AS destination_phone,
                    da.location_string AS destination_location,
                    da.signature AS destination_signature,
                    da.approve_date AS destination_approve_date,
                    da.latitude AS destination_latitude,
                    da.longitude AS destination_longitude,
                    da.device_serial AS destination_device_serial,
                    da.app_version AS destination_app_version,
                    da.created AS destination_created,
                    da.updated AS destination_updated

                    FROM smc_logistics_shipment s
                    LEFT JOIN smc_logistics_approvals sa ON s.source_approval_id = sa.approval_id
                    LEFT JOIN smc_logistics_approvals ca ON s.conveyor_approval_id = ca.approval_id
                    LEFT JOIN smc_logistics_approvals da ON s.destination_approval_id = da.approval_id
                    WHERE s.shipment_id = $shipment_id");
        //  combine all into one array
        return $shipmentDetails = [
            'shipment' => $shipment,
            'items' => $items,
            'approvals' => $approvals
        ];
    }
    #   Get list of movement by period
    public function getMovementList($periodid){
        return DbHelper::Table("SELECT
            smc_logistics_movement.movement_id,
            smc_period.title AS period,
            smc_logistics_movement.title,
            smc_logistics_transporter.transporter,
            smc_logistics_transporter.poc_phone AS transporter_phone,
            CONCAT_WS (' ',staff.`first`,staff.last) AS entered_by,
            staff.loginid AS entered_by_loginid,
            conveyor.loginid AS conveyor_loginid,
            CONCAT_WS(' ',conveyor.`first`,conveyor.last) AS conveyor_fullname,
            conveyor.phone AS conveyor_phone,
            smc_logistics_movement.updated
            FROM
            smc_logistics_movement
            INNER JOIN smc_period ON smc_logistics_movement.periodid = smc_period.periodid
            INNER JOIN smc_logistics_transporter ON smc_logistics_movement.transporter_id = smc_logistics_transporter.transporter_id
            INNER JOIN (SELECT usr_login.userid, usr_login.loginid, usr_identity.`first`, usr_identity.last, usr_identity.phone FROM usr_login INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) conveyor ON smc_logistics_movement.conveyor_id = conveyor.userid
            INNER JOIN (SELECT usr_login.userid, usr_login.loginid, usr_identity.`first`, usr_identity.last, usr_identity.phone FROM usr_login INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) staff ON smc_logistics_movement.userid = staff.userid
            WHERE
            smc_logistics_movement.periodid = $periodid");
    }
    #   Get detail list of shipment within the moment
    public function getMovementDetails($movement_id){
        return DbHelper::Table("SELECT
                smc_logistics_movement_items.item_id,
                smc_logistics_movement_items.movement_id,
                smc_logistics_movement_items.created,
                smc_logistics_shipment.shipment_id,
                smc_logistics_shipment.shipment_no,
                smc_logistics_shipment.shipment_type,
                smc_logistics_shipment.origin_location_type,
                smc_logistics_shipment.origin_string,
                smc_logistics_shipment.destination_location_type,
                smc_logistics_shipment.destination_string,
                smc_logistics_shipment.total_qty,
                smc_logistics_shipment.total_value,
                smc_logistics_shipment.shipment_status
                FROM
                smc_logistics_movement_items
                INNER JOIN smc_logistics_shipment ON smc_logistics_movement_items.shipment_id = smc_logistics_shipment.shipment_id
                WHERE
                smc_logistics_movement_items.movement_id = $movement_id");
    }
    #
    #   fix movement status collection process
    #
    #   Download list of movement for mobile app (Delivery Route) (count of locations)
    public function getAppMovementList($periodid, $conveyour_id){
        $movement = DbHelper::Table("SELECT
                        smc_logistics_movement.movement_id,
                        smc_logistics_movement.title,
                        smc_period.title AS period,
                        smc_logistics_transporter.transporter
                        FROM
                        smc_logistics_movement
                        INNER JOIN smc_logistics_transporter ON smc_logistics_movement.transporter_id = smc_logistics_transporter.transporter_id
                        INNER JOIN smc_period ON smc_logistics_movement.periodid = smc_period.periodid
                        WHERE
                        smc_logistics_movement.conveyor_id = $conveyour_id AND
                        smc_logistics_movement.periodid = $periodid");
        if(count($movement) > 0){
            $shipments = DbHelper::Table("SELECT
                smc_logistics_movement_items.item_id,
                smc_logistics_movement_items.movement_id,
                smc_logistics_shipment.shipment_id,
                smc_logistics_shipment.shipment_no,
                smc_logistics_shipment.shipment_type,
                smc_logistics_shipment.origin_location_type,
                smc_logistics_shipment.origin_string,
                smc_logistics_shipment.destination_location_type,
                smc_logistics_shipment.destination_string,
                smc_logistics_shipment.total_qty,
                smc_logistics_shipment.total_value,
                smc_logistics_shipment.shipment_status,
                smc_logistics_movement_items.created
                FROM
                smc_logistics_movement_items
                INNER JOIN smc_logistics_shipment ON smc_logistics_movement_items.shipment_id = smc_logistics_shipment.shipment_id
                INNER JOIN smc_logistics_movement ON smc_logistics_movement_items.movement_id = smc_logistics_movement.movement_id
                WHERE
                smc_logistics_movement.periodid = $periodid AND
                smc_logistics_movement.conveyor_id = $conveyour_id");
            //
            return [
                'movement' => $movement,
                'shipments' => $shipments
            ];
        }
        return [];
    }
    #   Mobile app functions ()
    #   Route Confirmation Pickup 
    public function confirmRoute($movementid){
        // update smc_logistics_shipment status to 'Route Confirmation'  inner join smc_logistics_movement_items where smc_logistics_movement_items.movement_id = :movementid 
        //  update smc_logistics_movement set status_value = 20, movement_status = 'processing' where movement_id = :movementid
        try {
            $stmt = $this->pdo->prepare("UPDATE smc_logistics_movement SET status_value = 20, movement_status = 'processing' WHERE movement_id = :movement_id");
            $stmt->bindParam(':movement_id', $movementid);
            if($stmt->execute()){
                //  update smc_logistics_shipment status to 'Route Confirmation'
                $updateShipmentStmt = $this->pdo->prepare("UPDATE smc_logistics_shipment SET shipment_status = 'Route Confirmation', status_value = 20 WHERE shipment_id IN (SELECT shipment_id FROM smc_logistics_movement_items WHERE movement_id = :movement_id)");
                $updateShipmentStmt->bindParam(':movement_id', $movementid);
                return $updateShipmentStmt->execute();
            }else{
                //  log error if any
                $error = $stmt->errorInfo();
                $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS CONFIRM ROUTE - UPDATE MOVEMENT ERROR ---\n" . $error[2] . "\n";
                file_put_contents('error-report.txt', $error_message, FILE_APPEND);
                return false;
            }
        } catch (\PDOException $e) {
            // Log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS MOVEMENT CONFIRMATION ERROR ---\n" . $e->getMessage() . "\n";
            file_put_contents('error-report.txt', $error_message, FILE_APPEND);
            return false;
        }
    }
    #   insert approval for shipment
    private function insertApproval($approval_type, $name, $designation, $phone, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion){
        try {

            // Insert into smc_logistics_approvals
            $stmt = $this->pdo->prepare("
                INSERT INTO smc_logistics_approvals (approval_type, user_id, approval_name, approval_designation, approval_phone, location_string, signature, approve_date, latitude, longitude, device_serial, app_version)
                VALUES (:approval_type, :user_id, :approval_name, :approval_designation, :approval_phone, :location_string, :signature, :approve_date, :latitude, :longitude, :device_serial, :app_version)
            ");
            $stmt->execute([
                ':approval_type' => $approval_type,
                ':user_id' => $userId,
                ':approval_name' => $name,
                ':approval_designation' => $designation,
                ':approval_phone' => $phone,
                ':location_string' => $locationString,
                ':signature' => $signature,
                ':approve_date' => $approveDate,
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':device_serial' => $deviceSerial,
                ':app_version' => $appVersion
            ]);

            // return the last inserted ID
            return $this->pdo->lastInsertId();

        } catch (\PDOException $e) {
            // Log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS APPROVAL INSERTION ERROR ---\n" . $e->getMessage() . "\n";
            file_put_contents('error-report.txt', $error_message, FILE_APPEND);
            return false;
        }
    }
    //  [$movementid, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion]
    public function OriginApproval($movementid, $name, $designation, $phone, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion){
        $approval_type = "Origin";
        $approval_id = $this->insertApproval($approval_type, $name, $designation, $phone, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion);
        if($approval_id){
            //  update movement with approval
            $stmt = $this->pdo->prepare("UPDATE smc_logistics_movement SET source_approval_id = :source_approval_id WHERE movement_id = :movement_id");
            $stmt->bindParam(':source_approval_id', $approval_id);
            $stmt->bindParam(':movement_id', $movementid);
            if($stmt->execute()){
                //  update shipment status to 'Ready for Dispatch'
                $updateShipmentStmt = $this->pdo->prepare("UPDATE smc_logistics_shipment SET shipment_status = 'Ready for Dispatch', status_value = 25 WHERE shipment_id IN (SELECT shipment_id FROM smc_logistics_movement_items WHERE movement_id = :movement_id)");
                $updateShipmentStmt->bindParam(':movement_id', $movementid);
                $updateShipmentStmt->execute();
                return true;
            }else{
                //  log error if any
                $error = $stmt->errorInfo();
                $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS MOVEMENT UPDATE ERROR ---\n" . $error[2] . "\n";
                file_put_contents('error-report.txt', $error_message, FILE_APPEND);
                return false;
            }
        }
    }
    public function ConveyorApproval($movementid, $name, $designation, $phone, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion){
        $approval_type = "Conveyor";
        $approval_id = $this->insertApproval($approval_type, $name, $designation, $phone, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion);
        if($approval_id){
            //  update movement with approval
            $stmt = $this->pdo->prepare("UPDATE smc_logistics_movement SET `status` = 'dispatched', `status_value` = 30, conveyor_approval_id = :conveyor_approval_id WHERE movement_id = :movement_id");
            $stmt->bindParam(':conveyor_approval_id', $approval_id);
            $stmt->bindParam(':movement_id', $movementid);
            if($stmt->execute()){
                //  update shipment status to 'Dispatched'
                $updateShipmentStmt = $this->pdo->prepare("UPDATE smc_logistics_shipment SET shipment_status = 'Dispatched', status_value = 30 WHERE shipment_id IN (SELECT shipment_id FROM smc_logistics_movement_items WHERE movement_id = :movement_id)");
                $updateShipmentStmt->bindParam(':movement_id', $movementid);
                $updateShipmentStmt->execute();
                return true;
            }else{
                //  log error if any
                $error = $stmt->errorInfo();
                $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS MOVEMENT UPDATE ERROR ---\n" . $error[2] . "\n";
                file_put_contents('error-report.txt', $error_message, FILE_APPEND);
                return false;
            }
        }
    }
    public function DestinationApproval($movementid, $shipmentid, $name, $designation, $phone, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion){
        $approval_type = "Destination";
        //  start transaction
        $this->pdo->beginTransaction();
        
        $approval_id = $this->insertApproval($approval_type, $name, $designation, $phone, $userId, $locationString, $signature, $approveDate, $latitude, $longitude, $deviceSerial, $appVersion);
        if($approval_id){
            //  update shipment status to 'Dispatched'
            //  get list of inventory intransit with same shipment id and not used
            //  insert inbound with the list to populate destination inventory
            //  update inventory transit as used
            //  complete the transaction
            $updateShipmentStmt = $this->pdo->prepare("UPDATE smc_logistics_shipment SET destination_approval_id =:approval_id, shipment_status = 'Delivered', status_value = 50 WHERE shipment_id = :shipment_id");
            $updateShipmentStmt->bindParam(':approval_id', $approval_id);
            $updateShipmentStmt->bindParam(':shipment_id', $shipmentid);
            if($updateShipmentStmt->execute()){
                $invetorydata = $this->getIntrasitInventory($shipmentid);
                if(count($invetorydata) > 0){
                    foreach($invetorydata as $inventory){
                        //  insert inbound with the inventory data
                        $inbound_id = $this->InboundMgt(
                            $inventory['product_code'],
                            $inventory['product_name'],
                            $inventory['destination_location_type'],
                            $inventory['destination_id'],
                            $inventory['batch'],
                            $inventory['expiry'],
                            $inventory['rate'],
                            $inventory['unit'],
                            $inventory['primary_qty'],
                            $inventory['secondary_qty'],
                            $userId
                        );
                        if($inbound_id){
                            //  update inventory transit as used
                            if(!$this->inventoryTransitUsed($inventory['inventory_id'])){
                                //  log error and rollback transaction
                                $this->pdo->rollBack();
                                return false;
                            }
                        }else{
                            //  log error and rollback transaction
                            $this->pdo->rollBack();
                            return false;
                        }
                    }
                }
                //  commit transaction
                $this->pdo->commit();
                return true;
            }else{
                //  log error if any
                $error = $updateShipmentStmt->errorInfo();
                $error_message = "[" . date('Y-m-d H:i:s') . "]\n--- LOGISTICS MOVEMENT UPDATE ERROR ---\n" . $error[2] . "\n";
                file_put_contents('error-report.txt', $error_message, FILE_APPEND);
                return false;
            }
        }
    }
    #
    #
    # [inventory_id, shipment_id, product_code, product_name, destination_location_type, destination_id, batch, expiry, rate, unit, primary_qty, secondary_qty, is_used]
    private function getIntrasitInventory($shipmentid){
        return DbHelper::Table("SELECT
                            s.inventory_id,
                            s.shipment_id,
                            s.product_code,
                            s.product_name,
                            l.destination_location_type,
                            l.destination_id,
                            s.batch,
                            s.expiry,
                            s.rate,
                            s.unit,
                            s.primary_qty,
                            s.secondary_qty,
                            s.is_used
                        FROM smc_inventory_central_transit s
                        INNER JOIN smc_logistics_shipment l
                            ON s.shipment_id = l.shipment_id
                        WHERE s.shipment_id = $shipmentid
                        AND s.is_used = 0");
    }
    #   insert inbound list to populate inventory 
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
        //  insert new roww into smc_inventory_inbound 
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
    #   update inventory transit as used
    private function inventoryTransitUsed($inventory_id){
        //  update smc_inventory_central_transit set is_used = 1 where inventory_id = :inventory_id
        $sql = "UPDATE smc_inventory_central_transit SET is_used = 1 WHERE inventory_id = :inventory_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':inventory_id', $inventory_id);
        $stmt->execute();
        if($stmt->errorCode() != '00000'){
            //  log error
            $error_message = "[" . date('Y-m-d H:i:s') . "]\n";
            $error_message .= "-- INVENTORY TRANSIT USED UPDATE --\n";
            $error_message .= "Inventory ID: $inventory_id\n";
            $error_message .= $stmt->errorInfo()[2];
            $error_file_name = "error-report.txt";
            WriteToFile($error_file_name, $error_message);
            return false;
        }
        return true;
    }


}

?>