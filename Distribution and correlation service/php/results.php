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

/*
 * Function to get hostname of an address from a DNS server
 */
function getHostFromAddrWithDNS($conf,$ip_checker){
	$result = array();
	$command = escapeshellcmd('host').' '.escapeshellarg("-W 2").' '.escapeshellarg($ip_checker)." ".escapeshellarg($conf['checker_DNS']);
	exec($command,$result);
	$result_ok = false;
	foreach( $result as $res ){
		if( strstr($res,"domain name pointer") ){
			$result_ok = true;
		}
	}
	
	if( $result_ok ){
		$host_line = $result[count($result)-1];
		$host_line_explode =  explode("domain name pointer",$host_line);
		$host = $host_line_explode[1];
		return $host;
	}
	else{
		return -1;
	}

}

$host = trim(htmlspecialchars($_POST['host']));
$protocole= trim(htmlspecialchars($_POST['protocole']));
$port= trim(htmlspecialchars($_POST['port']));
$string= trim(htmlspecialchars($_POST['string']));

$result_app = trim(htmlspecialchars($_POST['result']));
$id = trim(htmlspecialchars($_POST['id']));
$ip = $_SERVER['REMOTE_ADDR'];
$hostname = getHostFromAddrWithDNS($conf,$ip);
if( $hostname == -1 ){
	$hostname = gethostbyaddr($ip);
}
$version = trim(htmlspecialchars($_POST['version']));
$item = $host.":".$protocole.":".$port.":".$string;
$latency = trim(htmlspecialchars($_POST['date']));
$checktimestamp = date('Y/m/d H:i:s',time());
$checktimestamp_log = time();


$clean = $conf['check_lifetime'];

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

function clean($clean,$pdo) {
	$sql = "DELETE FROM results WHERE check_timestamp <= NOW() - INTERVAL :clean MINUTE";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':clean',$clean);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$statement->closeCursor();
}
//Checking latency
if($latency < $conf['max_latency'] && $latency > $conf['min_latency']) {
	$result_app = "4";
}
//Storage database
$pdo = connexionbdd($conf);
$req = $pdo->prepare("INSERT INTO results (item, checker_id, check_timestamp, app_version, result, latency, checker_ip, checker_hostname) VALUES ( :item, :checker_id, :check_timestamp, :app_version, :result, :latency, :checker_ip, :checker_hostname)");
$req->bindValue(":item",$item);
$req->bindValue(":checker_id",$id);
$req->bindValue(":check_timestamp",$checktimestamp);
$req->bindValue(":app_version",$version);
$req->bindValue(":result",$result_app);
$req->bindValue(":latency",$latency);
$req->bindValue(":checker_ip",$ip);
$req->bindValue(":checker_hostname",$hostname);
if( !$req->execute() ){
		throw new PDOException();
}

//Logstalgia creating the file if necessary
if($conf['active_logstalgia'] == 1) {
	if(file_exists('../tmp/result.txt')) {
		$json=file_get_contents('../tmp/result.txt');
		$result_log=json_decode($json,true);
	} else {
		$result_log=array();
	}

	if($result_app != "0"){
		$text = "Fail";
		$color = "FF0000";
	} else {
		$text = "Ok";
		$color = "00FF00";
	}
	if($latency <= 1024) {
		$latency = 1024;
	} else {
		$latency = round($latency * 10.24, 0);
	}
	$item_log = array($checktimestamp_log,$ip,$host,$text,$latency,$result_app,$color);
	if($result_app != "1")	{
		$result_log[$host]=array();
		$result_log[$host]["Danger"]["Status"]=array();
		$result_log[$host]["Danger"]["TimeStamp"]=array();
		$result_log[$host]["Danger"]["Status"]="Fail";
		$result_log[$host]["Danger"]["TimeStamp"]=$checktimestamp_log;
		file_put_contents("../tmp/result.txt",json_encode($result_log));
	} else {
		if(!empty($result_log[$host]) && $checktimestamp_log <= $result_log[$host]["Danger"]["TimeStamp"]+240)	{
			$item_log[5]="2";
			$item_log[3]="Danger";
			$item_log[6]="FFA500";
			$result_log[$host]["Danger"]["Status"]="Danger";
		}
	}
	$output = implode("|",$item_log)."\n";
	file_put_contents("../tmp/logstalgia.txt",$output,FILE_APPEND);
}
clean($clean,$pdo);
?>
