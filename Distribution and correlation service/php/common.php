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
 * Connection according to the database
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
 * Calculation based on common factors in the host name of checkers
 */
function common_host($conf,$item_md5){
	
	//it first connects to the database
	$pdo = connexionbdd($conf);
	
	//it retrieves the edge of the item of information
	$sql = "SELECT item from global_result WHERE md5=:md5";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':md5',$item_md5);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetch(PDO::FETCH_ASSOC);
	$item = $result['item'];
	$statement->closeCursor();

	
	//then retrieves checkers have failed the test
	$fails = array();
	$sql = "SELECT DISTINCT checker_id, checker_hostname FROM results WHERE result!=0 AND item=:item";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	 
	if( !$statement->execute()){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
		if($checker['checker_hostname'] != "") {
			$fails[] = $checker;
		}
	}
	$statement->closeCursor();
	$fails_tab = $fails;
	
	
	//then take the checker that have not failed the test
	$no_fails = array();
	$sql = "SELECT DISTINCT checker_id, checker_hostname FROM results WHERE result=0 AND item=:item";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
		if($checker['checker_hostname'] != "") {
			$no_fails[] = $checker;
		}
	}
	$statement->closeCursor();	
	$no_fails_tab = $no_fails;
	
	

	//we calculate failed common hostname strings
	//Then we will look for common strings in the hostname
	//We will finally select the shortest
	
	@$item = $fails[0];
	
	foreach( $fails as $string ){
		if( strlen($string['checker_hostname']) < strlen($item['checker_hostname'])){
			$item = $string;
		}
	}	 
	
	//we build all sub channels possible from the first
	$a_test = array();
	
	for($i=0;$i<strlen($item['checker_hostname']);$i++){
		for($j=0;$j<(strlen($item['checker_hostname'])-$i);$j++){
			$a_test[] = array( 'sub' => substr($item['checker_hostname'],$i,$j+1), 'index' => $i );
		}
	}
	
	
	
	array_splice($fails,intval(array_search($item,$fails)),1);
	$common_factor = array();
	
	//one keeps only the common factors to all hostnames
	foreach( $a_test as $test ){
		$i=0;		
		while( $i<count($fails) and strpos($fails[$i]['checker_hostname'],$test['sub']) !== false && strpos($fails[$i]['checker_hostname'],$test['sub']) == $test['index']){
			$i++;
		}
		if( $i == count($fails) ){
			$common_factor[] = $test;
		}
	}
	
	//Finally, it removes unnecessary under common factor
	$final_factors_failed = array_values($common_factor);
	for($i=0;$i<count($common_factor);$i++){
		$others_factor = $common_factor;
		array_splice($others_factor,$i,1);
		for($j=0;$j<count($others_factor);$j++){
			if(strpos($common_factor[$i]['sub'],$others_factor[$j]['sub']) !== false and  $common_factor[$i]['index'] <= $others_factor[$j]['index'] and strlen($others_factor[$j]['sub'])+$others_factor[$j]['index'] <= $common_factor[$i]['index']+strlen($common_factor[$i]['sub'])){
				if( array_search($others_factor[$j],$final_factors_failed ) !== false ){
					array_splice($final_factors_failed ,intval(array_search($others_factor[$j],$final_factors_failed ) ),1);
				}
			}
		}	
	}
	
	
	
	
	//Then comes the values ​​recovered in the overall results
	$sql = "SELECT * FROM global_result WHERE md5=:md5 ORDER BY timestamp DESC LIMIT 0,1";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':md5',$item_md5);
	
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetch(PDO::FETCH_ASSOC);
	$nb_all = $result['nb_f']+$result['nb_s'];
	$statement->closeCursor();
	
	
	//Calculating the number of occurrence per common factor for failed
	for($i=0;$i<count($final_factors_failed);$i++) {
		$compteur_fail = 0;
		$compteur_no_fail = 0;
		foreach($fails_tab as $val2) {
			if(preg_match("/^.{".$final_factors_failed[$i]['index']."}(".addslashes($final_factors_failed[$i]['sub']).")/",$val2['checker_hostname'])) {
				$compteur_fail ++;
			}
		}
		foreach($no_fails_tab as $val2) {
			if(preg_match("/^.{".$final_factors_failed[$i]['index']."}(".addslashes($final_factors_failed[$i]['sub']).")/",$val2['checker_hostname'])) {
				$compteur_no_fail ++;
			}
		}
		$final_factors_failed[$i]['nb_all_network']=$compteur_fail+$compteur_no_fail;
		$final_factors_failed[$i]['nb_f_network']=$compteur_fail;
	}
	
	
	//we construct the regex common factors
	$factors_str = "";
	foreach($final_factors_failed as $host_part ){
		if( $host_part['index'] != 0 ){
			for($j=0; $j<($host_part['index']-strlen($factors_str)+1);$j++) {
				$factors_str .= "*";
			}
		}
		$factors_str .= $host_part['sub'];
	}
	
	
	//Finally, it formats the data to send the results
	$nbf = 0;
	if( isset($final_factors_failed[0]) ){
		$nbf = $final_factors_failed[0]['nb_f_network'];
	}
	
	$res = array('hostname' => $factors_str,'nb_f' => $nbf );
	
	$result_final = array('hostnames' => $res,'nb_all' => $nb_all);
	return $result_final;
}

