<?php
namespace Dashboard;
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Eolin {
    private $db;
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    #   Expected (mobilization section)
    public function TopSummaryMobilization(){
        return $this->db->DataTable("SELECT Sum(hhm_mobilization.eolin_have_old_net) AS total_household, Sum(hhm_mobilization.eolin_total_old_net) AS total_net FROM hhm_mobilization");
    }
    #   Top level table - LGA view
    public function LgaSummaryMobilization(){
        return $this->db->DataTable("SELECT ms_geo_lga.LgaId AS lgaid, ms_geo_lga.Fullname AS lga, SUM(hhm_mobilization.eolin_have_old_net) AS total_household, SUM(hhm_mobilization.eolin_total_old_net) AS total_net FROM hhm_mobilization INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp' INNER JOIN ms_geo_lga ON sys_geo_codex.lgaid = ms_geo_lga.LgaId GROUP BY ms_geo_lga.LgaId ORDER BY lga ASC");
    }
    #   Top level table - Ward level
    public function WardSummaryMobilization($lgaid){
        return $this->db->DataTable("SELECT ms_geo_ward.wardid, ms_geo_ward.ward, SUM(hhm_mobilization.eolin_have_old_net) AS total_household, SUM(hhm_mobilization.eolin_total_old_net) AS total_net FROM hhm_mobilization INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp' INNER JOIN ms_geo_ward ON sys_geo_codex.wardid = ms_geo_ward.wardid WHERE sys_geo_codex.lgaid = $lgaid GROUP BY ms_geo_ward.wardid ORDER BY ms_geo_ward.ward ASC");
    }
    #   Top level table - DP level
    public function DpSummaryMobilization($wardid){
        return $this->db->DataTable("SELECT ms_geo_dp.dpid, ms_geo_dp.dp, SUM(hhm_mobilization.eolin_have_old_net) AS total_household, SUM(hhm_mobilization.eolin_total_old_net) AS total_net FROM hhm_mobilization INNER JOIN ms_geo_dp ON hhm_mobilization.dp_id = ms_geo_dp.dpid WHERE ms_geo_dp.wardid = $wardid GROUP BY ms_geo_dp.dpid ORDER BY dp ASC");
    }
    #
    #
    #
    #   Expected (distribution section)
    public function TopSummaryDistribution(){
        return $this->db->DataTable("SELECT Sum( hhm_distribution.eolin_bring_old_net ) AS total_household, Sum( hhm_distribution.eolin_total_old_net ) AS total_net FROM	hhm_distribution");
    }
    #   Top level table - LGA view  
    public function LgaSummaryDistribution(){
        return $this->db->DataTable("SELECT ms_geo_lga.LgaId AS lgaid, ms_geo_lga.Fullname AS lga, SUM(hhm_distribution.eolin_bring_old_net) AS total_household, SUM(hhm_distribution.eolin_total_old_net) AS total_net FROM hhm_distribution INNER JOIN sys_geo_codex ON hhm_distribution.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp' INNER JOIN ms_geo_lga ON sys_geo_codex.lgaid = ms_geo_lga.LgaId GROUP BY ms_geo_lga.LgaId ORDER BY lga ASC");
    }
    #   Top level table - Ward level    
    public function WardSummaryDistribution($lgaid){
        return $this->db->DataTable("SELECT ms_geo_ward.wardid, ms_geo_ward.ward, SUM(hhm_distribution.eolin_bring_old_net) AS total_household, SUM(hhm_distribution.eolin_total_old_net) AS total_net FROM hhm_distribution INNER JOIN sys_geo_codex ON hhm_distribution.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp' INNER JOIN ms_geo_ward ON sys_geo_codex.wardid = ms_geo_ward.wardid WHERE sys_geo_codex.lgaid = $lgaid GROUP BY ms_geo_ward.wardid ORDER BY ms_geo_ward.ward ASC");
    }
    #   Top level table - DP level
    public function DpSummaryDistribution($wardid){
        return $this->db->DataTable("SELECT ms_geo_dp.dpid, ms_geo_dp.dp, SUM(hhm_distribution.eolin_bring_old_net) AS total_household, SUM(hhm_distribution.eolin_total_old_net) AS total_net FROM hhm_distribution INNER JOIN ms_geo_dp ON hhm_distribution.dp_id = ms_geo_dp.dpid WHERE ms_geo_dp.wardid = $wardid GROUP BY ms_geo_dp.dpid ORDER BY dp ASC");
    }
}