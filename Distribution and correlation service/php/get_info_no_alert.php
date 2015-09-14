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

//Storage database
$pdo = connexionbdd($conf);
$sql = "SELECT checker_id FROM results GROUP by checker_id";
$statement = $pdo->prepare($sql);

if( !$statement->execute() ){
	throw new PDOException();
}
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach( $result as $checker ){
	$array_tmp = array("checker" =>$checker['checker_id']);
	$array[] = $array_tmp;
}
$statement->closeCursor();

$checker_nb = count($array);
$sql2 = "SELECT item FROM items";
$statement = $pdo->prepare($sql2);
 
if( !$statement->execute() ){
	throw new PDOException();
}
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach( $result as $checked ){
	if($checked['item'] != "") {
		$array_tmp2 = array("checked" =>$checked['item']);
		$array2[] = $array_tmp2;
	}
}
$statement->closeCursor();
$checked_nb = count($array2);
print_r(json_encode(array("checker" => $checker_nb, "checked" => $checked_nb)));
?>
