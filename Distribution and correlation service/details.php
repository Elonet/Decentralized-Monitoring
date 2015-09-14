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
 
<!DOCTYPE html>
<!--[if IE 8]>			<html class="ie ie8"> <![endif]-->
<!--[if IE 9]>			<html class="ie ie9"> <![endif]-->
<?php
	$i = trim(htmlspecialchars($_GET['i']));
	$p = trim(htmlspecialchars($_GET['p']));
	$po = trim(htmlspecialchars($_GET['po']));
	$md5 = trim(htmlspecialchars($_GET['md5']));
	require("/etc/decentralized_monitoring/config.conf");
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
?>
<html>
	<head>
		<!-- Basic -->
		<meta charset="utf-8">
		<title><?php echo $vocables[$lang]["title"]; ?></title>
		<meta name="keywords" content="Decentralized Monitoring" />
		<meta name="description" content="A solution to extend monitoring capabilities to endpoint users">
		<meta name="author" content="Elonet.fr">
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
		<link rel="icon" href="favicon.ico" type="image/x-icon"/>
		
		<!-- Libs CSS -->
		<link rel="stylesheet" href="bootstrap/css/bootstrap.css">
		<style>
			.container {margin-top:20px;}
			.row {width:80%;margin:0px auto;}
			#body{padding:10px;}
			tr {text-align:center;}
			td {font-size:13px;}
		</style>
	</head>
	<body>
		<div class="container" style="width:100%;display:none;">
			<center>
				<img src="img/Decmon-128.png" height="100">
				<h3 style="margin-top:0px;"><?php echo $vocables[$lang]['title']; ?></h3>
				<h5 class="all_success" style="margin-bottom:30px;">
			</center>
			<div class="row">
				<div class='panel panel-danger' style="text-align:center;">
					<div class='panel-heading' style="border-bottom-left-radius:0px;font-weight:bold;font-size:18px;">
						<center><?php echo $vocables[$lang]['details'].$i." (".$p."/".$po.")";?></center>
					</div>
					<div class='panel-body'>
						<table id="list_fail" class="table table-striped" style="word-wrap: break-word;max-width:100%;max-height:20%;margin:5px auto;background:rgba(255,255,255,1);"></table>
					</div>
				</div>
			</div>
		</div>
		<!-- Libs -->
		<script src="js/jquery-1.9.1.min.js"></script>
		<script src="js/highcharts.js"></script>
		<script src="js/exporting.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="js/no-data-to-display.js"></script>
		<!-- Isotope -->
		<script type="text/javascript" src="js/isotope/jquery.isotope.js"></script>
		<!-- Gallery -->
		<script type="text/javascript">
			$(window).load(function() {
				$(".container").fadeIn();
			});
			$(document).ready(function () {
				$.ajax({
					type: "POST",
					url: "./php/list_fail.php",
					data: "md5=<?php echo $md5;?>",
					success: function (html) {
						$("#list_fail").html(html);
					}
				});
				setInterval(function () {
					$.ajax({
						type: "POST",
						url: "./php/list_fail.php",
						data: "md5=<?php echo $md5;?>",
						success: function (html) {
							$("#list_fail").html(html);
						}
					});
				}, 30000);
			});
		</script>
	</body>
</html>
