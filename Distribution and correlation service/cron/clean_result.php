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
include($conf['multi']);
date_default_timezone_set('Europe/Berlin');
$host_db = $conf['host_db'];
$user_db = $conf['user_db'];
$password_db = $conf['password_db'];
$bdd_db = $conf['bdd_db'];
$clean = $conf['check_lifetime'];
$clean_number = $conf['graph_lifetime'];
$log_movie = $conf['log_movie'];
$directory = $conf['directory_tmp'];
$seuil = $conf['alert_limit_level'];
$time_min = $conf['time_min_alert'];
$from_mail = $conf['from_email'];
$return_path = $conf['return_path'];
$to_mail = $conf['email'];
$go_to_summary = $conf['charts_url'];
$img_header = $conf['logo_mail'];
$graph = $conf['graph'];
$lang = $conf['language_mail'];
$root_directory = $conf['root_directory'];

define('MICROSECOND', 1000000); // 1 second in microseconds

define('SOL_IP', 0);
define('IP_TTL', 2);



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

function clean_checker($clean_number) {
	$pdo = connexionbdd();
	$sql = "DELETE FROM checker_no_alert WHERE heure <= NOW() - INTERVAL :clean MINUTE";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':clean',$clean_number);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$statement->closeCursor();
}

function clean_all_result($clean_number) {
	$pdo = connexionbdd();
	$sql = "DELETE FROM global_result WHERE timestamp <= NOW() - INTERVAL :clean MINUTE";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':clean',$clean_number);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$statement->closeCursor();
}

function clean_result($clean) {
	$pdo = connexionbdd();
	$sql = "DELETE FROM results WHERE check_timestamp <= NOW() - INTERVAL :clean MINUTE";
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':clean',$clean);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$statement->closeCursor();
}

