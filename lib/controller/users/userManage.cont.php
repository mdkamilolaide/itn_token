<?php

    namespace Users;

    use DbHelper;

    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');
    #
    #   Class
    class UserManage
    {
        #
        #   Properties
        #
        private $db;
        private $Login_padding = "";

        public function __construct()
        {
            # Declare db;
            $this->db = GetMysqlDatabase();
        }
        
        #
        #   Private methods
        #
        private function InsertLogin($username, $password, $roleid, $usergroup)
        {
            $guid = generateUUID();
            $pwd = password_hash($password, PASSWORD_BCRYPT);
            $hash = md5($password);
            $date = getNowDbDate();
            #
            $id =  DbHelper::Insert('usr_login', array(
                "username"=>$username,
                "pwd"=>$pwd,
                "hash"=>$hash,
                "guid"=>$guid,
                "roleid"=>$roleid,
                "user_group"=>$usergroup,
                "created"=>$date,
                "updated"=>$date
            ));
            if($id)
            {
                #   Update the remaining
                $this->UpdateCombined($id);
            }
            #
            return $id;
        }
        private function UpdateCombined($id)
        {
            #
            #   Update LoginID
            #   SECURITY FIX: Use parameterized query to prevent SQL injection
            $this->db->beginTransaction();
            $pre_pad = $this->Login_padding? $this->Login_padding : GenerateCodeAlphabet(3);
            $gen = $pre_pad.str_pad((int)$id, 5, '0', STR_PAD_LEFT);
            $q1 = "UPDATE usr_login SET loginid = ? WHERE userid = ?";
            $this->db->executeTransaction($q1, [$gen, (int)$id]);
            #   Create finance
            $this->db->executeTransaction("INSERT INTO usr_finance (`userid`) VALUES (?)",array($id));
            #   create identity
            $this->db->executeTransaction("INSERT INTO usr_identity (`userid`) VALUES (?)",array($id));
            #   create security
            $this->db->executeTransaction("INSERT INTO usr_security (`userid`) VALUES (?)",array($id));
            #   Cloas transaction
            #   php 8  pdo upgrade
            $this->db->commitTransaction();
        }
        private function ActiveStatus($userid)
        {
            return DbHelper::GetScalar("SELECT
                    usr_login.active
                    FROM
                    usr_login
                    WHERE
                    usr_login.userid = $userid");
        }
        #
        #   Public methods
        #
        public function AddLoginPadding($padding)
        {
            $this->Login_padding = $padding;
        }
        public function CreateUser($username, $password, $roleid, $usergroup)
        {
            return $this->InsertLogin($username, $password, $roleid, $usergroup);
        }
        public function UpdateFinance($bank_name,$bank_code,$account_num,$account_name,$userid)
        {
            return DbHelper::Update("usr_finance",array(
                "bank_name"=>$bank_name,
                "bank_code"=>$bank_code,
                "account_name"=>$account_name,
                "account_no"=>$account_num
            ),"userid",$userid);
        }
        public function UpdateIdentity($first, $middle, $last, $gender, $email, $phone, $userid)
        {
            return DbHelper::Update("usr_identity",array(
                "first"=>$first,
                "middle"=>$middle,
                "last"=>$last,
                "gender"=>$gender,
                "email"=>$email,
                "phone"=>$phone
            ),"userid",$userid);
        }
        public function UpdateRole($roleid,$userid)
        {
            return DbHelper::Update("usr_login",array("roleid"=>$roleid),"userid",$userid);
        }
        public function UpdateSecurity($data, $userid){
            return DbHelper::Update('usr_security',array(
                'bio_feature'=>$data
            ),'userid',$userid);
        }
        public function ToggleUserStatus($userid)
        {
            $date = getNowDbDate();
            if($this->ActiveStatus($userid))
            {
                #   Deativate user
                return DbHelper::Update("usr_login",array(
                    "active"=>0,
                    "updated"=>$date
                ),"userid",$userid);
            }
            else
            {
                #   Activate User
                return DbHelper::Update("usr_login",array(
                    "active"=>1,
                    "updated"=>$date
                ),"userid",$userid);
            }
        }
        public function GetUserLoginId($userid)
        {
            return DbHelper::GetScalar("SELECT loginid FROM usr_login WHERE userid = $userid");
        }
        public function GetUserBaseInfo($userid)
        {
            return DbHelper::Table("SELECT
            usr_login.userid,
            usr_login.loginid,
            usr_login.username,
            usr_login.guid,
            usr_login.roleid,
            usr_role.title AS role,
            usr_login.user_group,
            usr_login.geo_level,
            usr_login.geo_level_id,
            usr_login.created,
            usr_login.updated,
            (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE sys_geo_codex.geo_level=usr_login.geo_level AND sys_geo_codex.geo_level_id=usr_login.geo_level_id) AS geo_string
            FROM
            usr_login
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid 
            WHERE
            usr_login.userid = $userid");
        }
        public function GetUserIdentity($userid)
        {
            return DbHelper::Table("SELECT
            usr_identity.id,
            usr_identity.userid,
            usr_identity.`first`,
            usr_identity.middle,
            usr_identity.last,
            usr_identity.gender,
            usr_identity.email,
            usr_identity.phone
            FROM
            usr_identity
            WHERE
            usr_identity.userid = $userid");
        }
        public function GetUserFinance($userid)
        {
            return DbHelper::Table("SELECT
            usr_finance.id,
            usr_finance.userid,
            usr_finance.bank_name,
            usr_finance.bank_code,
            usr_finance.account_name,
            usr_finance.account_no
            FROM
            usr_finance
            WHERE
            usr_finance.userid = $userid");
        }
        public function GetUserRoleStructure($roleid)
        {
            return DbHelper::Table("SELECT
            usr_role.roleid,
            usr_role.title,
            usr_role.system_privilege,
            usr_role.platform,
            usr_role.module,
            usr_role.`priority`
            FROM
            usr_role
            WHERE
            usr_role.roleid = $roleid");
        }
        public function TableTestList(){
            return DbHelper::Table("SELECT
            usr_login.loginid,
            usr_identity.`first`,
            usr_identity.last,
            usr_role.title AS role,
            '' AS pick
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid");
        }
        public function ListUserFull(){
            #
            #   Sample purpose
            return DbHelper::Table("SELECT
            usr_login.userid,
            usr_login.loginid,
            usr_login.roleid,
            usr_role.title AS role,
            usr_identity.`first`,
            usr_identity.middle,
            usr_identity.last,
            usr_identity.gender,
            usr_identity.email,
            usr_identity.phone,
            usr_finance.bank_name,
            usr_finance.bank_code,
            usr_finance.account_name,
            usr_finance.account_no,
            usr_security.bio_feature
            FROM
            usr_login
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN usr_finance ON usr_login.userid = usr_finance.userid
            INNER JOIN usr_security ON usr_login.userid = usr_security.userid
            LIMIT 10");
        }
        public function DeavtivateUserByGroup($groupname){
            $date = getNowDbDate();
            #
            return DbHelper::Update("usr_login",array(
                'active'=>0,
                'updated'=>$date
            ),'user_group',$groupname);
        }
        public function ActivateUserByGroup($groupname){
            $date = getNowDbDate();
            #
            return DbHelper::Update("usr_login",array(
                'active'=>1,
                'updated'=>$date
            ),'user_group',$groupname);
        }
        public function UpdateUserRole($roleid, $userid){
            # code...
            $date = getNowDbDate();
            return DbHelper::Update('usr_login',array(
                'roleid'=>$roleid,
                'updated'=>$date
            ), 'userid',$userid);
        }
        public function ChangeUserLevel($userid, $geo_level, $geo_level_id){
            return DbHelper::Update('usr_login', array(
                'geo_level'=>$geo_level,
                'geo_level_id'=>$geo_level_id
            ),'userid',$userid);
        }
        #
        #
        #
        public function RegisterUserFcm($userid, $device_serial, $fcm_token){
            return DbHelper::Update('usr_login',array('device_sn'=>$device_serial,'device_fcm_token'=>$fcm_token),'userid',$userid);
        }
        /*
         *  Bulk functions
         */
        public function BulkToggleUserStatus($array_userid)
        {
            $counter = 0;
            if(count($array_userid) > 0)
            {
                $this->db->beginTransaction();
                
                for($a=0;$a<count($array_userid);$a++)
                {
                    #   Get user active status
                    if($this->db->executeTransactionScalar("SELECT usr_login.active FROM
                        usr_login WHERE usr_login.userid = ".$array_userid[$a]))
                    {
                        #   Deactivate user
                        $this->db->executeTransaction("UPDATE usr_login SET `active` = 0 WHERE userid=?",array($array_userid[$a]));
                    }
                    else
                    {
                        #   Activate users
                        $this->db->executeTransaction("UPDATE usr_login SET `active` = 1 WHERE userid=?",array($array_userid[$a]));
                    }
                    $counter++;
                }
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
                
            }
            return $counter;
        }
        public function BulkUserUpdate($user_data)
        {
            $counter = 0;
            if(count($user_data))
            {
                #
                # userid, roleid, first, middle, last, gender, email, phone, bank_name, account_name, account_no, bank_code, bio_feature
                #
                $this->db->beginTransaction();
                foreach($user_data as $rw)
                {
                    # update userlogin
                    #$this->db->executeTransaction("UPDATE usr_login SET `roleid` = ?, `active` = 0 WHERE userid=?",array($rw['roleid'],$rw['userid']));
                    $this->db->executeTransaction("UPDATE usr_login SET `roleid` = ? WHERE userid=?",array($rw['roleid'],$rw['userid']));
                    #   Update user identity
                    $this->db->executeTransaction("UPDATE usr_identity SET `first`=?,`middle`=?,`last`=?, `gender`=?, `email`=?, `phone`=? WHERE `userid`=?",
                    array($rw['first'],$rw['middle'],$rw['last'],$rw['gender'],$rw['email'],$rw['phone'],$rw['userid']));
                    #   Update user bank
                    #   Get bank name
                    $bank_name = $this->db->executeTransactionScalar("SELECT sys_bank_code.bank_name FROM sys_bank_code WHERE sys_bank_code.bank_code = '".$rw['bank_code']."'");
                    $this->db->executeTransaction("UPDATE usr_finance SET `bank_name`=?,`bank_code`=?,`account_name`=?,`account_no`=? WHERE `userid`=?",
                    array($bank_name,$rw['bank_code'],$rw['account_name'],$rw['account_no'],$rw['userid']));
                    #   update user secuurity
                    $this->db->executeTransaction("UPDATE usr_security SET `bio_feature`=? WHERE `userid`=?",
                    array(GetSafeArrayValue($rw,'bio_feature'),$rw['userid']));
                    $counter++;
                }
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
                
            }
            return $counter;
        }
        public function BulkPasswordReset($userid_array,$password){
            $pwd = password_hash($password, PASSWORD_BCRYPT);
            $hash = md5($password);
            $counter = 0;
            if(count($userid_array)){
                # Start transactions
                $this->db->beginTransaction();
                foreach($userid_array as $userid){
                    $c_query = "UPDATE usr_login SET `pwd` = ?, `hash` = ?, `is_change_password` = 0, `Updated` = ? WHERE userid=?";
                    $this->db->executeTransaction($c_query,array($pwd,$hash,getNowDbDate(),$userid));
                    $counter++;
                }
                $this->db->commitTransaction();
            }
            return $counter;
        }
        public function BulkChangeGeoLocation($userid_array, $geo_level, $geo_level_id){
            $counter = 0;
            if(count($userid_array)){
                # Start transactions
                $this->db->beginTransaction();
                foreach($userid_array as $userid){
                    $c_query = "UPDATE usr_login SET `geo_level` = ?, `geo_level_id` = ? WHERE userid=?";
                    $this->db->executeTransaction($c_query,array($geo_level,$geo_level_id,$userid));
                    $counter++;
                }
                $this->db->commitTransaction();
            }
            return $counter;
        }   
        public function BulkChangeRole($userid_array, $roleid){
            $counter = 0;
            if(count($userid_array)){
                # Start transactions
                $this->db->beginTransaction();
                foreach($userid_array as $userid){
                    $c_query = "UPDATE usr_login SET `roleid` = ? WHERE userid=?";
                    $this->db->executeTransaction($c_query,array($roleid,$userid));
                    $counter++;
                }
                $this->db->commitTransaction();
            }
            return $counter;
        }
        #   Bulk work hour extension data structure
        #    [$userid, $extension_hour, $extension_date, $authorized_user]
        public function BulkWorkHourExtension($bulk_data){
            $counter = 0;
            $sql = "INSERT INTO usr_workhour_extension
                    (userid, extension_hour, extension_date, created_by_userid, created) 
                    VALUES (?, ?, ?, ?, ?)";
            #   Start transactions
            $pdo = $this->db->Conn;
            $stmt = $pdo->prepare($sql);
            // Begin transaction for performance and atomicity
            $pdo->beginTransaction();
            if(count($bulk_data)){
                foreach($bulk_data as $rw){
                    #   Execute
                    if($stmt->execute(array($rw['userid'],$rw['extension_hour'],$rw['extension_date'],$rw['authorized_user'],getNowDbDate()))){
                        $counter+= $stmt->rowCount();
                    }
                }
            }
            $pdo->commit();
            return $counter;
        }
        /*
         *
         *  User Change password
         * 
         */
        public function ChangePassword($login_id, $old_password, $new_password){
            #   Check old password
            #   SECURITY FIX: Use parameterized query to prevent SQL injection
            $db = GetMysqlDatabase();
            $stmt = $db->Conn->prepare("SELECT usr_login.pwd FROM usr_login WHERE usr_login.loginid = ?");
            $stmt->execute([$login_id]);
            $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            #   Change to new password
            if(count($data)){
                #   Varify using password
                if(password_verify($old_password, $data[0]['pwd'])){
                    //success
                    #   Update password
                    $pwd = password_hash($new_password, PASSWORD_BCRYPT);
                    $hash = md5($new_password);
                    if(DbHelper::Update('usr_login',array(
                        'pwd'=>$pwd,
                        'hash'=>$hash,
                        'is_change_password'=>0,
                        'Updated'=>getNowDbDate()
                    ),'loginid',$login_id)){
                        return true;
                    }
                    else{
                        return false;
                    }
                }else{
                    return false;
                }
            }
            else{
                return false;
            }
        }
        public function ResetPassword($login_id,$password){
            $pwd = password_hash($password, PASSWORD_BCRYPT);
            $hash = md5($password);
            $is = DbHelper::Update('usr_login',array(
                'pwd'=>$pwd,
                'hash'=>$hash,
                'is_change_password'=>1,
                'Updated'=>getNowDbDate()
            ),'loginid',$login_id);
            if($is){
                return true;
            }else{
                return false;
            }
        }
        /*
         *
         *  Get Data
         * 
         */
        public function GetRoleList($priority = 1){
            $where_clause = "";
            if($priority > 1){
                $where_clause .= " WHERE `priority` <= $priority AND `active` = 1 ";
            }
            else{
                $where_clause .= " WHERE `priority` = 1 AND `active` = 1 ";
            }
            #
            return DbHelper::Table("SELECT
            usr_role.roleid,
            usr_role.title AS role
            FROM
            usr_role
            $where_clause
            ORDER BY
            role ASC");
        }
        public function GetBadgeByGroup($groupname){
            return DbHelper::Table("SELECT
                        ul.userid,
                        ul.loginid,
                        ul.username,
                        ul.guid,
                        ur.title AS role,
                        sgc.geo_string,
                        CONCAT_WS(' ', ui.`first`, ui.last) AS fullname,
                        ui.phone
                    FROM usr_login ul
                    LEFT JOIN usr_role ur ON ul.roleid = ur.roleid
                    LEFT JOIN usr_identity ui ON ul.userid = ui.userid
                    LEFT JOIN sys_geo_codex sgc
                        ON sgc.geo_level = ul.geo_level AND sgc.geo_level_id = ul.geo_level_id
                    WHERE ul.user_group = '$groupname'");
        }
        public function GetBadgeByUserID($userid){
            return DbHelper::Table("SELECT
                                    ul.userid,
                                    ul.loginid,
                                    ul.username,
                                    ul.guid,
                                    ur.title AS role,
                                    sgc.geo_string,
                                    CONCAT_WS(' ', ui.`first`, ui.last) AS fullname,
                                    ui.phone
                                FROM usr_login ul
                                LEFT JOIN usr_role ur ON ul.roleid = ur.roleid
                                LEFT JOIN usr_identity ui ON ul.userid = ui.userid
                                LEFT JOIN sys_geo_codex sgc
                                    ON sgc.geo_level = ul.geo_level AND sgc.geo_level_id = ul.geo_level_id
                                WHERE ul.userid = $userid");
        }
        public function GetBadgeByLoginId($loginId){
            return DbHelper::Table("SELECT
                                ul.userid,
                                ul.loginid,
                                ul.username,
                                ul.guid,
                                ur.title AS role,
                                sgc.geo_string,
                                CONCAT_WS(' ', ui.`first`, ui.last) AS fullname,
                                    ui.phone
                            FROM usr_login ul
                            LEFT JOIN usr_role ur ON ul.roleid = ur.roleid
                            LEFT JOIN usr_identity ui ON ul.userid = ui.userid
                            LEFT JOIN sys_geo_codex sgc
                                ON sgc.geo_level = ul.geo_level AND sgc.geo_level_id = ul.geo_level_id
                            WHERE ul.loginid  = '$loginId'");
        }
        public function GetBadgeByUserIdList($loginId_array){
            $user_list = ArrayToCsv($loginId_array);
            return DbHelper::Table("SELECT
                                ul.userid,
                                ul.loginid,
                                ul.username,
                                ul.guid,
                                ur.title AS role,
                                sgc.geo_string,
                                CONCAT_WS(' ', ui.`first`, ui.last) AS fullname,
                                ui.phone
                            FROM usr_login ul
                            LEFT JOIN usr_role ur ON ul.roleid = ur.roleid
                            LEFT JOIN usr_identity ui ON ul.userid = ui.userid
                            LEFT JOIN sys_geo_codex sgc
                                ON sgc.geo_level = ul.geo_level AND sgc.geo_level_id = ul.geo_level_id
                            WHERE ul.userid IN ($user_list)");
        }
        public function GetUserGroupList(){
            return DbHelper::Table("SELECT DISTINCT
            usr_login.user_group
            FROM
            usr_login
            ORDER BY
            usr_login.user_group ASC");
        }
        #
        #
        #   User work hours management
        public function GetDefaultWorkHours(){
            return DbHelper::Table("SELECT
            sys_working_hours.start_time,
            sys_working_hours.end_time
            FROM
            sys_working_hours
            WHERE
            sys_working_hours.id = 1");
        }
        #
        #   Add users work hour a day
        public function AddUserWorkHour($userid,$extension_hours, $extension_date, $created_by_userid ){
            return DbHelper::Insert("usr_workhour_extension",array(
                'userid'=>$userid,
                'extension_hour'=>$extension_hours,
                'extension_date'=>$extension_date,
                'created_by_userid'=>$created_by_userid
            ));
        }
        public function GetUserWorkingHours($userid){
            return DbHelper::Table("SELECT
                                swh.start_time,
                                COALESCE(uwe.extension_hour, 0) AS added_hours,
                                ADDTIME(
                                    swh.end_time,
                                    SEC_TO_TIME(COALESCE(uwe.extension_hour, 0) * 3600)
                                ) AS end_time
                            FROM
                                sys_working_hours swh
                            LEFT JOIN (
                                SELECT extension_hour
                                FROM usr_workhour_extension
                                WHERE userid = $userid
                                AND extension_date = CURDATE()
                                LIMIT 1
                            ) uwe ON 1=1
                            WHERE
                                swh.id = 1");
        }
        /*
         *
         *  Users dashboard overview
         * 
         */
        public function DashCountUser(){
            return DbHelper::Table("SELECT COUNT(*) AS total FROM usr_login");
        }
        public function DashCountActive(){
            return DbHelper::Table("SELECT 
            (SELECT COUNT(*) FROM usr_login
            WHERE usr_login.active = 1) AS active,
            (SELECT COUNT(*) FROM usr_login
            WHERE usr_login.active = 0) AS inactive");
        }
        public function DashCountGeoLevel(){
            return DbHelper::Table("SELECT usr_login.geo_level, COUNT(usr_login.userid) AS total FROM usr_login
            GROUP BY usr_login.geo_level");
        }
        public function DashCountUserGroup(){
            return DbHelper::Table("SELECT usr_login.user_group, COUNT(usr_login.userid) AS total FROM usr_login
            GROUP BY usr_login.user_group");
        }
        public function DashCountTotalGroup(){
            return DbHelper::Table("SELECT COUNT(DISTINCT usr_login.user_group) AS total FROM usr_login");
        }
        public function DashCountGender(){
            return DbHelper::Table("SELECT
            usr_identity.gender,
            Count(usr_identity.id) AS total
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            WHERE
            usr_login.active = 1
            GROUP BY
            usr_identity.gender");
        }
        #   Verfy bank name and username
        private function VerifyName($source,$target){
            $source_name = trim($source);
            $target_name = trim($target);
            #      Turn source into array
            if($source_name){
                $source_array = array_filter(explode(" ", $source_name));
                #count to know list
                if(count($source_array)){
                    #begin comparing
                    $success_counter = 0;
                    foreach($source_array as $name){
                        $name = trim($name); // Trim each name
                        # find name in target
                        if (!empty($name) && strpos(strtolower($target_name), strtolower($name)) !== false) {
                            $success_counter++;
                        }
                    }
                    #
                    #   if success counter is more than 2
                    if($success_counter >= 2){
                        return true;
                    }
                }
            }
            return false;
        }
        public function RunBankVerification($userid)
        {
            //  get user bank detail
            $user_data = DbHelper::Table("SELECT
                        usr_finance.bank_name,
                        usr_finance.bank_code,
                        usr_finance.account_name,
                        usr_finance.account_no,
                        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS fullname
                        FROM
                        usr_finance
                        INNER JOIN usr_identity ON usr_finance.userid = usr_identity.userid
                        WHERE
                        usr_finance.userid = $userid
                        LIMIT 1");
            
            if(count($user_data)){
                //  run verification
                include_once('BulkBankVerification.cont.php');
                #
                $vr = new BankVerify($user_data[0]["account_no"],$user_data[0]["bank_code"]);
                $vr->Run();
                #
                $date = getNowDbDate();
                #
                if($vr->Response){
                    #   Successful
                    $message = $vr->ResponseMessage;
                    $account_number = $vr->ResponseData['account_number'];
                    $account_name = $vr->ResponseData['account_name'];
                    #   Verify Name
                    $verification_status = $this->VerifyName($user_data[0]["fullname"],$account_name)? 'success':'warning';
                    #   Update database
                    $this->db->beginTransaction();
                    #   Write success into the database
                    $s_query = "UPDATE `usr_finance` SET `is_verified`=1, 
                    `verification_count`=`verification_count`+1, 
                    `verification_message`=?, 
                    `verified_account_name`=?, 
                    `verification_status`=?, 
                    `last_verified_date`=?
                    WHERE `userid`=?";
                    #   Execute
                    $this->db->executeTransaction($s_query,array($message,$account_name,$verification_status,$date,$userid));
                    #
                    #   php 8  pdo upgrade
                    $this->db->commitTransaction();
                    #
                    return array('result'=>$verification_status,'message'=>$message,'account_name'=>$account_name,'account_number'=>$account_number);
                }
                else{
                    #   Failed
                    $message = $vr->ResponseMessage;
                    #   Update database
                    $this->db->beginTransaction();
                    $e_query = "UPDATE `usr_finance` SET `is_verified`=1, 
                    `verification_count`=`verification_count`+1, 
                    `verification_message`=?,
                    `verification_status`='failed', 
                    `last_verified_date`=?
                    WHERE `userid`=?";
                    $this->db->executeTransaction($e_query,array($message,$date,$userid));
                    #
                    #   php 8  pdo upgrade
                    $this->db->commitTransaction();
                    #
                    return array('result'=>'error','message'=>$message);
                }
            }
            else{
                #   could not find user
                return array('result'=>'error','message'=>'Unable to locate user, using the ID');
            }
        }
        #
        #   Excel Download 
        #   SECURITY FIX: Refactored to use parameterized queries to prevent SQL injection
        #
        public function ExcelDownloadUsers($user_geo_level, $user_geo_level_id, $loginid='',$active='',$phone='',$user_group='',$name='',$geo_level='',$geo_level_id='',$bank_verification_status='',$role_id=''){
            #
            // Whitelist allowed geo level values to prevent SQL injection in column name
            $allowed_geo_levels = ['state', 'lga', 'ward', 'dp', 'community', 'cluster'];
            if (!in_array($user_geo_level, $allowed_geo_levels, true)) {
                return json_encode([['sheetName' => 'User List', 'data' => []]]);
            }
            $where_key = $user_geo_level . "id";
            
            // Build parameterized WHERE clause
            $conditions = ["`$where_key` = ?"];
            $params = [$user_geo_level_id];
            
            if ($loginid) {
                $conditions[] = "usr_login.loginid = ?";
                $params[] = $loginid;
            }
            if ($active) {
                $active_val = $active == 'active' ? 1 : 0;
                $conditions[] = "usr_login.active = ?";
                $params[] = $active_val;
            }
            if ($user_group) {
                $conditions[] = "usr_login.user_group LIKE ?";
                $params[] = '%' . $user_group . '%';
            }
            if ($phone) {
                $conditions[] = "usr_identity.phone = ?";
                $params[] = $phone;
            }
            if ($name) {
                $conditions[] = "(usr_identity.`first` LIKE ? OR usr_identity.middle LIKE ? OR usr_identity.last LIKE ?)";
                $params[] = '%' . $name . '%';
                $params[] = '%' . $name . '%';
                $params[] = '%' . $name . '%';
            }
            if ($geo_level && $geo_level_id) {
                $conditions[] = "usr_login.geo_level = ? AND usr_login.geo_level_id = ?";
                $params[] = $geo_level;
                $params[] = $geo_level_id;
            }
            if ($bank_verification_status) {
                $conditions[] = "usr_finance.verification_status = ?";
                $params[] = $bank_verification_status;
            }
            if ($role_id) {
                $conditions[] = "usr_login.roleid = ?";
                $params[] = $role_id;
            }
            
            $where_condition = " WHERE " . implode(" AND ", $conditions);
            #
            $query = "SELECT
            usr_login.userid,
            usr_login.loginid AS `login id`,
            usr_identity.`first` AS `first name`,
            usr_identity.middle AS `middle name`,
            usr_identity.last AS surname,
            usr_identity.gender,
            usr_identity.email,
            usr_identity.phone,
            usr_role.title AS role,
            sys_geo_codex.geo_string AS `geo string`,
            usr_finance.verified_account_name AS `validated account name`,
            usr_finance.account_no AS `account no`,
            usr_finance.bank_name AS `bank name`
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN usr_role ON usr_login.roleid = usr_role.roleid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            INNER JOIN usr_finance ON usr_login.userid = usr_finance.userid
            $where_condition";
            
            // Execute with prepared statement
            $stmt = $this->db->Conn->prepare($query);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Format for Excel export (add headers as first row)
            $data = [];
            if (count($rows) > 0) {
                // Add header row
                $headers = [];
                foreach (array_keys($rows[0]) as $key) {
                    $headers[] = ['text' => strtoupper($key)];
                }
                $data[] = $headers;
                // Add data rows
                foreach ($rows as $row) {
                    $line = [];
                    foreach ($row as $val) {
                        $line[] = ['text' => $val];
                    }
                    $data[] = $line;
                }
            }
            
            #   Prep Payload
            $json_data = array(array(
                "sheetName" => "User List",
                "data" => $data
            ));
            #   return payload
            return json_encode($json_data);
        }
        
        #   SECURITY FIX: Refactored to use parameterized queries to prevent SQL injection
        public function ExcelCountUsers($user_geo_level, $user_geo_level_id, $loginid='',$active='',$phone='',$user_group='',$name='',$geo_level='',$geo_level_id='',$bank_verification_status='',$role_id=''){
            #
            // Whitelist allowed geo level values to prevent SQL injection in column name
            $allowed_geo_levels = ['state', 'lga', 'ward', 'dp', 'community', 'cluster'];
            if (!in_array($user_geo_level, $allowed_geo_levels, true)) {
                return 0;
            }
            $where_key = $user_geo_level . "id";
            
            // Build parameterized WHERE clause
            $conditions = ["`$where_key` = ?"];
            $params = [$user_geo_level_id];
            
            if ($loginid) {
                $conditions[] = "usr_login.loginid = ?";
                $params[] = $loginid;
            }
            if ($active) {
                $active_val = $active == 'active' ? 1 : 0;
                $conditions[] = "usr_login.active = ?";
                $params[] = $active_val;
            }
            if ($user_group) {
                $conditions[] = "usr_login.user_group LIKE ?";
                $params[] = '%' . $user_group . '%';
            }
            if ($phone) {
                $conditions[] = "usr_identity.phone = ?";
                $params[] = $phone;
            }
            if ($name) {
                $conditions[] = "(usr_identity.`first` LIKE ? OR usr_identity.middle LIKE ? OR usr_identity.last LIKE ?)";
                $params[] = '%' . $name . '%';
                $params[] = '%' . $name . '%';
                $params[] = '%' . $name . '%';
            }
            if ($geo_level && $geo_level_id) {
                $conditions[] = "usr_login.geo_level = ? AND usr_login.geo_level_id = ?";
                $params[] = $geo_level;
                $params[] = $geo_level_id;
            }
            if ($bank_verification_status) {
                $conditions[] = "usr_finance.verification_status = ?";
                $params[] = $bank_verification_status;
            }
            if ($role_id) {
                $conditions[] = "usr_login.roleid = ?";
                $params[] = $role_id;
            }
            
            $where_condition = " WHERE " . implode(" AND ", $conditions);
            #
            $query = "SELECT COUNT(*) FROM
                    usr_login
                    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
                    INNER JOIN usr_role ON usr_login.roleid = usr_role.roleid
                    INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
                    INNER JOIN usr_finance ON usr_login.userid = usr_finance.userid
                    $where_condition";
            
            $stmt = $this->db->Conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        }
    }
?>