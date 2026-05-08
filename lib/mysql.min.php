<?php 
    /*
     *  Database driver and helper library, this library is owned by Cleavey Active Technology
     *  All right reserved
     *  Usage of this code most be with appriopriate aproval from the owner
     *  2017 Copyright
     *  Production ready [ | ipolongo_smc_v2]
     */
class MysqlPdo{public $Conn;public $ErrorMessage;public $ConnMsg;public function __construct($connstr,$user,$pass){$this->Connect($connstr,$user,$pass);}private function Connect($str,$usr,$pwd){try{$options=array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC);$this->Conn=new PDO($str,$usr,$pwd,$options);}catch(PDOException $e){$this->ErrorMessage="Connection Error: ".$e;$this->ConnMsg="error";}}public function DataTable($query,$option=1){
            $data = array();
            $query = trim((string)$query);
            if ($query === '') {
                return $data;
            }
            try{
                if($this->Conn){
                    $stm = $this->Conn->query($query);
                    if (!$stm) return $data;
                    $rowCount = $stm->rowCount();
                    $columnCount = $stm->columnCount();
                    $a = 0;
                    if($option == 2){
                        while($r = $stm->fetch(PDO::FETCH_NUM)){
                            foreach($r as $key => $val){ $data[$a][$key] = $val; }
                            $a++;
                        }
                    } else {
                        while($r = $stm->fetch(PDO::FETCH_ASSOC)){
                            foreach($r as $key => $val){ $data[$a][$key] = $val; }
                            $a++;
                        }
                    }
                }
            } catch(PDOException $e){ $this->ErrorMessage = "Query Error: " . $e; }
            return $data;
        }public function ExcelDataTable($query){
            $data = array();
            $query = trim((string)$query);
            if ($query === '') {
                return $data;
            }
            try{
                if($this->Conn){
                    $stm = $this->Conn->query($query);
                    if (!$stm) return $data;
                    $rowCount = $stm->rowCount();
                    $columnCount = $stm->columnCount();
                    $a = 0;
                    while($r = $stm->fetch(PDO::FETCH_ASSOC)){
                        $line = array();
                        if ($a == 0){
                            foreach($r as $key => $val){ $line[] = ["text" => strtoupper($key)]; }
                            $data[$a] = $line;
                            $a++;
                            $line = array();
                        }
                        foreach($r as $key => $val){ $line[] = ["text" => $val]; }
                        $data[$a] = $line;
                        $a++;
                    }
                }
            } catch(PDOException $e){ $this->ErrorMessage = "Export Query Error: " . $e; }
            return $data;
        }public function SelectResult($query){
            $query = trim((string)$query);
            if ($query === '') {
                return null;
            }
            $result = null;
            try {
                if ($this->Conn) {
                    $stm = $this->Conn->query($query);
                    if ($stm) {
                        $row = $stm->fetch(PDO::FETCH_NUM);
                        if (is_array($row) && array_key_exists(0, $row)) {
                            $result = $row[0];
                        }
                    }
                }
            } catch (PDOException $e) { $this->ErrorMessage = "Query Error: " . $e; }
            return $result;
        }public function Execute($query,$data){try{if($this->Conn){return $this->Conn->prepare($query)->execute($data);}}catch(PDOException $e){$this->ErrorMessage="Query Error: ".$e;}return null;}public function Insert($query,$data){try{if($this->Conn){$this->Conn->prepare($query)->execute($data);return $this->Conn->lastInsertId();}}catch(PDOException $e){$this->ErrorMessage="Query Error: ".$e;}return null;}public function beginTransaction(){if ($this->Conn && !$this->Conn->inTransaction()){ $this->Conn->beginTransaction(); }} public function executeTransaction($query,$data){try{if($this->Conn){return $this->Conn->prepare($query)->execute($data);}}catch(PDOException $e){$this->ErrorMessage="Query Error: ".$e;}return null;}public function executeTransactionLastId(){
            $stm = $this->Conn ? $this->Conn->query("SELECT LAST_INSERT_ID()") : false;
            if (!$stm) return null;
            $row = $stm->fetch(PDO::FETCH_NUM);
            return is_array($row) && array_key_exists(0, $row) ? $row[0] : null;
        }
        public function executeTransactionScalar($query){
            $query = trim((string)$query);
            if ($query === '') return null;
            $stm = $this->Conn ? $this->Conn->query($query) : false;
            if (!$stm) return null;
            $row = $stm->fetch(PDO::FETCH_NUM);
            return is_array($row) && array_key_exists(0, $row) ? $row[0] : null;
        }public function executeTable($query){
            $data = array();
            $query = trim((string)$query);
            if ($query === '') {
                return $data;
            }
            try{
                $stm = $this->Conn ? $this->Conn->query($query) : false;
                if ($stm) {
                    $a = 0;
                    while ($r = $stm->fetch(PDO::FETCH_ASSOC)){
                        foreach ($r as $key => $val){ $data[$a][$key] = $val; }
                        $a++;
                    }
                }
            } catch(PDOException $e){ $this->ErrorMessage = "Query Error: " . $e; }
            return $data;
        }public function commitTransaction(){return $this->Conn->commit();}}
