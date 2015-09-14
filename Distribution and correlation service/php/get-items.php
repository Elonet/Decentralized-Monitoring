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
 
 
header('Access-Control-Allow-Origin: *');  
ini_set('display_errors', 1);
error_reporting(E_ALL);
include("/etc/decentralized_monitoring/config.conf");
date_default_timezone_set('Europe/Berlin');

$checker = $_SERVER['REMOTE_ADDR'];

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

/*
 * Deprecated code, while item selection is random
 */
function less_used_order($pdo){
	$sql = "SELECT * FROM items ORDER BY distribute_times";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	$items = array();
	foreach( $result as $item ){
		$items[] = $item;
	}
	$statement->closeCursor();
	return $items;
}

/*
 * Function to calculate item and checker in regex
 * if checker in regex then find item in regex
 */
function validate_from_regex($conf, $items, $checker){
	$regexes = $conf['regexes_for_check'];
	$items_final = $items;
	$source_found = "";
	if( count( $regexes ) > 0 ){		
		$i = 0;
		while( $i < count($regexes) && $source_found == ""){
			$regex = $regexes[$i];
			if( count($regex) > 0 && count($regex) == 2 ){
				$network_source = explode("/",$regex[0]);
				$ip = $network_source[0];
				$netmask = $network_source[1];			
				if( preg_match( "/".$conf['ipV4v6_regex']."/", $ip ) === 1 && 0 <= $netmask && $netmask < 33 ){
					if( netmask($ip,$netmask) == netmask($checker,$netmask) ){
						$source_found = $regex[1];
					}
				} else {
					$checker_host = getHostFromAddrWithDNS($conf,$checker);
					if( $checker_host != -1 ){
						if( preg_match($regex[0],$checker_host ) == 1 ){
							$source_found = $regex[1];
						}
					} else {						
						$checker_host = gethostbyaddr($checker);						
						if( preg_match($regex[0],$checker_host ) == 1 ){
							$source_found = $regex[1];
						}
					}
				}
			}
			$i++;
		}
		
		if( $source_found != "" ){
			foreach( $items as $item ){
				$regex_ip = explode("/",$source_found);
				$ip = $regex_ip[0];
				$netmask = $regex_ip[1];
				$item_explode = explode(":",$item['item']);
				if( preg_match( "/".$conf['ipV4v6_regex']."/", $ip ) == 1 && 0 <= $netmask && $netmask < 33  ){
					if( preg_match( "/".$conf['ipV4v6_regex']."/", $item_explode[0] ) == 0 ){
						$item_ip = gethostbyname($item_explode[0]);
					} else {
						$item_ip = $item_explode[0];
					}
					if( netmask($ip,$netmask) != netmask($item_ip,$netmask) ){
						array_splice($items_final,array_search($item,$items_final),1);
					}
				} else {
					if( preg_match( "/".$conf['ipV4v6_regex']."/", $item_explode[0] ) === 1 ){
						$item_host = gethostbyaddr($item_explode[0]);
					} else {
						$item_host = $item_explode[0];
					}					
					if( preg_match($source_found, $item_host) === 0 || preg_match($source_found, $item_host) === false ){
						array_splice($items_final,array_search($item,$items_final),1);
					}
				}				
			}
		} else {
			$items_final = array();
		}		
	}
	if(count($items_final) == 0){
		if( $conf['concordance'] == "inclusive" ){
			return $items;
		} else if( $conf['concordance'] == "exclusive" ){
			return $items_final;
		} else {
			error_log("Configuration error for the variable 'concordance'. Possible values ​​are exclusive or inclusive.");
		}
	} else {
		return $items_final;
	}	
}
 
 
/*
 * Function to calculate common networks for checkers
 */
function netmask($ip, $cidr) {
    $bitmask = $cidr == 0 ? 0 : 0xffffffff << (32 - $cidr);
    return long2ip(ip2long($ip) & $bitmask);
}


/*
 *Function to get hostname of an address from a DNS server
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
	} else {
		return -1;
	}
}
 
 /*
  * function to check if checker and item in same network
  */
function validate_from_network( $conf,$items, $checker ){
	$netmask_max = $conf['maximum_netmask_for_groups'];
	$netmask_min = $conf['minimum_netmask_for_groups'];
	$items_final = $items;
	if($conf['check_in_network_only'] == 1) {
		foreach( $items as $item ){
			$netmask = $netmask_max;
			$find = false;
			while( $netmask >= $netmask_min and !$find ){
				$ip_item = gethostbyname($item['hostname']);
				if( netmask($ip_item,$netmask) == netmask($checker,$netmask) ){
					$find = true;
				} else {
					$netmask--;
				}
			}
			if( !$find ){
				array_splice($items_final,array_search($item,$items_final),1);
			}
		}
	}
	return $items_final;
}

function increment($item,$pdo){
	$sql = "UPDATE items SET distribute_times = distribute_times+1 WHERE item = '".$item."'";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$statement->closeCursor();
}

function random_select($pdo,$checker,$conf){
	
	//first we select all items
	$sql = "SELECT * FROM items order by distribute_times";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	$items = array();
	foreach( $result as $item ){
		$items[] = $item;
	}
	$statement->closeCursor();
	
	//second we check for mask concordance
	$items = validate_from_network($conf,$items,$checker);
	$items = validate_from_regex($conf,$items,$checker);
	
	//third and last we randomly choose an item
	if(count($items) > 0 ){ 
		$max = count($items);
		$index = rand(0,$max);
		while( !isset($items[$index])){
			$index = rand(0,$max);
		}
		
		return $items[$index];
	} else {
		error_log("Unable to define a valid item for the checker :".$checker.". You should review your configuration." ,0);
		return null;
	}
}


$pdo = connexionbdd($conf);

$var = random_select($pdo,$checker,$conf);

if( $var != null ){
	$host_explode = explode(':',$var['item']);
	if( count($host_explode) == 3 ){
		$array = array("host" => $host_explode[0], "protocole" => $host_explode[1], "port" => $host_explode[2], "string" => "");
	} else {
		$array = array("host" => $host_explode[0], "protocole" => $host_explode[1], "port" => $host_explode[2], "string" => $host_explode[3]);
	}
	$json = json_encode($array);
	increment($var['item'],$pdo);
	echo $json;
}
?>
