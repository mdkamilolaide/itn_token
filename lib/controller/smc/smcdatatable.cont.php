<?php
namespace Smc;
use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
class SmcDataTable {
    private $db;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    public function GetChildSum(){
        return DbHelper::Table("select count(distinct `hh`.`hhid`) AS `household_count`,count(distinct `c`.`child_id`) AS `child_count` from (`smc_child_household` `hh` left join `smc_child` `c` on(`hh`.`hh_token` = `c`.`hh_token`))");
    }
    public function ChildRegistryTable(){
        $columns = array();
        #   Require variables
        $perpage = intval($_REQUEST['length']);
        $currentPage = $_REQUEST['draw'];
        $sortColumn = $_REQUEST['order_column'];
        $orderDir = $_REQUEST['order_dir']; // asc | desc
        $orderField = $columns[$_REQUEST['order_column']];
        $limitStart = $_REQUEST['start'];
        $date_format = $GLOBALS["conf_db_date_format"];
        $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
        #  Where condition
        $where_condition = " ";
        $seed = 0;
    }
}
?>