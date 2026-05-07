<?php
namespace Dashboard;
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Mobilization {
    private $db;
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    /*
     *  |====================|
     *  |=== Mobilization ===|
     *  |====================|
     * 
     */
    # Top level fields [households, netcards, family_size]
    public function TopSummary(){
        return $this->db->DataTable("select count(`hhm_mobilization`.`hhid`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size` from `hhm_mobilization`");
    }
    /*
     *      Aggregate by date
     *
     */
    # Top level aggregated summary by date [title, households, netcards, family_size]
    public function TopSummaryByDate(){
        return $this->db->DataTable("select cast(`hhm_mobilization`.`collected_date` as date) AS `title`,count(`hhm_mobilization`.`hhid`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size` from `hhm_mobilization` group by cast(`hhm_mobilization`.`collected_date` as date)");
    }
    # Drill level 1 - [title, households, netcards, family_size, lgaid] Mobilization @ selected date
    public function LgaAggregateByDate($date){
        return $this->db->DataTable("select `ms_geo_lga`.`Fullname` AS `title`,count(`hhm_mobilization`.`hhm_id`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,`ms_geo_lga`.`LgaId` AS `lgaid` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`)) where cast(`hhm_mobilization`.`collected_date` as date) = cast('$date' as date) group by `sys_geo_codex`.`lgaid` order by MIN(`sys_geo_codex`.`geo_string`)");
    }
    # Drill level 2 - [title, households, netcards, family_size,wardid] List Ward Mobilization @ selected LGA and date
    public function WardAggregateByDate($date, $lgaid){
        return $this->db->DataTable("select `ms_geo_ward`.`ward` AS `title`,count(`hhm_mobilization`.`hhm_id`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,ms_geo_ward.wardid from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_ward` on(`sys_geo_codex`.`wardid` = `ms_geo_ward`.`wardid`)) where cast(`hhm_mobilization`.`collected_date` as date) = cast('$date' as date) and `sys_geo_codex`.`lgaid` = $lgaid group by `sys_geo_codex`.`wardid` order by `ms_geo_ward`.`ward`");
    }
    # Drill level 3 - [title, households, netcards, family_size] List Ward Mobilization @ selected ward and date
    public function DpAggregateByDate($date, $wardid){
        return $this->db->DataTable("select `ms_geo_dp`.`dp` AS `title`,count(`hhm_mobilization`.`hhm_id`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,`ms_geo_dp`.`dpid` AS `dpid` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_dp` on(`sys_geo_codex`.`dpid` = `ms_geo_dp`.`dpid`)) where cast(`hhm_mobilization`.`collected_date` as date) = cast('$date' as date) and `sys_geo_codex`.`wardid` = $wardid group by `sys_geo_codex`.`dpid`");
    }
    /*
     *      Aggregate by Location
     * 
     */
    # Top level aggregated summary by date [title, households, netcards, family_size, lgaid]
    public function TopSummaryByLocation(){
        return $this->db->DataTable("select `ms_geo_lga`.`Fullname` AS `title`,count(`hhm_mobilization`.`hhm_id`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,`ms_geo_lga`.`LgaId` AS `lgaid` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`)) group by `sys_geo_codex`.`lgaid` order by MIN(`sys_geo_codex`.`geo_string`)");
    }
    # Drill level 1 -  [title, households, netcards, family_size, wardid]
    public function WardAggregateByLocation($lgaid){
        return $this->db->DataTable("select `ms_geo_ward`.`ward` AS `title`,count(`hhm_mobilization`.`hhm_id`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,`ms_geo_ward`.`wardid` AS `wardid` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_ward` on(`sys_geo_codex`.`wardid` = `ms_geo_ward`.`wardid`)) where `sys_geo_codex`.`lgaid` = $lgaid group by `sys_geo_codex`.`wardid` order by `ms_geo_ward`.`ward`");
    }
    # Drill level 2 -  [title, households, netcards, family_size, dpid]
    public function DpAggregateByLocation($wardid){
        return $this->db->DataTable("select `ms_geo_dp`.`dp` AS `title`,count(`hhm_mobilization`.`hhm_id`) AS `households`,sum(`hhm_mobilization`.`allocated_net`) AS `netcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,`ms_geo_dp`.`dpid` AS `dpid` from ((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_dp` on(`sys_geo_codex`.`dpid` = `ms_geo_dp`.`dpid`)) where `sys_geo_codex`.`wardid` = $wardid group by `sys_geo_codex`.`dpid`");
    }
   
}
?>