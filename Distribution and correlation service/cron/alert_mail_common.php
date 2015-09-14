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
date_default_timezone_set('Europe/Berlin');
/*
 * Functions to send mail
 */
function send_alert($conf,$item,$results,$vocables,$pdo){
	$directory = $conf['directory_tmp'];
	$split_item = split(':',$item);
	$ip = $split_item[0];
	$port = $split_item[2];
	$protocol = $split_item[1];
	$factors_str_host = "";
	$factors_str_network = "";
	$factors_str_rout = "";
	$factors_str_host_lost = "";
	$factors_str_network_lost = "";
	$factors_str_rout_lost = "";
	$lang = $conf['language_mail'];
	$main_text = "";
		
		
	foreach( $results as $factors ){
		foreach( $factors['factors'] as $factor ){
			
			if( $factors['type_of_checker'] == "failed" ){
				if( $factors['type'] == 'hostname' ){
					if( $factor['index'] != 0 ){
						for($j=0; $j<($factor['index']-strlen($factors_str_host)+1);$j++) {
							$factors_str_host .= "*";
						}
					}
					else{
						$factors_str_host .= ", ";
					}
					$factors_str_host .= $factor['sub'];
				} else if($factors['type'] == 'network') {
					$factors_str_network .= $factor['network']." ";
				} else if($factors['type'] == 'routeur') {
					$factors_str_rout .= $factor['routeur']." ";
				}
			} else if( $factors['type_of_checker'] == "lost" ){
				if( $factors['type'] == 'hostname' ){
					if( $factor['index'] != 0 ){
						for($j=0; $j<($factor['index']-strlen($factors_str_host_lost)+1);$j++) {
							$factors_str_host_lost .= "*";
						}
					}else{
						$factors_str_host_lost .= ", ";
					}
					$factors_str_host_lost .= $factor['sub'];
				} else if($factors['type'] == 'network') {
					$factors_str_network_lost .= $factor['network']." ";
				} else if($factors['type'] == 'routeur') {
					$factors_str_rout_lost .= $factor['routeur']." ";
				}
			}
		}
			
			
		
		if( $factors['type_of_checker'] == "failed" ){
			if( $factors['type'] == 'hostname' ){
				$main_text .= $vocables[$lang]['alert_mail_common_1'].$factors_str_host."<br/>";
			}
			else if( $factors['type'] == 'network' ){
				$main_text .= $vocables[$lang]['alert_mail_common_2'].$factors_str_network."<br/>";
			}
			else if($factors['type'] == 'routeur' && trim($factors_str_rout) != ""){
				$main_text .= $vocables[$lang]['alert_mail_common_3'].$factors_str_rout."<br/>";
			}
		}
		else if( $factors['type_of_checker'] == "lost" ){
			if( $factors['type'] == 'hostname' ){
				$main_text .=  $vocables[$lang]['alert_mail_common_4'].$factors_str_host_lost.$vocables[$lang]['alert_mail_common_5']."<br/>";
			}
			else if( $factors['type'] == 'network' ){
				$main_text .= $vocables[$lang]['alert_mail_common_6'].$factors_str_network_lost.$vocables[$lang]['alert_mail_common_7']."<br/>";
			}
			else if( $factors['type'] == 'routeur' && trim($factors_str_rout) != ""){
				$main_text .= $vocables[$lang]['alert_mail_common_8'].$factors_str_rout_lost.$vocables[$lang]['alert_mail_common_9']."<br/>";
			}
		}
	}
	
	
	/*******
	*
	* Listing all the possible facts
	*
	*******/
		$sql = "SELECT * FROM items WHERE item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach( $result as $hostname ){
			$hostname = $hostname['hostname'];
		}		
		
		//Table of people who missed an item
		$sql = "SELECT DISTINCT (checker_id) FROM results WHERE result=1 and item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		$array_failed = array();
		foreach( $result as $checker ){
			$array_failed[] = $checker['checker_id'];
		}
		$count_failed = count($array_failed);

		//Table of people who missed an item on the match
		$sql = "SELECT DISTINCT (checker_id) FROM results WHERE result=2 and item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		$array_failed_match = array();
		foreach( $result as $checker ){
			$array_failed_match[] = $checker['item'];
		}
		$count_failed_match = count($array_failed_match);

		//Table of people who missed an item on the proxy
		$sql = "SELECT DISTINCT checker_id FROM results WHERE result=3 and item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		$array_failed_proxy = array();
		foreach( $result as $checker ){
			$array_failed_proxy[] = $checker['checker_id'];
		}
		$count_failed_proxy = count($array_failed_proxy);
		
		//Table of people who missed an item on the latency
		$sql = "SELECT DISTINCT checker_id FROM results WHERE result=4  and item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		$array_failed_latency = array();
		foreach( $result as $checker ){
			$array_failed_latency[] = $checker['checker_id'];
		}
		$count_failed_latency = count($array_failed_latency);
	/***/

	/*******
	*
	* Listing of people who have tested an item
	*
	*******/
		$sql2 = "SELECT count(DISTINCT checker_id)AS total FROM results  where item='".$item."'";
		$statement = $pdo->prepare($sql2);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach( $result as $checker ){
			$array_all = $checker['total'];
		}
		$statement->closeCursor();
	/***/

	/*******
	*
	* Treatment
	*
	*******/
		$result = array();
		$glob_timeout = $glob_proxy = $glob_latency = $glob_match = 0;
		if( isset($array_all) && $array_all > 0 ){
			
			$glob_timeout = ($count_failed*100)/$array_all;
			
			$glob_proxy = ($count_failed_proxy*100)/$array_all;
			
			$glob_latency = ($count_failed_latency*100)/$array_all;
			
			$glob_match = ($count_failed_match*100)/$array_all;
					
		}		
	/***/
		
	$subject = $conf['email_begin_subject'].$vocables[$lang]["subject_email_current"].$hostname."[".$ip." (".$port."/".$protocol.")]";
	
	$pictos = "";
	
	if( round($glob_timeout) == 0 ){
		$pictos .= "<td>
					<img src='img/timeout.png' width='16px' title='Unreachability'/>
					".round($glob_timeout)."%<br/>
					(Unreachability)
				</td>";
	}
	else{
		$pictos .= "<td>
					<img src='img/timeout.png' width='24px' title='Unreachability'/>
					".round($glob_timeout)."%<br/>
					(Unreachability)
				</td>";
	}
	
	if( round($glob_proxy) == 0 ){
		$pictos .= "<td>
					<img src='img/proxy.png' width='16px' title='Proxy Error'/>
					".round($glob_proxy)."%<br/>
					(Proxy Error)
				</td>";
	}
	else{
		$pictos .= "<td>
					<img src='img/timeout.png' width='24px' title='Proxy Error'/>
					".round($glob_proxy)."%<br/>
					(Proxy Error)
				</td>";
	}
	
	if( round($glob_latency) == 0 ){
		$pictos .= "<td>
					<img src='img/latency.png' width='16px' title='Latency Issue'/>
					".round($glob_latency)."%<br/>
					(Latency Issue)
				</td>";
	}
	else{
		$pictos .= "<td>
					<img src='img/timeout.png' width='24px' title='Latency Issue'/>
					".round($glob_latency)."%<br/>
					(Latency Issue)
				</td>";
	}
	
	if( round($glob_match) == 0 ){
		$pictos .= "<td>
					<img src='img/match.png' width='16px' title='Unexpected Content'/>
					".round($glob_match)."%<br/>
					(Unexpected Content)
				</td>";
	}
	else{
		$pictos .= "<td>
					<img src='img/timeout.png' width='24px' title='Unexpected Content'/>
					".round($glob_match)."%<br/>
					(Unexpected Content)
				</td>";
	}
	
	$body = "<html>
			   <head>
				  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
				  <title>".$vocables[$lang]["title"]."</title>
			   </head>
			   <body scroll='auto' style='padding:0; margin:0; FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; cursor:auto; background:#F3F3F3'>
				  <TABLE cellSpacing=0 cellPadding=0 width='100%' bgColor=#f3f3f3>
					 <TR>
						<TD style='FONT-SIZE: 0px; HEIGHT: 20px; LINE-HEIGHT: 0'>
						   &nbsp;
						</TD>
					 </TR>
					 <TR>
						<TD vAlign=top>
						   <TABLE style='HEIGHT: 100%; MARGIN: 0px auto' cellSpacing=0 cellPadding=0 width=600 align=center border=0>
							  <TR>
								 <TD style='BORDER-TOP: #dbdbdb 1px solid; BORDER-RIGHT: #dbdbdb 1px solid; BORDER-BOTTOM: #dbdbdb 1px solid; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 1px solid; PADDING-RIGHT: 0px; BACKGROUND-COLOR: #feffff'>
									<TABLE style='WIDTH: 100%' cellSpacing=0 cellPadding=0>
									   <TR style='HEIGHT: 20px'>
										  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: middle; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
											 <IMG style='BORDER-TOP: #dbdbdb 0px solid; BORDER-RIGHT: #dbdbdb 0px solid; BORDER-BOTTOM: #dbdbdb 0px solid; MARGIN: 2px; BORDER-LEFT: #dbdbdb 0px solid; BACKGROUND-COLOR: transparent' src='".$conf['logo_mail_warning']."' height=60>
										  </TD>
										  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
											 <P style='FONT-SIZE: 18px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a8a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
												<STRONG>
												   ".$hostname." (".$ip.") : ".$port."/".$protocol."
												</STRONG>
											 </P>
											 <P style='FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a7a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
												".$vocables[$lang]['alert_mail_common_10'].$hostname." (".$ip.") : ".$port."/".$protocol."
											 </P>
										  </TD>
									   </TR>
									</TABLE>
								 </TD>
							  </TR>
							  <TR>
								 <TD style='BORDER-TOP: #dbdbdb 0px hidden; BORDER-RIGHT: #dbdbdb 0px hidden; BORDER-BOTTOM: #dbdbdb 0px hidden; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 0px hidden; PADDING-RIGHT: 0px; BACKGROUND-COLOR: transparent'>
									<TABLE style='WIDTH: 100%' cellSpacing=0 cellPadding=0>
									   <TR style='HEIGHT: 10px'>
										  <TD style='BORDER-TOP: #dbdbdb 0px hidden; BORDER-RIGHT: #dbdbdb 0px hidden; WIDTH: 100%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 0px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 0px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: transparent'>
										  </TD>
									   </TR>
									</TABLE>
								 </TD>
							  </TR>
							  <TR>
								<TD style='BORDER-TOP: #dbdbdb 0px solid; BORDER-RIGHT: #dbdbdb 0px solid; BORDER-BOTTOM: #dbdbdb 0px solid; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 0px solid; PADDING-RIGHT: 0px;'>
									<TABLE style='WIDTH: 100%;' cellSpacing=0 cellPadding=0>
									   <TR style='HEIGHT: 20px'>
										  <TD style='BORDER-TOP: #dbdbdb 1px solid; BORDER-RIGHT: #dbdbdb 1px solid; WIDTH: 100%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 1px solid; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px solid; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
											 <P style='FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a7a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
												".$main_text."
											 </P>
										  </TD>
									   </TR>
									</TABLE>
								</TD>
							  </TR>
							  <TR>
								 <TD style='BORDER-TOP: #dbdbdb 0px hidden; BORDER-RIGHT: #dbdbdb 0px hidden; BORDER-BOTTOM: #dbdbdb 0px hidden; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 0px hidden; PADDING-RIGHT: 0px; BACKGROUND-COLOR: transparent'>
									<TABLE style='WIDTH: 100%' cellSpacing=0 cellPadding=0>
									   <TR style='HEIGHT: 10px'>
										  <TD style='BORDER-TOP: #dbdbdb 0px hidden; BORDER-RIGHT: #dbdbdb 0px hidden; WIDTH: 100%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 0px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 0px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: transparent'>
										  </TD>
									   </TR>
									</TABLE>
								 </TD>
							  </TR>
							  <TR>
								<TD style='BORDER-TOP: #dbdbdb 0px solid; BORDER-RIGHT: #dbdbdb 0px solid; BORDER-BOTTOM: #dbdbdb 0px solid; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 0px solid; PADDING-RIGHT: 0px;'>
									<TABLE style='WIDTH: 100%;' cellSpacing=0 cellPadding=0>
									   <TR style='HEIGHT: 20px'>
										  <TD style='BORDER-TOP: #dbdbdb 1px solid; BORDER-RIGHT: #dbdbdb 1px solid; WIDTH: 100%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 1px solid; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px solid; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
											<TABLE style='WIDTH: 100%;' cellSpacing=0 cellPadding=0>
												<TR style='HEIGHT: 20px'>
													".$pictos."
												</tr>
											</table>
										  </TD>
									   </TR>
									</TABLE>
								</TD>
							  </TR>
							  <TR>
								 <TD style='BORDER-TOP: #dbdbdb 0px hidden; BORDER-RIGHT: #dbdbdb 0px hidden; BORDER-BOTTOM: #dbdbdb 0px hidden; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 0px hidden; PADDING-RIGHT: 0px; BACKGROUND-COLOR: transparent'>
									<TABLE style='WIDTH: 100%' cellSpacing=0 cellPadding=0>
									   <TR style='HEIGHT: 10px'>
										  <TD style='BORDER-TOP: #dbdbdb 0px hidden; BORDER-RIGHT: #dbdbdb 0px hidden; WIDTH: 100%; VERTICAL-ALIGN: middle; BORDER-BOTTOM: #dbdbdb 0px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 0px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: transparent'>
											 <DIV style='TEXT-ALIGN: center; MARGIN: 0px 10px 0px 0px'>
												<a href=".$conf['charts_url']."?md5=".md5($item)."&i=".$split_item[0]."&p=".$split_item[1]."&po=".$split_item[2]."><img src='".$conf['graph']."'/></a>
											 </DIV>
										  </TD>
									   </TR>
									</TABLE>
								 </TD>
							  </TR>
						   </TABLE>
						</TD>
					 </TR>
					 <TR>
						<TD style='FONT-SIZE: 0px; HEIGHT: 8px; LINE-HEIGHT: 0'>
						   &nbsp;
						</TD>
					 </TR>
				  </TABLE>
			   </body>
			</html>";
	$from_email  = $conf['from_email'];
	$entetedate  = "Date: ".date("l j F Y, G:i +0200")."\n"; 
	$entetemail  = "From: $from_email \nReturn-path:".$conf['return_path'];
	$entetemail .= "Cc: \n";
	$entetemail .= "Bcc: \n";
	$entetemail .= "Reply-To: ".$conf['from_email']."\n"; 
	$entetemail .= "X-Mailer: PHP \n" ;
	$entetemail .= "Content-Type: text/html; charset=\"ISO-8859-1\ \n";
	$entetemail .= "Date: $entetedate";
	
	//conf if the permits, we will be able to send mails
	if( $conf['time_min_alert'] > 0 ){
		//If the lock file does not exist
		if( !file_exists($directory."common_mail.lock") ){
			//we send the item in the lock file
			file_put_contents($directory."common_mail.lock",$item."\n");
			//then send the email
			mail($conf['email'], utf8_decode($subject), utf8_decode($body), $entetemail);
		}
		else{
			//If the lock file exists
			//we will look at the list of items in the file
			$items_str = file_get_contents($directory."common_mail.lock");
			$items = explode("\n",$items_str);
			//if not find the item in the file
			if( array_search($item,$items) === false ){
				//we send the item in the lock file
				file_put_contents($directory."common_mail.lock",$item."\n",FILE_APPEND);
				//then send the email
				mail($conf['email'], utf8_decode($subject), utf8_decode($body), $entetemail);
			}
			else{
				//if it has been found
				//we verified that the email is still blocked
				if( (@filemtime($directory."mail.lock")+($conf['time_min_alert']*60)<=time()) ){
					unlink($directory."common_mail.lock");
					unlink($directory."mail.lock");
					$lock = fopen($directory."mail.lock", "w") or die("Can't create file");
					fclose($lock);
					file_put_contents($directory."common_mail.lock",$item."\n");
					mail($conf['email'], utf8_decode($subject), utf8_decode($body), $entetemail);
				}
			}
		}	
	}
}



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