function GetMysqlDatabase(){
        // Prefer ITN_DB_* constants (defined in lib/config.php for the local
        // deployment). Constants are reliable across mod_php / php-fpm /
        // CLI; putenv() is not on every Windows configuration.
        // Fall back to env vars (used by phpunit.xml), then to defaults.
        $dsn  = (defined('ITN_DB_DSN')  ? ITN_DB_DSN  : null) ?: (getenv('IPOLONGO_DB_DSN') ?: getenv('DB_DSN'));
        if (!$dsn) {
            $db = getenv('DB_DATABASE') ?: 'ipolongo_v5';
            $host = getenv('DB_HOST') ?: 'localhost';
            $charset = getenv('DB_CHARSET') ?: 'utf8';
            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        }
        $user = (defined('ITN_DB_USER') ? ITN_DB_USER : null) ?: (getenv('IPOLONGO_DB_USER') ?: getenv('DB_USER') ?: 'root');
        $pass = defined('ITN_DB_PASS') ? ITN_DB_PASS : (getenv('IPOLONGO_DB_PASS') ?: getenv('DB_PASS') ?: '');

        // Reuse a single MysqlPdo per unique DSN+user in the current process. This
        // avoids exhausting MySQL's connection limit when application code and
        // tests call GetMysqlDatabase() many times.
        static $instances = [];
        $key = md5($dsn . "|" . $user);
        if (!isset($instances[$key]) || !($instances[$key] instanceof MysqlPdo)) {
            try {
                $instances[$key] = new MysqlPdo($dsn, $user, $pass);
            } catch (\Throwable $e) {
                // Preserve original behaviour but improve the message for diagnostics
                $tmp = new MysqlPdo($dsn, $user, $pass);
                // If constructor threw, surface a concise error via a temporary object
                $tmp->ErrorMessage = "Connection Error: " . $e->getMessage();
                return $tmp;
            }
        }

        return $instances[$key];
    }
