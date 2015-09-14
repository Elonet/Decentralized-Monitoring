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
 

$md5 = trim(htmlspecialchars($_GET['md']));
require("/etc/decentralized_monitoring/config.conf");


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


//Storage database
$pdo = connexionbdd();
//Table of people who missed an item
$item = "";
$sql = "SELECT item FROM items WHERE hash=:md5";
$statement = $pdo->prepare($sql);
$statement->bindValue(':md5',$md5);
if( !$statement->execute() ){
	throw new PDOException();
}
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
if( isset($result[0])){
	$item = $result[0]['item'];
}
$statement->closeCursor();


$from = 0;
$to = 0;
$sql = "SELECT from_date, to_date FROM alert WHERE item=:item order by from_date DESC LIMIT 0,1";
$statement = $pdo->prepare($sql);
$statement->bindValue(':item',$item);
if( !$statement->execute() ){
	throw new PDOException();
}
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
if( isset($result[0])){
	$from = $result[0]['from_date'];
	$to = $result[0]['to_date'];
}
$statement->closeCursor();
$result = array( 'start' => $from, 'stop' => $to );
echo json_encode($result);
?>