<?php
    namespace Mobilization;
    use DbHelper;
    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');
    #
    #
    #
    class MapData {
        #
        private $db;
        #
        public function __construct(){
            $this->db = GetMysqlDatabase();
        }
        private function GetGeneralCordinate(){
            return DbHelper::Table("SELECT
            ms_geo_state.StateId,
            ms_geo_state.Fullname AS state,
            ms_geo_state.longitude AS lng,
            ms_geo_state.latitude AS lat
            FROM
            ms_geo_state
            INNER JOIN sys_default_settings ON ms_geo_state.StateId = sys_default_settings.stateid
            LIMIT 1");
        }
        #
        #
        #
        public function GetMobilizationData($wardid,$mobilizerid, $start_date = "", $end_data = ""){
            $where_condition = " WHERE hhm_mobilization.longitude <> '' AND a.loginid = '$mobilizerid' ";
            $seed = 1;
            #conditional where clause
            if($start_date && $end_data){
                #   Get by date range
                if($seed == 0){
                    $where_condition = " WHERE DATE(hhm_mobilization.collected_date) BETWEEN DATE('$start_date') AND DATE('$end_data') ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND DATE(hhm_mobilization.collected_date) BETWEEN DATE('$start_date') AND DATE('$end_data') ";
            }
            elseif($start_date){
                #   Get specific date alone
                if($seed == 0){
                    $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$start_date')";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$start_date') ";
            }else{
                #   Get all the mobilization from the mobilizer
            }

            ##  Query
            $mobilization = DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.longitude AS lng,
            hhm_mobilization.Latitude AS lat,
            CONCAT_WS(' ',hhm_mobilization.hoh_first,hhm_mobilization.hoh_last) AS household,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.etoken_serial,
            CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
            hhm_mobilization.collected_date
            FROM
            hhm_mobilization
            INNER JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            $where_condition");
            //
            $lng = '7.443281666666667';
            $lat = '9.074513333333334';
            $map_coord = $this->GetGeneralCordinate();
            if(count($map_coord)){
                $lng = $map_coord[0]['lng'];
                $lat = $map_coord[0]['lat'];
            }
            $map = array('zoom'=>'11','lng'=>$lng,'lat'=>$lat);
            ##  return data
            return array('mob_data'=>$mobilization,'map'=>$map);
        }
        public function GetDpData($wardid,$dpid,$start_date = "", $end_data = ""){
            $where_condition = " WHERE hhm_mobilization.longitude <> '' AND hhm_mobilization.dp_id = $dpid ";
            $seed = 1;
            #conditional where clause
            if($start_date && $end_data){
                #   Get by date range
                if($seed == 0){
                    $where_condition = " WHERE DATE(hhm_mobilization.collected_date) BETWEEN DATE('$start_date') AND DATE('$end_data') ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND DATE(hhm_mobilization.collected_date) BETWEEN DATE('$start_date') AND DATE('$end_data') ";
            }
            elseif($start_date){
                #   Get specific date alone
                if($seed == 0){
                    $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$start_date')";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$start_date') ";
            }else{
                #   Get all the mobilization from the mobilizer
            }
            ##  Query
            $mobilization = DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.longitude AS lng,
            hhm_mobilization.Latitude AS lat,
            CONCAT_WS(' ',hhm_mobilization.hoh_first,hhm_mobilization.hoh_last) AS household,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.etoken_serial,
            CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
            hhm_mobilization.collected_date
            FROM
            hhm_mobilization
            INNER JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            $where_condition");
            #
            $lng = '7.443281666666667';
            $lat = '9.074513333333334';
            $map_coord = $this->GetGeneralCordinate();
            if(count($map_coord)){
                $lng = $map_coord[0]['lng'];
                $lat = $map_coord[0]['lat'];
            }
            $map = array('zoom'=>'11','lng'=>$lng,'lat'=>$lat);
            ##  return data
            return array('mob_data'=>$mobilization,'map'=>$map);
        }
        public function GetWardData($wardid,$date){
            $where_condition = " WHERE hhm_mobilization.longitude <> '' AND sys_geo_codex.wardid = $wardid ";
            $seed = 1;
            #conditional where clause
            if($date){
                #   Get by date range
                if($seed == 0){
                    $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$date') ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$date') ";
            }
            ##  Query
            $mobilization = DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.longitude AS lng,
            hhm_mobilization.Latitude AS lat,
            CONCAT_WS(' ',hhm_mobilization.hoh_first,hhm_mobilization.hoh_last) AS household,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.etoken_serial,
            CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
            hhm_mobilization.collected_date
            FROM
            hhm_mobilization
            INNER JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
            $where_condition");
            #
            $lng = '7.443281666666667';
            $lat = '9.074513333333334';
            $map_coord = $this->GetGeneralCordinate();
            if(count($map_coord)){
                $lng = $map_coord[0]['lng'];
                $lat = $map_coord[0]['lat'];
            }
            $map = array('zoom'=>'11','lng'=>$lng,'lat'=>$lat);
            ##  return data
            return array('mob_data'=>$mobilization,'map'=>$map);
        }
        public function GetLgaData($lgaid,$date){
            $where_condition = " WHERE hhm_mobilization.longitude <> '' AND sys_geo_codex.lgaid =  $lgaid ";
            $seed = 1;
            #conditional where clause
            if($date){
                #   Get by date range
                if($seed == 0){
                    $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$date') ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$date') ";
            }
            ##  Query
            $mobilization = DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.longitude AS lng,
            hhm_mobilization.Latitude AS lat,
            CONCAT_WS(' ',hhm_mobilization.hoh_first,hhm_mobilization.hoh_last) AS household,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.etoken_serial,
            CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
            hhm_mobilization.collected_date
            FROM
            hhm_mobilization
            INNER JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
            $where_condition");
            #
            $lng = '7.443281666666667';
            $lat = '9.074513333333334';
            $map_coord = $this->GetGeneralCordinate();
            if(count($map_coord)){
                $lng = $map_coord[0]['lng'];
                $lat = $map_coord[0]['lat'];
            }
            $map = array('zoom'=>'11','lng'=>$lng,'lat'=>$lat);
            ##  return data
            return array('mob_data'=>$mobilization,'map'=>$map);
        }
        public function GetStateData($stateid, $date){
            $where_condition = " WHERE hhm_mobilization.longitude <> '' AND sys_geo_codex.stateid = $stateid ";
            $seed = 1;
            #conditional where clause
            if($date){
                #   Get by date range
                if($seed == 0){
                    $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$date') ";
                    $seed = 1;
                }
                else
                    $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$date') ";
            }
            ##  Query
            $mobilization = DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.longitude AS lng,
            hhm_mobilization.Latitude AS lat,
            CONCAT_WS(' ',hhm_mobilization.hoh_first,hhm_mobilization.hoh_last) AS household,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.etoken_serial,
            CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
            hhm_mobilization.collected_date
            FROM
            hhm_mobilization
            INNER JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
            $where_condition");
            #
            $lng = '7.443281666666667';
            $lat = '9.074513333333334';
            $map_coord = $this->GetGeneralCordinate();
            if(count($map_coord)){
                $lng = $map_coord[0]['lng'];
                $lat = $map_coord[0]['lat'];
            }
            $map = array('zoom'=>'11','lng'=>$lng,'lat'=>$lat);
            ##  return data
            return array('mob_data'=>$mobilization,'map'=>$map);
        }
        public function GetPerItemData($hhid){
            ##  Query
            $mobilization = DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.longitude AS lng,
            hhm_mobilization.Latitude AS lat,
            CONCAT_WS(' ',hhm_mobilization.hoh_first,hhm_mobilization.hoh_last) AS household,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.etoken_serial,
            CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
            hhm_mobilization.collected_date
            FROM
            hhm_mobilization
            INNER JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid
            WHERE hhm_mobilization.hhid = $hhid");
            $lng = '7.44333';
            $lat = '9.0754';
            if(count($mobilization)){
                $lng = $mobilization[0]['lng'];
                $lat = $mobilization[0]['lat'];
            }
            $lng = '7.443281666666667';
            $lat = '9.074513333333334';
            $map_coord = $this->GetGeneralCordinate();
            if(count($map_coord)){
                $lng = $map_coord[0]['lng'];
                $lat = $map_coord[0]['lat'];
            }
            $map = array('zoom'=>'11','lng'=>$lng,'lat'=>$lat);
            ##  return data
            return array('mob_data'=>$mobilization,'map'=>$map);
        }
        public function GetTestAllData(){
            ##  Query
            $mobilization = DbHelper::Table("SELECT
            hhm_mobilization.hhid,
            hhm_mobilization.longitude AS lng,
            hhm_mobilization.Latitude AS lat,
            CONCAT_WS(' ',hhm_mobilization.hoh_first,hhm_mobilization.hoh_last) AS household,
            hhm_mobilization.hoh_phone,
            hhm_mobilization.hoh_gender,
            hhm_mobilization.family_size,
            hhm_mobilization.allocated_net,
            hhm_mobilization.etoken_serial,
            CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
            hhm_mobilization.collected_date
            FROM
            hhm_mobilization
            INNER JOIN (SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
            WHERE hhm_mobilization.longitude <> '' ");
            $lng = '7.44333';
            $lat = '9.0754';
            $map = array('zoom'=>'12','lng'=>$lng,'lat'=>$lat);
            ##  return data
            return array('mob_data'=>$mobilization,'map'=>$map);
        }
    }
?>