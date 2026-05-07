<?php
namespace Dashboard;
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Enetcard {
    private $db;
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    #
    #
    # Top level fields [households, netcards, family_size]
    public function TopSummary(){
        return $this->db->DataTable("SELECT COUNT(*) AS total, SUM(CASE WHEN location_value=100 THEN 1 ELSE 0 END) AS state, SUM(CASE WHEN location_value=80 THEN 1 ELSE 0 END) AS lga, SUM(CASE WHEN location_value=60 THEN 1 ELSE 0 END) AS ward, SUM(CASE WHEN location_value=40 THEN 1 ELSE 0 END) AS mobilizer_online, SUM(CASE WHEN location_value=35 THEN 1 ELSE 0 END) AS mobilizer_pending, SUM(CASE WHEN location_value=30 THEN 1 ELSE 0 END) AS mobilizer_wallet, SUM(CASE WHEN location_value=20 THEN 1 ELSE 0 END) AS beneficiary FROM nc_netcard");
    }
    #   Top level table - LGA view
    public function TopLgaSummary(){
        # fetch and return top level LGA table list summary
        return $this->db->DataTable("SELECT lga.LgaId, lga.Fullname AS lga, COUNT(*) AS lga_total, SUM(CASE WHEN nc.location_value=80 THEN 1 ELSE 0 END) AS lga_balance, SUM(CASE WHEN nc.location_value=60 THEN 1 ELSE 0 END) AS ward, SUM(CASE WHEN nc.location_value=40 THEN 1 ELSE 0 END) AS mob_online, SUM(CASE WHEN nc.location_value=35 THEN 1 ELSE 0 END) AS mob_pending, SUM(CASE WHEN nc.location_value=30 THEN 1 ELSE 0 END) AS wallet, SUM(CASE WHEN nc.location_value=20 THEN 1 ELSE 0 END) AS beneficiary FROM nc_netcard nc JOIN ms_geo_lga lga ON nc.lgaid=lga.LgaId GROUP BY nc.lgaid, lga.Fullname ORDER BY lga.Fullname");
    }
    #   Top level table - Ward level
    public function TopWardSummary($lgaid){
        # fetch and return top level ward table list summary
        return $this->db->DataTable("SELECT ms_geo_ward.wardid, ms_geo_ward.ward, COUNT(*) AS ward_total, SUM(CASE WHEN nc.location_value=60 THEN 1 ELSE 0 END) AS ward_balance, SUM(CASE WHEN nc.location_value=40 THEN 1 ELSE 0 END) AS mob_online, SUM(CASE WHEN nc.location_value=35 THEN 1 ELSE 0 END) AS mob_pending, SUM(CASE WHEN nc.location_value=30 THEN 1 ELSE 0 END) AS wallet, SUM(CASE WHEN nc.location_value=20 THEN 1 ELSE 0 END) AS beneficiary FROM nc_netcard nc INNER JOIN ms_geo_ward ON nc.wardid=ms_geo_ward.wardid WHERE nc.lgaid=$lgaid GROUP BY nc.wardid ORDER BY ms_geo_ward.ward");
    }
    #   Top level table - mobilizer level
    public function TopMobilizerSummary($wardid){
        # 
        return $this->db->DataTable("SELECT usr_login.userid, MIN(CONCAT_WS(' ', usr_identity.`first`, usr_identity.last, CONCAT('(',usr_login.loginid,')'))) AS mobilizer, COUNT(*) AS total, SUM(CASE WHEN nc.location_value=40 THEN 1 ELSE 0 END) AS mob_online, SUM(CASE WHEN nc.location_value=35 THEN 1 ELSE 0 END) AS mob_pending, SUM(CASE WHEN nc.location_value=30 THEN 1 ELSE 0 END) AS wallet, SUM(CASE WHEN nc.location_value=20 THEN 1 ELSE 0 END) AS beneficiary FROM nc_netcard nc INNER JOIN usr_login ON nc.mobilizer_userid=usr_login.userid INNER JOIN usr_identity ON usr_login.userid=usr_identity.userid WHERE nc.wardid=$wardid GROUP BY nc.mobilizer_userid ORDER BY mobilizer");
    }
    

}