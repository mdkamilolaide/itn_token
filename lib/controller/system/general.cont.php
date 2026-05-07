<?php

namespace System;

use DbHelper;
#
include_once('lib/common.php');
include_once('lib/mysql.min.php');

class General
{

    #
    #
    #
    public function __construct() {}

    #
    #
    #Public get list
    public function GetBankList()
    {
        return DbHelper::Table("SELECT
            sys_bank_code.bank_code,
            sys_bank_code.bank_name
            FROM
            sys_bank_code
            ORDER BY
            sys_bank_code.bank_name ASC");
    }
    public function GetStateList()
    {
        return DbHelper::Table("SELECT
            ms_geo_state.StateId AS stateid,
            ms_geo_state.Fullname AS state
            FROM
            ms_geo_state");
    }
    public function GetLgaList($stateid)
    {
        return DbHelper::Table("SELECT
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.StateId AS stateid,
            ms_geo_lga.Fullname AS lga
            FROM
            ms_geo_lga
            WHERE
            ms_geo_lga.StateId = $stateid");
    }
    public function GetThisLgaList($lgaid)
    {
        return DbHelper::Table("SELECT
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.StateId AS stateid,
            ms_geo_lga.Fullname AS lga
            FROM
            ms_geo_lga
            WHERE
            ms_geo_lga.LgaId = $lgaid");
    }
    public function GetClusterList($lgaid)
    {
        return DbHelper::Table("SELECT
            ms_geo_cluster.clusterid,
            ms_geo_cluster.lgaid,
            ms_geo_cluster.cluster
            FROM
            ms_geo_cluster
            WHERE
            ms_geo_cluster.lgaid = $lgaid");
    }
    public function GetWardList($lgaid)
    {
        return DbHelper::Table("SELECT
            ms_geo_ward.wardid,
            ms_geo_ward.lgaid,
            ms_geo_ward.ward
            FROM
            ms_geo_ward
            WHERE
            ms_geo_ward.lgaid = $lgaid");
    }
    public function GetDpList($wardid)
    {
        return DbHelper::Table("SELECT
            ms_geo_dp.dpid,
            ms_geo_dp.wardid,
            ms_geo_dp.dp
            FROM
            ms_geo_dp
            WHERE
            ms_geo_dp.wardid = $wardid");
    }
    public function GetDpListByLga($lgaid)
    {
        return DbHelper::Table("select `ms_geo_dp`.`dpid` AS `dpid`,`ms_geo_dp`.`wardid` AS `wardid`,`ms_geo_dp`.`dp` AS `dp` from ((`ms_geo_dp` join `ms_geo_ward` on(`ms_geo_dp`.`wardid` = `ms_geo_ward`.`wardid`)) join `ms_geo_lga` on(`ms_geo_ward`.`lgaid` = `ms_geo_lga`.`LgaId`)) where `ms_geo_lga`.`LgaId` = $lgaid");
    }
    public function GetMobilizerList($wardid)
    {
        return DbHelper::Table("SELECT
            usr_login.userid,
            usr_login.loginid,
            usr_identity.`first`,
            usr_identity.last,
            usr_identity.gender,
            usr_identity.phone
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            INNER JOIN usr_role ON usr_login.roleid = usr_role.roleid
            WHERE
            usr_login.geo_level = 'ward' AND
            usr_login.geo_level_id = $wardid AND 
            usr_role.role_code = 'AB021'");
    }
    public function GetUserByRoleInLevel($geo_level, $geo_level_id, $role_code)
    {
        return DbHelper::Table("SELECT
            usr_login.userid,
            usr_login.loginid,
            usr_identity.`first`,
            usr_identity.last,
            usr_identity.gender,
            usr_identity.phone
            FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
            INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
            INNER JOIN usr_role ON usr_login.roleid = usr_role.roleid
            WHERE
            usr_login.geo_level = '$geo_level' AND
            usr_login.geo_level_id = $geo_level_id AND 
            usr_role.role_code = '$role_code'");
    }
    public function GetGeoLevel()
    {
        return DbHelper::Table("SELECT
            sys_geo_level.id,
            sys_geo_level.geo_level,
            sys_geo_level.geo_value,
            sys_geo_level.geo_table
            FROM
            sys_geo_level");
    }
    public function GetDefaultSettings()
    {
        return DbHelper::Table("SELECT
            sys_default_settings.state,
            sys_default_settings.stateid,
            sys_default_settings.title
            FROM
            sys_default_settings");
    }
    #
    #   Adding community to it
    public function GetCommunityList($dpid)
    {
        return DbHelper::Table("SELECT ms_geo_comm.comid, ms_geo_comm.dpid, ms_geo_comm.community
            FROM ms_geo_comm
            WHERE
            ms_geo_comm.dpid = $dpid");
    }
    public function GetCommunityListByLga($lgaid)
    {
        return DbHelper::Table("select `ms_geo_comm`.`comid` AS `comid`,`ms_geo_comm`.`dpid` AS `dpid`,`ms_geo_comm`.`wardid` AS `wardid`,`ms_geo_comm`.`community` AS `community` from ((`ms_geo_comm` join `ms_geo_ward` on(`ms_geo_comm`.`wardid` = `ms_geo_ward`.`wardid`)) join `ms_geo_lga` on(`ms_geo_ward`.`lgaid` = `ms_geo_lga`.`LgaId`)) where `ms_geo_lga`.`LgaId` = $lgaid");
    }
    public function GetCommunityListByWard($wardid)
    {
        return DbHelper::Table("SELECT ms_geo_comm.comid, ms_geo_comm.dpid, ms_geo_comm.community
            FROM ms_geo_comm
            WHERE
            ms_geo_comm.wardid = $wardid");
    }
    /*
         *      Get All Data
         */
    public function GetAllLga()
    {
        return DbHelper::Table("SELECT
            ms_geo_state.StateId AS stateid,
            ms_geo_state.Fullname AS state,
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.Fullname AS lga,
            CONCAT(ms_geo_state.Fullname,' > ',ms_geo_lga.Fullname) AS combined
            FROM
            ms_geo_state
            INNER JOIN ms_geo_lga ON ms_geo_state.StateId = ms_geo_lga.StateId
            WHERE
            ms_geo_state.StateId = (SELECT sys_default_settings.stateid FROM sys_default_settings WHERE sys_default_settings.id = 1)");
    }
    public function GetAllCluster()
    {
        return DbHelper::Table("SELECT
            ms_geo_state.StateId AS stateid,
            ms_geo_state.Fullname AS state,
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.Fullname AS lga,
            ms_geo_cluster.clusterid,
            ms_geo_cluster.cluster,
            CONCAT(ms_geo_state.Fullname,' > ',ms_geo_lga.Fullname,' > ',ms_geo_cluster.cluster) AS combined
            FROM
            ms_geo_state
            INNER JOIN ms_geo_lga ON ms_geo_state.StateId = ms_geo_lga.StateId
            INNER JOIN ms_geo_cluster ON ms_geo_lga.LgaId = ms_geo_cluster.lgaid
            WHERE
            ms_geo_state.StateId = (SELECT sys_default_settings.stateid FROM sys_default_settings WHERE sys_default_settings.id = 1)");
    }
    public function GetAllWard()
    {
        return DbHelper::Table("SELECT
            ms_geo_state.StateId AS stateid,
            ms_geo_state.Fullname AS state,
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.Fullname AS lga,
            ms_geo_ward.wardid,
            ms_geo_ward.ward,
            CONCAT(ms_geo_state.Fullname,' > ',ms_geo_lga.Fullname,' > ',ms_geo_ward.ward) AS combined
            FROM
            ms_geo_state
            INNER JOIN ms_geo_lga ON ms_geo_state.StateId = ms_geo_lga.StateId
            INNER JOIN ms_geo_ward ON ms_geo_lga.LgaId = ms_geo_ward.lgaid
            WHERE
            ms_geo_state.StateId = (SELECT sys_default_settings.stateid FROM sys_default_settings WHERE sys_default_settings.id = 1)");
    }
    public function GetAllDp()
    {
        return DbHelper::Table("SELECT
            ms_geo_state.StateId AS stateid,
            ms_geo_state.Fullname AS state,
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.Fullname AS lga,
            ms_geo_ward.wardid,
            ms_geo_ward.ward,
            ms_geo_dp.dpid,
            ms_geo_dp.dp,
            CONCAT(ms_geo_state.Fullname,' > ',ms_geo_lga.Fullname,' > ',ms_geo_ward.ward,' > ',ms_geo_dp.dp) AS combined
            FROM
            ms_geo_state
            INNER JOIN ms_geo_lga ON ms_geo_state.StateId = ms_geo_lga.StateId
            INNER JOIN ms_geo_ward ON ms_geo_lga.LgaId = ms_geo_ward.lgaid
            INNER JOIN ms_geo_dp ON ms_geo_ward.wardid = ms_geo_dp.wardid
            WHERE
            ms_geo_state.StateId = (SELECT sys_default_settings.stateid FROM sys_default_settings WHERE sys_default_settings.id = 1)");
    }
    public function GetGeoLocationCodex($level = 'dp')
    {
        $where_condition = "  ";
        if ($level == 'dp') {
            $where_condition = "WHERE sys_geo_codex.geo_level = 'dp' ";
        } elseif ($level == 'ward') {
            $where_condition = "WHERE sys_geo_codex.geo_level = 'ward' ";
        } elseif ($level == 'lga') {
            $where_condition = "WHERE sys_geo_codex.geo_level = 'lga' ";
        } elseif ($level == 'state') {
            $where_condition = "WHERE sys_geo_codex.geo_level = 'state' ";
        } elseif ($level == 'all') {
            $where_condition = "  ";
        }
        return DbHelper::Table("SELECT
            sys_geo_codex.geo_level_id,
            sys_geo_codex.geo_level,
            sys_geo_codex.title,
            sys_geo_codex.geo_string,
            sys_geo_codex.stateid,
            sys_geo_codex.lgaid,
            sys_geo_codex.wardid,
            sys_geo_codex.dpid
            FROM
            sys_geo_codex
            $where_condition ");
    }

    public function GetGeoStructureId($geo_level, $geo_level_id)
    {
        return DbHelper::Table("SELECT `stateid`,`lgaid`,`clusterid`,`wardid`,`dpid`,`comid` FROM `sys_geo_codex` WHERE geo_level='$geo_level' AND geo_level_id=$geo_level_id");
    }
    #
    #   Public Static Methods
    #   
    public static function LogActivity($userid, $platform, $module, $description, $result, $longtitude = "", $latitude = "")
    {
        #
        return DbHelper::Insert("usr_user_activity", array(
            'userid' => $userid,
            'platform' => $platform,
            'module' => $module,
            'ip' => getUserIP(),
            'description' => $description,
            'longitude' => $longtitude,
            'latitude' => $latitude,
            'result' => $result,
            'created' => getNowDbDate()
        ));
    }
    #
    #   Get ID Badge Key
    public static function GetIdBadgeKey()
    {
        $key = DbHelper::Table("SELECT id_key FROM sys_default_settings WHERE id = 1");
        if (isset($key[0]['id_key'])) {
            return $key[0]['id_key'];
        } else {
            return '';
        }
    }
}
