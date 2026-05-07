<?php

include_once('lib/common.php');
include('lib/config.php');
include_once('lib/mysql.min.php');
//
log_system_access();
//  Log actions before leaving

# Detect and safe base directory
$system_base_directory = __DIR__;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$secret_key = file_get_contents('lib/privateKey.pem');

require('lib/vendor/autoload.php');    //JWT Autoload
/*
     *  configure required protocol access
     */
$jwt_token = $_COOKIE[$secret_code_token];

$token = JWT::decode($jwt_token, new Key($secret_key, 'HS512'));



if ($token->iss !== $issuer_claim && $token->nbf > $issuedat_claim->getTimestamp() || $token->exp < $issuedat_claim->getTimestamp()) {
    //
    http_response_code(404);
    echo json_encode(array(
        'result_code' => 404,
        'message' => 'Error:  404, Page not Found'
    ));
}

$current_userid = $token->user_id;
$current_loginid = $token->login_id;
$priviledges = $token->system_privilege;
$user_priviledge = $token->system_privilege;

#

$v_g_id = $token->user_id;
$v_g_fullname = $token->fullname;
$v_g_loginid = $token->login_id;
$v_g_geo_level = $token->geo_level;
$v_g_geo_level_id = $token->geo_level_id;
$v_g_rolename = $token->role;
$v_g_pass_change = $token->user_change_password;
$priv = $token->system_privilege;

/*
     *
     *  Datatable service, for table list data generation service
     * 
     */
