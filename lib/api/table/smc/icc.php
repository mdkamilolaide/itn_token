<?php

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
