<!--
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
 -->

<?php

header("Access-Control-Allow-Origin:*");

$debut = trim(htmlspecialchars($_GET['deb']));

$server_root = "/var/www/";

#if the required video file does not exist, it means the server
if( !file_exists($server_root.'LogVideoServer/videos/logstalgia'.$debut.'.mp4') ){
	echo 0;
}
else{
	//or we send the download link
	echo 'http://'.$_SERVER['HTTP_HOST'].'/LogVideoServer/videos/logstalgia'.$debut.'.mp4';
}
?>
