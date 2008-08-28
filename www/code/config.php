<?php

// how to connect to the MySQL database:
//
// the following lines assume that the information is set in the web server configuraton:
// (see apache.sample.conf for how to do this):
//
$db_server =  apache_getenv( 'mysql_db_server' );   // server address: hostname or IP number
$db_name   =  apache_getenv( 'mysql_db_name' );     // name of MySQL database
$db_user   =  apache_getenv( 'mysql_db_user' );     // user to connect as
$db_pwd    =  apache_getenv( 'mysql_db_password' ); // password to authenticate with

// alternatively, store the information in the following lines:
//
// $db_server = "127.0.0.1";
// $db_name   = "INSERT_NAME_OF_DATABASE";
// $db_user   = "INSERT_NAME_OF_DATABASE_USER";
// $db_pwd    = "INSERT_PASSWORD"; 

// ... that's it! (all further configuration will be stored in the database)

?>
