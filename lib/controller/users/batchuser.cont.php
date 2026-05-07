<?php
    namespace Users;
    use DbHelper;
    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');
    #
    class BatchUser{
        private $db;
        private $usergroup;
        private $password = 'DEmo2021';
        private $lastError;
        #
        public function __construct()
        {
            $this->db = GetMysqlDatabase();
        }
        #
        #   Batch user upload
        public function BulkUpload($location, $group, $roleid){
            $startTime = time();
            ##  Get list of users (from db or)
            $file = fopen($location, 'r');
            ##
            #
            echo "<h3>Starting Batch user data miration</h3>";
            ##  Open file
            $counter = 0;
            # Start transactions
            $this->db->beginTransaction();
            while (($line = fgetcsv($file)) !== FALSE) {
                # data Structure
                
                if($counter > 0){
                    #
                    $geo_level = $line[0];
                    $geo_level_id = $line[1]; 
                    $role = $line[2]; 
                    $name = $line[3]; 
                    $nn = $this->SplitName($name);
                    $first_name = $nn['first'];
                    $middle_name = $nn['middle'];
                    $last_name = $nn['last'];
                    $phone = $line[4];
                    $bank = $line[5]; 
                    $bank_code = $line[6]; 
                    $account_number = $line[7];
                    //echo "$counter, Geo-Level: $geo_level, Level ID: $geo_level_id, Role: $role, Name: $name, Phone: $phone, Bank: $bank, Bank Code: $bank_code, AC No: $account_number </br>";
                    #
                    #
                    $pwd = password_hash($this->password, PASSWORD_BCRYPT);
                    $hash = md5($this->password);
                    $date = getNowDbDate();
                    $username_pad = GenerateCodeAlphabet(2);
                    $user_group = $group;
                    $guid = generateUUID();
                    $username = $user_group.$username_pad.str_pad($counter+1, 3, '0', STR_PAD_LEFT);
                    #   Create Login
                    $query = "INSERT INTO usr_login (`username`, `pwd`, `hash`, `guid`,`roleid`,`geo_level`,`geo_level_id`, `user_group`, `active`, `created`, `updated`) 
                                VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $this->db->executeTransaction($query,array($username,$pwd,$hash,$guid,$roleid,$geo_level,$geo_level_id,$user_group,1,$date,$date));
                    #   get ID
                    $userid = $this->db->executeTransactionLastId();
                    #  Create Identity
                    $this->db->executeTransaction("INSERT INTO usr_identity (`userid`,`first`,`middle`,`last`,`phone`) VALUES (?,?,?,?,?)",array($userid,$first_name,$middle_name,$last_name,$phone));
                    # Create Finance
                    $this->db->executeTransaction("INSERT INTO usr_finance (`userid`,`bank_name`,`bank_code`,`account_name`,`account_no`) VALUES (?,?,?,?,?)",array($userid,$bank,$bank_code,$name,$account_number));
                    # Create Security
                    $this->db->executeTransaction("INSERT INTO usr_security (`userid`) VALUES (?)",array($userid));
                    #   Update Login ID
                    $pre_pad = GenerateCodeAlphabet(2);     //  PRE padding for user login id
                    $loginid = $pre_pad.str_pad($userid, 4, '0', STR_PAD_LEFT);
                    $q1 = "UPDATE usr_login SET loginid = '$loginid' WHERE userid = $userid";
                    $this->db->executeTransaction($q1,array());
                    #
                    #
                    echo $counter.". $loginid - $name created successfully<br>";
                }
                
                #
                $counter++;
                //if($counter == 6){ break; }
            }
            #   Complete 
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
            fclose($file);
            $endTime = time();
            $timediff = $endTime - $startTime;
            echo "<br>Batch user creation completed successfully. <b>Duration: $timediff Seconds | Total: $counter</b>";
        }
        #
        #
        private function SplitName($name){
            $delimeter = ' ';
            $names = explode($delimeter, trim($name));
            #
            $result = array('first'=>'','middle'=>'','last'=>'');
            if(count($names) == 1){
                # split into first name only
                $result['first'] = $names[0];
            }
            elseif(count($names) == 2){
                # split into first and last name 
                $result['first'] = $names[0];
                $result['last'] = $names[1];
            }
            elseif(count($names) > 2){
                # Split into first, middle, last
                $result['first'] = $names[0];
                $result['middle'] = $names[1];
                $result['last'] = $names[2];
            }
            #
            return $result;
        }
    }
?>