/*
 * Function to calculate common hostname in checkers
 */
function common_host($conf,$item,$vocables){
	
	$final_results = array();
	
	$item_checked = $item;
	$pdo = connexionbdd($conf);
	//second we take fails checker ip
	$fails = array();
	$sql = "SELECT DISTINCT checker_id, checker_hostname FROM results WHERE result!=0 AND item=:item";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':item',$item);
	if( !$statement->execute() ){
		throw new PDOException();
	} 
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
		if($checker['checker_hostname'] != "") {
			$fails[] = $checker;
		}
	}
	$statement->closeCursor();	
	if(!(!$fails)){
		//third we take no fails checker ip
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

		//fourth we take lost checker ip
		$lost = array();

		$sql = "SELECT distinct checker_id, checker_ip
		FROM results 
		WHERE item = :item
				and check_timestamp < :dateMinus3000 
				and check_timestamp > :dateMinus6000
				and checker_ip NOT IN ( select checker_ip from results where item = :item
											and check_timestamp < :date 
											and  check_timestamp > :dateMinus3000 )";				
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		$statement->bindValue(':dateMinus3000',date('Y-m-d H:i:s',time()-3000));
		$statement->bindValue(':dateMinus6000',date('Y-m-d H:i:s',time()-6000));
		$statement->bindValue(':date',date('Y-m-d H:i:s',time()));
		if( !$statement->execute() ){
			throw new PDOException();
		} 
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as $checker ){
			if($checker['checker_hostname'] != "") {
				$lost[] = $checker;
			}
		}
		$statement->closeCursor();
		
		
		//after taking usefull datas
		//we calculate failed common hostname strings	 
		// we select the shortest item
		$item = $fails[0];
		foreach( $fails as $string ){
			if( strlen($string['checker_hostname']) < strlen($item['checker_hostname'])){
				$item = $string;
			}
		}	 
		
		// after that we build, all possibles factors
		$a_test = array();
		for($i=0;$i<strlen($item['checker_hostname']);$i++){
			for($j=0;$j<(strlen($item['checker_hostname'])-$i);$j++){
				$a_test[] = array( 'sub' => substr($item['checker_hostname'],$i,$j+1), 'index' => $i );
			}
		}
		
		array_splice($fails,intval(array_search($item,$fails)),1);
		$common_factor = array();
		
		// then we keep just factors wich are common to all items
		foreach( $a_test as $test ){
			$i=0;		
			while( $i<count($fails) and strpos($fails[$i]['checker_hostname'],$test['sub']) !== false && strpos($fails[$i]['checker_hostname'],$test['sub']) == $test['index']){
				$i++;
			}
			if( $i == count($fails) ){
				$common_factor[] = $test;
			}
		}
		
		// finally we take away sub factor from common factors	
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
		// we calculate lost common hostname strings	 
		if(count($lost) > 1){
			$item = $lost[0];
			foreach( $lost as $string ){
				if( strlen($string['checker_hostname']) < strlen($item['checker_hostname'])){
					$item = $string;
				}
			}
		}
		// after that we build, all possibles factors
		$a_test = array();
		for($i=0;$i<strlen($item['checker_hostname']);$i++){
			for($j=0;$j<(strlen($item['checker_hostname'])-$i);$j++){
				$a_test[] = array( 'sub' => substr($item['checker_hostname'],$i,$j+1), 'index' => $i );
			}
		}	
		array_splice($lost,intval(array_search($item,$lost)),1);
		$common_factor = array();
		
		// then we keep just factors wich are common to all items
		foreach( $a_test as $test ){
			$i=0;		
			while( $i<count($lost) and strpos($lost[$i]['checker_hostname'],$test['sub']) !== false && strpos($lost[$i]['checker_hostname'],$test['sub']) == $test['index']){
				$i++;
			}
			if( $i == count($lost) ){
				$common_factor[] = $test;
			}
		}
		
		// finally we take away sub factor from common factors	
		$final_factors_lost = array_values($common_factor);
		for($i=0;$i<count($common_factor);$i++){
			$others_factor = $common_factor;
			array_splice($others_factor,$i,1);
			for($j=0;$j<count($others_factor);$j++){
				if(strpos($common_factor[$i]['sub'],$others_factor[$j]['sub']) !== false and  $common_factor[$i]['index'] <= $others_factor[$j]['index'] and strlen($others_factor[$j]['sub'])+$others_factor[$j]['index'] <= $common_factor[$i]['index']+strlen($common_factor[$i]['sub'])){
					if( array_search($others_factor[$j],$final_factors_lost ) !== false ){
						array_splice($final_factors_lost ,intval(array_search($others_factor[$j],$final_factors_lost ) ),1);
					}
				}
			}	
		}
		
		// Recovering the all_fail
		$sql = "SELECT * FROM global_result WHERE md5=:md5 ORDER BY timestamp DESC LIMIT 0,1";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':md5',md5($item_checked));
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetch(PDO::FETCH_ASSOC);
		$nb_all = $result['nb_f']+$result['nb_s'];
		$statement->closeCursor();
		
		// Calculating the number of occurrence per common factor for failed
		for($i=0;$i<count($final_factors_failed);$i++) {
			$compteur_fail = 0;
			$compteur_no_fail = 0;
			foreach($fails as $val2) {
				if(preg_match("/^.{".$final_factors_failed[$i]['index']."}(".addslashes($final_factors_failed[$i]['sub']).")/",$val2['checker_hostname'])) {
					$compteur_fail ++;
				}
			}
			foreach($no_fails as $val2) {
				if(preg_match("/^.{".$final_factors_failed[$i]['index']."}(".addslashes($final_factors_failed[$i]['sub']).")/",$val2['checker_hostname'])) {
					$compteur_no_fail ++;
				}
			}

			$final_factors_failed[$i]['nb_all_network']=$compteur_fail+$compteur_no_fail;
			$final_factors_failed[$i]['nb_f_network']=$compteur_fail;
		}	
		$host_to_alert = array();
		foreach( $final_factors_failed as $factor ){
			if( $factor['nb_f_network'] == $factor['nb_all_network'] ){
				$host_to_alert[] = $factor;
			}
		}
		
		
		
		if( count($host_to_alert) > 0 && $conf['active_logstalgia'] == 1 ){
			$final_results[] = array( 'factors' => $host_to_alert, 'type_of_checker' => 'failed');
			foreach($host_to_alert as $host) {
				$item_log_explode = explode(":",$item_checked);
				$size = ($host['nb_f_network']*100/$nb_all)*$conf['size_super_ball'];
				$item_log = time()."|".$host['sub']."|".$item_log_explode[0]."|"."Fail"."|".$size."|"."0"."|"."FF0000"."\n";
				file_put_contents($conf['directory_tmp']."logstalgia.txt",$item_log,FILE_APPEND);
			}
		}
		
		$result_final = array('hostname' => $host_to_alert,'nb_all' => $nb_all);
		// Calculating the number of occurrence per common factor for lost
		for($i=0;$i<count($final_factors_lost);$i++) {
			$compteur_fail = 0;
			$compteur_no_fail = 0;
			$compteur_lost = 0;
			$pos = 0;
			foreach($fails as $val2) {
				if(preg_match("/^.{".$final_factors_lost[$i]['index']."}(".addslashes($final_factors_lost[$i]['sub']).")/",$val2['checker_hostname'])) {
					$compteur_fail ++;
				}
			}
			foreach($no_fails as $val2) {
				if(preg_match("/^.{".$final_factors_lost[$i]['index']."}(".addslashes($final_factors_lost[$i]['sub']).")/",$val2['checker_hostname'])) {
					$compteur_no_fail ++;
				}
			}
			foreach($lost as $val2) {
				if(preg_match("/^.{".$final_factors_lost[$i]['index']."}(".addslashes($final_factors_lost[$i]['sub']).")/",$val2['checker_hostname'])) {
					$compteur_lost ++;
				}
			}

			$final_factors_lost[$i]['nb_all_network']= $compteur_fail+$compteur_no_fail+$compteur_lost;
			$final_factors_lost[$i]['nb_l_network']= $compteur_lost;		
		}
		
		$host_lost_to_alert = array();
		foreach( $final_factors_lost as $factor ){
			if( $factor['nb_l_network'] == $factor['nb_all_network'] ){
				$host_lost_to_alert[] = $factor;
			}
		}
		
		if( count($host_lost_to_alert) > 0  && $conf['active_logstalgia'] == 1){
			$final_results[] = array( 'factors' => $host_lost_to_alert, 'type_of_checker' => 'lost');
			foreach($host_lost_to_alert as $host) {
				$item_log_explode = explode(":",$item_checked);
				$size = ($host['nb_l_network']*100/$nb_all)*$conf['size_super_ball'];
				$item_log = time()."|".$host['sub']."|".$item_log_explode[0]."|"."Fail"."|".$size."|"."0"."|"."FF0000"."\n";
				file_put_contents($conf['directory_tmp']."logstalgia.txt",$item_log,FILE_APPEND);
			}
		}	
	}
	
	return $final_results;
}

