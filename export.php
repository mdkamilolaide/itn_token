<?php

include_once('lib/mysql.min.php');
include_once('lib/common.php');
include_once('lib/config.php');
//  Log actions before leaving
if (CleanData("qid") == 'gen001') {
    #Mobilizers mobilization
    $data = DbHelper::Table("select concat_ws(' ',`usr_login`.`loginid`,`usr_identity`.`first`,`usr_identity`.`middle`,`usr_identity`.`last`) AS `mobilizer`,`usr_identity`.`phone` AS `phone`,count(`hhm_mobilization`.`hhid`) AS `household`,sum(`hhm_mobilization`.`allocated_net`) AS `enetcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,`sys_geo_codex`.`geo_string` AS `geo_string` from (((`hhm_mobilization` join `usr_login` on(`hhm_mobilization`.`hhm_id` = `usr_login`.`userid`)) join `usr_identity` on(`usr_login`.`userid` = `usr_identity`.`userid`)) join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) group by `hhm_mobilization`.`hhm_id`");
    //
    echo json_encode($data);
}
elseif(CleanData("qid") == 'gen002'){
    //  LGA summ location balance
    $data = DbHelper::Table("select `ms_geo_lga`.`Fullname` AS `lga`,sum(1) AS `lga_total`,sum(if(`nc_netcard`.`location_value` = 80,1,0)) AS `lga_balance`,sum(if(`nc_netcard`.`location_value` = 60,1,0)) AS `ward`,sum(if(`nc_netcard`.`location_value` = 40,1,0)) AS `mob_online`,sum(if(`nc_netcard`.`location_value` = 30,1,0)) AS `wallet`,sum(if(`nc_netcard`.`location_value` = 20,1,0)) AS `beneficiary` from (`nc_netcard` join `ms_geo_lga` on(`nc_netcard`.`lgaid` = `ms_geo_lga`.`LgaId`)) group by `nc_netcard`.`lgaid`");
    //
    echo json_encode($data);
}
elseif(CleanData("qid") == 'gen003'){
    //  LGA summ location balance
    $data = DbHelper::Table("select cast(`hhm_mobilization`.`collected_date` as date) AS `date`,count(`hhm_mobilization`.`hhm_id`) AS `household`,sum(`hhm_mobilization`.`allocated_net`) AS `enetcards`,sum(`hhm_mobilization`.`family_size`) AS `family_size`,`sys_geo_codex`.`geo_string` AS `geo_string` from (`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) group by cast(`hhm_mobilization`.`collected_date` as date),`hhm_mobilization`.`dp_id` order by cast(`hhm_mobilization`.`collected_date` as date),`sys_geo_codex`.`geo_string`");
    //
    echo json_encode($data);
}
elseif(CleanData("qid") == 'gen004'){
    //  LGA mobilizers online balances
	$lgaid = CleanData("lgaid");
	if($lgaid){
		$data = DbHelper::Table("select count(`psmitn_platform_pl1`.`nc_netcard`.`ncid`) AS `total`,`psmitn_platform_pl1`.`nc_netcard`.`device_serial` AS `device_serial`,`a`.`hhm` AS `hhm`,`a`.`phone` AS `phone`,`a`.`geo_string` AS `geo_string` from (`psmitn_platform_pl1`.`nc_netcard` join (select `psmitn_platform_pl1`.`usr_login`.`userid` AS `userid`,concat_ws(' ',`psmitn_platform_pl1`.`usr_login`.`loginid`,'-',`psmitn_platform_pl1`.`usr_identity`.`first`,`psmitn_platform_pl1`.`usr_identity`.`middle`,`psmitn_platform_pl1`.`usr_identity`.`last`) AS `hhm`,`psmitn_platform_pl1`.`usr_identity`.`phone` AS `phone`,`psmitn_platform_pl1`.`sys_geo_codex`.`geo_string` AS `geo_string` from ((`psmitn_platform_pl1`.`usr_login` join `psmitn_platform_pl1`.`usr_identity` on(`psmitn_platform_pl1`.`usr_login`.`userid` = `psmitn_platform_pl1`.`usr_identity`.`userid`)) join `psmitn_platform_pl1`.`sys_geo_codex` on(`psmitn_platform_pl1`.`usr_login`.`geo_level` = `psmitn_platform_pl1`.`sys_geo_codex`.`geo_level` and `psmitn_platform_pl1`.`usr_login`.`geo_level_id` = `psmitn_platform_pl1`.`sys_geo_codex`.`geo_level_id`)) where `psmitn_platform_pl1`.`sys_geo_codex`.`lgaid` = $lgaid and `psmitn_platform_pl1`.`usr_login`.`roleid` = 1) `a` on(`psmitn_platform_pl1`.`nc_netcard`.`mobilizer_userid` = `a`.`userid`)) where `psmitn_platform_pl1`.`nc_netcard`.`location_value` = 30 and `psmitn_platform_pl1`.`nc_netcard`.`lgaid` = $lgaid group by `psmitn_platform_pl1`.`nc_netcard`.`mobilizer_userid`");
		//
		echo json_encode($data);
	}else{
		echo json_encode(array('error'=>'Invalid LGA ID'));
	}
}
elseif(CleanData("qid") == 'gen005'){
    //  Distribution aggregate LGA
    $data = DbHelper::Table("select `ms_geo_lga`.`Fullname` AS `lga`,cast(count(`hhm_distribution`.`dis_id`) as signed) AS `households`,cast(sum(`hhm_distribution`.`collected_nets`) as signed) AS `nets_collected` from ((`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`)) group by `sys_geo_codex`.`lgaid`");
    //
    $export_data = convertColumnsToInt($data,['households','nets_collected']);
    echo json_encode($export_data);
}
elseif(CleanData("qid") == 'gen006'){
	
    //  Distribution aggregate by DP
    $data = DbHelper::Table("select `psmitn_platform_pl1`.`ms_geo_lga`.`Fullname` AS `lga`,`psmitn_platform_pl1`.`ms_geo_ward`.`ward` AS `ward`,`psmitn_platform_pl1`.`ms_geo_dp`.`dp` AS `dp`,`psmitn_platform_pl1`.`dsh_mob_summary_dp`.`households` AS `mobilized_households`,`psmitn_platform_pl1`.`dsh_mob_summary_dp`.`enetcards` AS `enetcard_issued`,`psmitn_platform_pl1`.`dsh_mob_summary_dp`.`family_size` AS `family_size`,`a`.`households` AS `redeemed_households`,`a`.`nets` AS `redeemed_nets` from ((((`psmitn_platform_pl1`.`dsh_mob_summary_dp` join (select count(`psmitn_platform_pl1`.`hhm_distribution`.`dis_id`) AS `households`,sum(`psmitn_platform_pl1`.`hhm_distribution`.`collected_nets`) AS `nets`,`psmitn_platform_pl1`.`sys_geo_codex`.`dpid` AS `dpid` from (`psmitn_platform_pl1`.`hhm_distribution` join `psmitn_platform_pl1`.`sys_geo_codex` on(`psmitn_platform_pl1`.`hhm_distribution`.`dp_id` = `psmitn_platform_pl1`.`sys_geo_codex`.`dpid` and `psmitn_platform_pl1`.`sys_geo_codex`.`geo_level` = 'dp')) group by `psmitn_platform_pl1`.`sys_geo_codex`.`dpid`) `a` on(`psmitn_platform_pl1`.`dsh_mob_summary_dp`.`dpid` = `a`.`dpid`)) join `psmitn_platform_pl1`.`ms_geo_lga` on(`psmitn_platform_pl1`.`dsh_mob_summary_dp`.`lgaid` = `psmitn_platform_pl1`.`ms_geo_lga`.`LgaId`)) join `psmitn_platform_pl1`.`ms_geo_ward` on(`psmitn_platform_pl1`.`dsh_mob_summary_dp`.`wardid` = `psmitn_platform_pl1`.`ms_geo_ward`.`wardid`)) join `psmitn_platform_pl1`.`ms_geo_dp` on(`psmitn_platform_pl1`.`dsh_mob_summary_dp`.`dpid` = `psmitn_platform_pl1`.`ms_geo_dp`.`dpid`))");
	$export_data = convertColumnsToInt($data,['mobilized_households','enetcard_issued','family_size','redeemed_households','redeemed_nets']);
	echo json_encode($export_data);
}
elseif(CleanData("qid") == 'gen007'){
    //  unredeemed etoken details
    $data = DbHelper::Table("select `hhm_mobilization`.`hhid` AS `hhid`,`hhm_mobilization`.`hoh_first` AS `hoh_first`,`hhm_mobilization`.`hoh_last` AS `hoh_last`,`hhm_mobilization`.`hoh_phone` AS `hoh_phone`,`hhm_mobilization`.`hoh_gender` AS `hoh_gender`,`hhm_mobilization`.`family_size` AS `family_size`,`hhm_mobilization`.`hod_mother` AS `hod_mother`,`hhm_mobilization`.`sleeping_space` AS `sleeping_space`,`hhm_mobilization`.`adult_female` AS `adult_female`,`hhm_mobilization`.`adult_male` AS `adult_male`,`hhm_mobilization`.`children` AS `children`,`hhm_mobilization`.`allocated_net` AS `allocated_net`,`hhm_mobilization`.`etoken_serial` AS `etoken_serial`,`hhm_mobilization`.`etoken_pin` AS `etoken_pin`,`hhm_mobilization`.`collected_date` AS `collected_date`,`ms_geo_lga`.`Fullname` AS `lga`,`ms_geo_ward`.`ward` AS `ward`,`ms_geo_dp`.`dp` AS `dp` from (((((`hhm_mobilization` join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) left join `hhm_distribution` on(`hhm_mobilization`.`etoken_serial` = `hhm_distribution`.`etoken_serial`)) join `ms_geo_lga` on(`sys_geo_codex`.`lgaid` = `ms_geo_lga`.`LgaId`)) join `ms_geo_ward` on(`sys_geo_codex`.`wardid` = `ms_geo_ward`.`wardid`)) join `ms_geo_dp` on(`sys_geo_codex`.`dpid` = `ms_geo_dp`.`dpid`)) where `hhm_distribution`.`hhid` is null");
    //
    //$export_data = convertColumnsToInt($data,['family_size','sleeping_space','adult_female','adult_male','children','allocated_net']);
	echo json_encode($data);
}
elseif(CleanData("qid") == 'gen008'){
    //  unredeemed etoken details
    $data = DbHelper::Table("select `sys_geo_codex`.`geo_string` AS `geo_string`,sum(if(`hhm_distribution`.`dis_id` is not null,1,0)) AS `redeemed_households`,sum(if(`hhm_distribution`.`dis_id` is null,1,0)) AS `unredeemed_households`,count(`hhm_mobilization`.`hhid`) AS `mobilized_households`,sum(`hhm_mobilization`.`allocated_net`) AS `enetcard`,sum(if(`hhm_distribution`.`collected_nets` is not null,`hhm_distribution`.`collected_nets`,0)) AS `redeemed_net` from ((`hhm_mobilization` left join `hhm_distribution` on(`hhm_mobilization`.`etoken_serial` = `hhm_distribution`.`etoken_serial`)) join `sys_geo_codex` on(`hhm_mobilization`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) group by `hhm_mobilization`.`dp_id`");
    //
    
    echo json_encode($data);
}
elseif(CleanData("qid") == 'gen009'){
    //  unredeemed etoken details
    $data = DbHelper::Table("select `sys_geo_codex`.`geo_string` AS `geo_string`,count(`hhm_distribution`.`dis_id`) AS `Households`,sum(`hhm_distribution`.`collected_nets`) AS `nets` from (`hhm_distribution` join `sys_geo_codex` on(`hhm_distribution`.`dp_id` = `sys_geo_codex`.`dpid` and `sys_geo_codex`.`geo_level` = 'dp')) where cast(`hhm_distribution`.`collected_date` as date) = cast(current_timestamp() as date) group by `sys_geo_codex`.`dpid` order by `sys_geo_codex`.`geo_string`");
    //
    
    echo json_encode($data);
}
/*
 *  =======================================
 *      SMC MODULE 
 * 
 *  ======================================
 *  
 */
