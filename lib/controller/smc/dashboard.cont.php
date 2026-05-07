<?php

namespace Smc;

include_once('lib/common.php');
include_once('lib/mysql.min.php');
class Dashboard
{
    private $db;
    public function __construct()
    {
        $this->db = GetMysqlDatabase();
    }
    /*
     *  |====================|
     *  |=== SMC DASHBOARD ==|
     *  |====================|
     * 
     */
    # Child List LGA [id, title, total, male, female]
    public function ChildListLgaSummary($startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #   Filters
        if ($startDate && $endDate) {
            $where_condition = " WHERE DATE(smc_child.created) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`lgaid` AS `id`,`ms_geo_lga`.`Fullname` AS `title`,count(`smc_child`.`child_id`) AS `total`,sum((`smc_child`.`gender` = 'male')) AS `male`,sum((`smc_child`.`gender` = 'female')) AS `female` from ((`smc_child` join `sys_geo_codex` on(((`smc_child`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_lga` on((`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))) $where_condition group by `sys_geo_codex`.`lgaid`");
    }
    # Child List Ward [id, title, total, male, female]
    public function ChildListWardSummary($lgaid, $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #   Filters
        if ($startDate && $endDate) {
            $where_condition = " WHERE DATE(smc_child.created) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`wardid` AS `id`,`ms_geo_ward`.`ward` AS `title`,count(`smc_child`.`child_id`) AS `total`,sum((`smc_child`.`gender` = 'male')) AS `male`,sum((`smc_child`.`gender` = 'female')) AS `female` from ((`smc_child` join `sys_geo_codex` on(((`smc_child`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_ward` on((`sys_geo_codex`.`wardid` = `ms_geo_ward`.`wardid`))) where (`sys_geo_codex`.`lgaid` = $lgaid) $where_condition group by `sys_geo_codex`.`wardid`");
    }
    # Child List Ward [id, title, total, male, female]
    public function ChildListDpSummary($wardid, $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #   Filters
        if ($startDate && $endDate) {
            $where_condition = " WHERE DATE(smc_child.created) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`dpid` AS `id`,`ms_geo_dp`.`dp` AS `title`,count(`smc_child`.`child_id`) AS `total`,sum((`smc_child`.`gender` = 'male')) AS `male`,sum((`smc_child`.`gender` = 'female')) AS `female` from ((`smc_child` join `sys_geo_codex` on(((`smc_child`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_dp` on((`sys_geo_codex`.`dpid` = `ms_geo_dp`.`dpid`))) where (`sys_geo_codex`.`wardid` = $wardid) $where_condition group by `sys_geo_codex`.`dpid`");
    }
    /*
     *      DRUG ADMINISTRATION
     * 
     */
    #   Drug Administration LGA List [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    public function DrugAdminListLga($period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_drug_administration.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`lgaid` AS `id`,`ms_geo_lga`.`Fullname` AS `title`,count(`smc_drug_administration`.`adm_id`) AS `total`,sum(`smc_drug_administration`.`is_eligible`) AS `eligible`,sum((`smc_drug_administration`.`is_eligible` = 0)) AS `non_eligible`,sum(`smc_drug_administration`.`is_refer`) AS `referral`,sum((`smc_drug_administration`.`drug` = 'SPAQ 1')) AS `spaq1`,sum((`smc_drug_administration`.`drug` = 'SPAQ 2')) AS `spaq2` from ((`smc_drug_administration` join `sys_geo_codex` on(((`smc_drug_administration`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_lga` on((`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))) $where_condition group by `sys_geo_codex`.`lgaid`");
    }
    #   Drug Administration Ward List [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    public function DrugAdminListWard($lgaid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE sys_geo_codex.lgaid = $lgaid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($period_list) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.periodid IN ($period_list) ";
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`wardid` AS `id`,`ms_geo_ward`.`ward` AS `title`,count(`smc_drug_administration`.`adm_id`) AS `total`,sum(`smc_drug_administration`.`is_eligible`) AS `eligible`,sum((`smc_drug_administration`.`is_eligible` = 0)) AS `non_eligible`,sum(`smc_drug_administration`.`is_refer`) AS `referral`,sum((`smc_drug_administration`.`drug` = 'SPAQ 1')) AS `spaq1`,sum((`smc_drug_administration`.`drug` = 'SPAQ 2')) AS `spaq2` from ((`smc_drug_administration` join `sys_geo_codex` on(((`smc_drug_administration`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_ward` on((`sys_geo_codex`.`wardid` = `ms_geo_ward`.`wardid`))) $where_condition group by `sys_geo_codex`.`wardid`");
    }
    #   Drug Administration Ward List [id, title, total, eligible, non_eligible, referral, spaq1, spaq2]
    public function DrugAdminListDp($wardid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE sys_geo_codex.wardid = $wardid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_drug_administration.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`dpid` AS `id`,`ms_geo_dp`.`dp` AS `title`,count(`smc_drug_administration`.`adm_id`) AS `total`,sum(`smc_drug_administration`.`is_eligible`) AS `eligible`,sum((`smc_drug_administration`.`is_eligible` = 0)) AS `non_eligible`,sum(`smc_drug_administration`.`is_refer`) AS `referral`,sum((`smc_drug_administration`.`drug` = 'SPAQ 1')) AS `spaq1`,sum((`smc_drug_administration`.`drug` = 'SPAQ 2')) AS `spaq2` from ((`smc_drug_administration` join `sys_geo_codex` on(((`smc_drug_administration`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_dp` on((`sys_geo_codex`.`dpid` = `ms_geo_dp`.`dpid`))) $where_condition group by `sys_geo_codex`.`dpid`");
    }
    /*
     *      REFERRALS
     * 
     */
    #  Referral LGA List [id, title, total, referred, attended]
    public function ReferralListLga($period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_drug_administration.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`lgaid` AS `id`,`ms_geo_lga`.`Fullname` AS `title`,count(`smc_drug_administration`.`adm_id`) AS `total`,sum(`smc_drug_administration`.`is_refer`) AS `referred`,sum((`smc_referer_record`.`ref_id` is not null)) AS `attended` from (((`smc_drug_administration` join `sys_geo_codex` on(((`smc_drug_administration`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_lga` on((`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`))) left join `smc_referer_record` on((`smc_drug_administration`.`adm_id` = `smc_referer_record`.`adm_id`))) $where_condition group by `sys_geo_codex`.`lgaid`");
    }
    #  Referral Ward List [id, title, total, referred, attended]
    public function ReferralListWard($lgaid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE sys_geo_codex.lgaid = $lgaid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($period_list) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.periodid IN ($period_list) ";
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("select `sys_geo_codex`.`wardid` AS `id`,`ms_geo_ward`.`ward` AS `title`,count(`smc_drug_administration`.`adm_id`) AS `total`,sum(`smc_drug_administration`.`is_refer`) AS `referred`,sum((`smc_referer_record`.`ref_id` is not null)) AS `attended` from (((`smc_drug_administration` join `sys_geo_codex` on(((`smc_drug_administration`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_ward` on((`sys_geo_codex`.`wardid` = `ms_geo_ward`.`wardid`))) left join `smc_referer_record` on((`smc_drug_administration`.`adm_id` = `smc_referer_record`.`adm_id`))) $where_condition group by `sys_geo_codex`.`wardid`");
    }
    #  Referral LGA List [id, title, total, referred, attended]
    public function ReferralListDp($wardid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE sys_geo_codex.wardid = $wardid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_drug_administration.periodid IN ($period_list) ";
                $seed = 1;
            } else
                $where_condition .= " AND smc_drug_administration.periodid IN ($period_list) ";
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_drug_administration.collected_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("select `ms_geo_dp`.`dpid` AS `id`,`ms_geo_dp`.`dp` AS `title`,count(`smc_drug_administration`.`adm_id`) AS `total`,sum(`smc_drug_administration`.`is_refer`) AS `referred`,sum((`smc_referer_record`.`ref_id` is not null)) AS `attended` from (((`smc_drug_administration` join `sys_geo_codex` on(((`smc_drug_administration`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) join `ms_geo_dp` on((`sys_geo_codex`.`dpid` = `ms_geo_dp`.`dpid`))) left join `smc_referer_record` on((`smc_drug_administration`.`adm_id` = `smc_referer_record`.`adm_id`))) $where_condition group by `sys_geo_codex`.`dpid`");
    }
    /*
     *      Inventory control
     * 
    */
    #  ICC LGA List [id, title, count_facility, count_team, drug, issue, full_return, partial_return, wasted_return, used]
    public function IccListLga_deleted($period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_issue.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_icc_issue.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_icc_issue.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_icc_issue.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("SELECT
            `sys_geo_codex`.`lgaid` AS `id`,
            ms_geo_lga.Fullname AS title,
            COUNT(DISTINCT smc_icc_issue.dpid) AS count_facility,
            Count(DISTINCT smc_icc_issue.cdd_lead_id) AS count_team,
            smc_icc_issue.issue_drug AS `drug`,
            Sum(smc_icc_issue.drug_qty) AS issue,
            Sum(COALESCE(smc_icc_reconcile.remaining, (smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial)))) AS `full_return`,
            Sum(smc_icc_reconcile.partial) AS `partial_return`,
            Sum(smc_icc_reconcile.wasted) AS `wasted_return`,
            (Sum(smc_icc_issue.drug_qty) - Sum(COALESCE(smc_icc_reconcile.remaining, (smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial))))) AS `used`
            FROM
            smc_icc_issue
            INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
            INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid  AND sys_geo_codex.geo_level = 'dp'
            INNER JOIN ms_geo_lga ON sys_geo_codex.lgaid = ms_geo_lga.LgaId
            $where_condition
            GROUP BY
            ms_geo_lga.LgaId,
            smc_icc_issue.issue_drug");
    }
    #  ICC Ward List [id, title, count_facility, count_team, drug, issue, full_return, partial_return, wasted_return, used]
    public function IccListWard_deleted($lgaid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE sys_geo_codex.lgaid = $lgaid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_issue.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_icc_issue.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_icc_issue.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_icc_issue.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("SELECT
            sys_geo_codex.wardid AS id,
            ms_geo_ward.ward AS title,
            COUNT(DISTINCT smc_icc_issue.dpid) AS count_facility,
            Count(DISTINCT smc_icc_issue.cdd_lead_id) AS count_team,
            smc_icc_issue.issue_drug AS `drug`,
            Sum(smc_icc_issue.drug_qty) AS `issue`,
            Sum(COALESCE(smc_icc_reconcile.remaining, (smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial)))) AS `full_return`,
            Sum(smc_icc_reconcile.partial) AS `partial_return`,
            Sum(smc_icc_reconcile.wasted) AS `wasted_return`,
            (Sum(smc_icc_issue.drug_qty) - Sum(COALESCE(smc_icc_reconcile.remaining, (smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial))))) AS `used`
            FROM
            smc_icc_issue
            INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
            INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            INNER JOIN ms_geo_ward ON sys_geo_codex.wardid = ms_geo_ward.wardid
            $where_condition
            GROUP BY
            sys_geo_codex.wardid,
            smc_icc_issue.issue_drug");
    }
    #  ICC DP List [id, title, count_facility, count_team, drug, issue, full_return, partial_return, wasted_return, used]
    public function IccListDp_deleted($wardid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE sys_geo_codex.wardid = $wardid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE a.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND a.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(a.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(a.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("SELECT
            `sys_geo_codex`.`dpid` AS `id`,
            sys_geo_codex.title AS title,
            COUNT(DISTINCT smc_icc_issue.dpid) AS count_facility,
            Count(DISTINCT smc_icc_issue.cdd_lead_id) AS count_team,
            smc_icc_issue.issue_drug AS drug,
            Sum(smc_icc_issue.drug_qty) AS issue,
            Sum(COALESCE(smc_icc_reconcile.remaining, (smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial)))) AS full_return,
            Sum(smc_icc_reconcile.partial) AS partial_return,
            Sum(smc_icc_reconcile.wasted) AS wasted_return,
            (Sum(smc_icc_issue.drug_qty) - Sum(COALESCE(smc_icc_reconcile.remaining, (smc_icc_issue.drug_qty - (smc_icc_reconcile.`full` + smc_icc_reconcile.partial))))) AS used
            FROM
            smc_icc_issue
            INNER JOIN smc_icc_reconcile ON smc_icc_issue.issue_id = smc_icc_reconcile.issue_id
            INNER JOIN sys_geo_codex ON smc_icc_issue.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            GROUP BY
            sys_geo_codex.wardid,
            smc_icc_issue.issue_drug");
    }
    /*
     *      Inventory control
     * 
    */
    #  ICC LGA List [id, title, period, count_facility, count_team, drug, count_facility,count_team, issued, pending, confirmed, accepted, returned, reconciled, administered, redosed, wasted, loss]
    public function IccListLga($period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = "  ";
        $seed = 0;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_collection.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_icc_collection.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_icc_collection.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_icc_collection.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("SELECT
            ms_geo_lga.LgaId AS id,
            ms_geo_lga.Fullname AS title,
            smc_period.title AS period,
            smc_icc_collection.drug,
            COUNT(DISTINCT smc_icc_collection.dpid) AS count_facility,
            COUNT(DISTINCT smc_icc_collection.cdd_lead_id) AS count_team,
            SUM(CASE WHEN smc_icc_collection.status_code = 10 THEN qty ELSE 0 END) AS issued,
            SUM(CASE WHEN smc_icc_collection.status_code = 20 THEN qty ELSE 0 END) AS pending,
            SUM(smc_icc_collection.total_qty) AS total_issued,
            SUM(CASE WHEN smc_icc_collection.status_code = 30 THEN qty ELSE 0 END) AS confirmed,
            SUM(CASE WHEN smc_icc_collection.status_code = 40 THEN qty ELSE 0 END) AS accepted,
            SUM(CASE WHEN smc_icc_collection.status_code = 50 THEN qty ELSE 0 END) AS returned,
            SUM(CASE WHEN smc_icc_collection.status_code = 60 THEN qty ELSE 0 END) AS reconciled,
            SUM(IFNULL(smc_icc_collection.calculated_used,0)) AS administered,
            SUM(IFNULL(smc_icc_collection.calculated_partial,0)) AS redosed,
            SUM(IFNULL(smc_icc_reconcile.wasted_qty,0)) AS wasted,
            SUM(IFNULL(smc_icc_reconcile.loss_qty,0)) AS loss
            FROM smc_icc_collection
            INNER JOIN smc_period ON smc_icc_collection.periodid = smc_period.periodid
            INNER JOIN sys_geo_codex ON smc_icc_collection.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            INNER JOIN ms_geo_lga ON sys_geo_codex.lgaid = ms_geo_lga.LgaId
            LEFT JOIN smc_icc_reconcile ON smc_icc_collection.issue_id = smc_icc_reconcile.issue_id
            $where_condition
            GROUP BY smc_icc_collection.periodid, sys_geo_codex.lgaid, smc_icc_collection.drug");
    }
    #  ICC Ward List [id, title, period, count_facility, count_team, drug, count_facility,count_team, issued, pending, confirmed, accepted, returned, reconciled, administered, redosed, wasted, loss]
    public function IccListWard($lgaid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE sys_geo_codex.lgaid = $lgaid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_collection.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_icc_collection.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_icc_collection.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_icc_collection.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("SELECT
            ms_geo_ward.wardid AS id,
            ms_geo_ward.ward AS title,
            smc_period.title AS period,
            smc_icc_collection.drug,
            COUNT(DISTINCT smc_icc_collection.dpid) AS count_facility,
            COUNT(DISTINCT smc_icc_collection.cdd_lead_id) AS count_team,
            SUM(CASE WHEN smc_icc_collection.status_code = 10 THEN qty ELSE 0 END) AS issued,
            SUM(smc_icc_collection.total_qty) AS total_issued,
            SUM(CASE WHEN smc_icc_collection.status_code = 20 THEN qty ELSE 0 END) AS pending,
            SUM(CASE WHEN smc_icc_collection.status_code = 30 THEN qty ELSE 0 END) AS confirmed,
            SUM(CASE WHEN smc_icc_collection.status_code = 40 THEN qty ELSE 0 END) AS accepted,
            SUM(CASE WHEN smc_icc_collection.status_code = 50 THEN qty ELSE 0 END) AS returned,
            SUM(CASE WHEN smc_icc_collection.status_code = 60 THEN qty ELSE 0 END) AS reconciled,
            SUM(IFNULL(smc_icc_collection.calculated_used,0)) AS administered,
            SUM(IFNULL(smc_icc_collection.calculated_partial,0)) AS redosed,
            SUM(IFNULL(smc_icc_reconcile.wasted_qty,0)) AS wasted,
            SUM(IFNULL(smc_icc_reconcile.loss_qty,0)) AS loss
            FROM smc_icc_collection
            INNER JOIN smc_period ON smc_icc_collection.periodid = smc_period.periodid
            INNER JOIN sys_geo_codex ON smc_icc_collection.dpid = sys_geo_codex.dpid AND sys_geo_codex.geo_level = 'dp'
            INNER JOIN ms_geo_ward ON sys_geo_codex.wardid = ms_geo_ward.wardid
            LEFT JOIN smc_icc_reconcile ON smc_icc_collection.issue_id = smc_icc_reconcile.issue_id
            $where_condition
            GROUP BY smc_icc_collection.periodid, sys_geo_codex.wardid, smc_icc_collection.drug");
    }
    #  ICC DP List [id, title, period, count_facility, count_team, drug, count_facility,count_team, issued, pending, confirmed, accepted, returned, reconciled, administered, redosed, wasted, loss]
    public function IccListDp($wardid, $period_list = "", $startDate = '', $endDate = '')
    {
        #  Where condition
        $where_condition = " WHERE ms_geo_dp.wardid = $wardid ";
        $seed = 1;
        #   Filters
        if ($period_list) {
            if ($seed == 0) {
                $where_condition = " WHERE smc_icc_collection.periodid IN ($period_list) ";
                $seed = 1;
            } else {
                $where_condition .= " AND smc_icc_collection.periodid IN ($period_list) ";
            }
        }
        if ($startDate && $endDate) {
            if ($seed == 0) {
                $where_condition = " WHERE DATE(smc_icc_collection.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
                $seed = 1;
            } else {
                $where_condition .= " AND DATE(smc_icc_collection.issue_date) BETWEEN DATE('$startDate') AND DATE('$endDate') ";
            }
        }
        #
        return $this->db->DataTable("SELECT
            ms_geo_dp.dpid AS id,
            ms_geo_dp.dp AS title,
            smc_period.title AS period,
            smc_icc_collection.drug,
            COUNT(DISTINCT smc_icc_collection.dpid) AS count_facility,
            COUNT(DISTINCT smc_icc_collection.cdd_lead_id) AS count_team,
            SUM(CASE WHEN smc_icc_collection.status_code = 10 THEN qty ELSE 0 END) AS issued,
            SUM(smc_icc_collection.total_qty) AS total_issued,
            SUM(CASE WHEN smc_icc_collection.status_code = 20 THEN qty ELSE 0 END) AS pending,
            SUM(CASE WHEN smc_icc_collection.status_code = 30 THEN qty ELSE 0 END) AS confirmed,
            SUM(CASE WHEN smc_icc_collection.status_code = 40 THEN qty ELSE 0 END) AS accepted,
            SUM(CASE WHEN smc_icc_collection.status_code = 50 THEN qty ELSE 0 END) AS returned,
            SUM(CASE WHEN smc_icc_collection.status_code = 60 THEN qty ELSE 0 END) AS reconciled,
            SUM(IFNULL(smc_icc_collection.calculated_used,0)) AS administered,
            SUM(IFNULL(smc_icc_collection.calculated_partial,0)) AS redosed,
            SUM(IFNULL(smc_icc_reconcile.wasted_qty,0)) AS wasted,
            SUM(IFNULL(smc_icc_reconcile.loss_qty,0)) AS loss
            FROM smc_icc_collection
            INNER JOIN smc_period ON smc_icc_collection.periodid = smc_period.periodid
            INNER JOIN ms_geo_dp ON smc_icc_collection.dpid = ms_geo_dp.dpid
            LEFT JOIN smc_icc_reconcile ON smc_icc_collection.issue_id = smc_icc_reconcile.issue_id
            $where_condition
            GROUP BY smc_icc_collection.periodid, smc_icc_collection.dpid, smc_icc_collection.drug");
    }
}
