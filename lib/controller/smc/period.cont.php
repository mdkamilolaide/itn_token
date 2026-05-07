<?php
namespace Smc;

use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Period {
    private $db;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    #
    public function Create($name, $start_date, $end_date){
        $date = getNowDbDate();
        return DbHelper::Insert("smc_period",array(
            'title'=>$name,
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'active'=>0,
            'created'=>$date,
            'updated'=>$date
        ));
    }
    public function Update($name, $start_date, $end_date,$period_id){
        $date = getNowDbDate();
        return DbHelper::Update("smc_period",array(
            'title'=>$name,
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'updated'=>$date
        ),"periodid",$period_id);
    }
    public  function Delete($period_id){
        return DbHelper::Delete("smc_period","periodid",$period_id);
    }
    public function Activate($period_id){
        $date = getNowDbDate();
        $this->db->beginTransaction();
        $this->db->executeTransaction("UPDATE `smc_period` SET `active`=0",array());
        $this->db->executeTransaction("UPDATE `smc_period` SET `active`=1, `updated` = ? WHERE periodid = ?",array($date,$period_id));
        $this->db->commitTransaction();
        return true;
    }
    public function GetList(){
        return DbHelper::Table("SELECT
        smc_period.periodid,
        smc_period.title,
        smc_period.start_date,
        smc_period.end_date,
        smc_period.active,
        smc_period.created,
        smc_period.updated
        FROM
        smc_period
        ORDER BY
        smc_period.start_date ASC");
    }
}
?>