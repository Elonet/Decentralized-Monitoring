<?php
/*
 * Decentralized Monitoring source code
 * https://github.com/Elonet/Decentralized-Monitoring
 *
 * Copyright 2015, Leo Leroy
 * https://elonet.fr/
 *
 * Licensed under the GPLv3 license:
 * http://www.gnu.org/licenses/gpl-3.0.en.html
 */
 
 
include("/etc/decentralized_monitoring/config.conf");
date_default_timezone_set('Europe/Berlin');

function connexionbdd($conf,$user_mp,$host_db){
	try{
		return new PDO('mysql:host='.$host_db.';port=3306', "root" , $user_mp);
	}
	catch(Exception $e){
		echo 'Erreur : '.$e->getMessage().'<br />';
		echo 'N° : '.$e->getCode();
	}
}

if( $argc < 2 ){
	echo "Missing two arguments: mysql root password and hostname or IP accessing this database \n";
	exit(0);
}
else if( $argc < 3 ) {
	echo "Missing argument : mysql root password or hostname or IP accessing this database \n";
	exit(0);
}
else if( $argc > 3 ){
	echo "Too many arguments , only two are required. mysql root password or hostname or IP accessing this database \n";
	exit(0);
}

$db_name = $conf['bdd_db'];

$pdo = connexionbdd($conf,$argv[1],$argv[2]);

echo "Starting install new db and tables\n";
$create_db = "CREATE DATABASE IF NOT EXISTS $db_name";
$statement = $pdo->prepare($create_db);
if( !$statement->execute() ){
	echo "Error creating new database\n";
	throw new PDOException();
}
else{
	echo "Database created\n";
}


$change_db = "USE $db_name";
$statement = $pdo->prepare($change_db);
if( !$statement->execute() ){
	echo "Error using new database\n";
	throw new PDOException();
}
else{
	echo "Using new database\n";
}


$alert_table = 'CREATE TABLE `alert` (
  `item` varchar(50) NOT NULL,
  `from_date` int(11) NOT NULL,
  `to_date` int(11) DEFAULT NULL
)';

$statement = $pdo->prepare($alert_table );
if( !$statement->execute() ){
	echo "Error creating table alert\n";
	throw new PDOException();
}
else{
	echo "Create alert table\n";
}


$check_table = 'CREATE TABLE `checker_no_alert` (
  `nb_checker` int(11) DEFAULT NULL,
  `heure` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)';
$statement = $pdo->prepare($check_table);
if( !$statement->execute() ){
	echo "Error creating table check_no_alert\n";
	throw new PDOException();
}
else{
	echo "Create checker_no_alert table\n";
}


$files_table = 'CREATE TABLE `files` (
  `path` varchar(45) DEFAULT NULL,
  `ownerid` int(11) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `date_upload` datetime DEFAULT NULL
)';
$statement = $pdo->prepare($files_table);
if( !$statement->execute() ){
	echo "Error creating table files\n";
	throw new PDOException();
}
else{
	echo "Create files table\n";
}


$global_table = 'CREATE TABLE `global_result` (
  `item` varchar(45) NOT NULL,
  `nb_f` int(11) DEFAULT NULL,
  `nb_s` int(11) DEFAULT NULL,
  `md5` varchar(45) DEFAULT NULL,
  `glob` int(11) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL
)';
$statement = $pdo->prepare($global_table);
if( !$statement->execute() ){
	echo "Error creating table global_result\n";
	throw new PDOException();
}
else{
	echo "Create global_result table\n";
}


$items_table = "CREATE TABLE `items` (
  `item` varchar(50) CHARACTER SET latin1 NOT NULL,
  `create_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `imported_from` varchar(50) CHARACTER SET latin1 NOT NULL,
  `distribute_times` int(11) NOT NULL DEFAULT '0',
  `hostname` varchar(50) DEFAULT NULL,
  UNIQUE KEY `item_UNIQUE` (`item`)
)";
$statement = $pdo->prepare($items_table);
if( !$statement->execute() ){
	echo "Error creating table items\n";
	throw new PDOException();
}
else{
	echo "Create items table\n";
}


$result_table = "CREATE TABLE `results` (
  `item` varchar(50) NOT NULL,
  `checker_id` varchar(45) NOT NULL,
  `check_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `app_version` varchar(10) CHARACTER SET latin1 NOT NULL,
  `result` int(11) NOT NULL,
  `latency` int(11) NOT NULL,
  `checker_ip` varchar(45) DEFAULT NULL,
  `checker_hostname` varchar(45) DEFAULT NULL
)";
$statement = $pdo->prepare($result_table);
if( !$statement->execute() ){
	echo "Error creating table results\n";
	throw new PDOException();
}
else{
	echo "Create results table\n";
}


$trace_table = "CREATE TABLE `traceroute` (
  `check_net` varchar(50) DEFAULT NULL,
  `route` varchar(390) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$statement = $pdo->prepare($trace_table);
if( !$statement->execute() ){
	echo "Error creating table traceroute\n";
	throw new PDOException();
}
else{
	echo "Create traceroute table\n";
}

echo "End of DB installation\n";
?>