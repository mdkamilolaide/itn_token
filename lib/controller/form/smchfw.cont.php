<?php
namespace Form;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
#
class SmcHfw{
    #
    private $db;
    private $pdo;
    public $ErrorMessage;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
        $this->pdo = $this->db->Conn;
    }
    #
    public function BulkSave($bulk_ob){
        #data structure
        /*
        *   [uid,lgaid,wardid,dpid,periodid,userid,day,latitude,longitude,aa,ab,ba,bb,ca,cb,da,db,ea,eb,fa,fb,ga,gb,ha,hb,ia,ib,ja,jb,ka,kb,la,lb,m1a,m1b,m2a,m2b,m3a,m3b,m4a,m4b,n1a,n1b,n2a,n2b,n3a,n3b,n4a,n4b,n5a,n5b,n6a,n6b,o1a,o1b,o2a,o2b,o3a,o3b,pa,pb,q1a,q1b,q2a,q2b,ra,rb,s,t,v,domain,app_version,capture_date]
        */
        $id_list = array();     #   List of inserted IDs to be returned
        $date = getNowDbDate();
        if(count($bulk_ob)<1){
            $this->ErrorMessage = "Invalid bulk data";
            return $id_list;
        }
        $pdo = $this->pdo;
        #
        # bulkinsert into the mo_smc_supervisor_cdd table
        #   start transaction
        $pdo->beginTransaction();
        foreach($bulk_ob as $a){
            $query = "INSERT INTO `mo_smc_supervisor_hfw` (`uid`,`lgaid`,`wardid`,`dpid`,`periodid`,`userid`,`day`,`latitude`,`longitude`,
            `aa`,`ab`,`ba`,`bb`,`ca`,`cb`,`da`,`db`,`ea`,`eb`,`fa`,`fb`,`ga`,`gb`,
            `ha`,`hb`,`ia`,`ib`,`ja`,`jb`,`ka`,`kb`,`la`,`lb`,
            `m1a`, `m1b`, `m2a`, `m2b`, `m3a`, `m3b`, `m4a`, `m4b`,
            `n1a`, `n1b`, `n2a`, `n2b`, `n3a`, `n3b`, `n4a`, `n4b`,
            `n5a`, `n5b`, `n6a`, `n6b`, `o1a`, `o1b`,  `o2a`, `o2b`,
            `o3a`, `o3b`, `pa`, `pb`, `q1a`, `q1b`, `q2a`,`q2b`, `ra`,
            `rb`, `s`, `t`, v, domain, app_version, capture_date) VALUES 
            (?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?,?,?,?,?,?,?,?,
            ?,?,?)";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array($a['uid'],$a['lgaid'],$a['wardid'],$a['dpid'],$a['periodid'],$a['userid'],$a['day'],$a['latitude'],$a['longitude'],
            $a['aa'],$a['ab'],$a['ba'],$a['bb'],$a['ca'],$a['cb'],$a['da'],$a['db'],$a['ea'],$a['eb'],$a['fa'],$a['fb'],$a['ga'],$a['gb'],
            $a['ha'],$a['hb'],$a['ia'],$a['ib'],$a['ja'],$a['jb'],$a['ka'],$a['kb'],$a['la'],$a['lb'],
            $a['m1a'], $a['m1b'], $a['m2a'], $a['m2b'], $a['m3a'], $a['m3b'], $a['m4a'], $a['m4b'],
            $a['n1a'], $a['n1b'], $a['n2a'], $a['n2b'], $a['n3a'], $a['n3b'], $a['n4a'], $a['n4b'],
            $a['n5a'], $a['n5b'], $a['n6a'], $a['n6b'], $a['o1a'], $a['o1b'],  $a['o2a'], $a['o2b'],
            $a['o3a'], $a['o3b'], $a['pa'], $a['pb'], $a['q1a'], $a['q1b'], $a['q2a'],$a['q2b'], $a['ra'],
            $a['rb'], $a['s'], $a['t'], $a['v'], $a['domain'],$a['app_version'],$a['capture_date']));
            $id = $pdo->lastInsertId();
            if($id){
                $id_list[] = array('uid'=>$a['uid'],'id'=>$id);
            }else{
                # its duplicates and unable to save
                $id_list[] = array('uid'=>$a['uid'],'id'=>0);
                #   log error if any
                $error_message = $this->db->ErrorMessage;
                if(strlen($error_message)>0){
                    #   Write to file
                    $error_file_name = "error-report.txt";
                    $error_to_write = "\r\nForm smc hfw bulk error, message: $error_message\r\nData:".json_encode($bulk_ob)."\r\n$date\r\n";
                    WriteToFile($error_file_name, $error_to_write);
                }
            }
        }
        #   Complete transaction
        $pdo->commit();
        #   return
        return $id_list;
    }
}

?>