/*
 * Netmask calculation function for a network address
 */
function netmask($ip, $cidr) {
    $bitmask = $cidr == 0 ? 0 : 0xffffffff << (32 - $cidr);
    return long2ip(@ip2long($ip) & $bitmask);
}


//based formatting a string corresponding to the address of a network
//network address
function fromIpnetToNetwork( $network ){
	$network_explode = explode("/",$network);
	$ip_explode = explode(".",$network_explode[0]);
	if( $network_explode[1] < 8 &&  $network_explode[1] >= 0 ){
		return "0.0.0.0/".$network_explode[1];
	}
	else if( $network_explode[1] < 16 ){
		return $ip_explode[0].".0.0.0/".$network_explode[1];
	}
	else if( $network_explode[1] < 24 ){
		return $ip_explode[0].".".$ip_explode[1].".0.0/".$network_explode[1];
	}
	else{
		return $ip_explode[0].".".$ip_explode[1].".".$ip_explode[2].".0/".$network_explode[1];
	}
}

/*
 * Fontion calculation of common network of checkers
 */
function common_network($conf,$item_md5){	
	//it first connects to the database
	$pdo = connexionbdd($conf);
	
	//it retrieves the edge of the item of information
	$sql = "SELECT item from global_result WHERE md5='".$item_md5."'";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':md5',$item_md5);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetch(PDO::FETCH_ASSOC);
	$item = $result['item'];
	$statement->closeCursor();
	
	
	//second we take fails checker ip
	$fails = array();
	$sql = "SELECT DISTINCT checker_id, checker_ip FROM results WHERE result!=0 AND item=:item";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
		$fails[] = $checker;
	}
	$statement->closeCursor();

	//var_dump($fails);
	//third we take no fails checker ip	
	$no_fails = array();
	$sql = "SELECT DISTINCT checker_id, checker_ip FROM results WHERE result=0 AND item='".$item."'";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	
	if( !$statement->execute()){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
		$no_fails[] = $checker;
	}
	$statement->closeCursor();	
	
	//calculate common network for checker failed
	$netmask_find = 0;
	$netmask_incr = $conf['maximum_netmask_for_groups'];	
	$networks = array();	
	//first we search all shortest common network for all ip checker
	//we loop all ip checker who failed
	for($i=0;$i<count($fails);$i++){
		$checker = $fails[$i];
		//we loop again ip checker who failed to compare networks
		for($j=$i;$j<count($fails);$j++){
			$checker_to_compare = $fails[$j];
			//if the two ip checker are different
			if( $checker['checker_id'] != $checker_to_compare['checker_id'] ){
				//we look for the shortest common network between the two ips
				while( $netmask_find == 0 and $netmask_incr >= $conf['minimum_netmask_for_groups'] ){
					if( netmask($checker['checker_ip'],$netmask_incr) == netmask($checker_to_compare['checker_ip'],$netmask_incr) ){
						$netmask_find = $netmask_incr;
					}
					$netmask_incr--;
				}
				//if the networks exists in the array, we only add the ip checker to compare to the network key
				if( array_key_exists(netmask($checker['checker_ip'],$netmask_find)."/".$netmask_find,$networks )){
					$networks[netmask($checker['checker_ip'],$netmask_find)."/".$netmask_find][] = $checker_to_compare['checker_ip'] ;
				}
				else{
					// else we add a new key entry ( network ) in the array adding the two ips to compare
					$networks[netmask($checker['checker_ip'],$netmask_find)."/".$netmask_find] = array($checker['checker_ip'],$checker_to_compare['checker_ip']);
				}
			}
		}
	}
	//then we look for ip checker who doesn't failed to throw networks away if only one hasn't failed
	$networks_exclusiv = $networks;	
	foreach( $networks as $network => $ip_checkers ){
		$network_has_non_fails = false;
		$i = 0;
		while( $i<count($no_fails) and !$network_has_non_fails ){
			$netmask = split("/",$network);
			if( $netmask[0] == netmask($no_fails[$i],$netmask[1]) ){
				$network_has_non_fails = true;
			}
			$i++;
		}
		if( $network_has_non_fails ){
			array_splice($networks_exclusiv ,$network,1);
		}
	}
	$networks_for_display = array();
	
	foreach( $networks_exclusiv as $network => $ips ){
		$networks_for_display[] = array('nb_f_network' => count($ips) , 'network' => fromIpnetToNetwork($network));
	}
	
	
	$nb_target = array();
	$nb_rest = array();
	if( count($fails) < count($no_fails) ){
		$nb_target = array_values($fails);
		$nb_rest = array_values($no_fails);
	}
	else{
		$nb_target = array_values($no_fails);
		$nb_rest = array_values($fails);
	}
	
	foreach( $nb_rest as $checker ){
		$i = 0;
		while( $i < count($nb_target) && $checker['checker_id'] !== $nb_target[$i]['checker_id'] ){
			$i++;
		}
		
		if( $i < count($nb_rest) ){
			$nb_target[] = $checker;
		}
	}
	
	$nb_all =  count($nb_target);
	
	
	$result_final = $networks_for_display;	
	return $result_final;	
}