function date_movie($log_movie, $directory,$seuil,$time_min,$from_mail,$return_path,$to_mail,$go_to_summary,$img_header,$graph,$vocables,$lang,$conf) {
	$nb_alert=0;
	$array_failed = array();
	$array_success = array();
	$array_all = array();
	$date = date('Y-m-d H:i:s',time());
	$list_alert = "";
	$list_no_alert = "";
	//Storage database
	$pdo = connexionbdd();
	
	$sql = "SELECT DISTINCT checker_id, item FROM results WHERE result!=0";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $item ){
		if($item['item'] != "") {
			$array_failed[] = $item['item'];
		}
	}
	$statement->closeCursor();
	$array_count_failed = array_count_values($array_failed);

	//Items list
	$result = array();
	$sql = "SELECT * from items";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $item ){
		$array_item[] = $item;
	}
	$statement->closeCursor();
	//Table of people who tested an item
	$result = array();
	foreach($array_item as $value) {
		$sql = "SELECT count(DISTINCT checker_id)AS total,item FROM results WHERE item=:item";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$value['item']);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		foreach( $result as $checker ){
			if($checker['item']!= NULL && $checker['item']!= "") {
				$checker['hostname'] = $value['hostname'];
				$array_all[] = $checker;
			}
		}
	}
	$statement->closeCursor();
	$result = array();
	foreach($array_all as $val) {
		if(array_key_exists($val['item'],$array_count_failed)) {
			$glob = ($array_count_failed[$val['item']]*100)/$val['total'];
			$result[] = array("md5" => md5($val['item']),"nb_f" => $array_count_failed[$val['item']],"nb_s" => $val['total']-$array_count_failed[$val['item']],"item" => $val['item'],"glob" => $glob,"hostname" => $val['hostname'] );
		} else {
			$glob = "0";
			$result[] = array("md5" => md5($val['item']),"nb_f" => "0","nb_s" => $val['total'],"item" => $val['item'],"glob" => $glob,"hostname" => $val['hostname'] );
		}
	}
	if(is_array($result)) {
		foreach ($result as $val) {
			if($val['glob'] >= $seuil) {
				$nb_alert++;
			}
		}
	}
	
	$list_element = $result;
	$result = array();
	$alert_finish = false;
	$alert_begin = false;
	$subject_begin = array();
	$subject_finish = array();
	foreach($list_element as $item) {
		$sql = "SELECT * FROM alert WHERE item=:item ORDER BY from_date DESC LIMIT 0,1";
		$statement = $pdo->prepare($sql);
		$statement->bindValue(':item',$item['item']);
		if( !$statement->execute() ){
			throw new PDOException();
		}
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);
		
		$statement->closeCursor();
		if(empty($result)) {
			//If no alert of this item is present in the database
			if($item['glob'] != "0") {
				$sql2 = "INSERT INTO alert (item,from_date) VALUES (:item,:time);";
				$statement = $pdo->prepare($sql2);
				$statement->bindValue(':item',$item['item']);
				$statement->bindValue(':time',time());
				$statement->execute();
				//Preparation of items for new email alert
				if($item['glob'] >= $seuil) {
					$item_explode = explode(":",$item['item']);
					$list_alert .= "  <TR>
										 <TD style='BORDER-TOP: #dbdbdb 1px solid; BORDER-RIGHT: #dbdbdb 1px solid; BORDER-BOTTOM: #dbdbdb 1px solid; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 1px solid; PADDING-RIGHT: 0px; BACKGROUND-COLOR: #feffff'>
											<TABLE style='WIDTH: 100%' cellSpacing=0 cellPadding=0>
											   <TR style='HEIGHT: 20px'>
												  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: middle; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
													 <IMG style='BORDER-TOP: #dbdbdb 0px solid; BORDER-RIGHT: #dbdbdb 0px solid; BORDER-BOTTOM: #dbdbdb 0px solid; MARGIN: 2px; BORDER-LEFT: #dbdbdb 0px solid; BACKGROUND-COLOR: transparent' src='".$conf['logo_mail_warning']."' height=60>
												  </TD>
												  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
													 <P style='FONT-SIZE: 18px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a8a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
														<STRONG>
														  ".$item['hostname']."[".$item_explode[0]." (".$item_explode[2]."/".$item_explode[1].")]
														</STRONG>
													 </P>
													 <P style='FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a7a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
														".$item['glob'].$vocables[$lang]['clean_result_1']."<br/>
														<a href=".$go_to_summary."?md5=".md5($item['item'])."&i=".$item_explode[0]."&p=".$item_explode[1]."&po=".$item_explode[2]."><img src='".$graph."'/></a>
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
									  ";
					$subject_begin[] = $item_explode[0];
				}
				$alert_begin = true;
			}
		} else {
			//If an alert of this item and started to come to an end
			if($item['glob'] == "0" && $result[0]['from_date'] != "" && $result[0]['to_date'] == ""){
				$sql2 = "UPDATE alert set to_date = ".time()." WHERE item = :item AND from_date=:time";
				$statement = $pdo->prepare($sql2);
				$statement->bindValue(':item',$item['item']);
				$statement->bindValue(':time',$result[0]['from_date']);
				if( !$statement->execute() ){
					throw new PDOException();
				}
				$item_explode = explode(":",$item['item']);
				$list_no_alert .= "  <TR>
										 <TD style='BORDER-TOP: #dbdbdb 1px solid; BORDER-RIGHT: #dbdbdb 1px solid; BORDER-BOTTOM: #dbdbdb 1px solid; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 1px solid; PADDING-RIGHT: 0px; BACKGROUND-COLOR: #feffff'>
											<TABLE style='WIDTH: 100%' cellSpacing=0 cellPadding=0>
											   <TR style='HEIGHT: 20px'>
												  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: middle; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
													 <IMG style='BORDER-TOP: #dbdbdb 0px solid; BORDER-RIGHT: #dbdbdb 0px solid; BORDER-BOTTOM: #dbdbdb 0px solid; MARGIN: 2px; BORDER-LEFT: #dbdbdb 0px solid; BACKGROUND-COLOR: transparent' src='".$conf['logo_mail_check']."' height=60>
												  </TD>
												  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
													 <P style='FONT-SIZE: 18px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a8a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
														<STRONG>
															".$item['hostname']."[".$item_explode[0]." (".$item_explode[2]."/".$item_explode[1].")]
														</STRONG>
													 </P>
													 <P style='FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a7a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
														<center>
															<a href=".$go_to_summary."?md5=".md5($item['item'])."&from=".$result[0]['from_date']."&to=".time()."&i=".$item_explode[0]."&p=".$item_explode[1]."&po=".$item_explode[2]."><img src='".$graph."'/></a>
														</center>
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
									  ";
				$subject_finish[] = $item_explode[0];
				$alert_finish = true;
			//If an alert of this item is already present in the database and that the new alert is not
			} else if($item['glob'] != "0" && $result[0]['to_date'] != "") {
				$sql2 = "INSERT INTO alert (item,from_date) VALUES (:item,:time);";
				$statement = $pdo->prepare($sql2);
				$statement->bindValue(':item',$item['item']);
				$statement->bindValue(':time',time());
				if( !$statement->execute() ){
					throw new PDOException();
				}
				//Preparation of mail items to new alert
				if($item['glob'] >= $seuil) {
					$item_explode = explode(":",$item['item']);
					$list_alert .= "  <TR>
										 <TD style='BORDER-TOP: #dbdbdb 1px solid; BORDER-RIGHT: #dbdbdb 1px solid; BORDER-BOTTOM: #dbdbdb 1px solid; PADDING-BOTTOM: 0px; PADDING-TOP: 0px; PADDING-LEFT: 0px; BORDER-LEFT: #dbdbdb 1px solid; PADDING-RIGHT: 0px; BACKGROUND-COLOR: #feffff'>
											<TABLE style='WIDTH: 100%' cellSpacing=0 cellPadding=0>
											   <TR style='HEIGHT: 20px'>
												  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: middle; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
													 <IMG style='BORDER-TOP: #dbdbdb 0px solid; BORDER-RIGHT: #dbdbdb 0px solid; BORDER-BOTTOM: #dbdbdb 0px solid; MARGIN: 2px; BORDER-LEFT: #dbdbdb 0px solid; BACKGROUND-COLOR: transparent' src='".$conf['logo_mail_warning']."' height=60>
												  </TD>
												  <TD style='BORDER-TOP: #dbdbdb 1px hidden; BORDER-RIGHT: #dbdbdb 1px hidden; WIDTH: 49%; VERTICAL-ALIGN: top; BORDER-BOTTOM: #dbdbdb 1px hidden; PADDING-BOTTOM: 15px; TEXT-ALIGN: center; PADDING-TOP: 15px; PADDING-LEFT: 15px; BORDER-LEFT: #dbdbdb 1px hidden; PADDING-RIGHT: 15px; BACKGROUND-COLOR: #feffff'>
													 <P style='FONT-SIZE: 18px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a8a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
														<STRONG>
																	".$item['hostname']."[".$item_explode[0]." (".$item_explode[2]."/".$item_explode[1].")]
														</STRONG>
													 </P>
													 <P style='FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a7a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
														".$item['glob'].$vocables[$lang]['clean_result_1']."<br/>
														<center>
															<a href=".$go_to_summary."?md5=".md5($item['item'])."&i=".$item_explode[0]."&p=".$item_explode[1]."&po=".$item_explode[2]."><img src='".$graph."'/></a>
														</center>
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
									  ";
					$subject_begin[] = $item_explode[0];
				}
				$alert_begin = true;
			}
		}
	}
	//Send mail begin if necessary
	if($time_min > 0 && $alert_begin == true && $nb_alert > 0 ) {
		$subject_begin = $conf['email_begin_subject']."[Problem] ".implode(", ",$subject_begin);		
		$subject_begin = raccourcirstring($subject_begin, 50);
		$body = "<html>
			   <head>
				  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
				  <title".$vocables[$lang]["title"]."</title>
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
						   		<tr>
									<td>
										<P style='FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a7a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
											<center>".$vocables[$lang]['clean_result_2'].$nb_alert.$vocables[$lang]['clean_result_3']."</center>
										</p>
									</td>
								</tr>
								".$list_alert."
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
		$from_email  = $from_mail;
		$entetedate  = "Date: ".date("l j F Y, G:i +0200")."\n";
		$entetemail  = "From: $from_email \nReturn-path:".$return_path;
		$entetemail .= "Cc: \n";
		$entetemail .= "Bcc: \n";
		$entetemail .= "Reply-To: ".$from_mail."\n";
		$entetemail .= "X-Mailer: PHP \n";
		$entetemail .= "Content-Type: text/html; charset=\"ISO-8859-1\ \n";
		$entetemail .= "Date: $entetedate";
		mail($to_mail, utf8_decode($subject_begin), utf8_decode($body), $entetemail);
	}
	//Send mail ending if necessary
	if($time_min > 0 && $alert_finish == true) {
		$subject_finish = $conf['email_begin_subject']."[OK] ".implode(", ",$subject_finish);
		$subject_finish = raccourcirstring($subject_finish, 50);
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
								<tr>
									<td>
										<P style='FONT-SIZE: 12px; FONT-FAMILY: Arial, Helvetica, sans-serif; COLOR: #a7a7a7; MARGIN-TOP: 0px; LINE-HEIGHT: 1.3; BACKGROUND-COLOR: transparent' align=left>
											<center>".$vocables[$lang]['clean_result_2'].$nb_alert.$vocables[$lang]['clean_result_3']."</center>
										</p>
									</td>
								</tr>
								".$list_no_alert."
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
		$from_email  = $from_mail;
		$entetedate  = "Date: ".date("l j F Y, G:i +0200")."\n";
		$entetemail  = "From: $from_email \nReturn-path:".$return_path;
		$entetemail .= "Cc: \n";
		$entetemail .= "Bcc: \n";
		$entetemail .= "Reply-To: ".$from_mail."\n";
		$entetemail .= "X-Mailer: PHP \n" ;
		$entetemail .= "Content-Type: text/html; charset=\"ISO-8859-1\ \n";
		$entetemail .= "Date: $entetedate";
		mail($to_mail, utf8_decode($subject_finish), utf8_decode($body), $entetemail);
	}
}

