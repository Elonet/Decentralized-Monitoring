<?php
date_default_timezone_set('Europe/Berlin');
header("Access-Control-Allow-Origin:*");

$debut = trim(htmlspecialchars($_POST['deb']));
$fin = trim(htmlspecialchars($_POST['fin']));
$quality = trim(htmlspecialchars($_POST['quality']));
$length = trim(htmlspecialchars($_POST['length']));
$server_log_lostagia = trim(htmlspecialchars($_POST['loglog']));


$server_root = "/var/www/";


//On commence par calculé la taille de l'image que l'utilisateur souhaite
if( $quality == 'ld'){
	$width = 800;
	$height = 480;
}
else if( $quality == 'hd' ){
	$width = 1280;
	$height = 720;
}

//Puis on calcul la vitesse que doit avoir la video
$tps_video = $fin - $debut;

if( $length == 'full' ){
	$speed = $tps_video/$tps_video;
}
else{
	$length = intval($length);
	$speed = $tps_video/($length*60000*2.58571);
	if( $speed < 0.1 ){
		$speed = 0.1;
	}
	else if( $speed > 30){
		$speed = 30;
	}
	$speed = round($speed,2);
}
error_log($tps_video."=".date("i:s",$tps_video)." ".$speed,0);
$debut_date = date('Y-m-d H:i:s',intval($debut));
$fin_date = date('Y-m-d H:i:s',intval($fin));

//On échappe les caractères non souhaitable avant d'éxécuter le script bash
$debut_date = escapeshellarg($debut_date);
$fin_date = escapeshellarg($fin_date);
$height = escapeshellarg($height);
$width = escapeshellarg($width);
$speed = escapeshellarg($speed);

echo 1;
if( !@file_exists($server_root.'LogVideoServer/videos/logstalgia'.strtotime($debut_date).'.mp4') ){
	while( file_exists($server_root."LogVideoServer/request.lock") ){
		sleep(1);
	}
	file_put_contents($server_root."LogVideoServer/request.lock",$debut_date.";".$fin_date.";".$height.";".$width.";".$speed.";".$server_root.";".$server_log_lostagia);
	//shell_exec('./video.sh '.$debut_date.' '.$fin_date.' '.$width.' '.$height.' '.$speed);
}


?>
