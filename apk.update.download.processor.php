<?php

$pos_list = array(
    'v2.0.42'=>'./apk_downloads/ipolongo-pos-release-v2.0.42-2024-02-25.apk',
	'v2.1.46'=>'./apk_downloads/ipolongo-pos-release-v2.1.46-2024-02-29.apk',
	'v2.1.47'=>'./apk_downloads/ipolongo-pos-release-v2.1.47-2024-03-10.apk',
	'v2.1.48'=>'./apk_downloads/ipolongo-pos-release-v2.1.48-2024-03-10.apk',
	'v2.1.51'=>'./apk_downloads/ipolongo-pos-release-v2.1.51-2024-03-14.apk'
);
$mobile_list = array(
    'v2.0.34'=>'./apk_downloads/ipolongo-mobile-release-v2.0.34-2024-02-25.apk',
	'v2.1.36'=>'./apk_downloads/ipolongo-mobile-release-v2.1.36-2024-03-01.apk'
);
//
$submodule = isset($_REQUEST['submodule']) ? $_REQUEST['submodule'] : '';
$version = isset($_REQUEST['version']) ? $_REQUEST['version'] : '';
if($submodule == 'mobile'){
    $version_list = $mobile_list;
    $default = 'v2.1.36';
    //  
    $t_version = "";
    $t_filepath = "";
    if($version && array_key_exists($version,$version_list)){
        //  version is valid
        $t_version = $version;
        $t_filepath = $version_list[$version];
    }else{
        //  version invalid
        $t_version = $default;
        $t_filepath = $version_list[$default];
    }
    //  Download 
    StartDownload($t_filepath, 'ipolongo_mobile_'.$t_version.'.apk');
}
elseif($submodule == 'pos'){
    $version_list = $pos_list;
    $default = 'v2.1.51';
    //  
    $t_version = "";
    $t_filepath = "";
    if($version && array_key_exists($version,$version_list)){
        //  version is valid
        $t_version = $version;
        $t_filepath = $version_list[$version];
    }else{
        //  version invalid
        $t_version = $default;
        $t_filepath = $version_list[$default];
    }
    //  Download 
	StartDownload($t_filepath, 'ipolongo_mobile_'.$t_version);
	
}else{
    echo "Error-download error, kindly provide the correct url to download ipolongo asset here.";
}

function StartDownload($filepath,$outputname){
	
    #ob_start(); 
	header('Content-Description: File Transfer');
	header('Content-Type: application/vnd.android.package-archive');
	#header('Content-Type: application/application/octet-stream');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($filepath));
    header('Content-Disposition: attachment; filename="'.$outputname. '.apk"');
	#header("Content-Transfer-Encoding: binary");
	// Clear output buffer
	ob_clean();

	// Flush the output buffer
	flush();
	
    readfile($filepath);
    exit;
}
?>