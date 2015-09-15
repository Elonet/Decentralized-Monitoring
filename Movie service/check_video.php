<?php

header("Access-Control-Allow-Origin:*");

$debut = trim(htmlspecialchars($_GET['deb']));

$server_root = "/var/www/";

#si le fichier video requis n'existe pas encore, on le signifie au serveur
if( !file_exists($server_root.'LogVideoServer/videos/logstalgia'.$debut.'.mp4') ){
	echo 0;
}
else{
	//sinon on envoi le lien de téléchargement
	echo 'http://'.$_SERVER['HTTP_HOST'].'/LogVideoServer/videos/logstalgia'.$debut.'.mp4';
}
?>
