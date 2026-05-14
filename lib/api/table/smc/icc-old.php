<?php

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
