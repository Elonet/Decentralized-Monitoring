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
 
 
ini_set('display_errors', 1);
error_reporting(E_ALL);
include("/etc/decentralized_monitoring/config.conf");
date_default_timezone_set('Europe/Berlin');

$alert = false;
$list_alert = "";
$nb_alert=0;
$time_now=time();
$array_failed = array();
$array_failed_all = array();
$array_failed_match = array();
$array_failed_proxy = array();
$array_failed_latency = array();
$array_success = array();
$array_all = array();

/*
 * Function to connect to db
 */
function connexionbdd($conf){
	try{
		return new PDO('mysql:host='.$conf['host_db'].';port=3306;dbname='.$conf['bdd_db'], $conf['user_db'] , $conf['password_db']);
	}
	catch(Exception $e){
		echo 'Erreur : '.$e->getMessage().'<br />';
		echo 'N° : '.$e->getCode();
	}
}

/*******
*
* Listing all the possible facts
*
*******/
	//Storage database
	$pdo = connexionbdd($conf);
	
	//Table of people who missed an all item
	$sql = "SELECT DISTINCT checker_id, item FROM results WHERE result=1 OR result=2 OR result=3 OR result=4";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		if($checker['item'] != "") {
			$array_failed_all[] = $checker['item'];
		}
	}
	$array_count_failed_all = array_count_values($array_failed_all);
	
	//Table of people who missed an item
	$sql = "SELECT DISTINCT checker_id, item FROM results WHERE result=1";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		if($checker['item'] != "") {
			$array_failed[] = $checker['item'];
		}
	}
	$array_count_failed = array_count_values($array_failed);

	//Table of people who missed an item on the match
	$sql = "SELECT DISTINCT checker_id, item FROM results WHERE result=2";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		if($checker['item'] != "") {
			$array_failed_match[] = $checker['item'];
		}
	}
	$array_count_failed_match = array_count_values($array_failed_match);

	//Table of people who missed an item on the proxy
	$sql = "SELECT DISTINCT checker_id, item FROM results WHERE result=3";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		if($checker['item'] != "") {
			$array_failed_proxy[] = $checker['item'];
		}
	}
	$array_count_failed_proxy = array_count_values($array_failed_proxy);
	
	//Table of people who missed an item on the latency
	$sql = "SELECT DISTINCT checker_id, item FROM results WHERE result=4";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		if($checker['item'] != "") {
			$array_failed_latency[] = $checker['item'];
		}
	}
	$array_count_failed_latency = array_count_values($array_failed_latency);
/***/

/*******
*
* Listing items
*
*******/
	$sql = "SELECT * from items";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker){
		$array_item[] = $checker;
	}
	$statement->closeCursor();
/***/

/*******
*
* Listing of people who have tested an item
*
*******/
	
	foreach($array_item as $value) {

		$sql = "SELECT count(DISTINCT checker_id)AS total,item FROM results WHERE item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$value['item']);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach( $result as $checker ){
			if($checker['item']!= NULL && $checker['item']!= "") {
				$checker['hostname'] = $value['hostname'];
				$array_all[] = $checker;
			}
		}
	}
	$statement->closeCursor();
/***/

/*******
*
* Treatment and return
*
*******/
	$result = array();

	
	foreach($array_all as $val) {
		$type = array();
		//Table of people who missed an item
		if(array_key_exists($val['item'],$array_count_failed)) {
			$type[] = "none";
		}
		//Table of people who missed an item on the match
		if(array_key_exists($val['item'],$array_count_failed_match)) {
			$type[] = "match";
		}
		//Table of people who missed an item on the proxy
		if(array_key_exists($val['item'],$array_count_failed_proxy)) {
			$type[] = "proxy";
		}
		//Table of people who missed an item on the latency
		if(array_key_exists($val['item'],$array_count_failed_latency)) {
			$type[] = "latency";
		}


		if(array_key_exists($val['item'],$array_count_failed_all)) {			
			$type_send = implode("|",$type);
			$glob = ($array_count_failed_all[$val['item']]*100)/$val['total'];
			$result[] = array("type" => $type_send,"md5" => md5($val['item']),"nb_f" => $array_count_failed_all[$val['item']],"nb_s" => $val['total']-$array_count_failed_all[$val['item']],"item" => $val['item'],"glob" => $glob,"hostname" => $val['hostname'] );
		} else {
			$glob = "0";
			$result[] = array("type" => "","md5" => md5($val['item']),"nb_f" => "0","nb_s" => $val['total'],"item" => $val['item'],"glob" => $glob,"hostname" => $val['hostname'] );
		}
	}
	if(is_array($result)) {
		$result = array_values($result);
		print_r(json_encode($result));
	}
/***/
?>
