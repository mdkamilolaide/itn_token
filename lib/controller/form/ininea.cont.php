<?php

namespace Form;

use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
#
class INineA
{
    #
    private $db;
    public $ErrorMessage;
    #
    public function __construct()
    {
        $this->db = GetMysqlDatabase();
    }
    public function BulkSave($bulk_ob)
    {
        #data structure
        /*
        *   [uid,lgaid,wardid,comid,userid,latitude,longitude,aa,ab,ac,ad,ae,af,ag,ah,ai,domain,app_version,capture_date]
        */
        $id_list = array();     #   List of inserted IDs to be returned
        $date = getNowDbDate();
        if (count($bulk_ob) < 1) {
            $this->ErrorMessage = "Invalid bulk data";
            return $id_list;
        }
        #else
        #   init transaction
        $this->db->beginTransaction();
        $counter = 0;
        foreach ($bulk_ob as $a) {
            $query = "INSERT INTO `mo_form_i9a` (`uid`,`wardid`,`lgaid`,`comid`,`userid`,`latitude`,`longitude`,`aa`,`ab`,`ac`,`ad`,`ae`,`af`,`ag`,`ah`,`ai`,`domain`,`app_version`,`capture_date`,`created`) VALUES 
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $this->db->executeTransaction($query, array($a['uid'], $a['wardid'], $a['lgaid'], $a['comid'], $a['userid'], $a['latitude'], $a['longitude'], $a['aa'], $a['ab'], $a['ac'], $a['ad'], $a['ae'], $a['af'], $a['ag'], $a['ah'], $a['ai'], $a['domain'], $a['app_version'], $a['capture_date'], $date));
            $id = $this->db->executeTransactionLastId();
            if ($id) {
                $id_list[] = array('uid' => $a['uid'], 'id' => (string)$id);
                $counter++;
            } else {
                # its duplicates and unable to save
                $id_list[] = array('uid' => $a['uid'], 'id' => '0');
            }
        }
        #   Complete transaction
        $this->db->commitTransaction();
        #   log error if any
        $error_message = $this->db->ErrorMessage;
        if (strlen($error_message) > 0) {
            #   Write to file
            $error_file_name = "error-report.txt";
            $error_to_write = "\r\nForm i9a error, message: $error_message\r\nData:" . json_encode($bulk_ob) . "\r\n$date\r\n";
            WriteToFile($error_file_name, $error_to_write);
        }
        #   Return array list
        return $id_list;
    }
}
