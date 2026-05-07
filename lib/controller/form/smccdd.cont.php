<?php
namespace Form;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');
#
class SmcCdd{
    #
    private $db;
    private $pdo;
    public $ErrorMessage;
    #
    public function __construct(){
        $this->db = GetMysqlDatabase();
        $this->pdo = $this->db->Conn;
    }
    public function BulkSave($bulk_ob){
        #data structure
        /*
        *   [uid,lgaid,wardid,dpid,periodid,userid,day,latitude,longitude,aa,ab,ba,bb,ca,cb,da,db,ea,eb,fa,fb,ga,gb,ha,hb,ia,ib,ja,jb,ka,kb,la,lb,ma,mb,na,nb,oa,ob,pa,pb,q,r,s,domain,app_version,capture_date]
        */
        $id_list = array();     #   List of inserted IDs to be returned
        $date = getNowDbDate();
        if(count($bulk_ob)<1){
            $this->ErrorMessage = "Invalid bulk data";
            return $id_list;
        }
        $pdo = $this->pdo;
        //
        # bulkinsert into the mo_smc_supervisor_cdd table
        #   start transaction
        $pdo->beginTransaction();
        foreach($bulk_ob as $a){
            $query = "INSERT INTO `mo_smc_supervisor_cdd` (
                `uid`,`lgaid`,`wardid`,`dpid`,`periodid`,`userid`,`day`,`latitude`,`longitude`,
                `aa`,`ab`,`ba`,`bb`,`ca`,`cb`,`da`,`db`,`ea`,`eb`,`fa`,`fb`,`ga`,`gb`,
                `ha`,`hb`,`ia`,`ib`,`ja`,`jb`,`ka`,`kb`,`la`,`lb`,`ma`,`mb`,
                `na`,`nb`,`oa`,`ob`, `pa`, `pb`, `q`, `r`, `s`, `domain`, `app_version`, `capture_date`
            ) VALUES (
                :uid,:lgaid,:wardid,:dpid,:periodid,:userid,:day,:latitude,:longitude,
                :aa,:ab,:ba,:bb,:ca,:cb,:da,:db,:ea,:eb,:fa,:fb,:ga,:gb,
                :ha,:hb,:ia,:ib,:ja,:jb,:ka,:kb,:la,:lb,:ma,:mb,
                :na,:nb,:oa,:ob,:pa,:pb,:q,:r,:s,:domain,:app_version,:capture_date
            )";
            $stmt = $pdo->prepare($query);
            $stmt->execute(array(
                ':uid' => $a['uid'],
                ':lgaid' => $a['lgaid'],
                ':wardid' => $a['wardid'],
                ':dpid' => $a['dpid'],
                ':periodid' => $a['periodid'],
                ':userid' => $a['userid'],
                ':day' => $a['day'],
                ':latitude' => $a['latitude'],
                ':longitude' => $a['longitude'],
                ':aa' => $a['aa'],
                ':ab' => $a['ab'],
                ':ba' => $a['ba'],
                ':bb' => $a['bb'],
                ':ca' => $a['ca'],
                ':cb' => $a['cb'],
                ':da' => $a['da'],
                ':db' => $a['db'],
                ':ea' => $a['ea'],
                ':eb' => $a['eb'],
                ':fa' => $a['fa'],
                ':fb' => $a['fb'],
                ':ga' => $a['ga'],
                ':gb' => $a['gb'],
                ':ha' => $a['ha'],
                ':hb' => $a['hb'],
                ':ia' => $a['ia'],
                ':ib' => $a['ib'],
                ':ja' => $a['ja'],
                ':jb' => $a['jb'],
                ':ka' => $a['ka'],
                ':kb' => $a['kb'],
                ':la' => $a['la'],
                ':lb' => $a['lb'],
                ':ma' => $a['ma'],
                ':mb' => $a['mb'],
                ':na' => $a['na'],
                ':nb' => $a['nb'],
                ':oa' => $a['oa'],
                ':ob' => $a['ob'],
                ':pa' => $a['pa'],
                ':pb' => $a['pb'],
                ':q' => $a['q'],
                ':r' => $a['r'],
                ':s' => $a['s'],
                ':domain' => $a['domain'],
                ':app_version' => $a['app_version'],
                ':capture_date' => $a['capture_date']
            ));
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
                    $error_to_write = "\r\nForm smc cdd bulk error, message: $error_message\r\nData:".json_encode($bulk_ob)."\r\n$date\r\n";
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