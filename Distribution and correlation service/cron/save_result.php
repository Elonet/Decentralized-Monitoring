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
$host_db = $conf['host_db'];
$user_db = $conf['user_db'];
$password_db = $conf['password_db'];
$bdd_db = $conf['bdd_db'];

function connexionbdd(){
	global $conf;
	try{
		return new PDO('mysql:host='.$conf['host_db'].';port=3306;dbname='.$conf['bdd_db'], $conf['user_db'] , $conf['password_db']);
	}
	catch(Exception $e){
		echo 'Erreur : '.$e->getMessage().'<br />';
		echo 'N° : '.$e->getCode();
	}
}

function save_checker() {
	$array = array();
	$pdo = connexionbdd();
	$sql = "SELECT checker_id FROM results GROUP by checker_id";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $item ){
		$array_tmp = array("checker" =>$item['checker_id']);
		$array[] = $array_tmp;
	}
	$statement->closeCursor();
	$checkerr = count($array);
	$sql2 = "INSERT INTO checker_no_alert (nb_checker) VALUES (:checker);";
	$statement = $pdo->prepare($sql2);
	$statement->bindValue(':checker',$checkerr);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$statement->closeCursor();
}

function save_all_result() {
	$array_failed = array();
	$array_failed_all = array();
	$array_failed_match = array();
	$array_failed_proxy = array();
	$array_failed_latency = array();
	$array_success = array();
	$array_all = array();
	$checktimestamp = date('Y/m/d H:i:s',time());
	$array_failed = array();
	//Storage database
	$pdo = connexionbdd();
	/*******
	*
	* Listing all the possible facts
	*
	*******/
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
		$sql = "SELECT item from items";
		$statement = $pdo->prepare($sql);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach( $result as $item ){		
			$array_item[] = $item;
		}
		$statement->closeCursor();
	/***/

	/*******
	*
	* Listing of people who tested an item
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
			foreach( $result as $item ){		
				if($item['item']!= NULL && $item['item']!= "") {
					$array_all[] = $item;
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
			$glob_timeout = $glob_proxy = $glob_latency = $glob_match = 0;
			if(array_key_exists($val['item'],$array_count_failed_all)) {
				$glob = ($array_count_failed_all[$val['item']]*100)/$val['total'];
			}
			if(array_key_exists($val['item'],$array_count_failed)) {
				$glob_timeout = ($array_count_failed[$val['item']]*100)/$val['total'];
			}
			if(array_key_exists($val['item'],$array_count_failed_proxy)) {
				$glob_proxy = ($array_count_failed_proxy[$val['item']]*100)/$val['total'];
			}
			if(array_key_exists($val['item'],$array_count_failed_latency)) {
				$glob_latency = ($array_count_failed_latency[$val['item']]*100)/$val['total'];
			}
			if(array_key_exists($val['item'],$array_count_failed_match)) {
				$glob_match = ($array_count_failed_match[$val['item']]*100)/$val['total'];
			}	
			if(array_key_exists($val['item'],$array_count_failed_all)) {			
				$result[] = array("md5" => md5($val['item']),"nb_f" => $array_count_failed_all[$val['item']],"nb_s" => $val['total']-$array_count_failed_all[$val['item']],"item" => $val['item'],"glob" => $glob,"glob_timeout"=>$glob_timeout,"glob_proxy"=>$glob_proxy,"glob_latency"=>$glob_latency,"glob_match"=>$glob_match);
			} else {
				$result[] = array("md5" => md5($val['item']),"nb_f" => "0","nb_s" => $val['total'],"item" => $val['item'],"glob" => "0","glob_timeout"=>"0","glob_proxy"=>"0","glob_latency"=>"0","glob_match"=>"0");
			}
		}
		foreach($result as $value) {
			$sql2 = "INSERT INTO global_result (item,nb_f,nb_s,md5,glob,timestamp,glob_timeout,glob_proxy,glob_latency,glob_match) VALUES (:item,:nbf,:nbs,:md5,:glob,:timestamp,:globtimeout,:globproxy,:globlatency,:globmatch);";
			$statement = $pdo->prepare($sql2);
			$statement->bindValue(':item',$value["item"]);
			$statement->bindValue(':nbf',$value["nb_f"]);
			$statement->bindValue(':nbs',$value["nb_s"]);
			$statement->bindValue(':md5',$value["md5"]);
			$statement->bindValue(':glob',$value["glob"]);
			$statement->bindValue(':timestamp',$checktimestamp);
			$statement->bindValue(':globtimeout',$value["glob_timeout"]);
			$statement->bindValue(':globproxy',$value["glob_proxy"]);
			$statement->bindValue(':globlatency',$value["glob_latency"]);
			$statement->bindValue(':globmatch',$value["glob_match"]);
			if( !$statement->execute() ){
				throw new PDOException();
			}
			$statement->closeCursor();
		}
		
	/***/
}

save_checker();
save_all_result();
?>

