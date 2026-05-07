<?php
/**
 * Development Configuration Override
 * 
 * This file contains database settings for the dev container.
 * Copy this to lib/config.php or update GetMysqlDatabase() in mysql.min.php
 * to use 'db' as the host instead of 'localhost'.
 */

/**
 * Database connection for dev container
 * Host should be 'db' (the docker-compose service name) instead of 'localhost'
 */
function GetMysqlDatabaseDev() {
    $str = "mysql:host=db;dbname=ipolongo_v5;charset=utf8";
    $user = "root";
    $pass = "";
    return new MysqlPdo($str, $user, $pass);
}

/**
 * Instructions:
 * 
 * In the dev container, update lib/mysql.min.php:
 * Change: $str="mysql:host=localhost;dbname=ipolongo_v5;charset=utf8";
 * To:     $str="mysql:host=db;dbname=ipolongo_v5;charset=utf8";
 * 
 * Or use environment variables for more flexibility.
 */
