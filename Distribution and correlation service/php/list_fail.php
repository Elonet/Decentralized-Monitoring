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
	require($conf['multi']);
	$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	$lang_key= array_keys($vocables);
	$lang_existe = false;
	foreach($lang_key as $var => $valeur) {
		if($lang != $valeur && $lang_existe != true){
			$lang_existe = false;
		}
		else {
			$lang_existe = true;
		}
	}
	if($lang_existe == true){
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
	}
	else{
		$lang="en";
	}
	date_default_timezone_set('Europe/Berlin');
	$md5 = trim(htmlspecialchars($_POST['md5']));
	
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
	* Listing of people who have tested an item
	*
	*******/
	if( count($result_item) > 0 ){
		$sql2 = "SELECT checker_id, check_timestamp, app_version, result, latency, checker_ip, checker_hostname FROM results WHERE item=:item AND (result=1 OR result=2 OR result=3 OR result=4) GROUP BY checker_id";
		$statement = $pdo->prepare($sql2);
		$statement->bindValue(':item',$result_item[0]['item']);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach( $result as $checker ){
				$array_all = $checker;
		}
		$statement->closeCursor();
	}
	else{
		$result = array();
	}
	/***/
	
	$response = "<tbody>
					<tr>
						<th>".$vocables[$lang]["checker_id"]."</th>
						<th>".$vocables[$lang]["check_timestamp"]."</th>
						<th>".$vocables[$lang]["app_version"]."</th>
						<th>".$vocables[$lang]["result"]."</th>
						<th>".$vocables[$lang]["latency"]."</th>
						<th>".$vocables[$lang]["checker_ip"]."</th>
						<th>".$vocables[$lang]["checker_hostname"]."</th>
					</tr>";
	if( count($result) > 0 ){
		foreach($result as $value) {
			$response .= "<tr>
							<td>".$value["checker_id"]."</td>
							<td>".$value["check_timestamp"]."</td>
							<td>".$value["app_version"]."</td>";
			if($value["result"] == 1) {
				$response .= "<td>".$vocables[$lang]["timeout"]."</td>";
			}
			if($value["result"] == 2) {
				$response .= "<td>".$vocables[$lang]["proxy"]."</td>";
			}
			if($value["result"] == 3) {
				$response .= "<td>".$vocables[$lang]["match"]."</td>";
			}
			if($value["result"] == 4) {
				$response .= "<td>".$vocables[$lang]["latency"]."</td>";
			}
			$response .= "	<td>".$value["latency"]."</td>
							<td>".$value["checker_ip"]."</td>
							<td>".$value["checker_hostname"]."</td>
							</tr>";
		}
	}
	$response .= "</tbody>";
	echo $response;
?>