/*
 * Function to calculate common networks for checkers
 */
function netmask($ip, $cidr) {
    $bitmask = $cidr == 0 ? 0 : 0xffffffff << (32 - $cidr);
    
    return long2ip(ip2long($ip) & $bitmask);
}

function fromIpnetToNetwork( $network ){
	$network_explode = explode("/",$network);
	echo $network;
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


function common_network($conf,$item,$vocables){
	
	$final_results = array();
	
	$item_checked = $item;
	$pdo = connexionbdd($conf);	
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
	if(!(!$fails)){
		//var_dump($fails);
		//third we take no fails checker ip	
		$no_fails = array();

		$sql = "SELECT DISTINCT checker_id, checker_ip FROM results WHERE result=0 AND item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as $checker ){
			$no_fails[] = $checker;
		}
		$statement->closeCursor();
		
		//fourth we take lost checker ip
		$lost = array();

		$sql = "SELECT distinct checker_id, checker_ip
		FROM results 
		WHERE item = :item
				and check_timestamp < :dateMinus3000 
				and check_timestamp > :dateMinus6000
				and checker_ip NOT IN ( select checker_ip from results where item = :item
											and check_timestamp < :date 
											and  check_timestamp > :dateMinus3000 )";				
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		$statement->bindValue(':dateMinus3000',date('Y-m-d H:i:s',time()-3000));
		$statement->bindValue(':dateMinus6000',date('Y-m-d H:i:s',time()-6000));
		$statement->bindValue(':date',date('Y-m-d H:i:s',time()));
		if( !$statement->execute() ){
			throw new PDOException();
		}

		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as $checker ){
				$lost[] = $checker;
		}
		$statement->closeCursor();
		
		//var_dump($lost);
		
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
					//echo "<p>".netmask($checker['checker_ip'],$netmask_incr)."</p>";
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
			echo $network;
			$networks_for_display[] = array('nb_f_network' => count($ips) , 'network' => fromIpnetToNetwork($network));
		}
		
		
		
		$nb_all = count($fails)+count($no_fails);
		if( count($networks_for_display) > 0  && $conf['active_logstalgia'] == 1){
			$final_results[] = array( 'factors' => $networks_for_display, 'type_of_checker' => 'failed');
			foreach($networks_for_display as $network) {
				$item_log_explode = explode(":",$item_checked);
				$size = ($network['nb_f_network']*100/$nb_all)*$conf['size_super_ball'];
				$item_log = time()."|".$network['network']."|".$item_log_explode[0]."|"."Fail"."|".$size."|"."0"."|"."FF0000"."\n";
				file_put_contents($conf['directory_tmp']."logstalgia.txt",$item_log,FILE_APPEND);
			}
		}		
		
		$result_final = array('networks' => $networks_for_display,'nb_all' => $nb_all);
		
		
		
		//calculate common network for checker lost
		$netmask_find = 0;
		$netmask_incr = $conf['maximum_netmask_for_groups'];
		
		$networks = array();	

		//first we search all shortest common network for all ip checker
		for($i=0;$i<count($fails);$i++){
			$checker = $fails[$i];
			//we loop again ip checker who failed to compare networks
			for($j=$i;$j<count($fails);$j++){
				$checker_to_compare = $fails[$j];
				//if the two ip checker are different
				if( $checker['checker_ip'] != $checker_to_compare['checker_ip'] ){
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
		$networks_exclusiv_lost = $networks;
		
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
				array_splice($networks_exclusiv_lost  ,array_search($network,$networks_exclusiv_lost),1);
			}
			
			$network_has_fails = false;
			$i = 0;
			while( $i<count($fails) and !$network_has_fails ){
				$netmask = split("/",$network);
				
				if( $netmask[0] == netmask($fails[$i]["checker_ip"],$netmask[1]) ){
					$network_has_fails = true;
				}
				$i++;
			}
			if( $network_has_fails ){
				array_splice($networks_exclusiv_lost  ,array_search($network,$networks_exclusiv_lost),1);
			}
		}

		$networks_for_display_lost  = array();
		
		foreach( $networks_exclusiv_lost  as $network => $ips ){
			$networks_for_display_lost[] = array('nb_f_network' => count($ips) , 'network' => fromIpnetToNetwork($network));
		}
		
		
		
		if( count($networks_for_display_lost ) > 0  && $conf['active_logstalgia'] == 1){
			$final_results[] = array( 'factors' => $networks_for_display_lost, 'type_of_checker' => 'lost');
			foreach($networks_for_display_lost as $network) {
				$item_log_explode = explode(":",$item_checked);
				$size = ($network['nb_f_network']*100/$nb_all)*$conf['size_super_ball'];
				$item_log = time()."|".$network['network']."|".$item_log_explode[0]."|"."Fail"."|".$size."|"."0"."|"."FF0000"."\n";
				file_put_contents($conf['directory_tmp']."logstalgia.txt",$item_log,FILE_APPEND);
			}
		}
	}
	
	return $final_results;
}

