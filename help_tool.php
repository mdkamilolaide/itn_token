<?php

    include_once('lib/autoload.php');
    include('lib/common.php');
    include('lib/mysql.min.php');
    /*
     *      SET DATE AND TIME
     */
    date_default_timezone_set('Africa/Lagos');
    #
    #   List of function
    #
    #
    #
    #
    $function = CleanData('func');
    if($function == 'bulk_group_create')
    {
        #   Bulk group create variables
        
        #   Required
        #   $total = "";
        #   $group_name = "";
        #   $password = "";
        #   $level = "";
        #
        #   Create Bulk Users
        #

        ##  Get list of users (from db or)
        $file = fopen('benue_dp_list.csv', 'r');
        while (($line = fgetcsv($file)) !== FALSE) {
        
            $usr = new Users\BulkUser('CDD','DEmo2021','dp',$line[0]);
            $total = $usr->CreateBulkUser(10);
            if($total)
            {
                echo "<br>Total Created was <b>$total</b>";
            }
            else
            {
                echo "<br>Unable to create new record";
            }
        }
        fclose($file);
        
        
    }
    elseif($function == 'bulk_bank_verify'){
        #
        #   Parameters
        #
        $limit = CleanData('limit')? CleanData('limit'):1;          #   setting 5 as max default limit
        $pointer = CleanData('pointer')? CleanData('pointer'):0;    #   Setting 0 as default pointer, its the number to obmit before starting
        $run_type = CleanData('type');
        ##
        $geo_level = CleanData('geolevel');
        $geo_level_id = CleanData('geoid');
        #
        #   Geo-location run type
        if($run_type == 'geo-level'){
            # get the geo level parameter
            
            if(!$geo_level || !$geo_level_id)
            {
                echo json_encode(array('status'=>400,'error'=>'Invalid geo parameters'));
                return;
            }
        }
        #
        #   Start the job
        #
        $t = new Users\BulkBankVerification();
        if($run_type == 'status'){
            $data = $t->GetStatus();
            echo json_encode(array('status'=>200, 'data'=>$data));
            return;
        }
        $t->limit = $limit;
        $t->startPointer = $pointer;
        ##  option by state level
        $t->geoLevel = 'state';
        $t->geoLevelId = 26;
        $t->Run($run_type);
        ##
        $err = $t->rep_total_error;
        $suc = $t->rep_total_success;
        $total = $t->rep_total_process;
        $report = $t->rep_trans_report;
        $json = json_encode(array(
            'status'=>200,
            'error'=>$err,
            'success'=>$suc,
            'total'=>$total,
            'report'=>$report
        ));
        #
        echo $json;
    }
    elseif($function == 'bulk_bank_verify_temp_099'){
        #
        #   Parameters
        #
        $limit = CleanData('limit')? CleanData('limit'):1;          #   setting 5 as max default limit
        $pointer = CleanData('pointer')? CleanData('pointer'):0;    #   Setting 0 as default pointer, its the number to obmit before starting
        $run_type = CleanData('type');
        ##
        $geo_level = CleanData('geolevel');
        $geo_level_id = CleanData('geoid');
        #
        #   Geo-location run type
        if($run_type == 'geo-level'){
            # get the geo level parameter
            
            if(!$geo_level || !$geo_level_id)
            {
                echo json_encode(array('status'=>400,'error'=>'Invalid geo parameters'));
                return;
            }
        }
        #
        #   Start the job
        #
        $t = new Users\BulkBankVerification();
        if($run_type == 'status'){
            $data = $t->GetStatus();
            echo json_encode(array('status'=>200, 'data'=>$data));
            return;
        }
        $t->limit = $limit;
        $t->startPointer = $pointer;
        ##  option by state level
        $t->geoLevel = 'state';
        $t->geoLevelId = 26;
        $t->RunTemp();
        ##
        $err = $t->rep_total_error;
        $suc = $t->rep_total_success;
        $total = $t->rep_total_process;
        $report = $t->rep_trans_report;
        $json = json_encode(array(
            'status'=>200,
            'error'=>$err,
            'success'=>$suc,
            'total'=>$total,
            'report'=>$report
        ));
        #
        echo $json;
    }
    elseif($function == 'bulk_user_upload'){
        #   $bk = new users\BatchUser();
        #   $bk->BulkUpload("town_announcer.csv",'CDD','2');
    }
    elseif($function == 'batch_gs_verification'){
        $limit = CleanData('limit');
        $ob = new Distribution\GsVerification($limit);
        $ob->RunVerification();

    }
    elseif($function == 'helper_update_codex_guid'){
        #   Get all empty
        $data = DbHelper::Table("SELECT id, `guid` FROM oy_sys_geo_codex WHERE `guid` IS NULL");
        #   update
        if(count($data)){
            $counter = 0;
            $db = GetMysqlDatabase();
            #   Start transaction
            $db->beginTransaction();
            foreach($data as $d){
                $uuid = generateUUID();
                $db->executeTransaction("UPDATE oy_sys_geo_codex SET `guid`=? WHERE id=? LIMIT 1",array($uuid,$d['id']));
                $counter++;
            }
            $db->commitTransaction();
            #   report
            echo "Found and updated total of $counter records.";
        }
        else{
            echo "Found no data to update.";
        }
    }
    elseif($function == 'bulk_mobilization_upload'){
        $file_location = "temp/AO01112.csv";
        $file = fopen($file_location,'r');
        $counter = 0;
        $line_counter = 0;
        while(($line = fgetcsv($file)) !== FALSE){
            if($line_counter == 0){
                $line_counter++;
                continue;
            }
            $mo = new Mobilization\Mobilization();
            $bulk_data = [array('dp_id'=>$line[1],'hm_id'=>$line[2],'co_hm_id'=>$line[3],'hoh_first'=>$line[4],'hoh_last'=>$line[5],'hoh_phone'=>$line[6],
                'hoh_gender'=>$line[7],'family_size'=>$line[8], 'allocated_net'=>$line[9],'location_description'=>'Household',
                'longitude'=>$line[11],'latitude'=>$line[12],'netcards'=>$line[13],'etoken_id'=>$line[14],
                'etoken_serial'=>$line[15],'etoken_pin'=>$line[16],'collected_date'=>$line[17])];
            if($mo->BulkMobilization($bulk_data)){
                echo $line[15]." saved successfully.<br>";
                $counter++;
            }
            else{
                echo $line[15]." unable to save this e-token.<br>";
            }
            $line_counter++;
        }
        echo "<br><b>Total uploaded records: $counter";
    }
    elseif($function == 'bulk_distribution_upload'){
        #   
        #   
        $file_location = "temp/MUM151.csv";
        $file = fopen($file_location,'r');
        $counter = 0;
        $line_counter = 0;
        while(($line = fgetcsv($file)) !== FALSE){
            ##  jump the 1st line
            if($line_counter == 0){
                $line_counter++;
                continue;
            }
            #
            $dis = new Distribution\Distribution();
            #   [dp_id, mobilization_id, recorder_id, distributor_id, collected_nets,is_gs_net, gs_net_serial, collected_date, etoken_id]
            $bulk_data = [array('dp_id'=>$line[1],'mobilization_id'=>$line[2],'recorder_id'=>$line[3],'distributor_id'=>$line[4],
            'collected_nets'=>$line[5],'is_gs_net'=>$line[6],'gs_net_serial'=>$line[7],'collected_date'=>$line[8],'etoken_id'=>$line[9])];
            if($dis->BulkDistibution($bulk_data)){
                echo "e-Token ID: ".$line[9]." saved successfully<br>";
                $counter++;
            }else{
                echo "e-Token ID: ".$line[9]." failed<br>";
            }
            $line_counter++;
        }
        echo "<br><br> <b>Total uploaded $counter of $line_counter </b>";
    }elseif($function == 'bulk_create_users'){
        //  Create Group spread across all level
        $group_name = CleanData('gn');
        $password = CleanData('ps');
        $level = CleanData('lv'); 
        $roleid = CleanData('ri');
        $number_per_level = CleanData('to');
        $accepted_level = array('lga','ward');
        //  Get list of all the 
        if($level != 'lga' || $level != 'ward'){
            //  reject operation
           die("operation not acceptable base on wrong level");
        }
        if(!$group_name || !$password || !$level || !$roleid || !$number_per_level){
            //  No required data
            die("operation not acceptable no required data");
        }
        //  Get ID list
        $level_list = array();
        if($level == 'lga'){$level_list = GetLgaIds(); }
        elseif($level == 'ward'){$level_list = GetWardIds(); }
        //  Create users
        $total_operation = 0;
        foreach($level_list as $a){
            $usr = new Users\BulkUser($group_name,$password,$level,$a['id'],$roleid);
            $total = $usr->CreateBulkUser($number_per_level);
            //
            $total_operation++;
        }
        //
        echo "Total operations: $total_operation";
    }else{
        echo "Wrong code, you are not suppose to be here.";
    }
    /*
     * Preview CSV data in raw form
    $file = fopen('benue_lga.csv', 'r');
    while (($line = fgetcsv($file)) !== FALSE) {
        echo "<pre>";
        print_r($line);
        echo "</pre>";
    }
    fclose($file);
    */

    function GetLgaIds(){
        return DbHelper::Table("SELECT LgaId AS id FROM ms_geo_lga");
    }
    function GetWardIds(){
        return DbHelper::Table("SELECT wardid AS id FROM ms_geo_ward");
    }
?>