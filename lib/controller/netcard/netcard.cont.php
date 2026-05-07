<?php

    namespace Netcard;

    use DbHelper;

    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');

    class Netcard
    {
        private $length;
        private $db;
        private $max = 5000;
        #
        #
        #
        public function __construct($length)
        {
            if($length < 1)
            {
                $this->length = 0;
            }
            elseif($length > $this->max)
            {
                $this->length = $this->max;
            }
            else
            {
                $this->length = $length;
            }
            #
            $this->db = GetMysqlDatabase();
        }
        public function Generate()
        {
            //location = stock, active = 1
            #   Prepare query
            $query = "INSERT INTO nc_netcard (`uuid`, `active`,`location`,`location_value`,`geo_level`,`geo_level_id`,`stateid`,`status`) VALUES (?,?,?,?, ?, ?, ?, ?)";
            $geo_level_id = DbHelper::GetScalar("SELECT stateid FROM sys_default_settings WHERE id = 1");
            #
            $counter = 0;
            $this->db->beginTransaction();
            for($a=0;$a<$this->length;$a++)
            {
                $qdata = array(generateUUID(),1,'state',100,'state',$geo_level_id,$geo_level_id,'state');
                $this->db->executeTransaction($query,$qdata);
                $counter++;
            }
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
            #   return total data generated
            return $counter;
        }
        public function ChangeLength($new_length)
        {
            if($new_length < 1)
            {
                $this->length = 0;
            }
            elseif($new_length > $this->max)
            {
                $this->length = $this->max;
            }
            else
            {
                $this->length = $new_length;
            }
        }
    }
?>