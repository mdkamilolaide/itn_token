<?php
namespace Dashboard;
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Distribution {
    private $db;
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    /*
    *  |====================|
    *  |=== Distribution ===|
    *  |====================|
    * 
    */
    # Top level fields [household_mobilized, household_redeemed, familysize_mobilized, familysize_redeemed, net_issued, net_redeemed]
    public function TopSummary(){
        return $this->db->DataTable("WITH redeemed AS (SELECT m.family_size FROM hhm_mobilization m JOIN hhm_distribution d ON m.etoken_serial = d.etoken_serial) SELECT (SELECT COUNT(*) FROM hhm_mobilization) AS household_mobilized, (SELECT COUNT(*) FROM hhm_distribution) AS household_redeemed, COALESCE((SELECT SUM(family_size) FROM hhm_mobilization),0) AS familysize_mobilized, COALESCE((SELECT SUM(family_size) FROM redeemed),0) AS familysize_redeemed, COALESCE((SELECT SUM(allocated_net) FROM hhm_mobilization),0) AS net_issued, COALESCE((SELECT SUM(collected_nets) FROM hhm_distribution),0) AS net_redeemed");
    }

    #   
    # Aggregations by location
    #  
    # LGA level aggregated  [id, title, household_mobilized, household_redeemed, familysize_mobilized, familysize_redeemed, net_issued, net_redeemed]
    public function LgaAggregateByLocation(){
        return $this->db->DataTable("SELECT l.LgaId id, l.Fullname title, COUNT(DISTINCT m.hhid) household_mobilized, COUNT(DISTINCT d.dis_id) household_redeemed, SUM(m.family_size) familysize_mobilized, SUM(CASE WHEN d.etoken_serial IS NOT NULL THEN m.family_size ELSE 0 END) familysize_redeemed, SUM(m.allocated_net) net_issued, SUM(IFNULL(d.collected_nets,0)) net_redeemed FROM ms_geo_lga l JOIN sys_geo_codex g ON g.lgaid=l.LgaId AND g.geo_level='dp' JOIN hhm_mobilization m ON m.dp_id=g.dpid LEFT JOIN hhm_distribution d ON m.etoken_serial=d.etoken_serial GROUP BY l.LgaId ORDER BY l.Fullname");
    }
    #   [id, title, household_mobilized, household_redeemed, familysize_mobilized, familysize_redeemed, net_issued, net_redeemed]
    public function WardAggregateByLocation($lgaid){
        return $this->db->DataTable("SELECT w.wardid id, w.ward title, COUNT(DISTINCT m.hhid) household_mobilized, COUNT(DISTINCT d.dis_id) household_redeemed, SUM(m.family_size) familysize_mobilized, SUM(CASE WHEN d.etoken_serial IS NOT NULL THEN m.family_size ELSE 0 END) familysize_redeemed, SUM(m.allocated_net) net_issued, SUM(IFNULL(d.collected_nets,0)) net_redeemed FROM ms_geo_ward w JOIN sys_geo_codex g ON g.wardid=w.wardid AND g.geo_level='dp' JOIN hhm_mobilization m ON m.dp_id=g.dpid LEFT JOIN hhm_distribution d ON m.etoken_serial=d.etoken_serial WHERE g.lgaid=$lgaid GROUP BY w.wardid ORDER BY w.ward");
    }
    #   [id, title, household_mobilized, household_redeemed, familysize_mobilized, familysize_redeemed, net_issued, net_redeemed]
    public function DpAggregateByLocation($wardid){
        return $this->db->DataTable("SELECT dp.dpid id, dp.dp title, COUNT(DISTINCT m.hhid) household_mobilized, COUNT(DISTINCT IFNULL(d.dis_id,0)) household_redeemed, SUM(m.family_size) familysize_mobilized, SUM(CASE WHEN d.etoken_serial IS NOT NULL THEN m.family_size ELSE 0 END) familysize_redeemed, SUM(m.allocated_net) net_issued, SUM(IFNULL(d.collected_nets,0)) net_redeemed FROM ms_geo_dp dp JOIN hhm_mobilization m ON m.dp_id=dp.dpid LEFT JOIN hhm_distribution d ON m.etoken_serial=d.etoken_serial WHERE dp.wardid=$wardid GROUP BY dp.dpid ORDER BY dp.dp");
    }
    /*
     *      Aggregate by date
     *
     */
    #    [id, title, household_redeemed, net_redeemed, familysize_redeemed]
    public function TopAggregateByDate(){
        return $this->db->DataTable("SELECT DATE(d.collected_date) title, COUNT(DISTINCT d.dis_id) household_redeemed, SUM(IFNULL(d.collected_nets,0)) net_redeemed, SUM(CASE WHEN d.etoken_serial IS NOT NULL THEN m.family_size ELSE 0 END) familysize_redeemed FROM ms_geo_dp dp JOIN hhm_mobilization m ON m.dp_id=dp.dpid JOIN hhm_distribution d ON m.etoken_serial=d.etoken_serial WHERE d.etoken_serial IS NOT NULL GROUP BY DATE(d.collected_date) ORDER BY DATE(d.collected_date)");
    }
    #   [id, title, household_redeemed, net_redeemed, familysize_redeemed]
    public function LgaAggregateByDate($date){
        return $this->db->DataTable("SELECT lga.LgaId id, lga.Fullname title, COUNT(DISTINCT d.dis_id) household_redeemed, SUM(IFNULL(d.collected_nets,0)) net_redeemed, SUM(CASE WHEN d.etoken_serial IS NOT NULL THEN m.family_size ELSE 0 END) familysize_redeemed FROM ms_geo_lga lga JOIN sys_geo_codex geo ON geo.lgaid=lga.LgaId AND geo.geo_level='dp' JOIN hhm_mobilization m ON m.dp_id=geo.dpid JOIN hhm_distribution d ON m.etoken_serial=d.etoken_serial WHERE d.etoken_serial IS NOT NULL AND DATE(d.collected_date)=DATE('$date') GROUP BY lga.LgaId ORDER BY lga.Fullname");
    }
    #   [id, title, household_redeemed, net_redeemed, familysize_redeemed]
    public function WardAggregateByDate($date, $lgaid){
        return $this->db->DataTable("SELECT ward.wardid id, ward.ward title, COUNT(DISTINCT d.dis_id) household_redeemed, SUM(IFNULL(d.collected_nets,0)) net_redeemed, SUM(CASE WHEN d.etoken_serial IS NOT NULL THEN m.family_size ELSE 0 END) familysize_redeemed FROM ms_geo_ward ward JOIN sys_geo_codex geo ON geo.wardid=ward.wardid AND geo.geo_level='dp' JOIN hhm_mobilization m ON m.dp_id=geo.dpid JOIN hhm_distribution d ON m.etoken_serial=d.etoken_serial WHERE d.etoken_serial IS NOT NULL AND DATE(d.collected_date)=DATE('$date') AND geo.lgaid=$lgaid GROUP BY ward.wardid ORDER BY ward.ward");
    }
    #   [id, title, household_redeemed, net_redeemed, familysize_redeemed]
    public function DpAggregateByDate($date, $wardid){
        return $this->db->DataTable("SELECT dp.dpid id, dp.dp title, COUNT(DISTINCT d.dis_id) household_redeemed, SUM(IFNULL(d.collected_nets,0)) net_redeemed, SUM(CASE WHEN d.etoken_serial IS NOT NULL THEN m.family_size ELSE 0 END) familysize_redeemed FROM ms_geo_dp dp JOIN hhm_mobilization m ON m.dp_id=dp.dpid JOIN hhm_distribution d ON m.etoken_serial=d.etoken_serial WHERE d.etoken_serial IS NOT NULL AND DATE(d.collected_date)=DATE('$date') AND dp.wardid=$wardid GROUP BY dp.dpid ORDER BY dp.dp");
    }
}
?>