<?php

//$base_directory = __DIR__;

#
#   echo 
#
/*
    $config_page_structure = array(
        'netcard' => array('css'=>array('https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700',
                                        'assets/vendor/nucleo/css/nucleo.css',
                                        'assets/vendor/@fortawesome/fontawesome-free/css/all.min.css'),
                            'js'=>array('assets/vendor/jquery/dist/jquery.min.js',
                                        'assets/vendor/js-cookie/js.cookie.js',
                                        'assets/vendor/bootstrap/dist/js/bootstrap.bundle.min.js'),
                            'nav'=>array('level1'=>'./levelone_url',
                                        'level2'=>'./leveltwo_url',)),
        'systemadmin' => array('css'=>array(),
                            'js'=>array(),
                            'nav'=>array('level1'=>'./levelone_url',
                                        'level2'=>'./leveltwo_url',)),
        'users' => array('css'=>array(),
                            'js'=>array(),
                            'nav'=>array('level1'=>'./levelone_url',
                                        'level2'=>'./leveltwo_url',))
    );
    
    echo "<br><br>";
    echo "<pre>";
    print_r($config_page_structure);
    echo "</pre>";
    
    echo json_encode($config_page_structure);
    

    $config_data = file_get_contents(__DIR__."/lib/data/system_structure.json");
    //
    echo $config_data;
    
   include_once('lib/config.php');
    echo "<pre>";
    print_r($config_system_structure);
    echo "</pre>";
    

   $name= $_SERVER['SERVER_NAME'];
    $uri= $_SERVER['REQUEST_URI'];
    $document = $_SERVER['DOCUMENT_ROOT'];
    $self = $_SERVER['PHP_SELF'];

    echo "Name: $name <br>";
    echo "Uri: $uri <br>";
    echo "Document: $document <br>";
    echo "Self: $self <br>";
    
    
    include_once('lib/config.php');
    echo $config_pre_append_link;
    
    $name= $_SERVER['SERVER_NAME'];
    $uri= $_SERVER['REQUEST_URI'];
    $document = $_SERVER['DOCUMENT_ROOT'];
    $self = $_SERVER['PHP_SELF'];
    $request = $_SERVER['REQUEST_URI'];

    echo "Name: $name <br>";
    echo "Uri: $uri <br>";
    echo "Document: $document <br>";
    echo "Self: $self <br>";
    echo "Request: $request <br>";
    echo "this page: http://".$name.$uri;
    */
include("lib/config.php");
include("lib/common.php");
/*
	echo "<pre>";
	print_r($_SERVER);
	echo "</pre>";
    
    $path = $config_pre_append_path;
    $uri = $_SERVER['REQUEST_URI'];
    $diff = str_replace($path,'',$uri);
    echo "Path: $path";
    echo "<br> URI: $uri";
    echo "<br> DIFF: $diff";
    
    session_start();
    $uid = cleanThis($_SESSION[$instance_token.'_uid']);
    $fullname = cleanThis($_SESSION[$instance_token.'fullname']);
    $loginid = CleanThis($_SESSION[$instance_token.'_loginid']);
    $username = cleanThis($_SESSION[$instance_token.'_username']);
    $guid = cleanThis($_SESSION[$instance_token.'_guid']);
    $roleid = cleanThis($_SESSION[$instance_token.'_roleid']);
    $role = cleanThis($_SESSION[$instance_token.'_role']);
    //
    $geo_level = cleanThis($_SESSION[$instance_token.'_geo_level']);
    $geo_level_id = cleanThis($_SESSION[$instance_token.'_geo_level_id']);
    $geo_value = ($_SESSION[$instance_token.'_geo_value']);
    $active = cleanThis($_SESSION[$instance_token.'_active']);
    //      Get users privileges
    $privilege = cleanThis($_SESSION[$instance_token.'privileges']);
    $platform = cleanThis($_SESSION[$instance_token.'platform_priv']);
    $user_group = cleanThis($_SESSION[$instance_token.'_user_group']);

    #
    echo "USER ID: $uid<br>";
    echo "FULLNAME: $fullname<br>";
    echo "LOGIN ID: $loginid<br>";
    echo "USERNAME: $username<br>";
    echo "GUID: $guid<br>";
    echo "ROLE ID: $roleid<br>";
    echo "ROLE: $role<br>";
    echo "GEO LEVEL: $geo_level<br>";
    echo "GEO LEVEL ID: $geo_level_id<br>";
    echo "GEO VALUE: $geo_value<br>";
    echo "ACTIVE: $active<br>";
    echo "PRIVILEGE: $privilege<br>";
    echo "PLATFORM: $platform<br>";
    echo "USER GROUP: $user_group<br>";

    #
    #   Check the privilege availability
    $privi = 'logistics';
    if(IsPrivilegeInArray(json_decode($_SESSION[$instance_token.'privileges'],true),$privi)){
        echo "<p>Testing Privilege for $privi is available</p>";
    }
    else{
        echo "<p>Testing Privilege for <b>$privi</b> is not available</p>";
    }

    function CleanThis($obj)
    {
        $t = isset($obj)? $obj:'';
        return $t;
    }
    

    #
    #
    #   Titan code generator Job
    #
    #   This help to generate GUID into the CODEX locations
    include_once('lib/mysql.min.php');
    #   Get list
    $list = DbHelper::Table("SELECT
    sys_geo_codex.id
    FROM
    sys_geo_codex
    WHERE
    sys_geo_codex.guid IS NULL
    ORDER BY
    sys_geo_codex.id ASC");

    $counter = 0;
    if(count($list)){
        foreach($list as $a){
            #
            if(DbHelper::Update('sys_geo_codex',array('guid'=>generateUUID()),'id',$a['id']))
            {
                $counter++;
            }
        }
    }
    echo "Was able to update $counter record successfully.";
    


    #
    #
    #   View base64 logo in image
    #
    include_once('lib/mysql.min.php');
    #
    $data = DbHelper::GetScalar("SELECT `logo` FROM sys_default_settings WHERE id = 1");
    $static_data = "data:image/png;base64,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";
    $code_bin = base64_decode($data);
    $image = imagecreatefromstring($code_bin);
    #
    header('Content-Type: image/png');
    echo imagepng($image);
    //imagedestroy($image);
    */
// http_response_code(404);


$data = [
    'user_id' => '1',
    'role' => 'admin',
    'Fullname' => 'Abdulazeez Mdkamil'
];
$key = 'ipolongo';
$encrypted = encryptJsonGCM($data, $key);
echo "Encrypted: " . $encrypted;


echo "<br><br><br><br><br>";
$decrypted = decryptJsonGCM($encrypted, $key);

echo "<pre>";
var_dump($decrypted);
echo "</pre>";
