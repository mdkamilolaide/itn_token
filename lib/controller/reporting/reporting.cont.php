<?php

namespace Reporting;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');

class Reporting {
    private $db;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    private function TransportDataJson($query, $sheetname = "Export"){
        $data = $this->db->ExcelDataTable($query);
         #   Prep Payload
         $json_data = array(array(
            "sheetName" => $sheetname ,
            "data" => $data
        ));
        #   return payload
        return json_encode($json_data);
    }
    private function TransportData($query, $sheetname = "Export"){
        $data = $this->db->ExcelDataTable($query);
         #   Prep Payload
        return array(array(
            "sheetName" => $sheetname ,
            "data" => $data
        ));
    }
    #
    #   Activity Management
    #   
    #   Get json export for participants
    public function ListParticipants($trainingId, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select `tra_training`.`title` AS `Activity`,`usr_login`.`loginid` AS `loginid`,`usr_identity`.`first` AS `first`,`usr_identity`.`middle` AS `middle`,`usr_identity`.`last` AS `last`,`usr_identity`.`gender` AS `gender`,`usr_identity`.`email` AS `email`,`usr_identity`.`phone` AS `phone`,`usr_role`.`title` AS `role`,if(`tra_training`.`active`,'Active','Disabled') AS `activity_status`,`sys_geo_codex`.`geo_level` AS `geo_level`,`sys_geo_codex`.`geo_string` AS `geo_string` from (((((`tra_participants` join `usr_login` on(`tra_participants`.`userid` = `usr_login`.`userid`)) join `usr_identity` on(`tra_participants`.`userid` = `usr_identity`.`userid`)) join `tra_training` on(`tra_participants`.`trainingid` = `tra_training`.`trainingid`)) left join `usr_role` on(`usr_login`.`roleid` = `usr_role`.`roleid`)) join `sys_geo_codex` on(`usr_login`.`geo_level` = `sys_geo_codex`.`geo_level` and `usr_login`.`geo_level_id` = `sys_geo_codex`.`geo_level_id`))";
        $where_condition = "where `tra_participants`.`trainingid` = $trainingId ";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "and `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition .= "and `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition .= "and `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = " where `tra_participants`.`trainingid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition";
        #   return payload
        return $this->TransportDataJson($query, "Participants");
    }
    #   Get json export for Bank verification status
    public function ListBankVerification($trainingId, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select `tra_training`.`title` AS `Activity`,`usr_login`.`loginid` AS `loginid`,`usr_identity`.`first` AS `first`,`usr_identity`.`middle` AS `middle`,`usr_identity`.`last` AS `last`,`usr_identity`.`gender` AS `gender`,`usr_identity`.`email` AS `email`,`usr_identity`.`phone` AS `phone`,`usr_role`.`title` AS `role`,if(`tra_training`.`active`,'Active','Disabled') AS `activity_status`,`sys_geo_codex`.`geo_level` AS `geo_level`,`sys_geo_codex`.`geo_string` AS `geo_string`,`usr_finance`.`bank_name` AS `bank_name`,`usr_finance`.`account_name` AS `account_name`,`usr_finance`.`account_no` AS `account_no`,`usr_finance`.`verification_status` AS `verification_status`,`usr_finance`.`verified_account_name` AS `verified_account_name`,`usr_finance`.`last_verified_date` AS `last_verified_date` from ((((((`tra_participants` join `usr_login` on(`tra_participants`.`userid` = `usr_login`.`userid`)) join `usr_identity` on(`tra_participants`.`userid` = `usr_identity`.`userid`)) join `tra_training` on(`tra_participants`.`trainingid` = `tra_training`.`trainingid`)) left join `usr_role` on(`usr_login`.`roleid` = `usr_role`.`roleid`)) join `sys_geo_codex` on(`usr_login`.`geo_level` = `sys_geo_codex`.`geo_level` and `usr_login`.`geo_level_id` = `sys_geo_codex`.`geo_level_id`)) join `usr_finance` on(`usr_finance`.`userid` = `tra_participants`.`userid`))";
        $where_condition = "where `tra_participants`.`trainingid` = $trainingId ";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "and `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition .= "and `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition .= "and `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = " where `tra_participants`.`trainingid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition";
        #   return payload
        return $this->TransportDataJson($query, "Verification Status");
    }
    #   Get json export for Uncaptured Users
    public function ListUncapturedUsers($trainingId, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select `usr_login`.`loginid` AS `loginid`,`usr_role`.`title` AS `role`,`sys_geo_codex`.`geo_level` AS `geo_level`,`sys_geo_codex`.`geo_string` AS `geo_string` from ((((`tra_participants` join `usr_login` on(`tra_participants`.`userid` = `usr_login`.`userid`)) join `usr_identity` on(`tra_participants`.`userid` = `usr_identity`.`userid`)) left join `usr_role` on(`usr_login`.`roleid` = `usr_role`.`roleid`)) join `sys_geo_codex` on(`usr_login`.`geo_level` = `sys_geo_codex`.`geo_level` and `usr_login`.`geo_level_id` = `sys_geo_codex`.`geo_level_id`))";
        $where_condition = "where `tra_participants`.`trainingid` = $trainingId and `usr_identity`.`first` is null and `usr_identity`.`last` is null ";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "and `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition .= "and `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition .= "and `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = " where `tra_participants`.`trainingid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition";
        #   return payload
        return $this->TransportDataJson($query, "Uncaptured");
    }
    #
    #   Mobilization
    #   
    #   Get json export mobilization LGA level
    public function ListMobilizationByLga($geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select `ms_geo_lga`.`Fullname` AS `lga`,count(`hhm_mobilization`.`hhid`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `allocated net`,sum(`hhm_mobilization`.`family_size`) AS `family size` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))";
        $where_condition = "";
        $groupby = "group by `sys_geo_codex`.`lgaid`";
        $orderby = "order by `sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "where `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "where `sys_geo_codex`.`lgaid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = "where `sys_geo_codex`.`lgaid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Mobilization by LGA");
    }
    #   Get json export mobilization DP level
    public function ListMobilizationByDp($geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select `ms_geo_lga`.`Fullname` AS `lga`,`ms_geo_ward`.`ward` AS `ward`,`ms_geo_dp`.`dp` AS `dp`,count(`hhm_mobilization`.`hhid`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `allocated net`,sum(`hhm_mobilization`.`family_size`) AS `family size` from ((((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`)) join `ms_geo_ward` on(`ms_geo_lga`.`LgaId` = `ms_geo_ward`.`lgaid`)) join `ms_geo_dp` on(`ms_geo_ward`.`wardid` = `ms_geo_dp`.`wardid` and `sys_geo_codex`.`dpid` = `ms_geo_dp`.`dpid`))";
        $where_condition = "";
        $groupby = "group by `sys_geo_codex`.`dpid`";
        $orderby = "order by `sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition = "where `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition = "where `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition = "where `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition = "where `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = " where `sys_geo_codex`.`stateid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Mobilization by DP");
    }
    #   Get json export date mobilization by LGA level
    public function ListDateMobilizationByLga($date, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select cast(`hhm_mobilization`.`collected_date` as date) AS `date`,`ms_geo_lga`.`Fullname` AS `lga`,count(`hhm_mobilization`.`hhid`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `allocated_nets`,sum(`hhm_mobilization`.`family_size`) AS `family_size` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))";
        $where_condition = "where cast(`hhm_mobilization`.`collected_date` as date) = cast('$date' as date) ";
        $groupby = "group by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`lgaid`";
        $orderby = "order by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition .= "and `sys_geo_codex`.`lgaid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Date Mobilization");
    }
    #   Get json export date range mobilization by LGA level
    public function ListDateRangeMobilizationByLga($start_date, $end_date, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select cast(`hhm_mobilization`.`collected_date` as date) AS `date`,`ms_geo_lga`.`Fullname` AS `lga`,count(`hhm_mobilization`.`hhid`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `allocated_nets`,sum(`hhm_mobilization`.`family_size`) AS `family_size` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))";
        $where_condition = "where cast(`hhm_mobilization`.`collected_date` as date) between cast('$start_date' as date) and cast('$end_date' as date) ";
        $groupby = "group by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`lgaid`";
        $orderby = "order by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition .= "and `sys_geo_codex`.`lgaid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Date Mobilization");
    }
    #   Get json export date mobilization by DP level
    public function ListDateMobilizationByDp($date, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select cast(`hhm_mobilization`.`collected_date` as date) AS `date`,`sys_geo_codex`.`geo_string` AS `geo_string`,count(`hhm_mobilization`.`hhid`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `allocated_nets`,sum(`hhm_mobilization`.`family_size`) AS `family_size` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))";
        $where_condition = "where cast(`hhm_mobilization`.`collected_date` as date) = cast('$date' as date) ";
        $groupby = "group by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`dpid`";
        $orderby = "order by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`geo_string`";
        #   Condition
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "and `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition .= "and `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition .= "and `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = " and `sys_geo_codex`.`stateid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Date Mobilization");
    }
    #
    #   Distribution
    #   
    #   
    #   Get json export Distribution LGA level
    public function ListDistributionByLga($geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select `ms_geo_lga`.`Fullname` AS `lga`,count(`hhm_distribution`.`dis_id`) AS `Households`,sum(`hhm_distribution`.`collected_nets`) AS `nets` from ((`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))";
        $where_condition = " ";
        $groupby = "group by `sys_geo_codex`.`lgaid`";
        $orderby = "order by `sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "where `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "where `sys_geo_codex`.`lgaid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = "where `sys_geo_codex`.`lgaid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Distribution by LGA");
    }
    #   Get json export Distribution DP level
    public function ListDistributionByDp($geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select `sys_geo_codex`.`geo_string` AS `geo_string`,count(`hhm_distribution`.`dis_id`) AS `Households`,sum(`hhm_distribution`.`collected_nets`) AS `nets` from (`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp'))";
        $where_condition = "";
        $groupby = "group by `sys_geo_codex`.`dpid`";
        $orderby = "order by `sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition = "where `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition = "where `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition = "where `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition = "where `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition = " where `sys_geo_codex`.`stateid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Distribution by DP");
    }
    #   Get json export specific date distribution by LGA level
    public function ListDateDistributionByLga($date, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select cast(`hhm_distribution`.`collected_date` as date) AS `date`,`ms_geo_lga`.`Fullname` AS `lga`,count(distinct `hhm_distribution`.`dp_id`) AS `dp`,count(`hhm_distribution`.`dis_id`) AS `Households`,sum(`hhm_distribution`.`collected_nets`) AS `nets` from ((`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))";
        $where_condition = "where cast(`hhm_distribution`.`collected_date` as date) = cast('$date' as date) ";
        $groupby = "group by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`lgaid`";
        $orderby = "order by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition .= "and `sys_geo_codex`.`lgaid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Date Distribution");
    }
    #   Get json export date range distribution by LGA level
    public function ListDateRangeDistributionByLga($start_date, $end_date, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select cast(`hhm_distribution`.`collected_date` as date) AS `date`,`ms_geo_lga`.`Fullname` AS `lga`,count(distinct `hhm_distribution`.`dp_id`) AS `dp`,count(`hhm_distribution`.`dis_id`) AS `Households`,sum(`hhm_distribution`.`collected_nets`) AS `nets` from ((`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))";
        $where_condition = "where cast(`hhm_distribution`.`collected_date` as date) between cast('$start_date' as date) and cast('$end_date' as date) ";
        $groupby = "group by cast(`hhm_distribution`.`collected_date` as date),`sys_geo_codex`.`lgaid`";
        $orderby = "order by cast(`hhm_distribution`.`collected_date` as date),`sys_geo_codex`.`geo_string`";
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition .= "and `sys_geo_codex`.`lgaid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Date Distribution");
    }
    #   Get json export specific date distribution by DP level
    public function ListDateDistributionByDp($date, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select cast(`hhm_distribution`.`collected_date` as date) AS `date`,`sys_geo_codex`.`geo_string` AS `geo_string`,count(`hhm_distribution`.`dis_id`) AS `Households`,sum(`hhm_distribution`.`collected_nets`) AS `nets` from (`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp'))";
        $where_condition = "where cast(`hhm_distribution`.`collected_date` as date) = cast('$date' as date) ";
        $groupby = "group by cast(`hhm_distribution`.`collected_date` as date),`sys_geo_codex`.`dpid`";
        $orderby = "order by cast(`hhm_distribution`.`collected_date` as date),`sys_geo_codex`.`geo_string`";
        #   Condition
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "and `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition .= "and `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition .= "and `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition .= " and `sys_geo_codex`.`stateid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Date Distribution");
    }
    #   Get json export specific date distribution by DP level
    public function ListDateRangeDistributionByDp($start_date, $end_date, $geo_level, $geo_level_id){
        //  Where condition
        $q_compose = "select cast(`hhm_distribution`.`collected_date` as date) AS `date`,`sys_geo_codex`.`geo_string` AS `geo_string`,count(`hhm_distribution`.`dis_id`) AS `Households`,sum(`hhm_distribution`.`collected_nets`) AS `nets` from (`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp'))";
        $where_condition = "where cast(`hhm_distribution`.`collected_date` as date) between cast('$start_date' as date) and cast('$end_date' as date) ";
        $groupby = "group by cast(`hhm_distribution`.`collected_date` as date),`sys_geo_codex`.`dpid`";
        $orderby = "order by cast(`hhm_distribution`.`collected_date` as date),`sys_geo_codex`.`geo_string`";
        #   Condition
        #   Condition
        if($geo_level == 'state'){
            $where_condition .= "and `sys_geo_codex`.`stateid` = $geo_level_id";
        }elseif($geo_level == 'lga'){
            $where_condition .= "and `sys_geo_codex`.`lgaid` = $geo_level_id";
        }elseif($geo_level == 'cluster'){
            $where_condition .= "and `sys_geo_codex`.`clusterid` = $geo_level_id";
        }elseif($geo_level == 'ward'){
            $where_condition .= "and `sys_geo_codex`.`wardid` = $geo_level_id";
        }else{
            #   Generate nothing
            $where_condition .= " and `sys_geo_codex`.`stateid` = 0 ";
        }
        #
        $query = "$q_compose $where_condition $groupby $orderby";
        #   return payload
        return $this->TransportDataJson($query, "Date Distribution");
    }
}

?>