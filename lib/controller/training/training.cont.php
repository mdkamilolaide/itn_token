<?php

    namespace Training;

    use DbHelper;

    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');
    include_once('lib/config.php');

    class Training
    {
        private $db;
        
        public function __construct()
        {
            # Declare db;
            $this->db = GetMysqlDatabase();
        }
        #
        #
        #   Private functions
        private function TrainingActiveStatus($id)
        {
            return DbHelper::GetScalar("SELECT
            tra_training.active
            FROM
            tra_training
            WHERE
            tra_training.trainingid = $id");
        }
        #
        #   Public functions
        #
        #   Training
        public function CreateTraining($title, $geo_location, $location_id, $desciption, $start, $end){
            $guid = generateUUID();
            $date = getNowDbDate();
            return DbHelper::Insert('tra_training',array(
                'title'=>$title,
                'geo_location'=>$geo_location,
                'location_id'=>$location_id,
                'guid'=>$guid,
                'active'=>1,
                'description'=>$desciption,
                'start_date'=>$start,
                'end_date'=>$end,
                'created'=>$date,
                'updated'=>$date
            ));
        }
        public function UpdateTraining($title, $geo_location, $location_id, $desciption, $start, $end, $training_id){
            $date = getNowDbDate();
            return DbHelper::Update('tra_training',array(
                'title'=>$title,
                'geo_location'=>$geo_location,
                'location_id'=>$location_id,
                'description'=>$desciption,
                'start_date'=>$start,
                'end_date'=>$end,
                'updated'=>$date
            ),'trainingid',$training_id);
        }
        public function ToggleTraining($training_id){
            $date = getNowDbDate();
            if($this->TrainingActiveStatus($training_id)){
                return DbHelper::Update('tra_training',array(
                    'active'=>0,
                    'updated'=>$date
                ),'trainingid',$training_id);
            }
            else{
                return DbHelper::Update('tra_training',array(
                    'active'=>1,
                    'updated'=>$date
                ),'trainingid',$training_id);
            }
        }
        public function AddParticipants($training_id, $training_array_list){
            $counter = 0;
            if(count($training_array_list)){
                $this->db->beginTransaction();
                foreach($training_array_list as $rw){
                    $this->db->executeTransaction("INSERT INTO tra_participants  (`trainingid`,`userid`) VALUES (?,?)",array($training_id,$rw));
                    $counter++;
                }
                #
                #   update participant total
                $this->db->executeTransaction("UPDATE tra_training SET participant_count = participant_count+? WHERE trainingid = ?",array($counter,$training_id));;
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
            }
            return $counter;
        }
        # add participant by group
        public function AddParticipantsByGroup($training_id, $group_name){
            #   
            #   Find   if the group exist
            $group = DbHelper::Table("SELECT usr_login.userid
                FROM
                usr_login
                WHERE
                usr_login.user_group = '$group_name'");
            $counter = 0;
            if(count($group)){
                #   update 
                $this->db->beginTransaction();
                foreach($group as $rw){
                    #   Count 
                    $tot = $this->db->executeTransactionScalar("SELECT Count(*) FROM tra_participants WHERE tra_participants.trainingid = $training_id AND tra_participants.userid = ".$rw['userid']);
                    if(!$tot){
                        #insert only if participant doesn't exist in the group
                        $this->db->executeTransaction("INSERT INTO tra_participants  (`trainingid`,`userid`) VALUES (?,?)",array($training_id,$rw['userid']));
                        $counter++;
                    }
                }
                #
                #   update participant total
                $this->db->executeTransaction("UPDATE tra_training SET participant_count = participant_count+? WHERE trainingid = ?",array($counter,$training_id));;
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
            }
            return $counter;
        }
        # remove participant
        public function RemoveParticipant($training_id, $participant_id_list){
            $counter = 0;
            if(count($participant_id_list))
            {
                $this->db->beginTransaction();
                foreach($participant_id_list as $id)
                {
                    $this->db->executeTransaction("DELETE FROM tra_participants WHERE `participant_id` = ?",array($id));
                    $counter++;
                }
                #
                #   update participant total
                $this->db->executeTransaction("UPDATE tra_training SET participant_count = participant_count-? WHERE trainingid = ?",array($counter,$training_id));;
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
            }
            return $counter;
        }
        #   Check duplicate
        #   Deprecated as not duplicate will be working
        public function getParticipantDuplicate(){
            return DbHelper::Table("SELECT
            tra_training.title AS training,
            CONCAT_WS(' ',usr_identity.`first`,usr_identity.last) AS fullname,
            tra_participants.trainingid,
            tra_participants.userid,
            Count(tra_participants.userid) AS duplicate_total
            FROM
            tra_participants
            LEFT JOIN tra_training ON tra_participants.trainingid = tra_training.trainingid
            LEFT JOIN usr_identity ON tra_participants.userid = usr_identity.userid
            GROUP BY
            tra_participants.userid
            HAVING COUNT(tra_participants.userid) > 1");
        }
        #
        #   Get List
        #
        #   updated with 
        public function getGenericTraining($geo_level, $geo_level_id){
            #
            #   Conditional clause
            $where_condition = " ";
            if($geo_level == 'state'){
                $where_condition = " AND sys_geo_codex.stateid = $geo_level_id";
            }
            elseif($geo_level == 'lga'){
                $where_condition = " AND sys_geo_codex.lgaid = $geo_level_id";
            }
            elseif($geo_level == 'cluster'){
                $where_condition = " AND sys_geo_codex.clusterid = $geo_level_id";
            }
            elseif($geo_level == 'ward'){
                $where_condition = " AND sys_geo_codex.wardid = $geo_level_id";
            }
            elseif($geo_level == 'dp'){
                $where_condition = " AND sys_geo_codex.dpid = $geo_level_id";
            }
            #   nothing else
            return DbHelper::Table("SELECT
            tra_training.trainingid,
            tra_training.title,
            tra_training.geo_location,
            tra_training.location_id,
            tra_training.guid,
            tra_training.description,
            tra_training.start_date,
            tra_training.end_date,
            tra_training.participant_count
            FROM
            tra_training
            INNER JOIN sys_geo_level ON tra_training.geo_location = sys_geo_level.geo_level
            WHERE
            tra_training.active = 1
            AND sys_geo_level.geo_value <= (SELECT sys_geo_level.geo_value FROM sys_geo_level WHERE sys_geo_level.geo_level = '$geo_level')");
        }
        public function getFilteredTraining($level){
            return DbHelper::Table("SELECT
            tra_training.trainingid,
            tra_training.title,
            tra_training.geo_location,
            tra_training.location_id,
            tra_training.guid,
            tra_training.description,
            tra_training.start_date,
            tra_training.end_date,
            tra_training.participant_count
            FROM
            tra_training
            WHERE
            tra_training.active = 1");
        }
        public function getGenericSession($training_id){
            return DbHelper::Table("SELECT
            tra_session.sessionid,
            tra_session.trainingid,
            tra_session.title,
            tra_session.guid,
            tra_session.session_date,
            tra_session.created,
            tra_session.updated
            FROM
            tra_session
            WHERE
            tra_session.trainingid = $training_id");
        }
        #
        #   Updated with geo-location
        public function getParticipantsList($training_id,$geo_level, $geo_level_id){
            #
            #   Conditional clause
            $where_condition = " ";
            if($geo_level == 'state'){
                $where_condition = " AND sys_geo_codex.stateid = $geo_level_id";
            }
            elseif($geo_level == 'lga'){
                $where_condition = " AND sys_geo_codex.lgaid = $geo_level_id";
            }
            elseif($geo_level == 'cluster'){
                $where_condition = " AND sys_geo_codex.clusterid = $geo_level_id";
            }
            elseif($geo_level == 'ward'){
                $where_condition = " AND sys_geo_codex.wardid = $geo_level_id";
            }
            elseif($geo_level == 'dp'){
                $where_condition = " AND sys_geo_codex.dpid = $geo_level_id";
            }
            #   else nothing of ccause
            return DbHelper::Table("SELECT
            tra_participants.participant_id,
            tra_participants.userid,
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
            usr_finance.is_verified,
            usr_finance.verification_status,
            usr_security.bio_feature
            FROM
            tra_participants
            INNER JOIN usr_login ON tra_participants.userid = usr_login.userid
            INNER JOIN usr_identity ON tra_participants.userid = usr_identity.userid
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
            INNER JOIN usr_finance ON tra_participants.userid = usr_finance.userid
            INNER JOIN usr_security ON tra_participants.userid = usr_security.userid
            LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            WHERE
            usr_login.active = 1 
            AND tra_participants.trainingid = $training_id
            $where_condition");
        }
        #
        #
        public function getAttendanceList($sessionid){
            #  Require variable
            $date_format = '%d/%m/%Y';
            $datetime_format = '%d/%m/%Y %r';
            #
            return DbHelper::Table("SELECT
            usr.loginid,
            CONCAT_WS(' ',usr.`first`,usr.middle,usr.last) AS fullname,
            usr.phone,
            tra_attendant.at_type,
            DATE_FORMAT(tra_attendant.collected,'$datetime_format') AS collected,
            IF
                ( tra_attendant.bio_auth = 1, 'True', 'False' ) AS `bio_auth`,
            usr.role,
            tra_attendant.attendant_id,
            usr.userid,
            tra_participants.participant_id
            FROM
            tra_attendant
            INNER JOIN tra_participants ON tra_attendant.participant_id = tra_participants.participant_id
            INNER JOIN (
                SELECT
                    usr_login.userid,
                    usr_login.loginid,
                    usr_role.title AS role,
                    usr_identity.`first`,
                    usr_identity.middle,
                    usr_identity.last,
                    usr_identity.gender,
                    usr_identity.email,
                    usr_identity.phone 
                FROM
                    usr_login
                    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
                    LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid 
                ) AS usr ON tra_participants.userid = usr.userid
            WHERE
            tra_attendant.session_id = $sessionid
            ORDER BY
            tra_attendant.attendant_id DESC");
        }
        #
        #   Training Excel format export
        #
        public function ExcelGetParticipantList($training_id){
            $query = "SELECT
            usr_login.loginid AS `login id`,
            usr_role.title AS role,
            usr_identity.`first` AS `First Name`,
            usr_identity.middle AS `Middle Name`,
            usr_identity.last AS `Last Name`,
            usr_identity.gender,
            usr_identity.email,
            usr_identity.phone,
            usr_finance.bank_name AS `Bank Name`,
            usr_finance.bank_code AS `Bank Code`,
            usr_finance.account_name AS `Account Name`,
            usr_finance.account_no AS `Account Number`,
            if(usr_finance.is_verified,'Yes','No') AS `Bank Verification`,
            usr_finance.verification_status AS `Verification Status`,
            usr_finance.verified_account_name AS `Verified Account Name`,
            usr_finance.verification_message AS `Verification Message`,
            sys_geo_codex.geo_string
            FROM
            tra_participants
            INNER JOIN usr_login ON tra_participants.userid = usr_login.userid
            INNER JOIN usr_identity ON tra_participants.userid = usr_identity.userid
            LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
            INNER JOIN usr_finance ON tra_participants.userid = usr_finance.userid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            WHERE
            usr_login.active = 1
                AND tra_participants.trainingid =  $training_id";
            #   Get payload
            $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
            $json_data = array(array(
                "sheetName" => "Participant List",
                "data" => $data
            ));
            #   return payload
            return json_encode($json_data);
        }
        public function ExcelCountParticipantList($training_id){
            return DbHelper::GetScalar("SELECT
            Count(tra_participants.participant_id)
            FROM
            tra_participants
            INNER JOIN usr_login ON tra_participants.userid = usr_login.userid
            WHERE
            usr_login.active = 1 AND
            tra_participants.trainingid = $training_id");
        }
        public function ExcelGetAttendanceList($sessionid, $geo_level="", $geo_level_id=""){
            #  Require variable
            $date_format = '%d/%m/%Y';
            $datetime_format = '%d/%m/%Y %r';
            $where_condition = "  ";
            $seed = 1;
            #
            if($geo_level && $geo_level_id){
                if($seed == 0){
                    $where_condition = " WHERE sgc.geo_level = '$geo_level' AND sgc.geo_level_id = $geo_level_id  ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND sgc.geo_level = '$geo_level' AND sgc.geo_level_id = $geo_level_id ";
            }
            #
            /*
            SELECT
                usr.loginid,
                usr.role,
                usr.`first` AS `First Name`,
                usr.middle AS `Middle Name`,
                usr.last AS `LAst Name`,
                tra_session.title AS `session`,
                tra_training.title AS training,
                DATE_FORMAT( tra_session.session_date, '$date_format' ) AS `Session_date`,
                DATE_FORMAT(clin.collected, '%r' ) AS `Clock-in`,
                DATE_FORMAT(clout.collected, '%r' ) AS `Clock-out`,
            IF( tra_attendant.bio_auth = 1, 'True', 'False' ) AS `Biometric Auth`,
                tra_attendant.longitude,
                tra_attendant.latitude,
                usr.gender,
                usr.email,
                usr.phone,
                usr.geo_string,
                DATE_FORMAT( tra_attendant.created, '$datetime_format' ) AS `Created Date` 
            FROM
                tra_attendant
                INNER JOIN tra_session ON tra_attendant.session_id = tra_session.sessionid
                INNER JOIN tra_training ON tra_session.trainingid = tra_training.trainingid
                INNER JOIN tra_participants ON tra_attendant.participant_id = tra_participants.participant_id
                INNER JOIN (
                SELECT
                    usr_login.userid,
                    usr_login.loginid,
                    usr_role.title AS role,
                    usr_identity.`first`,
                    usr_identity.middle,
                    usr_identity.last,
                    usr_identity.gender,
                    usr_identity.email,
                    usr_identity.phone,
                    usr_login.geo_level,
                    usr_login.geo_level_id,
                    sys_geo_codex.geo_string 
                FROM
                    usr_login
                    INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
                    LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid 
                    INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
                ) usr ON tra_participants.userid = usr.userid
                LEFT JOIN (
                    SELECT
                    tra_attendant.participant_id,
                    tra_attendant.at_type,
                    tra_attendant.collected
                    FROM 
                    tra_attendant
                    WHERE tra_attendant.at_type = 'clock-in' AND tra_attendant.session_id = $sessionid
                    GROUP BY tra_attendant.participant_id) clin ON tra_attendant.participant_id = clin.participant_id
                LEFT JOIN (
                SELECT
                    tra_attendant.participant_id,
                    tra_attendant.at_type,
                    tra_attendant.collected
                    FROM 
                    tra_attendant
                    WHERE tra_attendant.at_type = 'clock-out' AND tra_attendant.session_id = $sessionid
                    GROUP BY tra_attendant.participant_id) clout ON tra_attendant.participant_id = clout.participant_id
                INNER JOIN sys_geo_codex ON usr.geo_level = sys_geo_codex.geo_level 
                AND usr.geo_level_id = sys_geo_codex.geo_level_id 
            WHERE
                tra_session.sessionid = $sessionid 
                $where_condition
                GROUP BY usr.loginid
            ORDER BY
                usr.`first`ASC
                */
            $query = "SELECT
                    ul.loginid,
                    ur.title AS role,
                    ui.`first` AS `First Name`,
                    ui.middle AS `Middle Name`,
                    ui.last AS `Last Name`,
                    ts.title AS session,
                    tt.title AS training,
                    DATE_FORMAT(ts.session_date, '%d/%m/%Y') AS `Session_date`,
                    DATE_FORMAT(MAX(CASE WHEN ta.at_type = 'clock-in' THEN ta.collected END), '%r') AS `Clock-in`,
                    DATE_FORMAT(MAX(CASE WHEN ta.at_type = 'clock-out' THEN ta.collected END), '%r') AS `Clock-out`,
                    IF(ta.bio_auth = 1, 'True', 'False') AS `Biometric Auth`,
                    ta.longitude,
                    ta.latitude,
                    ui.gender,
                    ui.email,
                    ui.phone,
                    sgc.geo_string,
                    DATE_FORMAT(ta.created, '%d/%m/%Y %r') AS `Created Date`
                    FROM tra_attendant ta
                    JOIN tra_session ts ON ta.session_id = ts.sessionid
                    JOIN tra_training tt ON ts.trainingid = tt.trainingid
                    JOIN tra_participants tp ON ta.participant_id = tp.participant_id
                    JOIN usr_login ul ON tp.userid = ul.userid
                    JOIN usr_identity ui ON ul.userid = ui.userid
                    LEFT JOIN usr_role ur ON ul.roleid = ur.roleid
                    JOIN sys_geo_codex sgc ON ul.geo_level = sgc.geo_level AND ul.geo_level_id = sgc.geo_level_id
                    WHERE ts.sessionid = $sessionid
                    $where_condition
                    GROUP BY ul.loginid
                    ORDER BY ui.`first` ASC;";
            #   Get payload
            $data = $this->db->ExcelDataTable($query);
            #   Prep Payload
            $json_data = array(array(
                "sheetName" => "Attendance List",
                "data" => $data
            ));
            #   return payload
            return json_encode($json_data);
        }
        public function ExcelCountAttendanceList($sessionid, $geo_level="", $geo_level_id=""){
            #
            #
            $where_condition = "  ";
            $seed = 1;
            #
            if($geo_level && $geo_level_id){
                if($seed == 0){
                    $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
            }
            #
            return DbHelper::GetScalar("SELECT
            Count(DISTINCT tra_attendant.participant_id)
            FROM
            tra_attendant
            INNER JOIN tra_participants ON tra_attendant.participant_id = tra_participants.participant_id
            INNER JOIN ( SELECT usr_login.geo_level, usr_login.geo_level_id, usr_login.userid FROM usr_login ) AS usr ON tra_participants.userid = usr.userid
            INNER JOIN sys_geo_codex ON usr.geo_level = sys_geo_codex.geo_level AND usr.geo_level_id = sys_geo_codex.geo_level_id
            WHERE
            tra_attendant.session_id =  $sessionid 
            $where_condition");
        }
        #
        #
        #   Session
        public function CreateSession($training_id, $title, $session_date){
            $guid = generateUUID();
            $date = getNowDbDate();
            return DbHelper::Insert('tra_session',array(
                'trainingid'=>$training_id,
                'title'=>$title,
                'guid'=>$guid,
                'session_date'=>$session_date,
                'created'=>$date,
                'updated'=>$date
            ));
        }
        public function UpdateSession($training_id, $title, $session_date, $session_id){
            $date = getNowDbDate();
            /*
            return DbHelper::Update('tra_session', array(
                'trainingid'=>$training_id,
                'title'=>$title,
                'session_date'=>$session_date,
                'updated'=>$date
            ),'sessionid', $session_id);
            */
            return $this->db->Execute("UPDATE tra_session SET `trainingid`=?, `title`=?, `session_date`=?, `updated`=? WHERE `sessionid`=? ",
            array($training_id, $title, $session_date, $date, $session_id));
        }
        public function DeleteSession($session_id){
            return DbHelper::Delete('tra_session','sessionid',$session_id);
        }
        #   take single attendance
        #   take bulk attendance
        #   SELECT Attendance by group by collected date
        public function AddAttendance($session_id,$participant_id, $type, $bio_auth, $collected, $longitude, $latitude, $userid, $app_version = ""){
            $date = getNowDbDate();
            $bio_auth = ($bio_auth) ? 1:0;
            return DbHelper::Insert('tra_attendant',array(
                'session_id'=>$session_id,
                'participant_id'=>$participant_id,
                'at_type'=>strtolower($type),
                'bio_auth'=>$bio_auth,
                'collected'=>$collected,
                'longitude'=>$longitude,
                'latitude'=>$latitude,
                'userid'=>$userid,
                'app_version'=>$app_version,
                'created'=>$date
            ));
        }
        public function AddAttendancebulk($Bulk_data){
            ### [session_id, participant_id, at_type, bio_auth, collected,longitude,latitude, userid, app_version]
            $date = getNowDbDate();
            $counter = 0;
            if(count($Bulk_data))
            {
                $this->db->beginTransaction();
                foreach($Bulk_data as $r){
                    $bio_auth = ($r['bio_auth']) ? 1:0;
                    $this->db->executeTransaction("INSERT INTO tra_attendant (`session_id`,`participant_id`,`at_type`,`bio_auth`,`collected`,`userid`,`longitude`,`latitude`,`app_version`,`created`) VALUES (?,?,?,?,?,?,?,?,?,?)",
                    array($r['session_id'], $r['participant_id'], strtolower($r['at_type']), $bio_auth, $r['collected'], $r['userid'], $r['longitude'],$r['latitude'],$r['app_version'],$date));
                    $counter++;
                }
                #   php 8  pdo upgrade
                $this->db->commitTransaction();
            }
            return $counter;
        }
        #
        #
        #   Dashboard
        public function DashCountTraining(){
            return DbHelper::Table("SELECT Count(trainingid) AS total FROM tra_training");
        }
        public function DashCountActive(){
            return DbHelper::Table("SELECT
            (SELECT COUNT(trainingid) FROM tra_training WHERE active=1) AS active,
            (SELECT COUNT(trainingid) FROM tra_training WHERE active=0) AS inactive");
        }
        public function DashCountSession(){
            return DbHelper::Table("SELECT COUNT(sessionid) AS total FROM tra_session");
        }

    }

?>