<?php
    namespace Dataset;
    #
    include_once('lib/common.php');
    include_once('lib/mysql.min.php');
    #
    class Pbi{
        private $db;
        public function __construct(){
            $this->db = GetMysqlDatabase();
        }
        #
        public function GeoLocationSet(){
            return $this->db->DataTable("SELECT
            ms_geo_state.StateId AS stateid,
            ms_geo_state.Fullname AS state,
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.Fullname AS lga,
            ms_geo_ward.wardid,
            ms_geo_ward.ward,
            ms_geo_dp.dpid,
            ms_geo_dp.dp
            FROM
            ms_geo_dp
            INNER JOIN ms_geo_ward ON ms_geo_dp.wardid = ms_geo_ward.wardid
            INNER JOIN ms_geo_lga ON ms_geo_ward.lgaid = ms_geo_lga.LgaId
            INNER JOIN ms_geo_state ON ms_geo_lga.StateId = ms_geo_state.StateId");
        }
        public function GsCombined(){
            return $this->db->DataTable("SELECT
            nc_token.serial_no,
            hhm_gs_net_serial.gtin,
            hhm_gs_net_serial.sgtin,
            hhm_gs_net_serial.batch,
            hhm_gs_net_serial.expiry,
            hhm_gs_net_serial.is_verified,
            hhm_distribution.collected_date,
            hhm_gs_net_verification.`status` AS verification_status,
            ms_geo_dp.dpid,
            ms_geo_dp.dp,
            ms_geo_ward.wardid,
            ms_geo_ward.ward,
            ms_geo_lga.LgaId AS lgaid,
            ms_geo_lga.Fullname AS lga,
            ms_geo_state.StateId AS stateid,
            ms_geo_state.Fullname AS state
            FROM
            hhm_gs_net_serial
            INNER JOIN hhm_distribution ON hhm_gs_net_serial.dis_id = hhm_distribution.dis_id
            INNER JOIN nc_token ON hhm_distribution.etoken_id = nc_token.tokenid
            INNER JOIN hhm_gs_net_verification ON hhm_gs_net_serial.snid = hhm_gs_net_verification.snid
            LEFT JOIN ms_geo_dp ON hhm_distribution.dp_id = ms_geo_dp.dpid
            INNER JOIN ms_geo_ward ON ms_geo_dp.wardid = ms_geo_ward.wardid
            INNER JOIN ms_geo_lga ON ms_geo_ward.lgaid = ms_geo_lga.LgaId
            INNER JOIN ms_geo_state ON ms_geo_lga.StateId = ms_geo_state.StateId");
        }
        public function gs_scanned_list(){
            return $this->db->DataTable("SELECT
            hhm_gs_net_serial.snid,
            hhm_distribution.dp_id,
            nc_token.serial_no,
            hhm_gs_net_serial.gtin,
            hhm_gs_net_serial.sgtin,
            hhm_gs_net_serial.batch,
            hhm_gs_net_serial.is_verified,
            hhm_gs_net_serial.net_serial,
            hhm_distribution.collected_date
            FROM
            hhm_gs_net_serial
            INNER JOIN hhm_distribution ON hhm_gs_net_serial.dis_id = hhm_distribution.dis_id
            INNER JOIN nc_token ON hhm_distribution.etoken_id = nc_token.tokenid");
        }
        public function gs_verification_list(){
            return $this->db->DataTable("SELECT
            hhm_gs_net_verification.snid,
            hhm_gs_net_verification.sgtin,
            hhm_gs_net_verification.`status`,
            hhm_gs_net_verification.created
            FROM
            hhm_gs_net_verification");
        }
        public function gs_summary_data(){
            return $this->db->DataTable("SELECT 
            (SELECT COUNT(*) FROM ms_product_sgtin) AS total_net_master,
            (SELECT COUNT(*) FROM hhm_mobilization) AS household_mobilized,
            (SELECT COUNT(*) FROM hhm_distribution) AS household_redeemed,
            (SELECT SUM(hhm_mobilization.family_size) FROM hhm_mobilization) AS family_size,
            (SELECT SUM(hhm_mobilization.allocated_net) FROM hhm_mobilization) AS netcard_issued,
            (SELECT SUM(hhm_distribution.collected_nets) FROM hhm_distribution) AS collected_nets");
        }
    }

?>