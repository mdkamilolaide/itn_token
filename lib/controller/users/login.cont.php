<?php

    namespace Users;

    use DbHelper;

    #
    include_once("lib/mysql.min.php");
    #
    class Login
    {
        #
        private $loginType = "id"; # By default we set ID options [id | badge]
        private $loginId;
        private $loginGuid;
        private $loginPwd;
        private $loginData;
        #
        #
        public $LastError = "";
        public $IsLoginIdValid = false;
        public $IsLoginSuccessful = false;
        public $IsAccountActive = false;
        /*
         *  HOW TO USE
         *  ==========
         *  - Init object with set typr
         *  $login = new Users\Login("id"|"badge");
         *  - You can change type
         *  $login=>SetLoginType("id" | "badge")
         * - You have to set credential base on the type
         *  - if Login ID and Password
         *  $login->SetLoginId($login_id, $password)
         *  - if Badge
         *  $login->SetBadge($badge_data)
         *  -- RUN Login
         *  if($login->RunLogin())
         *  {
         *          login successful
         *          -
         *          -
         *          -
         *          $login->GetLoginData()
         *  }
         *  else
         *  {
         *      login failed - you can get info from
         *      $login->LastError
         *  }
         *
         *
         *
         */
        public function __construct($type = 'id')
        {
            //option ["id" | "badge"]
            $this->loginType = $type;
        }
        #   option ["id" | "badge"]
        public function SetLoginType($type)
        {
            $this->loginType = $type;
        }
        /*
         *  This method set the Badge return true or false
         *  based on success
         */
        public function SetBadge($badge_data)
        {
            # badge data comes encode {loginid}|{guid}
            # split data
            $data = explode('|', $badge_data ?? '');
            if(count($data) == 2)
            {
                #   if it is the only expected data that is in the badge data
                $this->loginId = $data[0];
                $this->loginGuid = $data[1];
            }
            return false;
        }
        public function SetLoginId($loginId, $password)
        {
            $this->loginId = $loginId;
            $this->loginPwd = $password;
        }
        #
        #
        #
        private function getLogin()
        {
            #   Use user login to fetch details
            #   SECURITY FIX: Use parameterized query to prevent SQL injection
            $loginid = $this->loginId;
            $db = GetMysqlDatabase();
            $query = "SELECT
            usr_login.userid,
            usr_login.loginid,
            usr_login.username,
            usr_login.pwd,
            usr_login.guid,
            usr_login.roleid,
            usr_role.title AS role,
            usr_login.geo_level,
            usr_login.geo_level_id,
            usr_role.system_privilege,
            usr_role.platform,
            usr_role.module,
            usr_role.priority,
            usr_login.user_group,
            usr_login.active,
            CONCAT_WS(' ', usr_identity.`first`,usr_identity.middle,usr_identity.last) AS fullname,
            usr_role.role_code,
            if(usr_login.is_change_password,'Yes','No') AS user_change_password,
            sys_geo_codex.geo_value,
            sys_geo_codex.title AS geo_title,
            sys_geo_codex.geo_string,
            NOW() AS system_date_time
            FROM
            usr_login
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
            LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid
            LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            WHERE
            usr_login.loginid = ?";
            
            $stmt = $db->Conn->prepare($query);
            $stmt->execute([$loginid]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            #
            if(count($data))
            {

                $this->loginData = $data[0];
                return true;
            }
            return false;
        }
        private function UpdateDeviceRegistry($serial_no,$login_id){
            $date = getNowDbDate();
            # Log user device login
            DbHelper::Insert('sys_device_login',array(
                'device_serial'=>$serial_no,
                'loginid'=>$login_id,
                'created'=>$date
            ));
            # Update registry
            return DbHelper::Update('sys_device_registry',array(
                'connected'=>$date,
                'connected_loginid'=>$login_id
            ),'serial_no',$serial_no);
        }
        public function RunLogin(string $device_serial_no = "")
        {
            if($this->getLogin())
            {
                $this->IsLoginIdValid = true;
                #
                #   check User validity
                $data = $this->loginData;
                if($this->loginType == "id")
                {
                    #   Varify using password
                    if(password_verify($this->loginPwd, $data['pwd']))
                    {
                        //success
                    }
                    else
                    {
                        //  failed
                        $this->LastError = "Your password is incorrect, please try again";
                        return false;
                    }
                }
                elseif($this->loginType == "badge")
                {
                    # Verify using GUID
                    if($this->loginGuid != $data["guid"])
                    {
                        //  failed
                        $this->LastError = "Your badge value was incorrect";
                        return false;
                    }
                }
                #
                #   Check if active
                if($data["active"] == "0")
                {
                    //  failed
                    $this->LastError = "Your account is not active";
                    return false;
                }
                #
                #   Register device with the users
                if($device_serial_no){
                    $this->UpdateDeviceRegistry($device_serial_no,$this->loginId);
                }
                #
                #
                $this->IsLoginSuccessful = true;
                $this->IsAccountActive = true;
                return true;
            }
            $this->LastError = "Invalid login information";
            return false;
        }
        public function GetLoginData()
        {
            return $this->loginData;
        }
        public function GetLoginId()
        {
            return $this->loginId;
        }
    }
?>