#
#
#   Users Block
#
#   User List
if (CleanData('qid') == '001') {
    /*
         *      User List Table
         */
    $columns = array('userid', 'loginid', 'username', 'guid', 'user_group', 'role', 'first', 'middle', 'last', 'gender', 'email', 'phone', 'active', 'roleid', 'geo_level', 'geo_level_id', 'geo_string');
    //  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    #
    $geo_level = $v_g_geo_level;
    $geo_level_id = $v_g_geo_level_id;
    $where_key = $geo_level . "id";
    //  Where condition
    $where_condition = " WHERE `$where_key` = $geo_level_id ";
    $seed = 1;
    //  Where condition
    //$where_condition = "  ";
    //$seed = 0;
    #
    #   Filter column
    #
    $loginid = CleanData('lo');
    $active = CleanData('ac');
    $phone = CleanData('ph');
    $user_group = CleanData('gr');
    $name = CleanData('na');
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('gl_id');
    $bank_verification_status = CleanData('bv');    // parameters['failed' | 'success' | 'none']
    $role_id = CleanData('ri');                     # user filter by role id
    #
    if ($loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE  usr_login.loginid = '$loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.loginid = '$loginid'";
    }
    if ($active) {
        $active = $active == 'active' ? 1 : 0;
        if ($seed == 0) {
            $where_condition = " WHERE  usr_login.active = '$active' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.active = '$active' ";
    }
    if ($user_group) {
        if ($seed == 0) {
            $where_condition = " WHERE  usr_login.user_group LIKE '%$user_group%' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.user_group LIKE '%$user_group%' ";
    }
    if ($phone) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_identity.phone = '$phone' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_identity.phone = '$phone' ";
    }
    if ($name) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_identity.`first` LIKE '%$name%' OR
                usr_identity.middle LIKE '%$name%' OR
                usr_identity.last LIKE '%$name%' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_identity.`first` LIKE '%$name%' OR
                usr_identity.middle LIKE '%$name%' OR
                usr_identity.last LIKE '%$name%' ";
    }
    if ($geo_level && $geo_level_id) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_login.geo_level = '$geo_level' AND usr_login.geo_level_id = '$geo_level_id' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.geo_level = '$geo_level' AND usr_login.geo_level_id = '$geo_level_id' ";
    }
    if ($bank_verification_status) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_finance.verification_status = '$bank_verification_status' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_finance.verification_status = '$bank_verification_status' ";
    }
    if ($role_id) {
        if ($seed == 0) {
            $where_condition = " WHERE  usr_login.roleid = '$role_id' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.roleid = '$role_id'";
    }
    //  Query composition
    $sql_query = "SELECT
        usr_login.userid,
        usr_login.loginid,
        usr_login.username,
        usr_login.guid,
        usr_login.user_group,
        usr_role.title AS role,
        usr_identity.`first`,
        usr_identity.middle,
        usr_identity.last,
        usr_identity.gender,
        usr_identity.email,
        usr_identity.phone,
        usr_login.active,
        usr_login.roleid,
        usr_login.geo_level,
        usr_login.geo_level_id,
        '' AS pick,
        sys_geo_codex.title AS geo_title,
        sys_geo_codex.geo_string,
        usr_finance.is_verified,
        usr_finance.verification_status
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
        LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
        LEFT JOIN usr_finance ON usr_login.userid = usr_finance.userid
        $where_condition
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        LEFT JOIN usr_role ON usr_login.roleid = usr_role.roleid
        LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
        LEFT JOIN usr_finance ON usr_login.userid = usr_finance.userid
        $where_condition";


    //  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);


    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );

    echo json_encode($json_data);

    //echo $sql_query;

}
#   User Group list
elseif (CleanData('qid') == '002') {
    /*
         *      User group Table List
         */
    $columns = array('total', 'user_group');
    //  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    //  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filter column
    #
    $user_group = CleanData('gr');
    #
    if ($user_group) {
        if ($seed == 0) {
            $where_condition = " WHERE  usr_login.user_group LIKE '%$user_group%' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.user_group LIKE '%$user_group%' ";
    }
    //  Query composition
    $sql_query = "SELECT
        Count(usr_login.userid) AS total,
        usr_login.user_group,
        '' AS pick
        FROM
        usr_login
        $where_condition
        GROUP BY
        usr_login.user_group
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(DISTINCT usr_login.user_group)
        FROM
        usr_login
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#
#   Activity Management
#
#   Activity list
elseif (CleanData('qid') == '101') {
    /*
         *      Training list table
         */
    $columns = array('trainingid', 'title', 'geo_location', 'location_id', 'guid', 'active', 'description', 'start_date', 'end_date', 'participant_count', 'created', 'updated');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filter column
    $id = CleanData('id');
    $name = CleanData('tr');
    $active = CleanData('ac');

    #
    if ($id) {
        if ($seed == 0) {
            $where_condition = " WHERE tra_training.trainingid = $id ";
            $seed = 1;
        } else
            $where_condition .= " AND tra_training.trainingid = $id ";
    }
    if ($name) {
        if ($seed == 0) {
            $where_condition = " WHERE tra_training.title LIKE '%$name%' ";
            $seed = 1;
        } else
            $where_condition .= " AND tra_training.title LIKE '%$name%' ";
    }
    if ($active) {
        $active = $active == 'active' ? 1 : 0;
        if ($seed == 0) {
            $where_condition = " WHERE  tra_training.active = '$active' ";
            $seed = 1;
        } else
            $where_condition .= " AND tra_training.active = '$active' ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        tra_training.trainingid,
        LPAD(tra_training.trainingid,3,0) AS ui_id,
        tra_training.title,
        tra_training.geo_location,
        tra_training.location_id,
        tra_training.guid,
        tra_training.active,
        tra_training.description,
        DATE_FORMAT(tra_training.start_date,'$dateMed_format') AS start_date,
        tra_training.start_date AS db_start_date,
        DATE_FORMAT(tra_training.end_date,'$dateMed_format') AS end_date,
        tra_training.end_date AS db_end_date,
        tra_training.participant_count,
        DATE_FORMAT(tra_training.created,'$date_format') AS created,
        DATE_FORMAT(tra_training.updated,'$date_format') AS updated,
        '' AS pick
        FROM
        tra_training       
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        tra_training  
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   Participant list
elseif (CleanData('qid') == '102') {
    /*
         *      Training participant list
         */
    $columns = array('participant_id', 'userid', 'first', 'middle', 'last', 'gender', 'phone', 'email', 'loginid', 'username', 'active');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 1;
    #
    #   Filter column
    $name = CleanData('na');
    $loginid = CleanData('lo');
    $training_id = CleanData('id');
    #   Filtered by Geo-Level
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    #
    if ($name) {
        if ($seed == 0) {
            $where_condition = " WHERE CONCAT_WS(' ', usr_identity.`first`,usr_identity.middle,usr_identity.last) LIKE '%$name%' ";
            $seed = 1;
        } else
            $where_condition .= " AND  CONCAT_WS(' ', usr_identity.`first`,usr_identity.middle,usr_identity.last) LIKE '%$name%'  ";
    }
    if ($loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_login.loginid = '$loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.loginid = '$loginid' ";
    }
    if ($geo_level && $geo_level_id) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        tra_participants.participant_id,
        usr_identity.userid,
        usr_identity.`first`,
        usr_identity.middle,
        usr_identity.last,
        usr_identity.gender,
        usr_identity.phone,
        usr_identity.email,
        usr_login.loginid,
        usr_login.username,
        usr_login.user_group,
        usr_login.active,
        '' AS pick,
        sys_geo_codex.title AS geo_title,
        sys_geo_codex.geo_string,
        sys_geo_codex.geo_level
        FROM
        tra_participants
        INNER JOIN usr_identity ON tra_participants.userid = usr_identity.userid
        INNER JOIN usr_login ON tra_participants.userid = usr_login.userid
        LEFT JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
        WHERE
        usr_login.active = 1 
        AND tra_participants.trainingid = $training_id     
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        tra_participants
        INNER JOIN usr_identity ON tra_participants.userid = usr_identity.userid
        INNER JOIN usr_login ON tra_participants.userid = usr_login.userid
        WHERE
        tra_participants.trainingid = $training_id     
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   Attendance list
elseif (CleanData('qid') == '103') {
    /*
         *      Training attendance list
         */
    $columns = array('loginid', 'fullname', 'phone', 'at_type', 'collected', 'bio_auth', 'geo_title', 'geo_level', 'geo_string', 'role', 'attendant_id', 'userid', 'participant_id');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 1;
    #
    #   Filtered by Geo-Level
    $session_id = CleanData('se');
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    #

    if ($geo_level && $geo_level_id) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        usr.loginid,
        CONCAT_WS( ' ', usr.`first`, usr.middle, usr.last ) AS fullname,
        usr.phone,
        tra_attendant.at_type,
        tra_attendant.collected,
        IF
        ( tra_attendant.bio_auth = 1, 'True', 'False' ) AS `bio_auth`,
        sys_geo_codex.title AS geo_title,
        sys_geo_codex.geo_level,
        sys_geo_codex.geo_string,
        usr.role,
        tra_attendant.attendant_id,
        usr.userid,
        tra_participants.participant_id,
        '' AS pick 
        FROM
        tra_attendant
        INNER JOIN tra_participants ON tra_attendant.participant_id = tra_participants.participant_id
        INNER JOIN (
        SELECT
            usr_login.userid,
            usr_login.loginid,
            usr_login.geo_level,
            usr_login.geo_level_id,
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
        LEFT JOIN sys_geo_codex ON usr.geo_level = sys_geo_codex.geo_level AND usr.geo_level_id = sys_geo_codex.geo_level_id
        WHERE
        tra_attendant.session_id = $session_id   
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        tra_attendant
        INNER JOIN tra_participants ON tra_attendant.participant_id = tra_participants.participant_id
        INNER JOIN (
        SELECT
            usr_login.userid,
            usr_login.loginid,
            usr_login.geo_level,
            usr_login.geo_level_id,
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
        LEFT JOIN sys_geo_codex ON usr.geo_level = sys_geo_codex.geo_level AND usr.geo_level_id = sys_geo_codex.geo_level_id
        WHERE
        tra_attendant.session_id = $session_id   
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#
#   e-Netcard Management Block
#
#   Movement list
elseif (CleanData('qid') == '201') {
    /*
         *      Training participant list
         */
    $columns = array('mtid', 'total', 'move_type', 'origin_level', 'origin', 'destination_level', 'destination', 'user_fullname', 'created');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filter column
    $move_type = CleanData('mt');
    #
    if ($move_type) {
        if ($seed == 0) {
            $where_condition = " WHERE nc_netcard_movement.move_type = '$move_type' ";
            $seed = 1;
        } else
            $where_condition .= " AND nc_netcard_movement.move_type = '$move_type' ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        nc_netcard_movement.mtid,
        nc_netcard_movement.total,
        nc_netcard_movement.move_type,
        nc_netcard_movement.origin_level,
        (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE nc_netcard_movement.origin_level = sys_geo_codex.geo_level AND nc_netcard_movement.origin_level_id = sys_geo_codex.geo_level_id) AS origin,
        nc_netcard_movement.destination_level,
        (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE nc_netcard_movement.destination_level = sys_geo_codex.geo_level AND nc_netcard_movement.destination_level_id = sys_geo_codex.geo_level_id) AS destination,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS user_fullname,
        nc_netcard_movement.created,
        '' AS pick
        FROM
        nc_netcard_movement
        LEFT JOIN usr_identity ON nc_netcard_movement.userid = usr_identity.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        nc_netcard_movement
        LEFT JOIN usr_identity ON nc_netcard_movement.userid = usr_identity.userid
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   e-Netcard Allocation forward 
elseif (CleanData('qid') == '202') {
    /*
         *      e-Netcard allocation forward
         */
    $columns = array('atid', 'transfer_by', 'total', 'a_type', 'origin', 'destination', 'destination_userid', 'mobilizer', 'created');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  WHERE  nc_netcard_allocation.a_type = 'forward'  ";
    $seed = 1;
    #
    #   Filter column is empty for now
    #   Filter column 
    $requester_loginid = CleanData("rid");
    $mobilizer_loginid = CleanData("mid");
    $requested_date = CleanData("rda");
    #
    if ($requester_loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE b.loginid = '$requester_loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND b.loginid = '$requester_loginid' ";
    }
    if ($mobilizer_loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE a.loginid = '$mobilizer_loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND a.loginid = '$mobilizer_loginid' ";
    }
    if ($requested_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(nc_netcard_allocation.created) = DATE('$requested_date') ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE(nc_netcard_allocation.created) = DATE('$requested_date') ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        nc_netcard_allocation.atid,
        b.fullname AS transfer_by,
        nc_netcard_allocation.total,
        nc_netcard_allocation.a_type,
        (SELECT sys_geo_codex.geo_string FROM sys_geo_codex WHERE nc_netcard_allocation.origin = sys_geo_codex.geo_level AND nc_netcard_allocation.origin_id = sys_geo_codex.geo_level_id) AS origin,
        nc_netcard_allocation.destination,
        nc_netcard_allocation.destination_userid,
        CONCAT(a.fullname,' (',a.loginid,')') AS mobilizer,
        nc_netcard_allocation.created,
        '' AS pick
        FROM
        nc_netcard_allocation
        LEFT JOIN
        (SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) a ON nc_netcard_allocation.destination_userid = a.userid
        LEFT JOIN
        (SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) b ON nc_netcard_allocation.userid = b.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        nc_netcard_allocation
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   e-Netcard Allocation reverse order & fulfilment table
elseif (CleanData('qid') == '203') {
    /*
         *      e-Netcard Allocation reverse order
         */
    $columns = array('orderid', 'mobilizer', 'mobilizer_loginid', 'mobilizer_userid', 'requester_id', 'requester', 'requester_loginid', 'total_order', 'total_fulfilment', 'status', 'created', 'fulfilled_date');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "   ";
    $seed = 0;
    #
    #   Filter column 
    $requester_loginid = CleanData("rid");
    $mobilizer_loginid = CleanData("mid");
    $requested_date = CleanData("rda");
    #
    if ($requester_loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE b.loginid = '$requester_loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND b.loginid = '$requester_loginid' ";
    }
    if ($mobilizer_loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE a.loginid = '$mobilizer_loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND a.loginid = '$mobilizer_loginid' ";
    }
    if ($requested_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(nc_netcard_allocation_order.created) = DATE('$requested_date') ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE(nc_netcard_allocation_order.created) = DATE('$requested_date') ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        nc_netcard_allocation_order.orderid,
        a.fullname AS mobilizer,
        a.loginid AS mobilizer_loginid,
        a.userid AS mobilizer_userid,
        nc_netcard_allocation_order.requester_id,
        b.fullname AS requester,
        b.loginid AS requester_loginid,
        nc_netcard_allocation_order.total_order,
        nc_netcard_allocation_order.total_fulfilment,
        nc_netcard_allocation_order.`status`,
        nc_netcard_allocation_order.created,
        nc_netcard_allocation_order.fulfilled_date,
        '' AS pick
        FROM
        nc_netcard_allocation_order
        LEFT JOIN
        (SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) a ON nc_netcard_allocation_order.hhm_id = a.userid
        LEFT JOIN
        (SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) b ON nc_netcard_allocation_order.requester_id = b.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";
    # count
    $sql_count = "SELECT
        COUNT(*)
        FROM
        nc_netcard_allocation_order
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   e-Netcard direct Allocation reverse order & fulfilment table
#   e-Netcard Online History
elseif (CleanData('qid') == '204') {
    /*
         *      e-Netcard Allocation reverse order
         */
    $columns = array('id', 'mobilizer', 'mobilizer_loginid', 'mobilizer_userid', 'requester', 'requester_loginid', 'requester_userid', 'amount', 'created', 'fulfilled_date');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "   ";
    $seed = 0;
    #
    #   Filter column is empty for now
    #   Filter column 
    $requester_loginid = CleanData("rid");
    $mobilizer_loginid = CleanData("mid");
    $requested_date = CleanData("rda");
    #
    if ($requester_loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE b.loginid = '$requester_loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND b.loginid = '$requester_loginid' ";
    }
    if ($mobilizer_loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE a.loginid = '$mobilizer_loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND a.loginid = '$mobilizer_loginid' ";
    }
    if ($requested_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(nc_netcard_allocation_online.created) = DATE('$requested_date') ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE(nc_netcard_allocation_online.created) = DATE('$requested_date') ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        nc_netcard_allocation_online.id,
        a.fullname AS mobilizer,
        a.loginid AS mobilizer_loginid,
        a.userid AS mobilizer_userid,
        b.fullname AS requester,
        b.loginid AS requester_loginid,
        b.userid AS requester_userid,
        nc_netcard_allocation_online.amount,
        nc_netcard_allocation_online.created,
        '' AS pick 
        FROM
        nc_netcard_allocation_online
        LEFT JOIN (
        SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
        FROM
            usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
        ) a ON nc_netcard_allocation_online.hhm_id = a.userid
        LEFT JOIN (
        SELECT
            usr_login.userid,
            usr_login.loginid,
            CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
        FROM
            usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
        ) b ON nc_netcard_allocation_online.requester_id = b.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";
    # count
    $sql_count = "SELECT
        COUNT(*)
        FROM
        nc_netcard_allocation_order
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   e-Netcard wallent banlance pushed online transaction able
elseif (CleanData('qid') == '205') {
    $columns = array('id', 'loginid', 'fullname', 'phone', 'device_serial', 'amount', 'geo_string', 'amount', 'created');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "   ";
    $seed = 0;
    #
    #   Filter column is empty for now
    #   Filter column 
    $loginid = CleanData("lid");
    $device_serial = CleanData("dse");
    $geo_level = CleanData("lid");
    $geo_level_id = CleanData("lid");
    #
    if ($loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_login.loginid = '$loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.loginid = '$loginid' ";
    }
    if ($device_serial) {
        if ($seed == 0) {
            $where_condition = " WHERE nc_netcard_unused_pushed.device_serial = '$device_serial' ";
            $seed = 1;
        } else
            $where_condition .= " AND nc_netcard_unused_pushed.device_serial = '$device_serial' ";
    }
    if ($geo_level && $geo_level_id) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_login.geo_level = '$geo_level' AND  usr_login.geo_level_id = '$geo_level_id' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.geo_level = '$geo_level' AND  usr_login.geo_level_id = '$geo_level_id' ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        nc_netcard_unused_pushed.id,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.gender) AS fullname,
        usr_identity.phone,
        nc_netcard_unused_pushed.device_serial,
        nc_netcard_unused_pushed.amount,
        sys_geo_codex.geo_string,
        nc_netcard_unused_pushed.created
        FROM
        nc_netcard_unused_pushed
        INNER JOIN usr_login ON nc_netcard_unused_pushed.hhm_id = usr_login.userid
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id        
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";
    # count
    $sql_count = "SELECT
        COUNT(*)
        FROM
        nc_netcard_unused_pushed
        INNER JOIN usr_login ON nc_netcard_unused_pushed.hhm_id = usr_login.userid
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#
#   Mobilization Block
#
elseif (CleanData('qid') == '301') {
    $columns = array('hhid', 'geo_string', 'geo_level', 'geo_name', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'collected_date', 'mobilizer', 'mobilizer_loginid', 'mobilizer_userid');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "   ";
    $seed = 0;
    #
    #
    #   Filter column
    #   Filter by mobilizer's login id
    $loginid = CleanData('lgid');
    #   Filtered by mobilized date
    $mob_date = CleanData('mdt');
    #   Filtered by Geo-Level
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    #
    if ($loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE a.loginid = '$loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND a.loginid = '$loginid' ";
    }
    if ($mob_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$mob_date') ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$mob_date') ";
    }
    if ($geo_level && $geo_level_id) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        hhm_mobilization.hhid,
        sys_geo_codex.geo_string,
        sys_geo_codex.geo_level,
        sys_geo_codex.title AS geo_name,
        hhm_mobilization.hoh_first,
        hhm_mobilization.hoh_last,
        hhm_mobilization.hoh_phone,
        hhm_mobilization.hoh_gender,
        hhm_mobilization.family_size,
        hhm_mobilization.allocated_net,
        hhm_mobilization.location_description,
        hhm_mobilization.collected_date,
        a.fullname AS mobilizer,
        a.loginid AS mobilizer_loginid,
        a.userid AS mobilizer_userid,
        '' AS pick
        FROM
        hhm_mobilization
        INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.geo_level_id AND sys_geo_codex.geo_value = 10
        LEFT JOIN
        (SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";
    # count
    $sql_count = "SELECT
        COUNT(*)
        FROM
        hhm_mobilization
        LEFT JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.geo_level_id AND sys_geo_codex.geo_value = 10
        LEFT JOIN
        (SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_mobilization.hhm_id = a.userid
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#
#   Distribution Block
#
elseif (CleanData('qid') == '401') {
    $columns = array('dis_id', 'geo_level', 'dpid', 'geo_string', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'location_description', 'etoken_serial', 'collected_nets', 'is_gs_one_record', 'recorder_name', 'recorder_loginid', 'collected_date', 'created');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = " WHERE sys_geo_codex.geo_level = 'dp'  ";
    $seed = 1;
    #
    #
    #   Filter column
    #   Filtered by distribution date
    $dis_date = CleanData('dst');
    #   Filtered by Geo-Level
    $geo_level = CleanData('gl');
    $geo_level_id = CleanData('glid');
    #
    if ($dis_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(hhm_distribution.collected_date) = DATE('$dis_date') ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE(hhm_distribution.collected_date) = DATE('$dis_date') ";
    }
    if ($geo_level && $geo_level_id) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        hhm_distribution.dis_id,
        sys_geo_codex.geo_level,
        sys_geo_codex.dpid,
        sys_geo_codex.geo_string,
        hhm_mobilization.hoh_first,
        hhm_mobilization.hoh_last,
        hhm_mobilization.hoh_phone,
        hhm_mobilization.hoh_gender,
        hhm_mobilization.family_size,
        hhm_mobilization.allocated_net,
        hhm_mobilization.location_description,
        hhm_mobilization.etoken_serial,
        hhm_distribution.collected_nets,
        IF
            ( hhm_distribution.is_gs_net, 'Yes', 'No' ) AS is_gs_one_record,
        a.fullname AS recorder_name,
        a.loginid AS recorder_loginid,
        b.fullname AS distributor_name,
        b.loginid AS distributor_loginid,
        hhm_distribution.collected_date,
        hhm_distribution.created,
        hhm_mobilization.longitude,
        hhm_mobilization.Latitude AS latitude,
        '' AS pick
        FROM
        hhm_distribution
        INNER JOIN nc_token ON hhm_distribution.etoken_id = nc_token.tokenid
        INNER JOIN hhm_mobilization ON hhm_distribution.hhid = hhm_mobilization.hhid
        LEFT JOIN sys_geo_codex ON hhm_distribution.dp_id = sys_geo_codex.dpid
        LEFT JOIN (
            SELECT
                usr_login.userid,
                usr_login.loginid,
                CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
            FROM
                usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
            ) AS a ON hhm_distribution.recorder_id = a.userid
            LEFT JOIN (
            SELECT
                usr_login.userid,
                usr_login.loginid,
                CONCAT_WS( ' ', usr_identity.`first`, usr_identity.middle, usr_identity.last ) AS fullname 
            FROM
                usr_login
            INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid 
            ) AS b ON hhm_distribution.distributor_id = b.userid        
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";
    # count
    $sql_count = "SELECT
        COUNT(*)
        FROM
        hhm_distribution
        INNER JOIN nc_token ON hhm_distribution.etoken_id = nc_token.tokenid
        INNER JOIN hhm_mobilization ON hhm_distribution.hhid = hhm_mobilization.hhid
        LEFT JOIN sys_geo_codex ON hhm_distribution.dp_id = sys_geo_codex.dpid
        LEFT JOIN (SELECT
        usr_login.userid,
        usr_login.loginid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS a ON hhm_distribution.recorder_id = a.userid
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   Unredeemed e-Token 
elseif (CleanData('qid') == '402') {
    $columns = array('hhid', 'hoh_first', 'hoh_last', 'hoh_phone', 'hoh_gender', 'family_size', 'allocated_net', 'collected_date');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = " WHERE hhm_distribution.hhid IS NULL  ";
    $seed = 1;
    #
    #
    #   Filter column
    #   Filtered by 
    $hh_phone = CleanData('pph');
    $etoken_serial = CleanData('ets');
    $etoken_pin = CleanData('etp');
    $mobilization_date = CleanData('mdt');
    $geo_level = CleanData('glv');
    $geo_level_id = CleanData('gid');
    #
    if ($hh_phone) {
        if ($seed == 0) {
            $where_condition = " WHERE hhm_mobilization.hoh_phone LIKE '%$hh_phone%' ";
            $seed = 1;
        } else
            $where_condition .= " AND hhm_mobilization.hoh_phone LIKE '%$hh_phone%' ";
    }
    if ($etoken_serial) {
        if ($seed == 0) {
            $where_condition = " WHERE hhm_mobilization.etoken_serial LIKE '%$etoken_serial%' ";
            $seed = 1;
        } else
            $where_condition .= " AND hhm_mobilization.etoken_serial LIKE '%$etoken_serial%' ";
    }
    if ($etoken_pin) {
        if ($seed == 0) {
            $where_condition = " WHERE hhm_mobilization.etoken_pin LIKE '%$etoken_pin%' ";
            $seed = 1;
        } else
            $where_condition .= " AND hhm_mobilization.etoken_pin LIKE '%$etoken_pin%' ";
    }
    if ($mobilization_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(hhm_mobilization.collected_date) = DATE('$mobilization_date') ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE(hhm_mobilization.collected_date) = DATE('$mobilization_date') ";
    }
    if ($geo_level && $geo_level_id) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id  ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_level_id ";
    }
    #
    #  Query composition

    $sql_query = "SELECT
        hhm_mobilization.hhid,
        hhm_mobilization.hoh_first,
        hhm_mobilization.hoh_last,
        hhm_mobilization.hoh_phone,
        hhm_mobilization.hoh_gender,
        hhm_mobilization.family_size,
        hhm_mobilization.hod_mother,
        hhm_mobilization.allocated_net,
        hhm_mobilization.sleeping_space,
        hhm_mobilization.adult_female,
        hhm_mobilization.adult_male,
        hhm_mobilization.children,
        hhm_mobilization.etoken_serial,
        hhm_mobilization.etoken_pin,
        sys_geo_codex.geo_string,
        hhm_mobilization.collected_date
        FROM
        hhm_mobilization
        LEFT JOIN hhm_distribution ON hhm_mobilization.hhid = hhm_distribution.hhid
        INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";
    # count
    $sql_count = "SELECT
        COUNT(*)
        FROM
        hhm_mobilization
        LEFT JOIN hhm_distribution ON hhm_mobilization.hhid = hhm_distribution.hhid
        INNER JOIN sys_geo_codex ON hhm_mobilization.dp_id = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#   Sytem Admin: User activity
#
elseif (CleanData('qid') == '501') {
    $columns = array('id', 'userid', 'loginid', 'fullname', 'platform', 'module', 'ip', 'description', 'result', 'created');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $userid = CleanData("uid");     #   by user ID
    $loginid = CleanData("lid");    #   by user login id
    $platform = CleanData("pla");   #   by platform [ web | mobile | pos ]
    $module = CleanData("mod");     #   by module
    $result = CleanData("res");     #   by result [ success | failed ]
    #

    if ($userid) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_user_activity.userid = $userid  ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_user_activity.userid = $userid ";
    }
    if ($loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE usr.loginid = '$loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr.loginid = '$loginid' ";
    }
    if ($platform) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_user_activity.platform = '$platform' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_user_activity.platform = '$platform' ";
    }
    if ($module) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_user_activity.module = '$module' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_user_activity.module = '$module' ";
    }
    if ($result) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_user_activity.result = '$result' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_user_activity.result = '$result' ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        usr_user_activity.id,
        usr_user_activity.userid,
        usr.loginid,
        usr.fullname,
        usr_user_activity.platform,
        usr_user_activity.module,
        usr_user_activity.ip,
        usr_user_activity.description,
        usr_user_activity.result,
        usr_user_activity.created
        FROM
        usr_user_activity
        LEFT JOIN (SELECT
        usr_login.loginid,
        usr_login.userid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS usr ON usr_user_activity.userid = usr.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        usr_user_activity
        LEFT JOIN (SELECT
        usr_login.loginid,
        usr_login.userid,
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) AS usr ON usr_user_activity.userid = usr.userid
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#   Device Management: Device list
#
elseif (CleanData('qid') == '601') {
    $columns = array('id', 'device_name', 'device_id', 'guid', 'serial_no', 'device_type', 'active', 'connected', 'created', 'updated');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $active = CleanData("act");     #   Active
    $serial_no = CleanData("sno");    #   Serial

    if (strval($active) == '0' || strval($active) == '1') {
        if ($seed == 0) {
            $where_condition = " WHERE sys_device_registry.active = $active  ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_device_registry.active = $active ";
    }
    if ($serial_no) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_device_registry.serial_no = '$serial_no' ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_device_registry.serial_no = '$serial_no' ";
    }

    #
    #  Query composition
    $sql_query = "SELECT
        sys_device_registry.id,
        sys_device_registry.device_name,
        sys_device_registry.device_id,
        sys_device_registry.guid,
        sys_device_registry.serial_no,
        sys_device_registry.device_type,
        sys_device_registry.imei1,
        sys_device_registry.imei2,
        sys_device_registry.phone_serial,
        sys_device_registry.sim_network,
        sys_device_registry.sim_serial,
        sys_device_registry.active,
        sys_device_registry.connected,
        sys_device_registry.created,
        sys_device_registry.updated,
        '' AS pick
        FROM
        sys_device_registry
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        sys_device_registry
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
# Device login table
elseif (CleanData('qid') == '602') {
    $columns = array('id', 'device_name', 'device_id', 'serial_no', 'device_type', 'active', 'loginid', 'first', 'middle', 'last', 'created');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $date = CleanData("dat");     #  date
    $loginid = CleanData("lid");    #   loginid 
    $serial_no = CleanData("sno");    #   device serial

    if ($date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(sys_device_login.created) = DATE('$date')  ";
            $seed = 1;
        } else
            $where_condition .= " AND  DATE(sys_device_login.created) = DATE('$date') ";
    }
    if ($loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_device_login.loginid = '$loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_device_login.loginid = '$loginid' ";
    }
    if ($serial_no) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_device_login.device_serial = '$serial_no' ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_device_login.device_serial = '$serial_no' ";
    }

    #
    #  Query composition
    $sql_query = "SELECT
        sys_device_login.id,
        sys_device_registry.device_name,
        sys_device_registry.device_id,
        sys_device_registry.serial_no,
        sys_device_registry.device_type,
        sys_device_registry.active,
        sys_device_login.loginid,
        usr_identity.`first`,
        usr_identity.middle,
        usr_identity.last,
        sys_device_login.created,
        '' AS pick
        FROM
        sys_device_login
        LEFT JOIN sys_device_registry ON sys_device_login.device_serial = sys_device_registry.serial_no
        LEFT JOIN usr_login ON sys_device_login.loginid = usr_login.loginid
        LEFT JOIN usr_identity ON usr_login.userid = usr_identity.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        sys_device_login
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#   SMC module
#
# Child registry table
elseif (CleanData('qid') == '701') {
    $columns = array('hh_token', 'hoh_name', 'hoh_phone', 'beneficiary_id', 'name', 'gender', 'dob', 'created', 'updated', 'geo_string');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $hh_token = CleanData("hht");     #  head od household token or ID
    $hh_name = CleanData("hhn");    #   head od household name
    $hh_phone = CleanData("hhp");    #   head od household phone
    $child_id = CleanData("chi");     #  Beneficiary ID
    $child_name = CleanData("chn");    #   Beneficiary name
    $reg_date = CleanData("rda");    #   Registration date
    $geo_id = CleanData("gid");    #   Geo_level_id
    $geo_level = CleanData("glv");    #   Geo-Level

    if ($hh_token) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_child_household.hh_token = '$hh_token'  ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_child_household.hh_token = '$hh_token' ";
    }
    if ($hh_name) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_child_household.hoh_name LIKE '%$hh_name%' ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_child_household.hoh_name LIKE '%$hh_name%' ";
    }
    if ($hh_phone) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_child_household.hoh_phone LIKE '%$hh_phone%' ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_child_household.hoh_phone LIKE '%$hh_phone%' ";
    }
    if ($child_id) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_child.beneficiary_id LIKE '%$child_id%' ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_child.beneficiary_id LIKE '%$child_id%' ";
    }
    if ($child_name) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_child.`name` LIKE '%$child_name%' ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_child.`name` LIKE '%$child_name%' ";
    }
    if ($reg_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE('$reg_date') = DATE(smc_child.created) ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE('$reg_date') = DATE(smc_child.created) ";
    }
    if ($geo_id && $geo_level) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = 'dp' AND sys_geo_codex.geo_level_id = $geo_id ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = 'dp' AND sys_geo_codex.geo_level_id = $geo_id ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        smc_child_household.hh_token,
        smc_child_household.hoh_name,
        smc_child_household.hoh_phone,
        smc_child.beneficiary_id,
        smc_child.`name`,
        smc_child.gender,
        smc_child.dob,
        smc_child.created,
        smc_child.updated,
        sys_geo_codex.geo_string
        FROM
        smc_child_household
        INNER JOIN smc_child ON smc_child_household.hh_token = smc_child.hh_token
        INNER JOIN sys_geo_codex ON smc_child.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        smc_child_household
        INNER JOIN smc_child ON smc_child_household.hh_token = smc_child.hh_token
        INNER JOIN sys_geo_codex ON smc_child.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
# Drug Administration table
elseif (CleanData('qid') == '702') {
    $columns = array('geo_string', 'period', 'name', 'beneficiary_id', 'dob', 'drug', 'redose', 'redose_reason', 'eligibility', 'not_eligible_reason', 'collected_date');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $periodid = CleanData("pid");     #  period ID
    $is_eligible = CleanData("ise");    #  The child is eligible must be yes
    $is_redose = CleanData("isr");    #  Redose must be yes
    $reg_date = CleanData("rda");    #   Registration date
    $geo_id = CleanData("gid");    #   Geo_level_id
    $geo_level = CleanData("glv");    #   Geo-Level
    $beneficiary_id = CleanData("bid");    #   Beneficiary ID

    if ($periodid) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_drug_administration.periodid = $periodid ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_drug_administration.periodid = $periodid ";
    }
    if ($is_eligible == 'yes') {
        if ($seed == 0) {
            $where_condition = " WHERE smc_drug_administration.is_eligible = 1 ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_drug_administration.is_eligible = 1 ";
    }
    if ($is_eligible == 'no') {
        if ($seed == 0) {
            $where_condition = " WHERE smc_drug_administration.is_eligible = 0 ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_drug_administration.is_eligible = 0 ";
    }
    if ($is_redose == 'yes') {
        if ($seed == 0) {
            $where_condition = " WHERE smc_drug_administration.redose_count = 1 ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_drug_administration.redose_count = 1 ";
    }
    if ($is_redose == 'no') {
        if ($seed == 0) {
            $where_condition = " WHERE smc_drug_administration.redose_count = 0 ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_drug_administration.redose_count = 0 ";
    }
    if ($reg_date) {
        if ($seed == 0) {
            $where_condition = " WHERE DATE(smc_drug_administration.collected_date) = DATE('$reg_date') ";
            $seed = 1;
        } else
            $where_condition .= " AND DATE(smc_drug_administration.collected_date) = DATE('$reg_date') ";
    }
    if ($geo_id && $geo_level) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
    }
    if ($beneficiary_id) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_child.beneficiary_id = '$beneficiary_id' ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_child.beneficiary_id = '$beneficiary_id' ";
    }
    #
    #  Query composition
    $sql_query = "SELECT
        sys_geo_codex.geo_string,
        smc_period.title AS period,
        smc_child.`name`,
        smc_child.beneficiary_id,
        smc_child.dob,
        smc_drug_administration.drug,
        if(smc_drug_administration.redose_count,'Redosed','NA') AS redose,
        smc_drug_administration.redose_reason,
        smc_drug_administration.collected_date,
        if(smc_drug_administration.is_eligible = 0,'Not Eligible','NA') AS eligibility,
        smc_drug_administration.not_eligible_reason
        FROM
        smc_drug_administration
        INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
        INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        smc_drug_administration
        INNER JOIN smc_child ON smc_drug_administration.beneficiary_id = smc_child.beneficiary_id
        INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
# Referral  table
elseif (CleanData('qid') == '703') {
    $columns = array('adm_id', 'period', 'name', 'beneficiary_id', 'refer_type', 'attended', 'geo_string', 'referred_date', 'attended_date');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #  Where condition
    $where_condition = " WHERE smc_drug_administration.is_refer = 1 ";
    $seed = 1;
    #
    #   Filters
    $periodid = CleanData("pid");       #  period ID
    $geo_id = CleanData("gid");         #   Geo_level_id
    $geo_level = CleanData("glv");      #   Geo-Level
    $attended = CleanData('atd');       #   Attended filter
    #
    if ($periodid) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_drug_administration.periodid IN ($periodid) ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_drug_administration.periodid IN ($periodid) ";
    }
    if ($geo_id && $geo_level) {
        if ($seed == 0) {
            $where_condition = " WHERE sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
            $seed = 1;
        } else
            $where_condition .= " AND sys_geo_codex.geo_level = '$geo_level' AND sys_geo_codex.geo_level_id = $geo_id ";
    }
    if (strtolower($attended) == 'yes') {
        if ($seed == 0) {
            $where_condition = " WHERE smc_referer_record.ref_id IS NOT NULL ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_referer_record.ref_id IS NOT NULL ";
    }
    if (strtolower($attended) == 'no') {
        if ($seed == 0) {
            $where_condition = " WHERE smc_referer_record.ref_id IS NULL ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_referer_record.ref_id IS NULL ";
    }

    #
    #  Query composition
    $sql_query = "SELECT
        smc_drug_administration.adm_id,
        smc_period.title AS `period`,
        smc_child.`name`,
        smc_child.beneficiary_id,
        smc_drug_administration.not_eligible_reason AS refer_type,
        IF(smc_referer_record.ref_id IS NOT NULL,'Yes','No') AS attended,
        sys_geo_codex.geo_string,
        smc_drug_administration.collected_date AS referred_date,
        smc_referer_record.collected_date AS attended_date
        FROM
        smc_drug_administration
        LEFT JOIN smc_referer_record ON smc_drug_administration.adm_id = smc_referer_record.adm_id
        INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
        INNER JOIN smc_child ON smc_child.beneficiary_id = smc_drug_administration.beneficiary_id
        INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT
        COUNT(*)
        FROM
        smc_drug_administration
        LEFT JOIN smc_referer_record ON smc_drug_administration.adm_id = smc_referer_record.adm_id
        INNER JOIN smc_period ON smc_drug_administration.periodid = smc_period.periodid
        INNER JOIN sys_geo_codex ON smc_drug_administration.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
# Inventory Control table (old to delete)
elseif (CleanData('qid') == '704') {
    $columns = array('cdd_lead_id', 'issuer_name', 'issuer_loginid', 'received_teamlead_id', 'received_team_lead', 'dpid', 'period', 'drug', 'qty_issue', 'received_full_dose', 'received_partial', 'received_wasted', 'geo_string');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $periodid = CleanData("pid");       #  period ID
    $geo_id = CleanData("gid");         #   Geo_level_id
    $geo_level = CleanData("glv");      #   Geo-Level
    #
    if ($periodid) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_icc_issue.periodid IN ($periodid) ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_icc_issue.periodid IN ($periodid) ";
    }
    if ($geo_id && $geo_level) {
        $level = "";
        if ($geo_level == 'lga') {
            $level = "sys_geo_codex.lgaid";
        } elseif ($geo_level == 'ward') {
            $level = "sys_geo_codex.wardid";
        } elseif ($geo_level == 'dp') {
            $level = "sys_geo_codex.dpid";
        }
        #
        if ($seed == 0) {
            $where_condition = " WHERE $level = $geo_id ";
            $seed = 1;
        } else {
            $where_condition .= " AND $level = $geo_id ";
        }
    }

    #
    #  Query composition
    $sql_query = "SELECT
        smc_icc_issue.cdd_lead_id,
        a.fullname AS issuer_name,
        a.loginid AS issuer_loginid,
        usr_login.loginid AS received_teamlead_id,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS received_team_lead,
        smc_icc_issue.dpid, 
        smc_period.title AS period,
        smc_icc_issue.issue_drug AS drug,
        SUM(smc_icc_issue.drug_qty) AS qty_issue,
        SUM(smc_icc_receive.full_dose_qty) AS received_full_dose,
        SUM(smc_icc_receive.partial_qty) AS received_partial,
        SUM(smc_icc_receive.wasted_qty) AS received_wasted,
        sys_geo_codex.geo_string
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        INNER JOIN `smc_icc_issue` ON usr_login.userid = smc_icc_issue.cdd_lead_id
        INNER JOIN sys_geo_codex ON sys_geo_codex.dpid = smc_icc_issue.dpid AND sys_geo_codex.geo_level = 'dp'
        INNER JOIN smc_period ON smc_icc_issue.periodid = smc_period.periodid
        INNER JOIN (SELECT
        usr_login.userid,
        usr_login.loginid AS loginid,
        CONCAT_WS(' ',usr_identity.`first`,usr_identity.middle,usr_identity.last) AS fullname
        FROM
        usr_login
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid) a ON smc_icc_issue.issuer_id = a.userid
        LEFT JOIN smc_icc_receive ON usr_login.userid = smc_icc_receive.receiver_id
        $where_condition 
        GROUP BY smc_icc_issue.cdd_lead_id, smc_icc_issue.issue_drug
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT COUNT(*)
				FROM (SELECT
        COUNT(*)
				FROM
        usr_login
        INNER JOIN `smc_icc_issue` ON usr_login.userid = smc_icc_issue.cdd_lead_id
        INNER JOIN sys_geo_codex ON sys_geo_codex.dpid = smc_icc_issue.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition
        GROUP BY smc_icc_issue.cdd_lead_id, smc_icc_issue.issue_drug) AS a";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
# Inventory Control table (Web)
# 
elseif (CleanData('qid') == '706') {
    $columns = array('issue_id',);
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $periodid = CleanData("pid");       #  period ID
    $geo_id = CleanData("gid");         #   Geo_level_id
    $geo_level = CleanData("glv");      #   Geo-Level
    #
    if ($periodid) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_icc_issue.periodid IN ($periodid) ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_icc_issue.periodid IN ($periodid) ";
    }
    if ($geo_id && $geo_level) {
        $level = "";
        if ($geo_level == 'lga') {
            $level = "sys_geo_codex.lgaid";
        } elseif ($geo_level == 'ward') {
            $level = "sys_geo_codex.wardid";
        } elseif ($geo_level == 'dp') {
            $level = "sys_geo_codex.dpid";
        }
        #
        if ($seed == 0) {
            $where_condition = " WHERE $level = $geo_id ";
            $seed = 1;
        } else {
            $where_condition .= " AND $level = $geo_id ";
        }
    }

    #
    #  Query composition
    $sql_query = "SELECT
        sys_geo_codex.geo_string,
        smc_icc_issue.issue_id,
        smc_period.title AS period,
        smc_icc_issue.issue_drug,
        smc_icc_issue.drug_qty,
        smc_icc_issue.issue_date,
        IF(smc_icc_collection.is_download_confirm, 'Downloaded', NULL) AS downloaded,
        smc_icc_collection.download_confirm_date,
        IF(smc_icc_issue.confirmation = -1, 'Rejected', 'NA') AS is_rejected,
        smc_icc_issue.confirmation_note AS rejection_note,
        IF(smc_icc_collection.is_accepted, 'Accepted', NULL) AS is_accepted,
        smc_icc_collection.accepted_date,
        smc_icc_collection.calculated_used,
        smc_icc_collection.calculated_partial,
        IF(smc_icc_collection.is_returned, 'Yes', NULL) AS is_returned,
        smc_icc_collection.returned_qty,
        smc_icc_collection.returned_partial,
        smc_icc_collection.returned_date,
        IF(smc_icc_collection.is_reconciled, 'Yes', NULL) AS is_reconciled,
        smc_icc_collection.reconciled_date,
        smc_icc_collection.status,
        smc_icc_reconcile.full_qty,
        smc_icc_reconcile.partial_qty,
        smc_icc_reconcile.wasted_qty,
        smc_icc_reconcile.loss_qty,
        smc_icc_reconcile.loss_reason,
        CONCAT(issuer_identity.first, ' ', issuer_identity.last) AS issuer,
        issuer_login.loginid AS issuer_loginid,
        CONCAT(cdd_identity.first, ' ', cdd_identity.last) AS cdd_lead,
        cdd_login.loginid AS cdd_loginid
        FROM
        smc_icc_issue
        LEFT JOIN smc_icc_collection ON smc_icc_issue.issue_id = smc_icc_collection.issue_id
        LEFT JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
        INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        INNER JOIN smc_period ON smc_icc_issue.periodid = smc_period.periodid
        LEFT JOIN usr_login issuer_login ON smc_icc_issue.issuer_id = issuer_login.userid
        LEFT JOIN usr_identity issuer_identity ON issuer_login.userid = issuer_identity.userid
        LEFT JOIN usr_login cdd_login ON smc_icc_issue.cdd_lead_id = cdd_login.userid
        LEFT JOIN usr_identity cdd_identity ON cdd_login.userid = cdd_identity.userid
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT COUNT(*)
		FROM
        smc_icc_issue
        LEFT JOIN smc_icc_collection ON smc_icc_issue.issue_id = smc_icc_collection.issue_id
        LEFT JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
        INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        INNER JOIN smc_period ON smc_icc_issue.periodid = smc_period.periodid
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
# Icc Balances List table
elseif (CleanData('qid') == '705') {
    $columns = array('issue_id', 'cdd_lead_id', 'loginid', 'fullname', 'drug', 'issued', 'pending', 'confirmed', 'accepted', 'returned', 'reconciled', 'geo_level', 'geo_level_id', 'geo_string', 'period');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $loginid = CleanData("lid");       #  CDD lead login ID
    $geo_id = CleanData("gid");         #   Geo_level_id
    $geo_level = CleanData("glv");      #   Geo-Level
    $periodid = CleanData("pid");       #  period ID Visited
    #
    if ($loginid) {
        if ($seed == 0) {
            $where_condition = " WHERE usr_login.loginid = '$loginid' ";
            $seed = 1;
        } else
            $where_condition .= " AND usr_login.loginid = '$loginid' ";
    }
    if ($geo_id && $geo_level) {
        $level = "";
        if ($geo_level == 'lga') {
            $level = "sys_geo_codex.lgaid";
        } elseif ($geo_level == 'ward') {
            $level = "sys_geo_codex.wardid";
        } elseif ($geo_level == 'dp') {
            $level = "sys_geo_codex.dpid";
        }
        #
        if ($seed == 0) {
            $where_condition = " WHERE $level = $geo_id ";
            $seed = 1;
        } else {
            $where_condition .= " AND $level = $geo_id ";
        }
    }
    if ($periodid) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_icc_collection.periodid IN ($periodid) ";
            $seed = 1;
        } else {
            $where_condition .= " AND smc_icc_collection.periodid IN ($periodid) ";
        }
    }

    #
    #  Query composition
    $sql_query = "SELECT
        smc_icc_collection.issue_id,
        smc_icc_collection.cdd_lead_id,
        usr_login.loginid, 
        CONCAT_WS(' ',usr_identity.`first`, usr_identity.last) AS fullname,
        smc_icc_collection.drug,
        SUM(CASE WHEN status_code = 10 THEN qty ELSE 0 END) AS issued,
        SUM(CASE WHEN status_code = 20 THEN qty ELSE 0 END) AS pending,
        SUM(CASE WHEN status_code = 30 THEN qty ELSE 0 END) AS confirmed,
        SUM(CASE WHEN status_code = 40 THEN qty ELSE 0 END) AS accepted,
        SUM(CASE WHEN status_code = 50 THEN qty ELSE 0 END) AS returned,
        SUM(CASE WHEN status_code = 60 THEN qty ELSE 0 END) AS reconciled,
        sys_geo_codex.geo_level,
        sys_geo_codex.geo_level_id,
        smc_period.title AS period
        FROM smc_icc_collection
        INNER JOIN smc_period ON smc_icc_collection.periodid = smc_period.periodid
        INNER JOIN usr_login ON smc_icc_collection.cdd_lead_id = usr_login.userid
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id 
        $where_condition 
        GROUP BY smc_icc_collection.cdd_lead_id, smc_icc_collection.issue_id
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT COUNT(*)
		FROM smc_icc_collection
        INNER JOIN usr_login ON smc_icc_collection.cdd_lead_id = usr_login.userid
        INNER JOIN usr_identity ON usr_login.userid = usr_identity.userid
        INNER JOIN sys_geo_codex ON usr_login.geo_level = sys_geo_codex.geo_level AND usr_login.geo_level_id = sys_geo_codex.geo_level_id 
        $where_condition 
        GROUP BY smc_icc_collection.cdd_lead_id, smc_icc_collection.issue_id";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#
#   SMC LOGISTICS MODLE
#
#   SMC module logistics Issue Table
elseif (CleanData('qid') == '801') {
    $columns = array('issue_id', 'product_code', 'product_name', 'primary_qty', 'secondary_qty', 'created', 'updated', 'geo_string');
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    $product_name = CleanData("pid");       #  Product Name
    $geo_id = CleanData("gid");         #   Geo_level_id
    $geo_level = CleanData("glv");      #   Geo-Level
    #
    if ($product_name) {
        if ($seed == 0) {
            $where_condition = " WHERE smc_logistics_issues.product_name = '$product_name' ";
            $seed = 1;
        } else
            $where_condition .= " AND smc_logistics_issues.product_name = '$product_name' ";
    }
    if ($geo_id && $geo_level) {
        $level = "";
        if ($geo_level == 'lga') {
            $level = "sys_geo_codex.lgaid";
        } elseif ($geo_level == 'ward') {
            $level = "sys_geo_codex.wardid";
        } elseif ($geo_level == 'dp') {
            $level = "sys_geo_codex.dpid";
        }
        #
        if ($seed == 0) {
            $where_condition = " WHERE $level = $geo_id ";
            $seed = 1;
        } else {
            $where_condition .= " AND $level = $geo_id ";
        }
    }

    #
    #  Query composition
    $sql_query = "SELECT
        smc_logistics_issues.issue_id,
        smc_logistics_issues.product_code,
        smc_logistics_issues.product_name,
        smc_logistics_issues.primary_qty,
        smc_logistics_issues.secondary_qty,
        smc_logistics_issues.created,
        smc_logistics_issues.updated,
        sys_geo_codex.geo_string
        FROM
        smc_logistics_issues
        INNER JOIN sys_geo_codex ON smc_logistics_issues.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT COUNT(*)
		FROM
        smc_logistics_issues
        INNER JOIN sys_geo_codex ON smc_logistics_issues.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}
#   SMC module logistics Issue Table
elseif (CleanData('qid') == '802') {
    $columns = array('inbound_id', 'product_code', 'product_name', 'location_type', 'cms_name', 'batch', 'expiry', 'rate', 'unit', 'previous_primary_qty', 'current_primary_qty', 'total_primary_qty', 'previous_secondary_qty', 'current_secondary_qty', 'total_secondary_qty', 'created');    
    #  Require variable
    $perpage = intval($_REQUEST['length']);
    $currentPage = $_REQUEST['draw'];
    $sortColumn = $_REQUEST['order_column'];
    $orderDir = $_REQUEST['order_dir']; // asc | desc
    $orderField = $columns[$_REQUEST['order_column']];
    $limitStart = $_REQUEST['start'];
    $date_format = $GLOBALS["conf_db_date_format"];
    $dateMed_format = $GLOBALS["conf_db_date_medium_format"];
    #  Where condition
    $where_condition = "  ";
    $seed = 0;
    #
    #   Filters
    
    #
    #  Query composition
    $sql_query = "SELECT
        smc_inventory_inbound.inbound_id,
        smc_inventory_inbound.product_code,
        smc_inventory_inbound.product_name,
        smc_inventory_inbound.location_type,
        smc_cms_location.cms_name,
        smc_inventory_inbound.batch,
        smc_inventory_inbound.expiry,
        smc_inventory_inbound.rate,
        smc_inventory_inbound.unit,
        smc_inventory_inbound.previous_primary_qty,
        smc_inventory_inbound.current_primary_qty,
        (smc_inventory_inbound.previous_primary_qty+smc_inventory_inbound.current_primary_qty) AS total_primary_qty,
        smc_inventory_inbound.previous_secondary_qty,
        smc_inventory_inbound.current_secondary_qty,
        (smc_inventory_inbound.previous_secondary_qty+smc_inventory_inbound.current_secondary_qty) AS total_secondary_qty,
        smc_inventory_inbound.created
        FROM
        smc_inventory_inbound
        INNER JOIN smc_cms_location ON smc_inventory_inbound.location_id = smc_cms_location.location_id AND smc_inventory_inbound.location_type = 'CMS'
        $where_condition 
        order by $orderField $orderDir
        LIMIT $limitStart, $perpage";

    $sql_count = "SELECT COUNT(*)
		FROM
        smc_inventory_inbound
        INNER JOIN smc_cms_location ON smc_inventory_inbound.location_id = smc_cms_location.location_id AND smc_inventory_inbound.location_type = 'CMS'
        $where_condition";
    #  Access Database
    $c = new MysqlCentry();
    $data = $c->Table($sql_query);
    $count = $c->Single($sql_count);
    #
    $json_data = array(
        "draw" => $currentPage,
        "recordsTotal" => $count,
        "recordsFiltered" => $count,
        "data" => $data
    );
    echo json_encode($json_data);
}