class MysqlCentry{public $db;public $lastError;public $lastQuery;public static $instance;public static function getInstance(){if(self::$instance===null){self::$instance=new self();}return self::$instance;}public function __construct(){$this->db=GetMysqlDatabase();}public function Insert($table,$data){$fields="";$fieldval="";$vals=array();$sensor=1;foreach($data as $key=>$val){$vals[]=$val;if($sensor==1){$fields="INSERT INTO $table (`".$key."`, ";$fieldval="(?, ";}elseif($sensor==count($data)){$fields.="`".$key."`) VALUES ";$fieldval.="?)";}else{$fields.="`".$key."`, ";$fieldval.="?, ";}$sensor++;}$query=$fields.$fieldval;$this->lastQuery=$query;try{return $this->db->Insert($query,$vals);}catch(Exception $e){$this->lastError=$e->getMessage();}}public function Update($table,$data,$identifier,$key){$vals=array();$sensor=1;$query="";foreach($data as $k=>$v){$vals[]=$v;if(count($data)>1){if($sensor==1){$query="UPDATE $table SET `".$k."`=? ";}elseif($sensor==count($data)){$query.=", `".$k."`=? WHERE $identifier=?";$vals[]=$key;}else{$query.=", `".$k."`=? ";}}else{$query="UPDATE $table SET `".$k."`=? WHERE $identifier=?";$vals[]=$key;}$sensor++;}return $this->db->Execute($query,$vals);}public function Execute($query){$this->lastQuery=$query;try{return $this->db->Execute($query,array());}catch(Exception $e){$this->lastError=$e->getMessage();}}public function Delete($table,$identifier,$key){$query="DELETE FROM $table WHERE $identifier=?";$vals=array($key);return $this->db->Execute($query,$vals);}public function Table($query,$option=1){return $this->db->DataTable($query,$option);}public function Single($query){return $this->db->SelectResult($query);}}
class DbHelper{public static function EchoJsonTable($query){$c=new MysqlCentry();$data=$c->Table($query);echo json_encode($data);}public static function GetJsonTable($query){$c=new MysqlCentry();$data=$c->Table($query);return json_encode($data);}public static function Table($query){$c=new MysqlCentry();return $c->Table($query);}public static function IntTable($query){$c=new MysqlCentry();return $c->Table($query,2);}public static function Count($query){if(count(DbHelper::Table($query))){return true;}return false;}public static function EchoScalar($query){$c=new MysqlCentry();echo $c->Single($query);}public static function GetScalar($query){$c=new MysqlCentry();return $c->Single($query);}public static function Insert($table,$data){$c=new MysqlCentry();return $c->Insert($table,$data);}public static function Update($table,$data,$identifier,$key){$c=new MysqlCentry();return $c->Update($table,$data,$identifier,$key);}public static function Delete($table,$id,$key){$c=new MysqlCentry();return $c->Delete($table,$id,$key);}public static function Execute($query,$data){$c=new MysqlCentry();return $c->Execute($query,$data);}public static function LogUserActivity($userid,$module,$ip,$desc,$result){$data=array('uid'=>$userid,'module'=>$module,'ip'=>$ip,'description'=>$desc,'result'=>$result);return DbHelper::Insert('sys_user_activity',$data);}}class DataLib{
            public static function Column($data,$number=0){
                $mode = [];
                if (!is_array($data)) return $mode;
                foreach ($data as $d) {
                    if (!is_array($d)) { $mode[] = null; continue; }
                    // numeric index requested (position) — accept int or numeric-string
                    if (is_int($number) || (is_string($number) && ctype_digit($number))) {
                        $vals = array_values($d);
                        $idx = (int)$number;
                        $mode[] = array_key_exists($idx, $vals) ? $vals[$idx] : null;
                    } else {
                        // associative key requested
                        $mode[] = array_key_exists($number, $d) ? $d[$number] : null;
                    }
                }
                return $mode;
            }
            public static function ColumnToInt($data,$number=0){
                $mode = [];
                if (!is_array($data)) return $mode;
                foreach ($data as $d) {
                    if (!is_array($d)) { $mode[] = 0; continue; }
                    if (is_int($number) || (is_string($number) && ctype_digit($number))) {
                        $vals = array_values($d);
                        $idx = (int)$number;
                        $val = array_key_exists($idx, $vals) ? $vals[$idx] : 0;
                    } else {
                        $val = array_key_exists($number, $d) ? $d[$number] : 0;
                    }
                    $mode[] = intval($val);
                }
                return $mode;
            }
            public static function ColumnToFloat($data,$number=0){
                $mode = [];
                if (!is_array($data)) return $mode;
                foreach ($data as $d) {
                    if (!is_array($d)) { $mode[] = 0.0; continue; }
                    if (is_int($number) || (is_string($number) && ctype_digit($number))) {
                        $vals = array_values($d);
                        $idx = (int)$number;
                        $val = array_key_exists($idx, $vals) ? $vals[$idx] : 0.0;
                    } else {
                        $val = array_key_exists($number, $d) ? $d[$number] : 0.0;
                    }
                    $mode[] = floatval($val);
                }
                return $mode;
            }
            public static function Row($data,$number=0){
                if (!is_array($data)) return null;
                return array_key_exists($number, $data) ? $data[$number] : null;
            } } 
?>