/*
 * Function to calculate common routers
 */
function common_routeur($conf,$item_md5){	
	//first we connect to database
	$pdo = connexionbdd($conf);
	$sql = "SELECT item from global_result WHERE md5=:md5";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':md5',$item_md5);
	
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetch(PDO::FETCH_ASSOC);
	$item = $result['item'];
	$statement->closeCursor();	
	
	//second we take fails checker ip
	$fails = array();
	$sql = "SELECT DISTINCT checker_id, checker_ip FROM results WHERE result!=0 AND item=:item";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
			$fails[] = $checker;
	}
	$statement->closeCursor();	
	$nb_all_fail = $fails;
	//third we take no fails checker ip
	$no_fails = array();
	$sql = "SELECT DISTINCT checker_id, checker_ip FROM results WHERE result=0 AND item=:item";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	
	if( !$statement->execute()){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
			$no_fails[] = $checker;
	}
	$statement->closeCursor();	
	$nb_all_no_fail = $no_fails;
	// Calculate routeur ip for failed checker	
	//we initate networks array
	$network_for_failed_checker = array();
	foreach( $fails as $checker ){		
		//we try to take route for network from db
		$route = "";
		$network = netmask($checker['checker_ip'],$conf['minimum_netmask_for_groups']);
		$network = $network."/".$conf['minimum_netmask_for_groups'];
		$sql = "select route from traceroute where check_net = :network";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':network',$network);
			
		if( !$statement->execute() ){
			throw new PDOException();
		}		
		$result = $statement->fetch(PDO::FETCH_ASSOC);
		
		
		$route = $result['route'];	
		//if we have the traceroute for ip checker
		if( !(!$result) ){			
			//we first check if network has no no fail ip checkers
			$i=0;
			while( $i<count($no_fails) and $network != netmask($no_fails[$i]['checker_ip'],$conf['minimum_netmask_for_groups']) ){
				$i++;
			}		
			if( $i == count($no_fails)){				
				//if the networks exists in the array, we only add the ip checker to compare to the network key
				if( !array_key_exists($network,$network_for_failed_checker) ){
					$network_for_failed_checker[$network] = array();
					$route = explode(';',$result['route']);
					$ip_routeurs = array();
					foreach( $route as $routeur ){
						$ip_routeurs[] = explode(';',$routeur);
					}
					$network_for_failed_checker[$network]['route'] = $ip_routeurs;
					$network_for_failed_checker[$network]['nb_fail'] = 1;
				}
				else{
					$network_for_failed_checker[$network]['nb_fail'] = $network_for_failed_checker[$network]['nb_fail']+1;
				}
			}
		}
		else{
			//one must add the road
			$routes = shell_exec("traceroute -nI ".$checker['checker_ip']);
			$routes = explode("\n",$routes);
			array_splice($routes,0,1);
			$final_route = array();
			foreach( $routes as $route ){
				$route_explode = explode(' ', $route);
				if( count($route_explode) > 1 ){
					$final_route[] = $route_explode[3];
				}
			}
			$net_with_mask = $checker_network."/".$conf['minimum_netmask_for_groups'];
			$sql = "INSERT INTO traceroute (check_net,route,timestamp) VALUES (:check,:route,:time)";
			$statement = $pdo->prepare($sql);
			$statement->bindValue(':check',$net_with_mask);
			$statement->bindValue(':route',implode(';',$final_route));
			$statement->bindValue(':time',date('Y-m-d H:i:s',time()));
			if( !$statement->execute() ){
				throw new PDOException();
			}
		}
	}
	$routeurs_failed = array();	
	if( count($network_for_failed_checker) > 1 ){
		$network_keys = array_keys($network_for_failed_checker);
		foreach( $network_keys as $net ){
			$network_1 = $network_for_failed_checker[$net];
			$networks_left = $network_keys;
			$networks_left = array_slice($networks_left,0,array_search($net,$networks_left));
			foreach( $networks_left as $net_compare ){
				$network_2 = $network_for_failed_checker[$net_compare];
				if( $network_1 != $network_2 ){
					$trouver = false;
					$i=count($network_1['route'])-1;
					while( $i>=0 and !$trouver ){
						$j=count($network_2['route'])-1;
						while( $j>=0 and $network_1['route'][$i] != $network_2['route'][$j] ){
							$j--;
						}						
						if( $j>=0 ){
							
							$trouver = true;

							if( !array_key_exists($network_1['route'][$i],$routeurs_failed) ){
								$routeurs_failed[$network_1['route'][$i]] = array($network_1['nb_fail'],$network_2['nb_fail']);
							}
							else{
								echo "<p>".$network_1['route'][$i]."</p>";
								$routers_failed[$network_1['route'][$i]][] = $network_1['nb_fail'];
								$routers_failed[$network_1['route'][$i]][] = $network_2['nb_fail'];
							}							
						}						
						$i--;
					}
				}
			}
		}
	}
	else{		
		foreach( $network_for_failed_checker as $network_1 ){
			$routeur = $network_1['route'][count($network_1['route'])-1][0];
			$routeurs_failed = array( $routeur => array($network_1['nb_fail']));
		}
	}
	
	$routeurs_for_display = array();
	foreach( $routeurs_failed as $routeur => $fails ){
		$total_fails_for_routeur = 0;
		foreach( $fails as $nb_fails ){
			$total_fails_for_routeur = $total_fails_for_routeur+$nb_fails;
		}
		$routeurs_for_display[] = array('routeur' => $routeur , 'nb_fail' => $total_fails_for_routeur);
	}
	
	$nb_all = count($nb_all_fail)+count($nb_all_no_fail);
	
	$return_final = $routeurs_for_display;
	return $return_final;
	
}

function choosen_common_factor($types){
	$final_types = array();
	if( $types != "" ){
		$types_array = explode(";",$types);
		foreach($types_array as $type ){
			if( $type == "hostname" or $type == "network" or $type == "routeur" ){
				$final_types[] = $type;
			}
		}
		
	}
	else{
		error_log("No common factor selected for audits results",0);
	}
	
	return $final_types;
}
 
/*
 * Main code
 * which choose type of common factor and return 
 * values which has problems
 */


$md5 = trim(htmlspecialchars($_REQUEST['md5']));
$types = choosen_common_factor($conf['type_common_factor_for_alert']);
$results = array();
foreach( $types as $type ){
	switch( $type ){
		
		case 'hostname' : $results = common_host($conf,$md5);
			$nb_all = $results['nb_all'];
			$results['hostnames'] = $results['hostnames'];
			$results['nb_all'] = $nb_all;
		break;
		
		case 'network' : $results['networks'] = common_network($conf,$md5);
		break;
		
		case 'routeur' : $results['routeurs'] = common_routeur($conf,$md5);
		break;
		
		default : $results = 0;
		break;
		
	}	
}
echo json_encode($results);
