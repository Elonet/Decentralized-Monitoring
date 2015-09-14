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
$from = "";
$to = "";
@$from = trim(htmlspecialchars($_GET['from']));
@$to = trim(htmlspecialchars($_GET['to']));
$i = trim(htmlspecialchars($_GET['i']));
$p = trim(htmlspecialchars($_GET['p']));
$po = trim(htmlspecialchars($_GET['po']));
$md5 = trim(htmlspecialchars($_GET['md5']));
date_default_timezone_set('Europe/Berlin');
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
$red = $conf['color_limit_level']/100;
$orange = $red/2;
$yellow = $orange/2;
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
			.info {width:100%;padding-left:10px;padding-right:10px;text-align:center;}
			.number {font-weight:bold;font-size: 24px;margin-bottom:4px;}
			.two {width:60%;height:40px;text-align:center;font-weight:bold;font-size: 24px;}
			.container {margin-top:20px;}
			.global{display:inline-block;float:left;width:60%;height:400px;}
			.float-top{display:inline-block;float:right;width:35%;}
			.float-bottom{display:inline-block;float:right;width:35%;}
			span.glyphicon {font-size: 36px;line-height: 1.0;top:8px;margin-right:4px;}
			.table{width:60%;margin-top:30px;}
			th{text-align:center;}
			.float-top .panel-heading{font-weight:bold;font-size: 24px;margin-bottom:4px;}
			.float-bottom .panel-heading{font-weight:bold;font-size: 24px;margin-bottom:4px;}
			.row {width:80%;margin:0px auto;}
			#body{padding:10px;}
			#picto span {margin:0 10px 0 10px;-moz-border-radius:5px;-webkit-border-radius:5px;border:1px solid #999;border-radius:5px;display:inline-block;}
		</style>
	</head>
	<body>
		<div class="container" style="display:none;">
			<center>
				<img src="img/Decmon-128.png" height="100">
				<h3 style="margin-top:0px;"><?php echo $vocables[$lang]['title']; ?></h3>
				<h5 class="all_success" style="margin-bottom:30px;">
			</center>
		</div>
		<div class="row">
			<div class='global panel panel-danger' style="text-align:center;">
				<div class='panel-heading' style="border-bottom-left-radius:0px;font-weight:bold;font-size:18px;">
					<center><?php echo $vocables[$lang]['monitoring_data_1'].$i." (".$p."/".$po.")";?></center>
				</div>
				<div id="container" style="width:90%; height: 280px; margin: 10px auto">
				</div>
				<div id="picto">
				</div>
			</div>
			
			<div class='float-top panel panel-danger'>
				<div class='panel-heading' style="border-bottom-left-radius:0px;">
				</div>
				<div id="body"></div>
			</div>
			
			<div class='float-bottom panel panel-danger' id="video_body" style='display:none'>
				<div class='panel-heading' style="border-bottom-left-radius:0px;">
					<?php echo $vocables[$lang]['monitoring_data_2']; ?>
				</div>
				<div style='padding:10px;'>
					<p style="text-align:justify"><?php echo $vocables[$lang]['monitoring_data_3'];?> <span id='start_video'><?php if(isset($from)){echo date('d/m/y H:i:s',intval($from));}?></span><?php echo $vocables[$lang]['monitoring_data_4'];?> <span id='stop_video'><?php if(isset($from)){echo date('d/m/y H:i:s',intval($to));}?></span></p>
					<p style="text-align:justify"><?php echo $vocables[$lang]['monitoring_data_5']; ?></p>
					<center><input id='video_title' type='button' class='btn btn-info' value='Obtain video from this alert'/></center>
					<div id='video_quality' style='display:none'>
						<h4><?php echo $vocables[$lang]['monitoring_data_6']; ?></h4>
						<p><?php echo $vocables[$lang]['monitoring_data_7']; ?></p>
						<p><?php echo $vocables[$lang]['monitoring_data_8']; ?></p>
						<p><?php echo $vocables[$lang]['monitoring_data_9']; ?></p>
						<input id='ld' type='radio' name='quality' value='ld' style='margin:10px;' checked><?php echo $vocables[$lang]['monitoring_data_10']; ?></input>
						<input id='hd' type='radio' name='quality' value='hd' style='margin:10px;' ><?php echo $vocables[$lang]['monitoring_data_11']; ?></input>
						<input id='next' type='button' class='btn btn-info' value='Next' style='margin:10px;'/>
					</div>
					<div id='video_length' style='display:none'>
						<h4><?php echo $vocables[$lang]['monitoring_data_12']; ?></h4>
						<p><?php echo $vocables[$lang]['monitoring_data_13']; ?></p>
						<input id='1' type='radio' name='length' value='1' style='margin:10px;' checked>1 mn</input>
						<input id='3' type='radio' name='length' value='3' style='margin:10px;'>3 mn</input>
						<input id='5' type='radio' name='length' value='5' style='margin:10px;'>5 mn</input>
						<input id='full' type='radio' name='length' value='full' style='margin:10px;' ><?php echo $vocables[$lang]['monitoring_data_14']; ?></input>
						<input id='submit' type='button' class='btn btn-info' value='Request video' style='margin:10px;'/>
					</div>
					<div id ='video_wait' style='display:none'>
						<h4><?php echo $vocables[$lang]['monitoring_data_15']; ?></h4>
						<p id='wait'><?php echo $vocables[$lang]['monitoring_data_16']; ?></p>
					</div>
					<div id='video_dl' style='display:none'>
						<h4><?php echo $vocables[$lang]['monitoring_data_17']; ?></h4>
						<input id='dl' type='button' class='btn btn-info' value='Download video'/>
					</div>
				</div>
			</div>
		</div>
		<center>
			<table class="table table-bordered"></table>
		</center>
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
			$(function () {
				$(document).ready(function () {
					//Resize du graph
					$(window).resize(function() {
						var chart = $('#container').highcharts();
						var height = chart.height;
						var width = $(".global").width()-60;
					});
					$.ajax({
						type: "POST",
						url: "./php/graph_item.php",
						data: "id=restore&md5=<?php echo $md5;?>",
						dataType: 'json',
						success: function (html) {
							var data = [];
							var data_timeout = [];
							var data_proxy = [];
							var data_latency = [];
							var data_match = [];
							if(html.length > 24) {
								var i = html.length-24;
							} else {
								var i = 0;
							}
							var time = (new Date()).getTime();
							for (html[i]; i < html.length; i++) {
								//Graph glob
								if(parseFloat(html[i].glob) == 0) {
									data.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob),
										marker: {enabled: false}
									});
								} else {
									data.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob),
										marker: {enabled: false}
									});
								}
								//Graph timeout
								if(parseFloat(html[i].glob_timeout) == 0) {
									data_timeout.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_timeout),
										marker: {enabled: false}
									});
								} else {
									data_timeout.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_timeout),
										marker: {enabled: false}
									});
								}
								//Graph proxy
								if(parseFloat(html[i].glob_proxy) == 0) {
									data_proxy.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_proxy),
										marker: {enabled: false}
									});
								} else {
									data_proxy.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_proxy),
										marker: {enabled: false}
									});
								}
								//Graph Latency
								if(parseFloat(html[i].glob_latency) == 0) {
									data_latency.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_latency),
										marker: {enabled: false}
									});
								} else {
									data_latency.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_latency),
										marker: {enabled: false}
									});
								}
								//Graph match
								if(parseFloat(html[i].glob_match) == 0) {
									data_match.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_match),
										marker: {enabled: false}
									});
								} else {
									data_match.push({
										x: html[i].timestamp * 1000,
										y: parseFloat(html[i].glob_match),
										marker: {enabled: false}
									});
								}
							}
							graph(data,data_timeout,data_proxy,data_latency,data_match);
						}
					});
					$.ajax({
						type: "POST",
						url: "./php/graph_item.php",
						data: "id=glob&md5=<?php echo $md5;?>",
						dataType: 'json',
						success: function (html) {
							if(html[0] != "" ){
								$('#body').parent().fadeIn();
								var all = parseInt(html[0].nb_s)+parseInt(html[0].nb_f);
								var percent = Math.round(parseInt(html[0].nb_f)*100/all);
								$(".float-top").children(".panel-heading").html("<b><center><span class='glyphicon glyphicon-remove-circle'></span>"+percent+"%</center><b>");
								$(".float-top").children("#body").html("<center>"+html[0].nb_f+"<?php echo $vocables[$lang]['monitoring_data_18']; ?>"+all+"<?php echo $vocables[$lang]['monitoring_data_19']; ?><br/><a href='details.php?md5=<?php echo $md5; ?>&i=<?php echo $i; ?>&p=<?php echo $p; ?>&po=<?php echo $po; ?>' target='_blank'><button type='button' class='btn btn-default'>List of affected users</button></a></center>");
								$('#picto').html("");
								if(html[0].glob_timeout==0) {
									$('#picto').append("<span style='padding:3px 8px;font-size:12px;'><img src='img/timeout.png' width='16px' title='Unreachability'/> "+html[0].glob_timeout+"%</span>");
								} else {
									$('#picto').append("<span style='padding:3px 8px;font-size:16px;'><img src='img/timeout.png' width='24px' title='Unreachability'/> "+html[0].glob_timeout+"%</span>");
								}
								if(html[0].glob_proxy==0) {
									$('#picto').append("<span style='padding:3px 8px;font-size:12px;'><img src='img/proxy.png' width='16px' title='Proxy Error'/> "+html[0].glob_proxy+"%</span>");
								} else {
									$('#picto').append("<span style='padding:3px 8px;font-size:16px;'><img src='img/proxy.png' width='24px' title='Proxy Error'/> "+html[0].glob_proxy+"%</span>");
								}
								if(html[0].glob_latency==0) {
									$('#picto').append("<span style='padding:3px 8px;font-size:12px;'><img src='img/latency.png' width='16px' title='Latency Issue'/> "+html[0].glob_latency+"%</span>");
								} else {
									$('#picto').append("<span style='padding:3px 8px;font-size:16px;'><img src='img/latency.png' width='24px' title='Latency Issue'/> "+html[0].glob_latency+"%</span>");
								}
								if(html[0].glob_match==0) {
									$("#picto").append("<span style='padding:3px 8px;font-size:12px;'><img src='img/match.png' width='16px' title='Unexpected Content'/> "+html[0].glob_match+"%</span>");
								} else {
									$("#picto").append("<span style='padding:3px 8px;font-size:16px;'><img src='img/match.png' width='24px' title='Unexpected Content'/> "+html[0].glob_match+"%</span>");
								}
								
							} else {
								$(".float-top").children(".panel-heading").html("<b><center><span style='font-size:18px'><?php echo $vocables[$lang]['monitoring_data_20_1']; ?></span></center><b>");
							}
						} 
					});
					$.ajax({
						type: "POST",
						url: "./php/common.php",
						data: "md5=<?php echo $md5;?>",
						dataType: 'json',
						success: function (html) {
							var table = "<thead><tr><th style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_21']; ?></th><th style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_22']; ?></th><th style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_23']; ?></th></tr></thead><tbody>";
							if(html.networks !== undefined) {
								for (i=0; i < html.networks.length; i++) {
									var percent_f_all = Math.round(parseInt(html.networks[i].nb_f_network)*100/parseInt(html.nb_all));
									table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_24']; ?>"+html.networks[i].network+"</td><td style='vertical-align: middle;text-align:center;'>"+html.networks[i].nb_f_network+"</td><td style='vertical-align: middle;text-align:center;'>"+percent_f_all+"<small>%</small></td></tr>";
								}
								table += "</tbody>";
								$('.table-bordered').html(table);
							}
							if(html.hostnames !== undefined) {
								var percent_f_all = Math.round(parseInt(html.hostnames.nb_f)*100/parseInt(html.nb_all));
								table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_25']; ?>"+html.hostnames.hostname+"</td><td style='vertical-align: middle;text-align:center;'>"+html.hostnames.nb_f+"</td><td style='vertical-align: middle;text-align:center;'>"+percent_f_all+"<small>%</small></td></tr>";
								table += "</tbody>";
								$('.table-bordered').html(table);
							}
							if(html.routeurs !== undefined) {
								for (i=0; i < html.routeurs.length; i++) {
									var percent_f_all = Math.round(parseInt(html.routeurs[i].nb_fail)*100/parseInt(html.nb_all));
									var common_factor = html.routeurs[i].routeur;
									if(common_factor == "") {
										table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_29']; ?></td><td style='vertical-align: middle;text-align:center;'></td><td style='vertical-align: middle;text-align:center;'></td></tr>";
									} else {
										table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_26']; ?>"+common_factor+"</td><td style='vertical-align: middle;text-align:center;'>"+html.routeurs[i].nb_fail+"</td><td style='vertical-align: middle;text-align:center;'>"+percent_f_all+"<small>%</small></td></tr>";
									}
								}
								table += "</tbody>";
								$('.table-bordered').html(table);
							}
						}
					});
				});
			});

			function graph(data,data_timeout,data_proxy,data_latency,data_match) {
				Highcharts.setOptions({
					global: {
						useUTC: false
					}
				});
				$('#container').highcharts({
					chart: {
						animation: Highcharts.svg, // don't animate in old IE
						marginRight: 5,
						events: {
							load: function () {
								var series = this.series[0];
								var series_timeout = this.series[1];
								var series_proxy = this.series[2];
								var series_latency = this.series[3];
								var series_match = this.series[4];
								setInterval(function () {
									$.ajax({
										type: "POST",
										url: "./php/graph_item.php",
										data: "id=add&md5=<?php	echo $md5;?>",
										dataType: 'json',
										success: function (html) {
											var exist = 0;
											for (var i = 0; i < series.points.length; i++) {
												if(series.points[i].x === html.timestamp * 1000) {
													exist += 1;
												}
											}
											var	xx = html.timestamp * 1000;
											var	yy = parseFloat(html.glob);
											var	yy_timeout = parseFloat(html.glob_timeout);
											var	yy_proxy = parseFloat(html.glob_proxy);
											var	yy_latency = parseFloat(html.glob_latency);
											var	yy_match = parseFloat(html.glob_match);
											if(exist <= 0) {
												if(yy == 0) {
													series.addPoint({color:'#62c462',marker: {enabled: false},x:xx, y:yy}, true, false);
													series_timeout.addPoint({color:'#62c462',marker: {enabled: false},x:xx, y:yy_timeout}, true, false);
													series_proxy.addPoint({color:'#62c462',marker: {enabled: false},x:xx, y:yy_proxy}, true, false);
													series_latency.addPoint({color:'#62c462',marker: {enabled: false},x:xx, y:yy_latency}, true, false);
													series_match.addPoint({color:'#62c462',marker: {enabled: false},x:xx, y:yy_match}, true, false);
												} else {
													series.addPoint({color:'#A94442',marker: {enabled: false},x:xx, y:yy}, true, false);
													series_timeout.addPoint({color:'#A94442',marker: {enabled: false},x:xx, y:yy_timeout}, true, false);
													series_proxy.addPoint({color:'#A94442',marker: {enabled: false},x:xx, y:yy_proxy}, true, false);
													series_latency.addPoint({color:'#A94442',marker: {enabled: false},x:xx, y:yy_latency}, true, false);
													series_match.addPoint({color:'#A94442',marker: {enabled: false},x:xx, y:yy_match}, true, false);
												}

											} 
										}
									});
									$.ajax({
										type: "POST",
										url: "./php/common.php",
										data: "md5=<?php echo $md5;?>",
										dataType: 'json',
										success: function (html) {
											var table = "<thead><tr><th style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_21']; ?></th><th style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_22']; ?></th><th style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_23']; ?></th></tr></thead><tbody>";
											if(html.networks !== undefined) {
												for (i=0; i < html.networks.length; i++) {
													var percent_f_all = Math.round(parseInt(html.networks[i].nb_f_network)*100/parseInt(html.nb_all));
													table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_24']; ?>"+html.networks[i].network+"</td><td style='vertical-align: middle;text-align:center;'>"+html.networks[i].nb_f_network+"</td><td style='vertical-align: middle;text-align:center;'>"+percent_f_all+"<small>%</small></td></tr>";
												}
												table += "</tbody>";
												$('.table-bordered').html(table);
											}
											if(html.hostnames !== undefined) {
												var percent_f_all = Math.round(parseInt(html.hostnames.nb_f)*100/parseInt(html.nb_all));
												table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_25']; ?>"+html.hostnames.hostname+"</td><td style='vertical-align: middle;text-align:center;'>"+html.hostnames.nb_f+"</td><td style='vertical-align: middle;text-align:center;'>"+percent_f_all+"<small>%</small></td></tr>";
												table += "</tbody>";
												$('.table-bordered').html(table);
											}
											if(html.routeurs !== undefined) {
												for (i=0; i < html.routeurs.length; i++) {
													var percent_f_all = Math.round(parseInt(html.routeurs[i].nb_fail)*100/parseInt(html.nb_all));
													var common_factor = html.routeurs[i].routeur;
													if(common_factor == "") {
														table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_29']; ?></td><td style='vertical-align: middle;text-align:center;'></td><td style='vertical-align: middle;text-align:center;'></td></tr>";
													} else {
														table += "<tr><td style='vertical-align: middle;'><?php echo $vocables[$lang]['monitoring_data_26']; ?>"+common_factor+"</td><td style='vertical-align: middle;text-align:center;'>"+html.routeurs[i].nb_fail+"</td><td style='vertical-align: middle;text-align:center;'>"+percent_f_all+"<small>%</small></td></tr>";
													}				
												}
												table += "</tbody>";
												$('.table-bordered').html(table);
											}
										}
									});
									$.ajax({
										type: "POST",
										url: "./php/graph_item.php",
										data: "id=glob&md5=<?php echo $md5;?>",
										dataType: 'json',
										success: function (html) {
											if(html[0] != ""){
												var all = parseInt(html[0].nb_s)+parseInt(html[0].nb_f);
												var percent = Math.round(parseInt(html[0].nb_f)*100/all);
												$(".float-top").children(".panel-heading").html("<b><center><span class='glyphicon glyphicon-remove-circle'></span>"+percent+"%</center><b>");
												$(".float-top").children("#body").html("<center>"+html[0].nb_f+"<?php echo $vocables[$lang]['monitoring_data_18']; ?>"+all+"<?php echo $vocables[$lang]['monitoring_data_19']; ?><br/><a href='details.php?md5=<?php echo $md5; ?>&i=<?php echo $i; ?>&p=<?php echo $p; ?>&po=<?php echo $po; ?>' target='_blank'><button type='button' class='btn btn-default'>List of affected users</button></a></center>");
												$('#picto').html("");
												if(html[0].glob_timeout==0) {
													$('#picto').append("<span style='padding:3px 8px;font-size:12px;'><img src='img/timeout.png' width='16px' title='Unreachability'/> "+html[0].glob_timeout+"%</span>");
												} else {
													$('#picto').append("<span style='padding:3px 8px;font-size:16px;'><img src='img/timeout.png' width='24px' title='Unreachability'/> "+html[0].glob_timeout+"%</span>");
												}
												if(html[0].glob_proxy==0) {
													$('#picto').append("<span style='padding:3px 8px;font-size:12px;'><img src='img/proxy.png' width='16px' title='Proxy Error'/> "+html[0].glob_proxy+"%</span>");
												} else {
													$('#picto').append("<span style='padding:3px 8px;font-size:16px;'><img src='img/proxy.png' width='24px' title='Proxy Error'/> "+html[0].glob_proxy+"%</span>");
												}
												if(html[0].glob_latency==0) {
													$('#picto').append("<span style='padding:3px 8px;font-size:12px;'><img src='img/latency.png' width='16px' title='Latency Issue'/> "+html[0].glob_latency+"%</span>");
												} else {
													$('#picto').append("<span style='padding:3px 8px;font-size:16px;'><img src='img/latency.png' width='24px' title='Latency Issue'/> "+html[0].glob_latency+"%</span>");
												}
												if(html[0].glob_match==0) {
													$("#picto").append("<span style='padding:3px 8px;font-size:12px;'><img src='img/match.png' width='16px' title='Unexpected Content'/> "+html[0].glob_match+"%</span>");
												} else {
													$("#picto").append("<span style='padding:3px 8px;font-size:16px;'><img src='img/match.png' width='24px' title='Unexpected Content'/> "+html[0].glob_match+"%</span>");
												}
											} else {
												$(".float-top").children(".panel-heading").html("<b><center><span style='font-size:18px'><?php echo $vocables[$lang]['monitoring_data_20_1']; ?></span></center><b>");
											}
										} 
									});
									if(series.points.length && series.points.length > 24) {
										series.data[0].remove();
										series_timeout.data[0].remove();
										series_proxy.data[0].remove();
										series_latency.data[0].remove();
										series_match.data[0].remove();
									}
								}, 30000);
							}
						}
					},
					title: {
						text: "<?php echo $vocables[$lang]['monitoring_data_27']; ?>",
					},
					xAxis: {
						type: 'datetime',
						tickPixelInterval: 150,
						labels: {
							style: {
								fontWeight: 'bold'
							}
						}
					},
					yAxis: {
						title: {
							text: ''
						},
						tickPositions: [0, 25, 50, 75, 100],
						labels: {
							style: {
								fontWeight: 'bold'
							}
						}
					},
					redits: {
						enabled: false
					},
					legend: {
						enabled: false
					},
					exporting: {
						enabled: true
					},
					credits: {
						enabled: false
					},
					lang: {
						noData: "<?php echo $vocables[$lang]['monitoring_data_20']; ?>",
					},
					noData: {
						style: {
							fontWeight: 'bold',
							fontSize: '15px',
							color: '#303030'
						}
					},
					series: [
						{
							name: "<?php echo $vocables[$lang]['monitoring_data_27']; ?>",
							color: {
								linearGradient: { x1: 0, x2: 0, y1: 100, y2: 100 },
								stops: [
									[0, 'green'],
									[<?php echo $yellow;?>, 'yellow'],
									[<?php echo $orange;?>, 'orange'],
									[<?php echo $red;?>, 'red']
								]
							},
							data: data,
							lineWidth:8
						},
						{
							name: "Timeout",
							data: data_timeout,
							color: 'purple',
							lineWidth:2
						},
						{
							name: "Proxy",
							data: data_proxy,
							color: 'blue',
							lineWidth:2
						},
						{
							name: "Latency",
							data: data_latency,
							color: 'green',
							lineWidth:2
						},
						{
							name: "Match",
							data: data_match,
							color: 'orange',
							lineWidth:2
						},
					]
				});
			}
		</script>
		<!-- Script management for video -->
		 <script>
			$(function(){
					$(document).ready(function(){
						<?php if(isset($from) AND isset($to) AND $from != "" AND $to != ""){ ?>
							$('#video_body').fadeIn();
							var from ="<?php echo $from;?>";
							var to ="<?php echo $to;?>";
						<?php }else{ ?>
							var from ="";
							var to ="";
							var finished_alert = setInterval(function(){
								$.ajax({
									type: "GET",
									url: "<?php echo $conf['check_end_alert'];?>",
									data: "md=<?php echo $md5;?>",
									dataType: 'json',
									success : function(json){
										from = json.start;
										to = json.stop;
										if ( to != null ) {
											
											
											if ( $('#start_video').text() == "" ) {
												to_compare = "0/0/0 0:0:0"
											}
											else{
												to_compare = $('#start_video').text();
											}
											to_compare = to_compare.split(" ");
											date = to_compare[0].split("/");
											hours = to_compare[1].split(":");
											if ( date[3] == 0 ) {
												date[3] = 1;
											}
											old_start = new Date(date[2],date[1]-1,date[0],hours[0],hours[1],hours[2]);
											
											new_start = new Date(parseInt(from)*1000);
											if ( old_start.getTime() != new_start.getTime() ) {
												$('#video_body').fadeOut();
												from_date = new_start;
												$('#start_video').text(from_date.toLocaleString());
											}
											else{
												
												from_date = new Date(new_start);
												to_date = new Date(parseInt(to)*1000);
												$('#start_video').text(from_date.toLocaleString());
												$('#stop_video').text(to_date.toLocaleString());
												$('#video_body').fadeIn();
											}
										}
									}
								});
							},60000);
							
						<?php } ?>	
						$('#video_title').click(function(){
							$('#video_title').fadeOut(function(){
								$('#video_quality').fadeIn();
							});
						});
						
						/*
						 * a décommenter lorsque le calcul de longueur
						 * vidéo logstalgia sera opérationel
						 */
						/*
						$('#next').click(function(){
							$('#video_quality').fadeOut(function(){
									$('#video_length').fadeIn();
							});
						});
						*/
						/*
						 * a décommenter lorsque le calcul de longueur
						 * vidéo logstalgia sera opérationel
						 */
						//$('#submit').click(function(){
						$('#next').click(function(){
							var quality = "";
							if( $('#ld:checked').val() == 'ld' ){
								quality = 'ld';
							}
							else if( $('#hd:checked').val() == 'hd' ){
								quality = 'hd';
							}
							
							/*
							* a décommenter lorsque le calcul de longueur
							* vidéo logstalgia sera opérationel
							*/
							/*
							var video_length = "";
							
							if( $('#1:checked').val() == '1' ){
								video_length = '1';
							}
							else if( $('#2:checked').val() == '2' ){
								video_length = '3';
							}
							else if( $('#5:checked').val() == '5' ){
								video_length = '5';
							}
							else if( $('#full:checked').val() == 'full' ){
								video_length = 'full';
							}
							*/
							video_length = 'full';
							
							/*
							* a décommenter lorsque le calcul de longueur
							* vidéo logstalgia sera opérationel
							*/
							//$('#video_length').fadeOut(function(){
							$('#video_quality').fadeOut(function(){
								$('#video_wait').fadeIn(function(){
									$.ajax({
										type: "GET", 
										url: "<?php echo $conf['logname_url'];?>",
										data: "start="+from+"&stop="+to,
										dataType: 'text',
										success : function(text){
											text = text.replace(/^\s+/g,'').replace(/\s+$/g,'');
											$.ajax({
												type: "POST", 
												url: "<?php echo $conf['get_video_url'];?>",
												data: "deb="+from+"&fin="+to+"&quality="+quality+"&length="+video_length+"&log=<?php echo $conf['log_url']; ?>"+text,
												dataType: 'text',
												success : function(text){
									
												}
											});
										}
									});
								});
							});
							var finished = setInterval(function(){
								$.ajax({
									type: "GET",
									url: "<?php echo $conf['check_video_url'];?>",
									data: "deb="+from,
									dataType: 'text',
									success : function(text){
										if( text != '0' ){
											$('#dl').attr('onclick',"self.location.href='"+text+"'");
											$('#video_wait').fadeOut(function(){
												$('#video_dl').fadeIn();
												$.ajax({
													type: "GET",
													url: "<?php echo $conf['logname_url'];?>",
													data: "deb="+from,
													dataType: 'text'
												});
											});
											clearInterval(finished);
										}
									}
								});
							},60000);
						});
					});
				});
		</script>
	</body>
</html>
