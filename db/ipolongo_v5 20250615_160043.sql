DROP DATABASE IF EXISTS `ipolongo_v5`;

CREATE DATABASE `ipolongo_v5`;

USE `ipolongo_v5`;

DROP TABLE IF EXISTS `amf_distribution`;
;

DROP TABLE IF EXISTS `amf_five_revisit`;
;

DROP TABLE IF EXISTS `amf_mobilization`;
;

DROP TABLE IF EXISTS `dataset_activity_participants`;
;

DROP TABLE IF EXISTS `dataset_activity_session`;
;

DROP TABLE IF EXISTS `dataset_mobilization_distribution`;
;

DROP TABLE IF EXISTS `dataset_users_all`;
;

DROP TABLE IF EXISTS `dsh_mob_summary_dp`;
CREATE TABLE `dsh_mob_summary_dp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dpid` int(11) DEFAULT NULL,
  `wardid` int(11) DEFAULT NULL,
  `clusterid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `households` int(11) DEFAULT NULL,
  `enetcards` int(11) DEFAULT NULL,
  `family_size` int(11) DEFAULT NULL,
  `geo_string` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `dpid` (`dpid`) USING BTREE,
  KEY `wardid` (`wardid`) USING BTREE,
  KEY `lgaid` (`lgaid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `hhm_distribution`;
