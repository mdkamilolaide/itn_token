<?php

    namespace Users;

    use DbHelper;

    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');
    #
    #   Class
    class BulkUser
    {
        private $db;
        private $usergroup;
        private $password;
        private $geo_level;
        private $geo_level_id;
        private $role_id;
        #
        private $lastError;

        public function __construct($usergroup, $password, $geo_level, $geo_level_id, $role_id)
        {
            $username = "";
            # 
            $this->db = GetMysqlDatabase();
            $this->usergroup = $usergroup;
            $this->password = $password;
            $this->geo_level = $geo_level;
            $this->geo_level_id = $geo_level_id;
            $this->role_id = $role_id;
        }
        public function CreateBulkUser($total)
        {
            $pwd = password_hash($this->password, PASSWORD_BCRYPT);
            $hash = md5($this->password);
            $date = getNowDbDate();
            $username_pad = GenerateCodeAlphabet(2);
            $user_group = $this->usergroup;
            #
            if($total > 0)
            {
                # Start transactions
                $this->db->beginTransaction();
                $counter = 0;
                for($a=0;$a<$total;$a++)
                {
                    $guid = generateUUID();
                    $username = $user_group.$username_pad.str_pad($a+1, 3, '0', STR_PAD_LEFT);
                    #   Create Login
                    $query = "";
                    if(!$this->role_id){
                        $query = "INSERT INTO usr_login (`username`, `pwd`, `hash`, `guid`,`geo_level`,`geo_level_id`, `user_group`, `active`, `created`, `updated`) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)";   
                        $this->db->executeTransaction($query,array($username,$pwd,$hash,$guid,$this->geo_level,$this->geo_level_id,$user_group,1,$date,$date));
                    }   
                    else{
                        $query = "INSERT INTO usr_login (`username`, `pwd`, `hash`, `guid`,`geo_level`,`geo_level_id`,`roleid`, `user_group`, `active`, `created`, `updated`) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)";
                        $this->db->executeTransaction($query,array($username,$pwd,$hash,$guid,$this->geo_level,$this->geo_level_id,$this->role_id,$user_group,1,$date,$date));
                    }
                    #   get ID
                    $userid = $this->db->executeTransactionLastId();
                    #  Create Identity
                    $this->db->executeTransaction("INSERT INTO usr_identity (`userid`) VALUES (?)",array($userid));
                    # Create Finance
                    $this->db->executeTransaction("INSERT INTO usr_finance (`userid`) VALUES (?)",array($userid));
                    # Create Security
                    $this->db->executeTransaction("INSERT INTO usr_security (`userid`) VALUES (?)",array($userid));
                    #   Update Login ID
                    $pre_pad = GenerateCodeAlphabet(2);     //  PRE padding for user login id
                    $gen = $pre_pad.str_pad($userid, 5, '0', STR_PAD_LEFT);
                    $q1 = "UPDATE usr_login SET loginid = '$gen' WHERE userid = $userid";
                    $this->db->executeTransaction($q1,array());
                    $counter++;
                }
                #   Complete 
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
                return $counter;
            }
            else
            {
                $this->lastError = "Invalid total to generate";
                return 0;
            }
        }
    }
?>