/*
 * Function to calculate common routeurs
 */
function common_routeur($conf,$item,$vocables){
	
	$final_results = array();
	
	$checktimestamp_log = time();
	$item_checked = $item;
	$pdo = connexionbdd($conf);	
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
	if(!(!$fails)){
	
		//third we take no fails checker ip
		$no_fails = array();

		$sql = "SELECT DISTINCT checker_id, checker_ip FROM results WHERE result=0 AND item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as $checker ){
				$no_fails[] = $checker;
		}
		$statement->closeCursor();


		
		//fourth we take lost checker ip
		$lost = array();
		$sql = "SELECT distinct checker_id, checker_ip
		FROM results 
		WHERE item = :item
				and check_timestamp < :dateMinus3000 
				and check_timestamp > :dateMinus6000
				and checker_ip NOT IN ( select checker_ip from results where item = :item
											and check_timestamp < :date 
											and  check_timestamp > :dateMinus3000 )";				
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item);
		$statement->bindValue(':dateMinus3000',date('Y-m-d H:i:s',time()-3000));
		$statement->bindValue(':dateMinus6000',date('Y-m-d H:i:s',time()-6000));
		$statement->bindValue(':date',date('Y-m-d H:i:s',time()));
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as $checker ){
				$lost[] = $checker;
		}
		$statement->closeCursor();	
		
		
		
		
		// Calculate routeur ip for failed checker	
		//we initate networks array
		$network_for_failed_checker = array();
		foreach( $fails as $checker ){
			
			//we try to take route for network from db
			$route = "";
			$network = netmask($checker['checker_ip'],$conf['minimum_netmask_for_groups']);
			$network = $network."/".$conf['minimum_netmask_for_groups'];

			$sql = "select route from traceroute where check_net = :net";

			$statement = $pdo->prepare($sql);
			$statement->bindValue(':net',$network);
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
				$net_with_mask = $network;
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
		
		
		
		$nb_all = count($nb_all_fail);
		if( count($routeurs_for_display) > 0  && $conf['active_logstalgia'] == 1){
			$final_results[] = array( 'factors' => $routeurs_for_display, 'type_of_checker' => 'failed');
			foreach($routeurs_for_display as $routeur) {
				$item_log_explode = explode(":",$item_checked);
				$size = ($routeur['nb_fail']*100/$nb_all)*$conf['size_super_ball'];
				$item_log = time()."|".$routeur['routeur']."|".$item_log_explode[0]."|"."Fail"."|".$size."|"."0"."|"."FF0000"."\n";
				file_put_contents($conf['directory_tmp']."logstalgia.txt",$item_log,FILE_APPEND);
			}
		}	
		// Calculate routeurs ip for lost checker	 
		 //we initate networks array
		$network_for_lost_checker = array();
		foreach( $lost as $checker ){
			//we try to take route for network from db
			$route = "";
			$network = netmask($checker['checker_ip'],$conf['minimum_netmask_for_groups']);
			$sql = "SELECT route FROM traceroute WHERE check_net=:check and  check_timestamp > :time";
			$statement = $pdo->prepare($sql);
			$statement->bindValue(':check',$network);
			$statement->bindValue(':time',date('Y-m-d H:i:s',time()));
			if( !$statement->execute() ){
				throw new PDOException();
			}
			$result = $statement->fetch(PDO::FETCH_ASSOC);
		
			//if we have the traceroute for ip checker
			if( !(!$result) ){			
				//we first check if network has no no fail ip checkers
				$i=0;
				while( $i<count($no_fails) and $network != netmask($no_fails[$i]['checker_ip'],$conf['minimum_netmask_for_groups']) ){
					$i++;
				}			
				if( $i == count($no_fails)){
					$i=0;
					while( $i<count($fails) and $network != netmask($fails[$i]['checker_ip'],$conf['minimum_netmask_for_groups']) ){
					$i++;
					}
				
					if( $i == count($fails)){
						//if the networks exists in the array, we only add the ip checker to compare to the network key
						if( !array_key_exists($network,$network_for_lost_checker) ){
							$network_for_lost_checker[$network] = array();
							$route = explode(';',$result['route']);
							$ip_routeurs = array();
							foreach( $route as $routeur ){
								$ip_routeurs = explode(';',$routeur);
							}
							$network_for_lost_checker[$network]['route'] = $ip_routeurs;
							$network_for_lost_checker[$network]['nb_fail'] = 1;
						}
						else{
							$network_for_lost_checker[$network]['nb_fail'] = $network_for_lost_checker[$network]['nb_fail']+1;
						}
					}
				}
			}
		}	
		
		$routers_lost = array();	
		if( count($network_for_lost_checker) > 1 ){
			$network_keys = array_keys($network_for_lost_checker);
			foreach( $network_keys as $net ){
				$network_1 = $network_for_lost_checker[$net];
				$networks_left = $network_keys;
				$networks_left = array_slice($networks_left,0,array_search($net,$networks_left));
				foreach( $networks_left as $net_compare ){
					$network_2 = $network_for_lost_checker[$net_compare];
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
			//problem, on doit choper le seul element pour prendre son routeur le plus à gauche
			foreach( $network_for_lost_checker as $network_1 ){
				$routeur = $network_1['route'][count($network_1['route'])-1][0];
				$routeurs_lost = array( $routeur => $network_1['nb_fail']);
			}
		}


		$routeurs_for_lost_display = array();
		foreach( $routers_lost as $routeur ){
			$total_lost_for_routeur = 0;
			foreach( $routeur as $nb_lost ){
				$total_lost_for_routeur = $total_lost_for_routeur+$nb_lost;
			}
			$routeurs_for_lost_display[] = array('routeur' => $routeur , 'nb_lost' => $total_lost_for_routeur);
		}
		
		if( count($routeurs_for_lost_display) > 0  && $conf['active_logstalgia'] == 1){
			$final_results[] = array( 'factors' => $routeurs_for_lost_display, 'type_of_checker' => 'lost');
			foreach($routeurs_for_lost_display as $routeur) {
				$item_log_explode = explode(":",$item_checked);
				$size = ($routeur['nb_lost']*100/$nb_all)*$conf['size_super_ball'];
				$item_log = time()."|".$routeur['routeur']."|".$item_log_explode[0]."|"."Fail"."|".$size."|"."0"."|"."FF0000"."\n";
				file_put_contents($conf['directory_tmp']."logstalgia.txt",$item_log,FILE_APPEND);
			}
		}
	}
	
	return $final_results;
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

$types = choosen_common_factor($conf['type_common_factor_for_alert']);
$results = array();

$pdo = connexionbdd($conf);
$sql = "SELECT DISTINCT item FROM results WHERE result=1 OR result=2 OR result=3 OR result=4";
$statement = $pdo->prepare($sql);
if( !$statement->execute() ){
	throw new PDOException();
}
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach( $result as $item ){
	foreach( $types as $type ){
		switch( $type ){
		
			case 'hostname' : $founds = common_host($conf,$item['item'],$vocables);
				$type = 'hostname';	
			break;
		
			case 'network' : $founds = common_network($conf,$item['item'],$vocables);
				$type = 'network';
			break;
		
			case 'routeur' : $founds = common_routeur($conf,$item['item'],$vocables);
				$type = 'routeur';
			break;
		
			default : $results = 0;
			break;
		
		}
		foreach( $founds as $found ){
			if( count($found['factors']) > 0 ){
				$results[] = array( 'type' => $type , 'factors' => $found['factors'], 'type_of_checker' => $found['type_of_checker']);
			}
		}
	}
	send_alert($conf,$item['item'],$results,$vocables,$pdo);
}
	