CREATE TABLE `hhm_distribution` (
  `dis_id` int(11) NOT NULL AUTO_INCREMENT,
  `dp_id` int(11) DEFAULT NULL,
  `hhid` int(11) DEFAULT NULL,
  `etoken_id` int(11) DEFAULT NULL,
  `etoken_serial` varchar(50) DEFAULT NULL,
  `recorder_id` int(11) DEFAULT NULL,
  `distributor_id` int(11) DEFAULT NULL,
  `collected_nets` int(11) DEFAULT NULL,
  `is_gs_net` tinyint(1) NOT NULL DEFAULT 0,
  `gs_net_serial` text DEFAULT NULL,
  `eolin_bring_old_net` tinyint(1) DEFAULT NULL,
  `eolin_total_old_net` int(11) DEFAULT NULL,
  `longitude` varchar(100) DEFAULT NULL,
  `latitude` varchar(100) DEFAULT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `collected_date` datetime DEFAULT current_timestamp(),
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`dis_id`) USING BTREE,
  UNIQUE KEY `etoken_serial` (`etoken_serial`) USING BTREE,
  KEY `is_gs_net` (`is_gs_net`) USING BTREE,
  KEY `dp_id` (`dp_id`) USING BTREE,
  KEY `hhid` (`hhid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `hhm_gs_net_serial`;
CREATE TABLE `hhm_gs_net_serial` (
  `snid` int(11) NOT NULL AUTO_INCREMENT COMMENT 'scanned net barcode list id',
  `dis_id` int(11) DEFAULT NULL,
  `hhid` int(11) DEFAULT NULL,
  `etoken_id` int(11) DEFAULT NULL,
  `net_serial` varchar(255) DEFAULT NULL,
  `gtin` varchar(20) DEFAULT NULL,
  `sgtin` varchar(20) DEFAULT NULL,
  `batch` varchar(20) DEFAULT NULL,
  `expiry` varchar(20) DEFAULT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`snid`) USING BTREE,
  KEY `sgtin` (`sgtin`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `hhm_gs_net_verification`;
CREATE TABLE `hhm_gs_net_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sgtinid` int(11) NOT NULL,
  `snid` int(11) NOT NULL,
  `sgtin` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `hhm_gs_net_verification_log`;
CREATE TABLE `hhm_gs_net_verification_log` (
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `total_verification` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`logid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `hhm_location_categories`;
CREATE TABLE `hhm_location_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `hhm_mobilization`;
CREATE TABLE `hhm_mobilization` (
  `hhid` int(11) NOT NULL AUTO_INCREMENT,
  `dp_id` int(11) NOT NULL,
  `comid` int(11) DEFAULT NULL,
  `hhm_id` int(11) NOT NULL,
  `co_hhm_id` int(11) DEFAULT NULL,
  `hoh_first` varchar(50) DEFAULT NULL,
  `hoh_last` varchar(50) DEFAULT NULL,
  `hoh_phone` varchar(50) DEFAULT NULL,
  `hoh_gender` varchar(50) DEFAULT NULL,
  `family_size` int(11) DEFAULT NULL,
  `hod_mother` varchar(255) DEFAULT NULL,
  `sleeping_space` int(11) DEFAULT 0,
  `adult_female` int(11) DEFAULT 0,
  `adult_male` int(11) DEFAULT 0,
  `children` int(11) DEFAULT 0,
  `allocated_net` int(11) DEFAULT NULL COMMENT 'Total number of allocated physical net',
  `location_description` varchar(50) DEFAULT NULL COMMENT 'Location description - [household | orphanage | barrack | others ]',
  `eolin_have_old_net` tinyint(1) DEFAULT NULL,
  `eolin_total_old_net` int(11) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `Latitude` varchar(50) DEFAULT NULL,
  `netcards` varchar(255) DEFAULT NULL,
  `etoken_id` int(11) DEFAULT NULL,
  `etoken_serial` varchar(20) DEFAULT NULL,
  `etoken_pin` varchar(10) DEFAULT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`hhid`) USING BTREE,
  UNIQUE KEY `etoken_serial` (`etoken_serial`) USING BTREE,
  KEY `etoken_pin` (`etoken_pin`) USING BTREE,
  KEY `dp_id` (`dp_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mnt_dis_daily_today`;
;

DROP TABLE IF EXISTS `mnt_dis_date_summary`;
;

DROP TABLE IF EXISTS `mnt_mob_aggregate_dp`;
;

DROP TABLE IF EXISTS `mnt_mob_aggregate_dp_with_id`;
;

DROP TABLE IF EXISTS `mnt_mob_aggregate_lga`;
;

DROP TABLE IF EXISTS `mnt_mob_daterange_dp`;
;

DROP TABLE IF EXISTS `mnt_mob_hhm_mobilization`;
;

DROP TABLE IF EXISTS `mnt_mob_mobilizer_performance_daily`;
;

DROP TABLE IF EXISTS `mnt_mob_summary`;
;

DROP TABLE IF EXISTS `mnt_mob_summary_by_date`;
;

DROP TABLE IF EXISTS `mnt_mob_today_dp_summary`;
;

DROP TABLE IF EXISTS `mnt_netcard_lga_sum_location_balance`;
;

DROP TABLE IF EXISTS `mnt_netcard_vs_allocated`;
;

DROP TABLE IF EXISTS `mo_form_end_process`;
CREATE TABLE `mo_form_end_process` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL,
  `wardid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `comid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `aa` varchar(100) DEFAULT NULL,
  `ab` varchar(100) DEFAULT NULL,
  `ac` varchar(100) DEFAULT NULL,
  `ad` varchar(100) DEFAULT NULL,
  `ae` varchar(100) DEFAULT NULL,
  `af` varchar(100) DEFAULT NULL,
  `ag` varchar(100) DEFAULT NULL,
  `ah` varchar(100) DEFAULT NULL,
  `ai` varchar(100) DEFAULT NULL,
  `aj` varchar(100) DEFAULT NULL,
  `ak` varchar(100) DEFAULT NULL,
  `al` varchar(100) DEFAULT NULL,
  `am` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mo_form_five_revisit`;
CREATE TABLE `mo_form_five_revisit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL,
  `wardid` int(11) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `comid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `aa` varchar(100) DEFAULT NULL,
  `ab` varchar(100) DEFAULT NULL,
  `ac` varchar(100) DEFAULT NULL,
  `ad` varchar(100) DEFAULT NULL,
  `ae` varchar(100) DEFAULT NULL,
  `af` varchar(100) DEFAULT NULL,
  `ag` varchar(100) DEFAULT NULL,
  `ah` varchar(100) DEFAULT NULL,
  `ai` varchar(100) DEFAULT NULL,
  `aj` varchar(100) DEFAULT NULL,
  `etoken_serial` varchar(100) DEFAULT NULL,
  `etoken_uuid` varchar(100) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mo_form_i9a`;
CREATE TABLE `mo_form_i9a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL,
  `wardid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `comid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `aa` varchar(100) DEFAULT NULL,
  `ab` varchar(100) DEFAULT NULL,
  `ac` varchar(100) DEFAULT NULL,
  `ad` varchar(100) DEFAULT NULL,
  `ae` varchar(100) DEFAULT NULL,
  `af` varchar(100) DEFAULT NULL,
  `ag` varchar(100) DEFAULT NULL,
  `ah` varchar(100) DEFAULT NULL,
  `ai` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mo_form_i9b`;
CREATE TABLE `mo_form_i9b` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL,
  `wardid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `comid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `supervisor` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `aa` varchar(10) DEFAULT NULL,
  `ab` varchar(255) DEFAULT NULL,
  `ba` varchar(10) DEFAULT NULL,
  `bb` varchar(255) DEFAULT NULL,
  `ca` varchar(10) DEFAULT NULL,
  `cb` varchar(255) DEFAULT NULL,
  `da` varchar(10) DEFAULT NULL,
  `db` varchar(255) DEFAULT NULL,
  `ea` varchar(10) DEFAULT NULL,
  `eb` varchar(255) DEFAULT NULL,
  `fa` varchar(10) DEFAULT NULL,
  `fb` varchar(255) DEFAULT NULL,
  `ga` varchar(10) DEFAULT NULL,
  `gb` varchar(255) DEFAULT NULL,
  `ha` varchar(10) DEFAULT NULL,
  `hb` varchar(255) DEFAULT NULL,
  `ia` varchar(10) DEFAULT NULL,
  `ib` varchar(255) DEFAULT NULL,
  `ja` varchar(10) DEFAULT NULL,
  `jb` varchar(255) DEFAULT NULL,
  `ka` varchar(10) DEFAULT NULL,
  `kb` varchar(255) DEFAULT NULL,
  `la` varchar(10) DEFAULT NULL,
  `lb` varchar(255) DEFAULT NULL,
  `ma` varchar(10) DEFAULT NULL,
  `mb` varchar(255) DEFAULT NULL,
  `na` varchar(10) DEFAULT NULL,
  `nb` varchar(255) DEFAULT NULL,
  `oa` varchar(10) DEFAULT NULL,
  `ob` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mo_form_i9c`;
CREATE TABLE `mo_form_i9c` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(50) NOT NULL,
  `wardid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `aa` varchar(100) DEFAULT NULL,
  `ab` varchar(100) DEFAULT NULL,
  `ac` varchar(100) DEFAULT NULL,
  `ad` varchar(100) DEFAULT NULL,
  `ae` varchar(100) DEFAULT NULL,
  `af` varchar(100) DEFAULT NULL,
  `ag` varchar(100) DEFAULT NULL,
  `ah` varchar(100) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mo_form_structures`;
CREATE TABLE `mo_form_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(20) NOT NULL,
  `structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `version` varchar(50) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mo_smc_supervisor_cdd`;
CREATE TABLE `mo_smc_supervisor_cdd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) NOT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `wardid` int(11) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `periodid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `day` varchar(255) DEFAULT NULL COMMENT '(Day 1 - 4 options)',
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `aa` varchar(5) DEFAULT NULL,
  `ab` varchar(255) DEFAULT NULL,
  `ba` varchar(5) DEFAULT NULL,
  `bb` varchar(255) DEFAULT NULL,
  `ca` varchar(5) DEFAULT NULL,
  `cb` varchar(255) DEFAULT NULL,
  `da` varchar(5) DEFAULT NULL,
  `db` varchar(255) DEFAULT NULL,
  `ea` varchar(5) DEFAULT NULL,
  `eb` varchar(255) DEFAULT NULL,
  `fa` varchar(5) DEFAULT NULL,
  `fb` varchar(255) DEFAULT NULL,
  `ga` varchar(5) DEFAULT NULL,
  `gb` varchar(255) DEFAULT NULL,
  `ha` varchar(5) DEFAULT NULL,
  `hb` varchar(255) DEFAULT NULL,
  `ia` varchar(5) DEFAULT NULL,
  `ib` varchar(255) DEFAULT NULL,
  `ja` varchar(5) DEFAULT NULL,
  `jb` varchar(255) DEFAULT NULL,
  `ka` varchar(5) DEFAULT NULL,
  `kb` varchar(255) DEFAULT NULL,
  `la` varchar(5) DEFAULT NULL,
  `lb` varchar(255) DEFAULT NULL,
  `ma` varchar(5) DEFAULT NULL,
  `mb` varchar(255) DEFAULT NULL,
  `na` varchar(5) DEFAULT NULL,
  `nb` varchar(255) DEFAULT NULL,
  `oa` varchar(5) DEFAULT NULL,
  `ob` varchar(255) DEFAULT NULL,
  `pa` varchar(5) DEFAULT NULL,
  `pb` varchar(255) DEFAULT NULL,
  `q` text DEFAULT NULL,
  `r` text DEFAULT NULL,
  `s` text DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `mo_smc_supervisor_hfw`;
CREATE TABLE `mo_smc_supervisor_hfw` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(255) NOT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `wardid` int(11) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `periodid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `day` varchar(255) DEFAULT NULL COMMENT 'DAY 1 - 4 option',
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `aa` varchar(5) DEFAULT NULL,
  `ab` varchar(255) DEFAULT NULL,
  `ba` varchar(5) DEFAULT NULL,
  `bb` varchar(255) DEFAULT NULL,
  `ca` varchar(5) DEFAULT NULL,
  `cb` varchar(255) DEFAULT NULL,
  `da` varchar(5) DEFAULT NULL,
  `db` varchar(255) DEFAULT NULL,
  `ea` varchar(5) DEFAULT NULL,
  `eb` varchar(255) DEFAULT NULL,
  `fa` varchar(5) DEFAULT NULL,
  `fb` varchar(255) DEFAULT NULL,
  `ga` varchar(5) DEFAULT NULL,
  `gb` varchar(255) DEFAULT NULL,
  `ha` varchar(5) DEFAULT NULL,
  `hb` varchar(255) DEFAULT NULL,
  `ia` varchar(5) DEFAULT NULL,
  `ib` varchar(255) DEFAULT NULL,
  `ja` varchar(5) DEFAULT NULL,
  `jb` varchar(255) DEFAULT NULL,
  `ka` varchar(5) DEFAULT NULL,
  `kb` varchar(255) DEFAULT NULL,
  `la` varchar(5) DEFAULT NULL,
  `lb` varchar(255) DEFAULT NULL,
  `m1a` varchar(5) DEFAULT NULL,
  `m1b` varchar(255) DEFAULT NULL,
  `m2a` varchar(5) DEFAULT NULL,
  `m2b` varchar(255) DEFAULT NULL,
  `m3a` varchar(5) DEFAULT NULL,
  `m3b` varchar(255) DEFAULT NULL,
  `m4a` varchar(5) DEFAULT NULL,
  `m4b` varchar(255) DEFAULT NULL,
  `n1a` varchar(5) DEFAULT NULL,
  `n1b` varchar(255) DEFAULT NULL,
  `n2a` varchar(5) DEFAULT NULL,
  `n2b` varchar(255) DEFAULT NULL,
  `n3a` varchar(5) DEFAULT NULL,
  `n3b` varchar(255) DEFAULT NULL,
  `n4a` varchar(5) DEFAULT NULL,
  `n4b` varchar(255) DEFAULT NULL,
  `n5a` varchar(5) DEFAULT NULL,
  `n5b` varchar(255) DEFAULT NULL,
  `n6a` varchar(5) DEFAULT NULL,
  `n6b` varchar(255) DEFAULT NULL,
  `o1a` varchar(5) DEFAULT NULL,
  `o1b` varchar(255) DEFAULT NULL,
  `o2a` varchar(5) DEFAULT NULL,
  `o2b` varchar(255) DEFAULT NULL,
  `o3a` varchar(5) DEFAULT NULL,
  `o3b` varchar(255) DEFAULT NULL,
  `pa` varchar(5) DEFAULT NULL,
  `pb` varchar(255) DEFAULT NULL,
  `q1a` varchar(5) DEFAULT NULL,
  `q1b` varchar(255) DEFAULT NULL,
  `q2a` varchar(5) DEFAULT NULL,
  `q2b` varchar(255) DEFAULT NULL,
  `ra` varchar(5) DEFAULT NULL,
  `rb` varchar(255) DEFAULT NULL,
  `s` text DEFAULT NULL,
  `t` text DEFAULT NULL,
  `v` text DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ms_geo_cluster`;
CREATE TABLE `ms_geo_cluster` (
  `clusterid` int(11) NOT NULL AUTO_INCREMENT,
  `lgaid` int(11) DEFAULT NULL,
  `cluster` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`clusterid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ms_geo_comm`;
CREATE TABLE `ms_geo_comm` (
  `comid` int(11) NOT NULL AUTO_INCREMENT,
  `wardid` int(11) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `community` varchar(255) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`comid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14938 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ms_geo_dp`;
CREATE TABLE `ms_geo_dp` (
  `dpid` int(11) NOT NULL AUTO_INCREMENT,
  `wardid` int(11) NOT NULL,
  `dp` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`dpid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4081 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ms_geo_lga`;
CREATE TABLE `ms_geo_lga` (
  `LgaId` int(11) NOT NULL AUTO_INCREMENT,
  `StateId` int(11) NOT NULL,
  `state_code` varchar(5) DEFAULT NULL,
  `lga_map_code` varchar(20) DEFAULT NULL,
  `Fullname` varchar(50) NOT NULL,
  `longitude` varchar(100) DEFAULT NULL,
  `latitude` varchar(100) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`LgaId`) USING BTREE,
  KEY `mslga_Fullname` (`Fullname`) USING BTREE,
  KEY `StateId` (`StateId`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=682 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `ms_geo_state`;
CREATE TABLE `ms_geo_state` (
  `StateId` int(11) NOT NULL AUTO_INCREMENT,
  `RegionId` int(11) DEFAULT NULL,
  `Fullname` varchar(20) NOT NULL,
  `state_code` varchar(3) DEFAULT NULL,
  `Zone` varchar(30) DEFAULT NULL,
  `longitude` varchar(100) DEFAULT NULL,
  `latitude` varchar(100) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`StateId`) USING BTREE,
  KEY `msstate_Fullname` (`Fullname`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `ms_geo_ward`;
CREATE TABLE `ms_geo_ward` (
  `wardid` int(11) NOT NULL AUTO_INCREMENT,
  `lgaid` int(11) NOT NULL,
  `ward` varchar(255) DEFAULT NULL,
  `longitude` varchar(100) DEFAULT NULL,
  `latitude` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`wardid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2326 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ms_product_item`;
CREATE TABLE `ms_product_item` (
  `itemid` int(11) NOT NULL AUTO_INCREMENT,
  `gtin` varchar(14) NOT NULL,
  `hierarchy_level` varchar(12) NOT NULL,
  `brand_name` varchar(70) DEFAULT NULL,
  `product_description` varchar(200) NOT NULL,
  `manufacturer_gln` varchar(13) DEFAULT NULL,
  `manufacturer_name` varchar(200) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `created_by` varchar(24) NOT NULL,
  `updated` datetime DEFAULT current_timestamp(),
  `updated_by` varchar(24) NOT NULL,
  `source` varchar(24) NOT NULL,
  PRIMARY KEY (`itemid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ms_product_sgtin`;
CREATE TABLE `ms_product_sgtin` (
  `sgtinid` int(11) NOT NULL AUTO_INCREMENT,
  `itemid` int(11) NOT NULL,
  `sgtin` varchar(20) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(24) NOT NULL,
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(24) NOT NULL,
  `source` varchar(24) NOT NULL,
  PRIMARY KEY (`sgtinid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `ms_product_sscc`;
