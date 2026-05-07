<?php

    spl_autoload_register('myAutoLoader');

    function myAutoLoader($classname)
    {
        $path = "lib/controller/";
        $ext = ".cont.php";
        $fullpath = $path . $classname . $ext;
        #
        if(!file_exists($fullpath))
        {
            return false;
        }
        #
        include_once $fullpath;
    }

?>