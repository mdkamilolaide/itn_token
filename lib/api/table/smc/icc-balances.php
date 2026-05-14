<?php

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