function netmask($ip, $cidr) {
    $bitmask = $cidr == 0 ? 0 : 0xffffffff << (32 - $cidr);
    return long2ip(ip2long($ip) & $bitmask);
}

function raccourcirstring($string, $tailleMax) {
	$positionDernierEspace = 0;
	if( strlen($string) >= $tailleMax ) {
		$string = substr($string,0,$tailleMax);
		$string .= '...';
	}
	return $string;
}


function check_routes(){
	global $conf;
	$pdo = connexionbdd();
	//outskirts of recovering all the testers
	$checkers = array();
	$sql = "SELECT DISTINCT checker_id, checker_ip FROM results";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach($result as $checker ){
			$checkers[] = $checker;
	}
	$statement->closeCursor();
	//var_dump($checkers);

	//then retrieves all roads
	$networks = array();
	$sql = "SELECT check_net,timestamp FROM traceroute";
	$statement = $pdo->prepare($sql);
	if( !$statement->execute() ){
		throw new PDOException();
	}
	$result = $statement->fetchAll(PDO::FETCH_ASSOC);
	foreach( $result as $network ){
		$networks[] = $network;
	}
	//var_dump($networks);
	foreach( $checkers as $checker ){
		//echo $checker['checker_ip']."\n";
		$checker_network = netmask($checker['checker_ip'],$conf['minimum_netmask_for_groups']);
		//echo $checker_network."\n";
		$network_find = false;
		$i = 0;
		while( $i<count($networks) and !$network_find ){
			if( $checker_network."/".$conf['minimum_netmask_for_groups'] == $networks[$i]['check_net'] ){
				$network_find = true;
			}
			else{
				$i++;
			}
		}
		if( !$network_find ){
			$routes = shell_exec("traceroute -nI ".$checker['checker_ip']);
			$routes = explode("\n",$routes);
			array_splice($routes,0,1);
			$final_route = array();
			foreach( $routes as $route ){
				$route_explode = explode(' ', $route);
				if( count($route_explode) > 1 ){
					$final_route[] = $route_explode[3].':'.$route_explode[5];
				}
			}
			$net_with_mask = $checker_network."/".$conf['minimum_netmask_for_groups'];
			//echo $net_with_mask;
			$sql = "INSERT INTO traceroute (check_net,route,timestamp) VALUES (:check,:route,:time)";
			$statement = $pdo->prepare($sql);
			$statement->bindValue(':check',$net_with_mask);
			$statement->bindValue(':route',implode(';',$final_route));
			$statement->bindValue(':time',date('Y-m-d H:i:s',time()));
			if( !$statement->execute() ){
				throw new PDOException();
			}
			$networks[] = array( 'check_net' => $net_with_mask, 'route' => implode(';',$final_route), 'timestamp' => date('Y-m-d H:i:s',time()) );
		}
		else{
			if( $networks[$i]['timestamp'] < time() - ($conf['maximum_route_time_validaty'] * 3600000 ) ){
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
				$sql = "update traceroute set route = :route, timestamp = :time where check_net = :check";
				$statement = $pdo->prepare($sql);
				$statement->bindValue(':check',$checker_network."/".$conf['minimum_netmask_for_groups']);
				$statement->bindValue(':route',implode(';',$final_route));
				$statement->bindValue(':time',date('Y-m-d H:i:s',time()));
				if( !$statement->execute() ){
					throw new PDOException();
				}
			}
		}
	}
}

