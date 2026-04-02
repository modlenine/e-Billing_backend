<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// เช็คว่าทำงานบน local development
$host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
$is_local = in_array($host, ['localhost', '127.0.0.1', '::1']);

if($is_local){
	$mysqlServer = "192.168.20.34";
	$username = "ofintra";
	$password = "Ofin1234";
	// $mssql = "192.168.10.60";
	// $mssqlDB_sln = "SLC_STD_TEST";
	// $mssqlDB_tbb = "APPLY_STD_TEST";

	$mssql = "192.168.10.54";
	$mssqlDB_sln = "SLC_STD";
	$mssqlDB_tbb = "APPLY_STD";

}else{
	$mysqlServer = "192.168.20.34";
	$username = "ofintra";
	$password = "Ofin1234";

	$mssql = "192.168.10.54";
	$mssqlDB_sln = "SLC_STD";
	$mssqlDB_tbb = "APPLY_STD";
}

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => $mysqlServer,
	'username' => $username,
	'password' => $password,
	'database' => 'ebilling',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);


$db['saleecolour'] = array(
	'dsn'	=> '',
	'hostname' => $mysqlServer,
	'username' => $username,
	'password' => $password,
	'database' => 'saleecolour',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);



$db['mssql'] = array(
	'dsn' => '',
	'hostname' => $mssql,
	// 'hostname' => '192.168.10.60',
	'username' => 'dataconnector',
	'password' => 'Admin1234',
	'database' => $mssqlDB_sln,
	'dbdriver' => 'sqlsrv',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
   );


   $db['mssql2'] = array(
	'dsn' => '',
	'hostname' => $mssql,
	// 'hostname' => '192.168.10.60',
	'username' => 'dataconnector',
	'password' => 'Admin1234',
	'database' => $mssqlDB_tbb,
	'dbdriver' => 'sqlsrv',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
   );


