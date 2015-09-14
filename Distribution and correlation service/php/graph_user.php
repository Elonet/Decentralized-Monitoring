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

$clean = $conf['check_lifetime'];
$array = array();

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


if(trim(htmlspecialchars($_REQUEST['id'])) == "restore") {
	$pdo = connexionbdd($conf);
	$sql = "SELECT * FROM checker_no_alert ORDER BY heure ASC";
	$statement = $pdo->prepare($sql);
	$statement->execute();
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		$array_tmp = array("nb_checker" =>$checker ['nb_checker'], "heure" => strtotime($checker ['heure']));
		$array[] = $array_tmp;
	}
	$statement->closeCursor();
	print_r(json_encode($array));
} else if(trim(htmlspecialchars($_REQUEST['id'])) == "add") {
	$pdo = connexionbdd($conf);
	$sql = "SELECT * FROM checker_no_alert ORDER BY heure DESC LIMIT 0,1";
	$statement = $pdo->prepare($sql);
	$statement->execute();
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $checker ){
		$array = array("nb_checker" =>$checker['nb_checker'], "heure" => strtotime($checker['heure']));
	}
	$statement->closeCursor();
	print_r(json_encode($array));
} else if(trim(htmlspecialchars($_REQUEST['id'])) == "only") {
	$pdo = connexionbdd($conf);
	//Table of people who missed an item
	$sql = "SELECT DISTINCT checker_id FROM results WHERE result=1";
	$statement = $pdo->prepare($sql);
	$statement->execute();
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	$array_failed = array();
	foreach( $result as $checker ){
		$array_failed[] = $checker['checker_id'];
	}
	$array_count_failed = count($array_failed);
	$array = array("nb_checker" =>$array_count_failed);
	print_r(json_encode($array));
}
?>
