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

$clean = $conf['check_lifetime'];
$array = array();
$id = trim(htmlspecialchars($_POST['id']));
$md5 = trim(htmlspecialchars($_POST['md5']));
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

if($id == "restore") {
	$pdo = connexionbdd($conf);
	$sql = "SELECT * FROM global_result WHERE md5=:md5 ORDER BY timestamp";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':md5',$md5);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		$array_tmp = array("item" => $checker['item'],
						   "nb_f" => $checker['nb_f'],
						   "nb_s" => $checker['nb_s'],
						   "md5" => $checker['md5'],
						   "glob" => $checker['glob'],
						   "timestamp" => strtotime($checker['timestamp']),
						   "glob_timeout" => $checker['glob_timeout'],
						   "glob_proxy" => $checker['glob_proxy'],
						   "glob_latency" => $checker['glob_latency'],
						   "glob_match" => $checker['glob_match'],
						  );
		$array[] = $array_tmp;
	}
	$statement->closeCursor();
	print_r(json_encode($array));
} else if($id == "add") {
	$pdo = connexionbdd($conf);
	$sql = "SELECT * FROM global_result WHERE md5=:md5 ORDER BY timestamp DESC LIMIT 0,1";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':md5',$md5);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		$array = array("item" => $checker['item'],
						   "nb_f" => $checker['nb_f'],
						   "nb_s" => $checker['nb_s'],
						   "md5" => $checker['md5'],
						   "glob" => $checker['glob'],
						   "timestamp" => strtotime($checker['timestamp']),
						   "glob_timeout" => $checker['glob_timeout'],
						   "glob_proxy" => $checker['glob_proxy'],
						   "glob_latency" => $checker['glob_latency'],
						   "glob_match" => $checker['glob_match'],
						  );
	}
	$statement->closeCursor();
	print_r(json_encode($array));
} else if ($id == "glob") {
	//Storage database
	$pdo = connexionbdd($conf);
	
	/*******
	*
	* Recovery of the item's name
	*
	*******/
		$sql = "SELECT * FROM items WHERE hash=:md5";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':md5',$md5);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result_item = $statement->fetchAll(PDO::FETCH_ASSOC);
		$statement->closeCursor();
	/***/
	
	/*******
	*
	* Listing of all the missed inspections
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
		$statement->execute();
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
	* Listing of people who have tested an item
	*
	*******/
		$sql2 = "SELECT count(DISTINCT checker_id)AS total,item FROM results WHERE item=:item";
		$statement = $pdo->prepare($sql2);
		$statement->bindValue(':item',$result_item[0]['item']);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach( $result as $checker ){
			if($checker['item']!= NULL && $checker['item']!= "") {
				$checker['hostname'] = $result_item[0]['hostname'];
				$array_all = $checker;
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
		$glob_timeout = $glob_proxy = $glob_latency = $glob_match = $glob = 0;
		$nb_f = 0;
		if(isset($array_all['item'])) {
			if(array_key_exists($array_all['item'],$array_count_failed_all)) {
				$glob = ($array_count_failed_all[$array_all['item']]*100)/$array_all['total'];
				$nb_f = $array_count_failed_all[$array_all['item']];
			}
			if(array_key_exists($array_all['item'],$array_count_failed)) {
				$glob_timeout = ($array_count_failed[$array_all['item']]*100)/$array_all['total'];
			}
			if(array_key_exists($array_all['item'],$array_count_failed_proxy)) {
				$glob_proxy = ($array_count_failed_proxy[$array_all['item']]*100)/$array_all['total'];
			}
			if(array_key_exists($array_all['item'],$array_count_failed_latency)) {
				$glob_latency = ($array_count_failed_latency[$array_all['item']]*100)/$array_all['total'];
			}
			if(array_key_exists($array_all['item'],$array_count_failed_match)) {
				$glob_match = ($array_count_failed_match[$array_all['item']]*100)/$array_all['total'];
			}
			
			
			$result[] = array("md5" => md5($array_all['item']),"nb_f" => $nb_f,"nb_s" => $array_all['total']-$nb_f,"item" => $array_all['item'],"glob" => $glob,"glob_timeout"=>$glob_timeout,"glob_proxy"=>$glob_proxy,"glob_latency"=>$glob_latency,"glob_match"=>$glob_match,"hostname" => $array_all['hostname'] );
		} else {
			$result[] = "";
		}
		if(is_array($result)) {
			print_r(json_encode($result));
		}
	/***/
}
?>
