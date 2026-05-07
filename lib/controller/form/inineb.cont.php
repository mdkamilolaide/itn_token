<?php
namespace Form;
use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
#
class INineB{
    #
    private $db;
    public $ErrorMessage;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
    }
    public function BulkSave($bulk_ob){
        #data structure
        /*
        *   [uid,lgaid,wardid,dpid,comid,userid,latitude,longitude,sp,aa,ab,ba,bb,ca,cb,da,db,ea,eb,fa,fb,ga,gb,ha,hb,ia,ib,ja,jb,ka,kb,la,lb,ma.mb.na,nb,oa,ob,domain,app_version,capture_date]
        */
        $id_list = array();     #   List of inserted IDs to be returned
        $date = getNowDbDate();
        if(count($bulk_ob)<1){
            $this->ErrorMessage = "Invalid bulk data";
            return $id_list;
        }
        #else
        #   init transaction
        $this->db->beginTransaction();
        $counter = 0;
        foreach($bulk_ob as $a){
            $query = "INSERT INTO `mo_form_i9b` (`uid`,`wardid`,`lgaid`,`dpid`,`comid`,`userid`,`latitude`,`longitude`,`supervisor`,
            `aa`,`ab`,`ba`,`bb`,`ca`,`cb`,`da`,`db`,`ea`,`eb`,`fa`,`fb`,`ga`,`gb`,
            `ha`,`hb`,`ia`,`ib`,`ja`,`jb`,`ka`,`kb`,`la`,`lb`,`ma`,`mb`,`na`,`nb`,`oa`,`ob`,
            `domain`,`app_version`,`capture_date`,`created`) VALUES 
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $this->db->executeTransaction($query,array($a['uid'],$a['wardid'],$a['lgaid'],$a['dpid'],$a['comid'],$a['userid'],$a['latitude'],$a['longitude'],
            $a['sp'],$a['aa'],$a['ab'],$a['ba'],$a['bb'],$a['ca'],$a['cb'],$a['da'],$a['db'],$a['ea'],$a['eb'],$a['fa'],$a['fb'],$a['ga'],$a['gb'],
            $a['ha'],$a['hb'],$a['ia'],$a['ib'],$a['ja'],$a['jb'],$a['ka'],$a['kb'],$a['la'],$a['lb'],$a['ma'],$a['mb'],$a['na'],$a['nb'],$a['oa'],$a['ob'],
            $a['domain'],$a['app_version'],$a['capture_date'],$date));
            $id = $this->db->executeTransactionLastId();
            if($id){
                $id_list[] = array('uid'=>$a['uid'],'id'=>(string)$id);
                $counter++;
            }else{
                # its duplicates and unable to save
                $id_list[] = array('uid'=>$a['uid'],'id'=>"0");
            }
        }
        #   Complete transaction
        $this->db->commitTransaction();
        #   log error if any
        $error_message = $this->db->ErrorMessage;
        if(strlen($error_message)>0){
            #   Write to file
            $error_file_name = "error-report.txt";
            $error_to_write = "\r\nForm i9b error, message: $error_message\r\nData:".json_encode($bulk_ob)."\r\n$date\r\n";
            WriteToFile($error_file_name, $error_to_write);
        }
        #   Return array list
        return $id_list;
    }
}
?>