CREATE TABLE `ms_product_sscc` (
  `ssccid` int(11) NOT NULL AUTO_INCREMENT,
  `itemid` int(11) NOT NULL,
  `sscc` varchar(18) NOT NULL,
  `production_date` date DEFAULT NULL,
  `batch_no` varchar(20) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(24) NOT NULL,
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(24) NOT NULL,
  `source` varchar(24) NOT NULL,
  PRIMARY KEY (`ssccid`) USING BTREE,
  KEY `ms_product_item_fk` (`itemid`) USING BTREE,
  CONSTRAINT `ms_product_item_fk` FOREIGN KEY (`itemid`) REFERENCES `ms_product_item` (`itemid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard`;
CREATE TABLE `nc_netcard` (
  `ncid` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(60) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `location` varchar(100) DEFAULT NULL COMMENT 'state | lga | ward | mobilizer | beneficiary',
  `location_value` int(11) DEFAULT NULL COMMENT 'state(100)|lga(80)|ward(60)|mobilizer(40)|downloaded(30)|beneficiary(20)',
  `geo_level` varchar(20) DEFAULT NULL COMMENT 'state | lga | ward (Keeps movement memory)',
  `geo_level_id` int(11) DEFAULT NULL,
  `state_mtid` int(11) DEFAULT NULL,
  `stateid` int(11) DEFAULT NULL,
  `lga_mtid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `ward_mtid` int(11) DEFAULT NULL,
  `wardid` int(11) DEFAULT NULL,
  `mobilizer_userid` int(11) DEFAULT NULL,
  `atid` int(11) DEFAULT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `beneficiaryid` int(11) DEFAULT NULL,
  `utid` int(11) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `updated` datetime DEFAULT current_timestamp(),
  `status` text DEFAULT NULL COMMENT 'internal | mobilization | used',
  PRIMARY KEY (`ncid`) USING BTREE,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `location` (`location`) USING BTREE,
  KEY `location_value` (`location_value`) USING BTREE,
  KEY `device_serial` (`device_serial`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21001 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_allocation`;
CREATE TABLE `nc_netcard_allocation` (
  `atid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `total` int(11) DEFAULT NULL,
  `a_type` varchar(20) DEFAULT NULL COMMENT 'forward |reverse. e-Netcard allocation type',
  `origin` varchar(20) DEFAULT NULL COMMENT 'ward | mobilizer',
  `origin_id` int(11) DEFAULT NULL,
  `destination` varchar(20) DEFAULT NULL COMMENT 'ward | mobilizer',
  `destination_userid` int(11) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`atid`) USING BTREE,
  KEY `a_type` (`a_type`) USING BTREE,
  KEY `origin` (`origin`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_allocation_online`;
CREATE TABLE `nc_netcard_allocation_online` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hhm_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_allocation_order`;
CREATE TABLE `nc_netcard_allocation_order` (
  `orderid` int(11) NOT NULL AUTO_INCREMENT,
  `hhm_id` int(11) NOT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `requester_id` int(11) DEFAULT NULL,
  `total_order` int(11) DEFAULT NULL,
  `total_fulfilment` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL COMMENT 'pending | fulfilled',
  `created` datetime DEFAULT current_timestamp(),
  `fulfilled_date` datetime DEFAULT NULL,
  PRIMARY KEY (`orderid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `device_serial` (`device_serial`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_download`;
CREATE TABLE `nc_netcard_download` (
  `sn` int(11) NOT NULL AUTO_INCREMENT,
  `download_id` varchar(50) NOT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `is_confirmed` tinyint(1) DEFAULT 0,
  `is_destroyed` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'pending',
  `netcard_list` text DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `updated` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`sn`) USING BTREE,
  UNIQUE KEY `download_id` (`download_id`) USING BTREE,
  KEY `device_id` (`device_id`) USING BTREE,
  KEY `user_id` (`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_movement`;
CREATE TABLE `nc_netcard_movement` (
  `mtid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `total` int(11) DEFAULT NULL COMMENT 'Total number of netcard moved',
  `move_type` varchar(10) DEFAULT NULL COMMENT 'forward | reverse',
  `origin_level` varchar(30) DEFAULT NULL,
  `origin_level_id` int(11) DEFAULT NULL,
  `destination_level` varchar(30) DEFAULT NULL,
  `destination_level_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`mtid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_unlocked_log`;
CREATE TABLE `nc_netcard_unlocked_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hhm_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `amount` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `hhm_id` (`hhm_id`) USING BTREE,
  KEY `device_serial` (`device_serial`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_unused_pushed`;
CREATE TABLE `nc_netcard_unused_pushed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hhm_id` int(11) NOT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_netcard_usage`;
CREATE TABLE `nc_netcard_usage` (
  `utid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `tokenid` int(11) NOT NULL,
  `token` varchar(60) NOT NULL,
  `ncid_list` varchar(255) NOT NULL,
  `mobid` int(11) DEFAULT NULL COMMENT 'mobilization ID',
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`utid`) USING BTREE,
  KEY `utid` (`utid`,`ncid_list`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_token`;
CREATE TABLE `nc_token` (
  `tokenid` int(11) NOT NULL AUTO_INCREMENT,
  `batchid` int(11) DEFAULT NULL,
  `batch_no` varchar(20) DEFAULT NULL,
  `serial_no` varchar(20) DEFAULT NULL,
  `uuid` varchar(60) NOT NULL,
  `status` varchar(100) DEFAULT NULL,
  `status_code` smallint(6) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `updated` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`tokenid`) USING BTREE,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `serial_no` (`serial_no`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=497 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `nc_token_batch`;
CREATE TABLE `nc_token_batch` (
  `batchid` int(11) NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(20) DEFAULT NULL,
  `deviceid` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`batchid`) USING BTREE,
  KEY `batch_no` (`batch_no`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `pmi_mobilization_geo_location`;
;

DROP TABLE IF EXISTS `smc_child`;
CREATE TABLE `smc_child` (
  `child_id` int(11) NOT NULL AUTO_INCREMENT,
  `hh_token` varchar(50) DEFAULT NULL,
  `beneficiary_id` varchar(50) NOT NULL,
  `dpid` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date NOT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`child_id`) USING BTREE,
  UNIQUE KEY `beneficiary_id` (`beneficiary_id`) USING BTREE,
  KEY `hh_token` (`hh_token`) USING BTREE,
  KEY `comid` (`dpid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=54155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_child_household`;
CREATE TABLE `smc_child_household` (
  `hhid` int(11) NOT NULL AUTO_INCREMENT,
  `dpid` int(11) DEFAULT NULL,
  `hh_token` varchar(50) DEFAULT NULL,
  `hoh_name` varchar(100) DEFAULT NULL,
  `hoh_phone` varchar(20) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`hhid`) USING BTREE,
  UNIQUE KEY `hh_token` (`hh_token`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=55155 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_cms_location`;
CREATE TABLE `smc_cms_location` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `cms_name` varchar(255) NOT NULL,
  `level` varchar(100) DEFAULT NULL COMMENT 'State or LGA',
  `address` varchar(255) DEFAULT NULL,
  `poc` varchar(200) DEFAULT NULL,
  `poc_phone` varchar(200) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_commodity`;
CREATE TABLE `smc_commodity` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `min_age` int(11) DEFAULT NULL,
  `max_age` int(11) DEFAULT NULL,
  `extension_age` int(11) DEFAULT 0,
  `product_value` int(11) DEFAULT NULL,
  `primary_qty` int(11) NOT NULL DEFAULT 0,
  `secondary_qty` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`product_id`) USING BTREE,
  KEY `drug_index` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_drug_administration`;
CREATE TABLE `smc_drug_administration` (
  `adm_id` int(11) NOT NULL AUTO_INCREMENT,
  `periodid` int(11) DEFAULT NULL COMMENT 'Visit period',
  `uid` varchar(50) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `beneficiary_id` varchar(50) DEFAULT NULL COMMENT 'child etoken',
  `is_eligible` smallint(6) DEFAULT 1 COMMENT 'check if child is eligible or not',
  `not_eligible_reason` varchar(255) NOT NULL,
  `is_refer` smallint(6) DEFAULT 0 COMMENT 'check to determine is child is given referal form',
  `issue_id` int(11) DEFAULT NULL COMMENT 'The reference to issue',
  `redose_issue_id` int(11) NOT NULL,
  `drug` varchar(255) DEFAULT NULL COMMENT 'Drug admitted',
  `drug_qty` int(11) NOT NULL DEFAULT 0,
  `redose_count` int(11) NOT NULL DEFAULT 0,
  `redose_reason` varchar(250) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`adm_id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE,
  KEY `beneficiary_id` (`beneficiary_id`) USING BTREE,
  KEY `dpid` (`dpid`) USING BTREE,
  KEY `periodid` (`periodid`) USING BTREE,
  KEY `drug` (`drug`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `device_serial` (`device_serial`) USING BTREE,
  KEY `app_version` (`app_version`) USING BTREE,
  KEY `issue_id` (`issue_id`),
  KEY `resode_issue_id` (`redose_issue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_aggragator`;
CREATE TABLE `smc_icc_aggragator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cdd_lead_id` int(11) NOT NULL,
  `drug` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `downloaded` int(11) DEFAULT 0,
  `device_id` varchar(100) DEFAULT NULL,
  `version` varchar(100) DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `cdd_lead_id` (`cdd_lead_id`) USING BTREE,
  KEY `drug` (`drug`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_collection`;
CREATE TABLE `smc_icc_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `periodid` int(11) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `dpid` int(11) NOT NULL,
  `drug` varchar(100) DEFAULT NULL,
  `qty` tinyint(4) DEFAULT NULL,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `issue_date` datetime DEFAULT NULL,
  `cdd_lead_id` int(11) NOT NULL,
  `download_id` varchar(100) DEFAULT NULL,
  `download_date` datetime DEFAULT NULL,
  `is_download_confirm` tinyint(1) NOT NULL DEFAULT 0,
  `download_confirm_date` datetime DEFAULT NULL,
  `is_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `accepted_date` datetime DEFAULT NULL,
  `calculated_used` int(11) DEFAULT 0 COMMENT 'Keeps records of drug administration used',
  `calculated_partial` int(11) DEFAULT 0,
  `is_returned` tinyint(1) NOT NULL DEFAULT 0,
  `returned_qty` int(11) DEFAULT NULL,
  `returned_partial` int(11) DEFAULT NULL,
  `returned_date` datetime DEFAULT NULL,
  `is_reconciled` tinyint(1) NOT NULL DEFAULT 0,
  `reconciled_qty` int(11) DEFAULT NULL,
  `reconciled_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'issued' COMMENT 'issued - 10\r\npending - 20\r\nconfirmed - 30\r\naccepted - 40\r\nreturned - 50\r\nreconciled - 60',
  `status_code` tinyint(2) NOT NULL DEFAULT 10,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `issue_id` (`issue_id`),
  KEY `download_id` (`download_id`),
  KEY `status_code` (`status_code`),
  KEY `cdd_lead_id` (`cdd_lead_id`),
  KEY `dpid` (`dpid`),
  KEY `periodid` (`periodid`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_download_log`;
CREATE TABLE `smc_icc_download_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `download_id` varchar(50) NOT NULL,
  `issue_id` int(11) DEFAULT NULL,
  `cdd_lead_id` int(11) DEFAULT NULL,
  `drug` varchar(100) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `version` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `aggr_id` (`download_id`) USING BTREE,
  KEY `cdd_lead_id` (`cdd_lead_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_issue`;
CREATE TABLE `smc_icc_issue` (
  `issue_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(100) NOT NULL,
  `dpid` int(11) NOT NULL,
  `issuer_id` int(11) DEFAULT NULL,
  `cdd_lead_id` int(11) DEFAULT NULL,
  `cdd_team_code` varchar(50) DEFAULT NULL,
  `periodid` int(11) DEFAULT NULL,
  `issue_date` datetime DEFAULT NULL,
  `issue_day` varchar(50) DEFAULT NULL,
  `issue_drug` varchar(200) DEFAULT NULL,
  `drug_qty` int(11) DEFAULT NULL,
  `confirmation` tinyint(1) DEFAULT 0 COMMENT '0 - Awaiting confirmation\r\n1 - Confirmed\r\n-1 - rejected',
  `confirmation_note` text DEFAULT NULL,
  `device_serial` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  `updated` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`issue_id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE,
  KEY `dpdi` (`dpid`) USING BTREE,
  KEY `issuer_id` (`issuer_id`) USING BTREE,
  KEY `cdd_lead_id` (`cdd_lead_id`) USING BTREE,
  KEY `cdd_team_code` (`cdd_team_code`) USING BTREE,
  KEY `periodid` (`periodid`) USING BTREE,
  KEY `issue_drug` (`issue_drug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_push`;
CREATE TABLE `smc_icc_push` (
  `push_id` int(11) NOT NULL AUTO_INCREMENT,
  `periodid` int(11) DEFAULT NULL,
  `dpid` int(11) NOT NULL,
  `issue_id` int(11) DEFAULT NULL,
  `cdd_lead_id` int(11) NOT NULL,
  `drug` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `version` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`push_id`) USING BTREE,
  KEY `dpid` (`dpid`) USING BTREE,
  KEY `cdd_lead_id` (`cdd_lead_id`) USING BTREE,
  KEY `drug` (`drug`) USING BTREE,
  KEY `periodid` (`periodid`),
  KEY `issue_id` (`issue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_receive`;
CREATE TABLE `smc_icc_receive` (
  `receive_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(100) NOT NULL,
  `dpid` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `cdd_lead_id` int(11) DEFAULT NULL,
  `cdd_team_code` varchar(50) DEFAULT NULL,
  `periodid` int(11) DEFAULT NULL,
  `received_date` datetime DEFAULT NULL,
  `received_day` varchar(50) DEFAULT NULL,
  `received_drug` varchar(255) NOT NULL DEFAULT '0',
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `full_dose_qty` int(11) NOT NULL DEFAULT 0,
  `partial_qty` int(11) NOT NULL DEFAULT 0,
  `wasted_qty` int(11) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`receive_id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE,
  KEY `dpid` (`dpid`) USING BTREE,
  KEY `receiver_id` (`receiver_id`) USING BTREE,
  KEY `cdd_lead_id` (`cdd_lead_id`) USING BTREE,
  KEY `cdd_team_code` (`cdd_team_code`) USING BTREE,
  KEY `periodid` (`periodid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_reconcile`;
CREATE TABLE `smc_icc_reconcile` (
  `recon_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) NOT NULL,
  `cdd_lead_id` int(11) NOT NULL,
  `drug` varchar(100) NOT NULL,
  `used_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'Total used',
  `full_qty` int(11) DEFAULT NULL COMMENT 'total full reconcile',
  `partial_qty` int(11) DEFAULT NULL COMMENT 'total partial reconciled',
  `wasted_qty` int(11) DEFAULT NULL COMMENT 'total adjustment',
  `loss_qty` int(11) NOT NULL DEFAULT 0 COMMENT 'discrepancy if any, 0 by default',
  `loss_reason` varchar(255) DEFAULT NULL COMMENT 'reason for discrepancy ',
  `receiver_id` int(11) DEFAULT NULL COMMENT 'HFW id',
  `device_serial` varchar(50) DEFAULT NULL,
  `app_version` varchar(50) DEFAULT NULL,
  `reconcile_date` datetime DEFAULT NULL COMMENT 'date the batch is officially closed',
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`recon_id`) USING BTREE,
  UNIQUE KEY `issue_id` (`issue_id`),
  KEY `cdd_lead_id` (`cdd_lead_id`) USING BTREE,
  KEY `drug` (`drug`) USING BTREE,
  KEY `device_serial` (`device_serial`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_reconcile_log`;
CREATE TABLE `smc_icc_reconcile_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` varchar(100) NOT NULL,
  `cdd_lead_id` int(11) NOT NULL,
  `dpid` int(11) NOT NULL,
  `drug` varchar(100) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `device_id` varchar(100) DEFAULT NULL,
  `app_version` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`log_id`) USING BTREE,
  KEY `cdd_lead_id` (`cdd_lead_id`) USING BTREE,
  KEY `dpid` (`dpid`) USING BTREE,
  KEY `drug` (`drug`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_icc_unlock`;
CREATE TABLE `smc_icc_unlock` (
  `unlock_id` int(11) NOT NULL AUTO_INCREMENT,
  `issue_id` int(11) DEFAULT NULL,
  `dpid` int(11) NOT NULL,
  `cdd_lead_id` int(11) NOT NULL,
  `drug` varchar(100) NOT NULL,
  `qty` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL COMMENT 'The user who make the unlock',
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`unlock_id`) USING BTREE,
  KEY `issue_id` (`issue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_inventory_central`;
CREATE TABLE `smc_inventory_central` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `location_type` enum('CMS','Facility') DEFAULT NULL COMMENT 'cms | facility',
  `location_id` int(11) DEFAULT NULL COMMENT 'CMS ID or Facility ID',
  `batch` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `rate` decimal(8,2) DEFAULT NULL COMMENT 'Unit Cost',
  `unit` varchar(100) DEFAULT NULL,
  `primary_qty` int(11) DEFAULT NULL,
  `secondary_qty` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`inventory_id`),
  KEY `product_code` (`product_code`),
  KEY `location_type` (`location_type`),
  KEY `location_id` (`location_id`),
  KEY `batch` (`batch`),
  KEY `expiry` (`expiry`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_inventory_central_transit`;
CREATE TABLE `smc_inventory_central_transit` (
  `inventory_id` int(11) NOT NULL AUTO_INCREMENT,
  `outbound_id` int(11) NOT NULL,
  `shipment_id` int(11) DEFAULT NULL,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `location_type` enum('CMS','Facility') DEFAULT NULL COMMENT 'cms | facility',
  `location_id` int(11) DEFAULT NULL COMMENT 'CMS ID or Facility ID',
  `batch` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `rate` decimal(8,2) DEFAULT NULL COMMENT 'Unit Cost',
  `unit` varchar(100) DEFAULT NULL,
  `primary_qty` int(11) DEFAULT NULL,
  `secondary_qty` int(11) DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`inventory_id`),
  KEY `product_code` (`product_code`),
  KEY `location_type` (`location_type`),
  KEY `location_id` (`location_id`),
  KEY `batch` (`batch`),
  KEY `expiry` (`expiry`),
  KEY `outbound_id` (`outbound_id`),
  KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_inventory_inbound`;
CREATE TABLE `smc_inventory_inbound` (
  `inbound_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `location_type` enum('CMS','Facility') DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL COMMENT 'CMS id or Facility id',
  `batch` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `rate` decimal(8,2) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `previous_primary_qty` int(11) NOT NULL DEFAULT 0,
  `previous_secondary_qty` int(11) NOT NULL DEFAULT 0,
  `current_primary_qty` int(11) NOT NULL DEFAULT 0,
  `current_secondary_qty` int(11) NOT NULL DEFAULT 0,
  `userid` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`inbound_id`),
  KEY `product_code` (`product_code`),
  KEY `location_id` (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_inventory_outbound`;
CREATE TABLE `smc_inventory_outbound` (
  `outbound_id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) DEFAULT NULL,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `location_type` enum('CMS','Facility') DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL COMMENT 'CMS id or Facility id',
  `batch` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `rate` decimal(8,2) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `previous_primary_qty` int(11) NOT NULL DEFAULT 0,
  `previous_secondary_qty` int(11) NOT NULL DEFAULT 0,
  `current_primary_qty` int(11) NOT NULL DEFAULT 0,
  `current_secondary_qty` int(11) NOT NULL DEFAULT 0,
  `userid` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`outbound_id`),
  KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_inventory_transaction`;
CREATE TABLE `smc_inventory_transaction` (
  `trans_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `location_type` enum('CMS','Facility') DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `rate` decimal(8,2) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `primary_qty` int(11) NOT NULL,
  `secondary_qty` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  `transaction_type` enum('In','Out') NOT NULL COMMENT 'in (add) - out (less)',
  PRIMARY KEY (`trans_id`),
  KEY `product_code` (`product_code`),
  KEY `location_type` (`location_type`),
  KEY `location_id` (`location_id`),
  KEY `batch` (`batch`),
  KEY `expiry` (`expiry`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_inventory_transfer`;
CREATE TABLE `smc_inventory_transfer` (
  `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `source_facility_id` int(11) NOT NULL,
  `destination_facility_id` int(11) NOT NULL,
  `product_code` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `rate` decimal(8,2) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `primary_qty` int(11) NOT NULL DEFAULT 0,
  `secondary_qty` int(11) NOT NULL DEFAULT 0,
  `userid` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_approvals`;
CREATE TABLE `smc_logistics_approvals` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_type` enum('Origin','Conveyor','Destination') NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Approval user details, containing all the approval user details',
  `approval_name` varchar(255) DEFAULT NULL,
  `approval_designation` varchar(255) DEFAULT NULL,
  `approval_phone` varchar(255) DEFAULT NULL,
  `location_string` text DEFAULT NULL COMMENT 'Geo-string of the user',
  `signature` mediumblob DEFAULT NULL COMMENT 'bitmap signature',
  `approve_date` date DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `device_serial` varchar(255) DEFAULT NULL,
  `app_version` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`approval_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_issues`;
CREATE TABLE `smc_logistics_issues` (
  `issue_id` int(11) NOT NULL AUTO_INCREMENT,
  `periodid` int(11) NOT NULL,
  `dpid` int(11) NOT NULL,
  `product_code` varchar(100) NOT NULL,
  `product_name` varchar(200) DEFAULT NULL,
  `primary_qty` int(11) DEFAULT NULL,
  `secondary_qty` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`issue_id`) USING BTREE,
  KEY `dpid` (`dpid`) USING BTREE,
  KEY `product_code` (`product_code`) USING BTREE,
  KEY `periodid` (`periodid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_movement`;
CREATE TABLE `smc_logistics_movement` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `periodid` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `transporter_id` int(11) DEFAULT NULL,
  `userid` int(11) NOT NULL,
  `conveyor_id` int(11) DEFAULT NULL,
  `source_approval_id` int(11) DEFAULT NULL,
  `conveyor_approval_id` int(11) DEFAULT NULL,
  `destination_approval_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending' COMMENT 'movement status',
  `status_value` int(11) DEFAULT 10 COMMENT 'Movement status value',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`movement_id`),
  KEY `source_approval_id` (`source_approval_id`),
  KEY `transport_approval_id` (`conveyor_approval_id`),
  KEY `destination_approval_id` (`destination_approval_id`),
  KEY `periodid` (`periodid`),
  KEY `transporter_id` (`transporter_id`),
  KEY `conveyor_approval_id` (`conveyor_approval_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_movement_items`;
CREATE TABLE `smc_logistics_movement_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `movement_id` int(11) NOT NULL,
  `shipment_id` int(11) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `movement_id` (`movement_id`),
  KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_shipment`;
CREATE TABLE `smc_logistics_shipment` (
  `shipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_no` varchar(200) NOT NULL,
  `shipment_type` enum('Forward','Reverse') DEFAULT NULL COMMENT 'Forward or Reverse',
  `periodid` int(11) NOT NULL,
  `origin_id` int(11) NOT NULL,
  `origin_location_type` enum('CMS','Facility') DEFAULT NULL,
  `origin_string` text DEFAULT NULL,
  `destination_id` int(11) NOT NULL,
  `destination_location_type` enum('CMS','Facility') DEFAULT NULL,
  `destination_string` text DEFAULT NULL,
  `total_qty` int(11) NOT NULL,
  `total_value` double(8,2) DEFAULT NULL,
  `rate` double(8,2) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `shipment_status` enum('Pending','Processing','Route Confirmation','Ready for Dispatch','Dispatched','Delivered','Cancelled','On Hold','Rejected','Discrepancy Reported','Returned') NOT NULL,
  `status_value` int(11) DEFAULT 10,
  `source_approval_id` int(11) DEFAULT NULL,
  `conveyor_approval_id` int(11) DEFAULT NULL,
  `destination_approval_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`shipment_id`),
  KEY `shipment_no` (`shipment_no`),
  KEY `origin_id` (`origin_id`),
  KEY `destination_id` (`destination_id`),
  KEY `source_approval` (`source_approval_id`),
  KEY `transport_approval` (`conveyor_approval_id`),
  KEY `destination_approval` (`destination_approval_id`),
  KEY `conveyor_approval` (`conveyor_approval_id`),
  KEY `status_value` (`status_value`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_shipment_item`;
CREATE TABLE `smc_logistics_shipment_item` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `shipment_id` int(11) NOT NULL,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `rate` double(8,2) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `primary_qty` int(11) DEFAULT NULL,
  `secondary_qty` int(11) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `shipment_no` (`shipment_id`),
  KEY `destination_id` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_shipment_sorting`;
CREATE TABLE `smc_logistics_shipment_sorting` (
  `sorting_id` int(11) NOT NULL AUTO_INCREMENT,
  `lgaid` int(11) DEFAULT NULL,
  `lga` varchar(200) DEFAULT NULL,
  `origin_id` int(11) DEFAULT NULL,
  `origin_string` varchar(255) DEFAULT NULL,
  `origin_type` enum('CMS','Facility') DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `destination_string` text DEFAULT NULL,
  `destination_type` enum('CMS','Facility') DEFAULT NULL,
  `periodid` int(11) DEFAULT NULL,
  `product_code` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `rate` double(8,2) DEFAULT NULL,
  `unit` varchar(100) DEFAULT NULL,
  `primary_qty` int(11) DEFAULT NULL,
  `secondary_qty` int(11) DEFAULT NULL,
  `is_used` tinyint(4) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`sorting_id`),
  KEY `origin_id` (`origin_id`),
  KEY `destination_id` (`destination_id`),
  KEY `product_code` (`product_code`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_logistics_transporter`;
CREATE TABLE `smc_logistics_transporter` (
  `transporter_id` int(11) NOT NULL AUTO_INCREMENT,
  `transporter` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `poc` varchar(200) DEFAULT NULL,
  `poc_phone` varchar(200) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transporter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_period`;
CREATE TABLE `smc_period` (
  `periodid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `active` smallint(6) NOT NULL DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`periodid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_process_setting`;
CREATE TABLE `smc_process_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pointer` varchar(50) NOT NULL COMMENT 'Key for find called pointer',
  `val` varchar(255) NOT NULL COMMENT 'Value to retreive',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `key_value` (`pointer`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_product`;
CREATE TABLE `smc_product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `min_age` int(11) DEFAULT NULL,
  `max_age` int(11) DEFAULT NULL,
  `extension_age` int(11) DEFAULT 0,
  `com_value` int(11) DEFAULT NULL,
  `expiry` date DEFAULT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `rate` decimal(10,0) NOT NULL COMMENT 'unit cost',
  `unit` varchar(100) DEFAULT NULL,
  `primary_qty` int(11) NOT NULL DEFAULT 1,
  `secondary_qty` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`product_id`) USING BTREE,
  KEY `drug_index` (`name`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `smc_referer_record`;
CREATE TABLE `smc_referer_record` (
  `ref_id` int(11) NOT NULL AUTO_INCREMENT,
  `adm_id` int(11) DEFAULT NULL,
  `uid` varchar(100) NOT NULL,
  `beneficiary_id` varchar(20) NOT NULL COMMENT 'Child beneficiary id',
  `userid` int(11) NOT NULL COMMENT 'HFW',
  `refer_type` varchar(50) NOT NULL COMMENT 'referrer type',
  `ill_cause_of` tinyint(1) DEFAULT NULL COMMENT 'Child evaluated to determine cause of illness',
  `ill_diagnosis` varchar(255) DEFAULT NULL COMMENT 'Diagnosis',
  `ill_child_treated` tinyint(1) DEFAULT NULL COMMENT 'Child treated',
  `ill_dose_of_treatment` varchar(255) DEFAULT NULL COMMENT 'Name and dose of treatment',
  `ill_admitted` tinyint(1) DEFAULT NULL COMMENT 'Child admitted to health facility or referred to hospital for severe illness',
  `fe_tested_for_malaria` tinyint(1) DEFAULT NULL COMMENT 'Child tested for malaria',
  `fe_rdt_result` tinyint(1) DEFAULT NULL COMMENT 'RDT result',
  `fe_admitted` tinyint(1) DEFAULT NULL COMMENT 'Child admitted to health facility or referred to hospital for severe malaria',
  `fe_treated_with_act` tinyint(1) DEFAULT NULL COMMENT 'Child with confirmed positive malaria test treated with ACT',
  `fe_name_dose` varchar(255) DEFAULT NULL COMMENT 'Name and dose of ACT',
  `fe_given_spaq` tinyint(1) DEFAULT NULL COMMENT 'Child with negative RDT given SPAQ this cycle',
  `ad_child_evaluated` tinyint(1) DEFAULT NULL COMMENT 'Child evaluated for adverse drug reaction to SP and AQ',
  `ad_pv_form_completed` tinyint(1) DEFAULT NULL COMMENT 'National PV Form was completed',
  `ad_child_admitted` tinyint(1) DEFAULT NULL COMMENT 'Child admitted to health facility or referred to hospital for SAE',
  `outcome` text DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`ref_id`) USING BTREE,
  UNIQUE KEY `uid` (`uid`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE,
  KEY `refer_type` (`refer_type`) USING BTREE,
  KEY `adm_id` (`adm_id`) USING BTREE,
  KEY `beneficiary_id` (`beneficiary_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sms_reasons`;
CREATE TABLE `sms_reasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `socket_connect`;
CREATE TABLE `socket_connect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `socket_id` varchar(100) NOT NULL,
  `token` varchar(100) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  `deviceid` varchar(100) DEFAULT NULL,
  `loginid` varchar(100) DEFAULT NULL,
  `roleid` int(11) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `geo_level` varchar(100) DEFAULT NULL,
  `geo_level_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `socket_id` (`socket_id`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `socket_queue`;
CREATE TABLE `socket_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `directive` varchar(255) DEFAULT NULL,
  `handshake` tinyint(1) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created` datetime DEFAULT current_timestamp(),
  `updated` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_bank_code`;
CREATE TABLE `sys_bank_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_code` varchar(10) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `bank_code` (`bank_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_default_settings`;
CREATE TABLE `sys_default_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state` varchar(100) DEFAULT NULL,
  `stateid` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `logo` blob DEFAULT NULL,
  `receipt_header` text DEFAULT NULL,
  `id_key` text DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_device_login`;
CREATE TABLE `sys_device_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_serial` varchar(20) DEFAULT NULL,
  `loginid` varchar(20) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `device_serial` (`device_serial`) USING BTREE,
  KEY `loginid` (`loginid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_device_registry`;
CREATE TABLE `sys_device_registry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_name` varchar(100) DEFAULT NULL,
  `device_id` varchar(50) DEFAULT NULL,
  `guid` varchar(50) DEFAULT NULL,
  `serial_no` varchar(50) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `imei1` varchar(100) DEFAULT NULL,
  `imei2` varchar(100) DEFAULT NULL,
  `phone_serial` varchar(50) DEFAULT NULL,
  `sim_network` varchar(50) DEFAULT NULL COMMENT 'SIM network carrier',
  `sim_serial` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 0,
  `connected` datetime DEFAULT NULL COMMENT 'Last time connected',
  `connected_loginid` varchar(20) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `device_id` (`device_id`) USING BTREE,
  KEY `guid` (`guid`) USING BTREE,
  KEY `connected_loginid` (`connected_loginid`) USING BTREE,
  KEY `serial_no` (`serial_no`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_geo_codex`;
CREATE TABLE `sys_geo_codex` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parentid` int(11) DEFAULT NULL,
  `guid` varchar(50) DEFAULT NULL COMMENT 'unique identifier',
  `stateid` int(11) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `clusterid` int(11) DEFAULT NULL,
  `wardid` int(11) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `comid` int(11) DEFAULT NULL,
  `geo_level_id` int(11) DEFAULT NULL,
  `geo_level` varchar(50) DEFAULT NULL,
  `geo_value` int(11) DEFAULT NULL,
  `is_gsone` smallint(6) NOT NULL DEFAULT 0,
  `net_capping` int(2) NOT NULL DEFAULT 4,
  `title` varchar(255) DEFAULT NULL,
  `geo_string` text DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `guid` (`guid`) USING BTREE,
  KEY `geo_level_id` (`geo_level_id`) USING BTREE,
  KEY `geo_level` (`geo_level`) USING BTREE,
  KEY `parentid` (`parentid`) USING BTREE,
  KEY `lgaid` (`lgaid`) USING BTREE,
  KEY `clusterid` (`clusterid`) USING BTREE,
  KEY `wardid` (`wardid`) USING BTREE,
  KEY `dpid` (`dpid`) USING BTREE,
  KEY `comid` (`comid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=12783 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_geo_hierachy_define`;
CREATE TABLE `sys_geo_hierachy_define` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country` varchar(100) DEFAULT NULL,
  `stateid` int(11) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `lgaid` int(11) DEFAULT NULL,
  `lga` varchar(100) DEFAULT NULL,
  `wardid` int(11) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `dpid` int(11) DEFAULT NULL,
  `dp` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_geo_level`;
CREATE TABLE `sys_geo_level` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `geo_level` varchar(100) DEFAULT NULL,
  `geo_value` int(11) DEFAULT NULL,
  `geo_table` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_list_module`;
CREATE TABLE `sys_list_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_list_platform`;
CREATE TABLE `sys_list_platform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_list_privileges`;
CREATE TABLE `sys_list_privileges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_request_counts`;
CREATE TABLE `sys_request_counts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `hour` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `unique_hour` (`date`,`hour`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6923 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `sys_working_hours`;
CREATE TABLE `sys_working_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `active` smallint(1) DEFAULT 1,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `temp_shortener`;
;

DROP TABLE IF EXISTS `tra_attendant`;
CREATE TABLE `tra_attendant` (
  `attendant_id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `participant_id` int(11) DEFAULT NULL,
  `at_type` varchar(20) DEFAULT NULL COMMENT 'Attendant Type Clock-in | Clock-out',
  `bio_auth` tinyint(1) DEFAULT NULL,
  `collected` datetime DEFAULT NULL COMMENT 'date and time it was collected',
  `userid` int(11) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `app_version` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL COMMENT 'Date & time it entered the system',
  PRIMARY KEY (`attendant_id`) USING BTREE,
  KEY `at_type` (`at_type`) USING BTREE,
  KEY `session_id` (`session_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tra_participants`;
CREATE TABLE `tra_participants` (
  `participant_id` int(11) NOT NULL AUTO_INCREMENT,
  `trainingid` int(11) DEFAULT NULL,
  `userid` int(11) DEFAULT NULL,
  PRIMARY KEY (`participant_id`) USING BTREE,
  KEY `trainingid` (`trainingid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tra_session`;
CREATE TABLE `tra_session` (
  `sessionid` int(11) NOT NULL AUTO_INCREMENT,
  `trainingid` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `guid` varchar(50) DEFAULT NULL,
  `session_date` date DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`sessionid`) USING BTREE,
  UNIQUE KEY `guid` (`guid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `tra_training`;
CREATE TABLE `tra_training` (
  `trainingid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `geo_location` varchar(50) DEFAULT NULL,
  `location_id` int(11) DEFAULT NULL,
  `guid` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `participant_count` int(11) DEFAULT 0,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`trainingid`) USING BTREE,
  UNIQUE KEY `guid` (`guid`) USING BTREE,
  KEY `title` (`title`) USING BTREE,
  KEY `location_id` (`location_id`) USING BTREE,
  KEY `geo_location` (`geo_location`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `usr_finance`;
CREATE TABLE `usr_finance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `bank_code` varchar(10) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `account_no` varchar(100) DEFAULT NULL,
  `is_verified` smallint(6) NOT NULL DEFAULT 0,
  `verification_count` int(11) NOT NULL DEFAULT 0,
  `verification_message` varchar(255) DEFAULT NULL,
  `verified_account_name` varchar(255) DEFAULT NULL,
  `verification_status` varchar(10) DEFAULT 'none' COMMENT '[none | warning | failed | success]',
  `last_verified_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1059 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `usr_identity`;
CREATE TABLE `usr_identity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `first` varchar(30) DEFAULT NULL,
  `middle` varchar(30) DEFAULT NULL,
  `last` varchar(30) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1059 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `usr_login`;
CREATE TABLE `usr_login` (
  `userid` int(11) NOT NULL AUTO_INCREMENT,
  `loginid` varchar(30) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `pwd` varchar(100) DEFAULT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `guid` varchar(60) DEFAULT NULL,
  `roleid` int(11) DEFAULT NULL,
  `geo_level` varchar(20) DEFAULT NULL,
  `geo_level_id` int(11) DEFAULT NULL,
  `user_group` varchar(50) DEFAULT NULL,
  `active` smallint(6) NOT NULL DEFAULT 1,
  `device_sn` varchar(255) DEFAULT NULL,
  `device_fcm_token` varchar(255) DEFAULT NULL,
  `is_change_password` smallint(6) NOT NULL DEFAULT 1,
  `created` datetime DEFAULT NULL,
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`userid`) USING BTREE,
  KEY `loginid` (`loginid`) USING BTREE,
  KEY `device_sn` (`device_sn`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1059 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `usr_role`;
CREATE TABLE `usr_role` (
  `roleid` int(11) NOT NULL AUTO_INCREMENT,
  `role_code` varchar(10) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `system_privilege` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `platform` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `module` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `priority` tinyint(1) NOT NULL DEFAULT 0,
  `active` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`roleid`) USING BTREE,
  KEY `role_code` (`role_code`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `usr_security`;
CREATE TABLE `usr_security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `bio_feature` blob DEFAULT NULL,
  `finger` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1059 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `usr_user_activity`;
CREATE TABLE `usr_user_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `platform` varchar(30) DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `result` varchar(20) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  KEY `module` (`module`) USING BTREE,
  KEY `result` (`result`) USING BTREE,
  KEY `created` (`created`) USING BTREE,
  KEY `platform` (`platform`) USING BTREE,
  KEY `userid` (`userid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1307 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `usr_workhour_extension`;
CREATE TABLE `usr_workhour_extension` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) DEFAULT NULL,
  `extension_hour` int(11) DEFAULT NULL,
  `extension_date` date DEFAULT NULL,
  `created_by_userid` int(11) DEFAULT NULL,
  `created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;





DROP TRIGGER IF EXISTS `trg_after_insert_smc_drug_administration`;
CREATE DEFINER=`root`@`localhost` TRIGGER trg_after_insert_smc_drug_administration
AFTER INSERT ON smc_drug_administration
FOR EACH ROW
BEGIN
  -- Increment calculated_used
  UPDATE smc_icc_collection
  SET calculated_used = IFNULL(calculated_used, 0) + 1, qty = IFNULL(qty, 0) - 1
  WHERE issue_id = NEW.issue_id;

  -- Increment calculated_partial if redose_count is 1
  IF NEW.redose_count = 1 AND NEW.redose_issue_id IS NOT NULL THEN
    UPDATE smc_icc_collection
    SET calculated_partial = IFNULL(calculated_partial, 0) + 1, qty = IFNULL(qty, 0) - 1
    WHERE issue_id = NEW.redose_issue_id;
  END IF;
END;

DROP TRIGGER IF EXISTS `trg_after_update_smc_drug_administration`;
CREATE DEFINER=`root`@`localhost` TRIGGER trg_after_update_smc_drug_administration
AFTER UPDATE ON smc_drug_administration
FOR EACH ROW
BEGIN
  -- If redose_count is 1 and redose_issue_id is provided, update partial
  IF NEW.redose_count = 1 AND NEW.redose_issue_id IS NOT NULL THEN
    UPDATE smc_icc_collection
    SET calculated_partial = IFNULL(calculated_partial, 0) + 1, qty = IFNULL(qty, 0) - 1
    WHERE issue_id = NEW.redose_issue_id;
  END IF;
END;

DROP TRIGGER IF EXISTS `after_inbound_insert_create_transaction`;
CREATE DEFINER=`root`@`localhost` TRIGGER after_inbound_insert_create_transaction
AFTER INSERT ON smc_inventory_inbound
FOR EACH ROW
BEGIN
  INSERT INTO smc_inventory_transaction (
    product_code,
    product_name,
    location_type,
    location_id,
    batch,
    expiry,
    rate,
    unit,
    primary_qty,
    secondary_qty,
    created,
    updated,
    transaction_type
  )
  VALUES (
    NEW.product_code,
    NEW.product_name,
    NEW.location_type,
    NEW.location_id,
    NEW.batch,
    NEW.expiry,
    NEW.rate,
    NEW.unit,
    NEW.current_primary_qty,
    NEW.current_secondary_qty,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP,
    'in'
  );
END;

DROP TRIGGER IF EXISTS `after_outbound_insert_create_transaction`;
CREATE DEFINER=`root`@`localhost` TRIGGER after_outbound_insert_create_transaction
AFTER INSERT ON smc_inventory_outbound
FOR EACH ROW
BEGIN
  INSERT INTO smc_inventory_transaction (
    product_code,
    product_name,
    location_type,
    location_id,
    batch,
    expiry,
    rate,
    unit,
    primary_qty,
    secondary_qty,
    created,
    updated,
    transaction_type
  )
  VALUES (
    NEW.product_code,
    NEW.product_name,
    NEW.location_type,
    NEW.location_id,
    NEW.batch,
    NEW.expiry,
    NEW.rate,
    NEW.unit,
    NEW.current_primary_qty,
    NEW.current_secondary_qty,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP,
    'out'
  );
END;

DROP TRIGGER IF EXISTS `trg_insert_to_central_transit`;
CREATE DEFINER=`root`@`localhost` TRIGGER trg_insert_to_central_transit
AFTER INSERT ON smc_inventory_outbound
FOR EACH ROW
BEGIN
  INSERT INTO smc_inventory_central_transit (
    shipment_id,
    outbound_id,
    product_code,
    product_name,
    location_type,
    location_id,
    batch,
    expiry,
    rate,
    unit,
    primary_qty,
    secondary_qty,
    is_used,
    created,
    updated
  )
  VALUES (
    NEW.shipment_id,
    NEW.outbound_id,
    NEW.product_code,
    NEW.product_name,
    NEW.location_type,
    NEW.location_id,
    NEW.batch,
    NEW.expiry,
    NEW.rate,
    NEW.unit,
    NEW.current_primary_qty,
    NEW.current_secondary_qty,
    0, -- default is_used = 0
    NOW(),
    NOW()
  );
END;

DROP TRIGGER IF EXISTS `after_inventory_transaction_insert`;
CREATE DEFINER=`root`@`localhost` TRIGGER after_inventory_transaction_insert
AFTER INSERT ON smc_inventory_transaction
FOR EACH ROW
BEGIN
  DECLARE v_exists INT DEFAULT 0;
  DECLARE v_factor INT;

  -- Determine if quantity should be added or subtracted
  SET v_factor = IF(NEW.transaction_type = 'in', 1, -1);

  -- Check if a matching row exists in smc_inventory_central
  SELECT COUNT(*) INTO v_exists
  FROM smc_inventory_central
  WHERE product_code = NEW.product_code
    AND location_type = NEW.location_type
    AND location_id = NEW.location_id
    AND batch = NEW.batch
    AND expiry = NEW.expiry;

  IF v_exists > 0 THEN
    -- Update existing row
    UPDATE smc_inventory_central
    SET
      primary_qty = primary_qty + (v_factor * NEW.primary_qty),
      secondary_qty = secondary_qty + (v_factor * NEW.secondary_qty),
      updated = CURRENT_TIMESTAMP
    WHERE product_code = NEW.product_code
      AND location_type = NEW.location_type
      AND location_id = NEW.location_id
      AND batch = NEW.batch
      AND expiry = NEW.expiry;
  ELSE
    -- Insert new row
    INSERT INTO smc_inventory_central (
      product_code, product_name, location_type, location_id,
      batch, expiry, rate, unit,
      primary_qty, secondary_qty, created, updated
    )
    VALUES (
      NEW.product_code, NEW.product_name, NEW.location_type, NEW.location_id,
      NEW.batch, NEW.expiry, NEW.rate, NEW.unit,
      v_factor * NEW.primary_qty, v_factor * NEW.secondary_qty,
      CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
    );
  END IF;
END;

DROP TRIGGER IF EXISTS `trg_update_shipment_source_approval`;
CREATE DEFINER=`root`@`localhost` TRIGGER trg_update_shipment_source_approval
AFTER UPDATE ON smc_logistics_movement
FOR EACH ROW
BEGIN
    UPDATE smc_logistics_shipment s
    JOIN smc_logistics_movement_items mi ON s.shipment_id = mi.shipment_id
    SET s.source_approval_id = NEW.source_approval_id
    WHERE mi.movement_id = NEW.movement_id;
END;

DROP TRIGGER IF EXISTS `trg_update_shipment_conveyor_approval`;
CREATE DEFINER=`root`@`localhost` TRIGGER trg_update_shipment_conveyor_approval
AFTER UPDATE ON smc_logistics_movement
FOR EACH ROW
BEGIN
    UPDATE smc_logistics_shipment s
    JOIN smc_logistics_movement_items mi ON s.shipment_id = mi.shipment_id
    SET s.conveyor_approval_id = NEW.conveyor_approval_id
    WHERE mi.movement_id = NEW.movement_id;
END;

DROP TRIGGER IF EXISTS `trg_update_shipment_destination_approval`;
CREATE DEFINER=`root`@`localhost` TRIGGER trg_update_shipment_destination_approval
AFTER UPDATE ON smc_logistics_movement
FOR EACH ROW
BEGIN
    UPDATE smc_logistics_shipment s
    JOIN smc_logistics_movement_items mi ON s.shipment_id = mi.shipment_id
    SET s.destination_approval_id = NEW.destination_approval_id
    WHERE mi.movement_id = NEW.movement_id;
END;