elseif(CleanData("qid") == 'xtci001'){
    //  Child registry raw
    $header = getallheaders();
    $token = isset($header['token'])?$header['token']:'';
    # 
    header('Content-Type: application/json');
    #
    if($token == 'abcdef123456'){
        $perpage = CleanData('length');
        $currentPage = CleanData('page');
        $maxDownload = 2;
        #
        $currentPage = intval($currentPage)>0?intval($currentPage):1;
        $perpage = intval($perpage)>0?intval($perpage):$maxDownload;
        $limitStart = $currentPage - 1;
        #
        $data = DbHelper::Table("select `sys_geo_codex`.`geo_string` AS `geo_location`,`smc_child_household`.`hh_token` AS `parent_id`,`smc_child_household`.`hoh_name` AS `parent_name`,`smc_child_household`.`hoh_phone` AS `parent_phone`,`smc_child`.`beneficiary_id` AS `child_id`,`smc_child`.`name` AS `child_name`,`smc_child`.`gender` AS `child_gender`,`smc_child`.`dob` AS `child_dob`,`smc_child`.`created` AS `registered_date` from ((`smc_child` join `smc_child_household` on((`smc_child`.`hh_token` = `smc_child_household`.`hh_token`))) join `sys_geo_codex` on(((`smc_child`.`dpid` = `sys_geo_codex`.`dpid`) and (`sys_geo_codex`.`geo_level` = 'dp')))) limit $limitStart, $perpage");
        //
        echo json_encode($data);
    }else{
        echo json_encode(array('error'=>'Invalid access token'));
    }
}
else{
	echo "Nothing to show";
}
#
#
#
#
#
function convertColumnsToInt($array, $columnIndexes) {
    foreach ($array as &$row) {
        foreach ($columnIndexes as $index) {
            if (isset($row[$index])) {
                $row[$index] = (int)$row[$index];
            }
        }
    }
    unset($row); // unset the reference
	return $array;
}
function parseDpGeoString($string) {
    $array = explode(" > ", $string);

    // Create an associative array
    $result = array(
        'state' => isset($array[0]) ? $array[0] : "",
        'lga' => isset($array[1]) ? $array[1] : "",
        'ward' => isset($array[2]) ? $array[2] : "",
        'dp' => isset($array[3]) ? $array[3] : ""
    );

    return $result;
}
function processGeoString(array $data): array {
    $geo_parts = explode(" > ", $data["geo_string"]);

    $geo_array = [
        "state" => $geo_parts[0],
        "lga" => $geo_parts[1],
        "ward" => $geo_parts[2],
        "dp" => $geo_parts[3]
    ];

    unset($data["geo_string"]);
    $data = array_replace($data, $geo_array);

    return $data;
}

?>