function clean_logstalgia($clean_number, $directory, $root_directory) {
	global $conf;	
	$filename = $directory."logstalgia.txt";
    $file = new SplFileObject($filename);
    $line_0 = $file->current();
	$line_last = "";
	
    $i=0;
	while ( $file->valid()  && $i < $conf['limit_logstalgia']){ 
        $i++;
		if( $file->current() != "" ){
			$line_last = $file->current();
		}
        $file->next();
    }

	if( $i = $conf['limit_logstalgia'] ){
        $line_0_explode = explode('|',$line_0);
        $line_last_explode = explode('|',$line_last);
		exec("cp $filename $root_directory/backup/logstalgia-".$line_0_explode[0]."-".$line_last_explode[0].".txt");
        exec("chown ".$conf['user_logstalgia']." $root_directory/backup/logstalgia-".$line_0_explode[0]."-".$line_last_explode[0].".txt");
        $handle = fopen($filename, "w") or die("Can't create file");
       	fclose($handle);
    }
}

clean_checker($clean_number);
clean_result($clean);
clean_all_result($clean_number);
if($conf['active_logstalgia'] == 1) {
	date_movie($log_movie, $directory, $seuil,$time_min,$from_mail,$return_path,$to_mail,$go_to_summary,$img_header,$graph,$vocables,$lang,$conf);
	clean_logstalgia($clean_number, $directory, $root_directory);
}
?>
