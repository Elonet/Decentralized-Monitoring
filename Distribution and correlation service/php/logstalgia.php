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
 
include('/etc/decentralized_monitoring/config.conf');
date_default_timezone_set('Europe/Berlin');
if($conf['active_logstalgia'] == 0) {
	exit;
}

if(file_exists('../tmp/result.txt'))
{
	$json=file_get_contents('../tmp/result.txt');
	$result=json_decode($json,true);
} else {
	$result=array();
}
$checktimestamp = date('Y/m/d H:i:s',time());
$ip=trim(htmlspecialchars($_GET['ip']));
$host = trim(htmlspecialchars($_GET['host']));
$test = trim(htmlspecialchars($_GET['result']));
if($test != 0){
	$text = "Fail";
	$color = "FF0000";
//	$result_tmp = "0";
} else {
	$text = "Ok";
	$color = "00FF00";
//	$result_tmp = "1";
}
$test=1;
$string = time($checktimestamp)."|".$ip."|".$host."|".$text."|"."1024"."|".$test."|".$color."\n";
$item = explode("|",$string);
if( !isset($result[$item[2]]))
{
	$result[$item[2]]=array();
	$result[$item[2]]["Danger"]["Status"]=array();
	$result[$item[2]]["Danger"]["TimeStamp"]=array();
}
if($item[5]=="1")
{
	$result[$item[2]]["Danger"]["Status"]="Fail";
$result[$item[2]]["Danger"]["TimeStamp"]=$item[0];
}
else
{
	if((int)$item[0] <= (int)$result[$item[2]]["Danger"]["TimeStamp"]+240)
	{
	//	$result[$item[2]]["Danger"]["TimeStamp"]=$item[0];
		$item[5]="2";
		$item[3]="Danger";
		$item[6]="FFA500"."\n";
		$result[$item[2]]["Danger"]["Status"]="Danger";
	}
}
$output = implode("|",$item);
if(rand(5,15) == 10){
$output .= time($checktimestamp + 10)."|Network 10.0.0.0/24|server6.paris.dns|Fail|65536|".$test."|FF0000\n";
}
file_put_contents("../tmp/logstalgia.txt",$output,FILE_APPEND);
file_put_contents("../tmp/result.txt",json_encode($result));

?>
