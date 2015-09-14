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
$red = $conf['color_limit_level'];
$orange =$red/2;
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
		<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
		<style>
			.box:after, .box:before {display: table;}
			.box {clear: both;margin-top: 0px;margin-bottom: 25px;padding: 0px;width:100%;height:100px;}
			.mark {width:20%;float:left;height:100%;text-align:center;}
			.mark > span.glyphicon {font-size: 42px;line-height: 1.0;}
			.info {float:left;width:80%;padding-left:10px;padding-right:10px;text-align:center;}
			.number {font-weight:bold;font-size: 24px;margin-bottom:4px;}
			.two {width:70%;float:left;text-align:center;font-size: 24px;margin-top:30px;}
			.three {width:10%;float:right;font-weight:bold;margin-top:30px;}
			.four {float:left;font-weight:bold;margin-top:0px;margin-left:-30px;font-size: 18px;height:100px;line-height:100px;}
			.container {margin-top:30px;}
			.global{width:50%;height:450px;margin:0px auto;min-width:620px;}
			.four p {vertical-align:middle;display:inline-block;line-height:normal;text-align:left;}
			.picto { width:30px;margin:2px 0px 2px -10px}
		</style>
	</head>
	<body>
		<div class="container" style="display:none;">
			<center>
				<div style="display:inline-block;">
					<img src="img/Decmon-128.png" height="100">
				</div>
				<div style="display:inline-block;vertical-align: middle;" id="checker"></div> 
				<h3 style="margin-top:0px;"><?php echo $vocables[$lang]['title']; ?></h3>
				<h5 class="all_success"></div>
			</center>
			<div style="width:80%;margin:30px auto;">
				<div id="filter-items">
					
				</div>
			</div>
			<div class='global box panel panel-primary' style="display:none;text-align:center;">
				<div class='panel-heading' style="border-bottom-left-radius:0px;">
					<b><center><?php echo $vocables[$lang]['monitoring_1']; ?></center></b>
				</div>
				<div class="panel-body">
					<p id="checked">
					</p>
				</div>
				<center>
					<div id="container" style="margin: 30px auto"></div>
				</center>
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
			$(window).resize(function(){
				var width = $(window).width();
				console.log(width);
				if (width<=500) {
					$(".panel-body").html("<div><p style='font-size:100;color:red;'><?php echo $vocables[$lang]['resize_chart_error_1'];?></p><br/><p style='font-size:80'><?php echo $vocables[$lang]['resize_chart_error_2'];?></p><div>");
					$("#container").html("");
				}
			});
			$(window).load(function() {
				$(".container").fadeIn();
			});
			$(function () {
			/*-----------------------------------------------------------------------------------*/
			/*	Isotope
			/*-----------------------------------------------------------------------------------*/
			var $container = $('#filter-items');
			$container.isotope({
				itemSelector: '.item',
				layoutMode: 'vertical',
				filter: function () {
					var number = $(this).find('.number').text();
					return parseInt(number);
				},
				sortBy: 'number',
				getSortData: {
					number: '.number parseInt'
				}
			});
			// filter items when filter link is clicked
			$(document).ready(function () {				
				$.ajax({
					type: "POST",
					url: "./php/monitoring-data.php",
					dataType: 'json',
					success: function (html) {
						var i = 0;
						var $elems;
						var compteur = 0;
						if(html.length !== 0) {
							for (html[i]; i < html.length; i++) {
								var type = html[i].type;
								var pourcentage = parseInt(html[i].glob);
								var item = html[i].item.split(":")[0];
								var id = html[i].md5;
								var nb_f = html[i].nb_f;
								var nb_s = html[i].nb_s;
								var prot = html[i].item.split(":")[2];
								var port = html[i].item.split(":")[1];
								var hostname = html[i].hostname;
								//Item counter not working perfectly
								if (html[i].glob != 0) {
									compteur = compteur + 1;
								}
								if (pourcentage <= <?php echo $orange; ?>) {
									$elems = getItemElement(pourcentage, item, 'glyphicon-ok-circle', 'panel-success', id, nb_f, nb_s, prot, port, hostname, type);
									$container.isotope('insert', $elems);
								} else if (pourcentage > <?php echo $orange; ?> && pourcentage <= <?php echo $red; ?>) {
									$elems = getItemElement(pourcentage, item, 'glyphicon-warning-sign', 'panel-warning', id, nb_f, nb_s, prot, port, hostname, type);
									$container.isotope('insert', $elems);
								} else if (pourcentage > <?php echo $red; ?>) {
									$elems = getItemElement(pourcentage, item, 'glyphicon-remove-circle', 'panel-danger', id, nb_f, nb_s, prot, port, hostname, type);
									$container.isotope('insert', $elems);
								}
							}
						}
						var success = 0;
						var failed = 0;
						$(".item").each(function () {
							if($(this).children('.mark').children('.number').text() !== "0%"){
								failed = failed + 1;
							} else {
								success = success + 1;
							}
						});
						$.ajax({
							type: "POST",
							url: "./php/get_info_no_alert.php",
							dataType: 'json',
							success: function (data) {
								if(parseInt(data.checker) == 1) {
									$("#checker").html("<strong>" + data.checker + "<br/><?php echo $vocables[$lang]['monitoring_10']; ?></strong>");
								} else {
									$("#checker").html("<strong>" + data.checker + "<br/><?php echo $vocables[$lang]['monitoring_11']; ?></strong>");
								}
								$("#checked").html(data.checked + "<?php echo $vocables[$lang]['monitoring_12']; ?>");
								if(success > 0 && failed > 0) {
									$(".all_success").html(Math.round((parseInt(success)*100/(parseInt(failed)+parseInt(success))))+"<?php echo $vocables[$lang]['monitoring_2']; ?>");
								} else if (success > 0 && failed == 0){	
									$(".all_success").html("<?php echo $vocables[$lang]['monitoring_2_1']; ?>");
								} else if(success == 0 && failed > 0) {
									$(".all_success").html("<?php echo $vocables[$lang]['monitoring_2_2']; ?>");
								} else {
									$(".all_success").html("<?php echo $vocables[$lang]['monitoring_4']; ?>");
								}
							}
						});
						if (compteur == 0) {
							getNumberCheck();
						} else {
							$(".global").fadeOut(function() {
								$("#filter-items").fadeIn();
							});
						}
					}
				});
				//Loading graphic
				$.ajax({
					type: "POST",
					url: "./php/graph_user.php",
					data: "id=restore",
					dataType: 'json',
					success: function (html) {
						var data = [];
						var i;
						if(html.length > 24) {
							i = html.length-24;
						} else {
							i = 0;
						}
						var time = (new Date()).getTime();
						for (html[i]; i < html.length; i++) {
							data.push({
								x: html[i].heure * 1000,
								y: parseFloat(html[i].nb_checker)
							});
						}
						graph(data);
					}
				});
				var update = function () {
					$.ajax({
						type: "POST",
						url: "./php/monitoring-data.php",
						dataType: 'json',
						success: function (html) {
							var i = 0;
							var $elems;
							var compteur = 0;
							if(html.length !== 0) {
								for (html[i]; i < html.length; i++) {
									var type = html[i].type;
									var pourcentage = Math.round(html[i].glob);
									var item = html[i].item.split(":")[0];
									var id = html[i].md5;
									var jid = "#" + id;
									var nb_f = html[i].nb_f;
									var nb_s = html[i].nb_s;
									var prot = html[i].item.split(":")[2];
									var port = html[i].item.split(":")[1];
									var hostname = html[i].hostname;
									//Item counter not working perfectly
									if (html[i].glob != 0) {
										compteur = compteur + 1;
									}
									//Items management (color, number, placement)
									if ($(jid).length == 0) {
										if (pourcentage <= <?php echo $orange; ?>) {
											$elems = getItemElement(pourcentage, item, 'glyphicon-ok-circle', 'panel-success', id, nb_f, nb_s, prot, port, hostname, type);
											$container.isotope('insert', $elems);
										} else if (pourcentage > <?php echo $orange; ?> && pourcentage <= <?php echo $red; ?>) {
											$elems = getItemElement(pourcentage, item, 'glyphicon-warning-sign', 'panel-warning', id, nb_f, nb_s, prot, port, hostname, type);
											$container.isotope('insert', $elems);
										} else if (pourcentage > <?php echo $red; ?>) {
											$elems = getItemElement(pourcentage, item, 'glyphicon-remove-circle', 'panel-danger', id, nb_f, nb_s, prot, port, hostname, type);
											$container.isotope('insert', $elems);
										}
									} else {
										if (pourcentage <= <?php echo $orange; ?>) {
											changeItemElement(pourcentage, 'glyphicon-ok-circle', 'panel-success', jid, nb_f, nb_s, type);
											$container.isotope();
										} else if (pourcentage > <?php echo $orange; ?> && pourcentage <= <?php echo $red; ?>) {
											changeItemElement(pourcentage, 'glyphicon-warning-sign', 'panel-warning', jid, nb_f, nb_s, type);
											$container.isotope();
										} else if (pourcentage > <?php echo $red; ?>) {
											changeItemElement(pourcentage, 'glyphicon-remove-circle', 'panel-danger', jid, nb_f, nb_s, type);
											$container.isotope();
										}
									}
								}
							}
							var success = 0;
							var failed = 0;
							$(".item").each(function () {
								var i = 0,c = 0;
								var id = $(this).attr('id');
								if($(this).children('.mark').children('.number').text() !== "0%"){
									failed = failed + 1;
								} else						{
									success = success + 1;
								}
								for (html[i]; i < html.length; i++) {
									if (html[i].md5 === id) {
										c = c + 1;
									}
								}
								//If the item does not exist is suppressed
								if (c == 0) {
									$container.isotope('remove', this).isotope('layout');
								}
							});
							if (compteur == 0) {
								getNumberCheck();
							} else {
								$(".global").fadeOut(function() {
									$("#filter-items").fadeIn();
								});
							}
							$.ajax({
								type: "POST",
								url: "./php/get_info_no_alert.php",
								dataType: 'json',
								success: function (data) {
									if(parseInt(data.checker) == 1) {
										$("#checker").html("<strong>" + data.checker + "<br/><?php echo $vocables[$lang]['monitoring_10']; ?></strong>");
									} else {
										$("#checker").html("<strong>" + data.checker + "<br/><?php echo $vocables[$lang]['monitoring_11']; ?></strong>");
									}
									$("#checked").html(data.checked + "<?php echo $vocables[$lang]['monitoring_12']; ?>");
									if(success > 0 && failed > 0) {
										$(".all_success").html(Math.round((parseInt(success)*100/(parseInt(failed)+parseInt(success))))+"<?php echo $vocables[$lang]['monitoring_2']; ?>");
									} else if (success > 0 && failed == 0){	
										$(".all_success").html("<?php echo $vocables[$lang]['monitoring_2_1']; ?>");
									} else if(success == 0 && failed > 0) {
										$(".all_success").html("<?php echo $vocables[$lang]['monitoring_2_2']; ?>");
									} else {
										$(".all_success").html("<?php echo $vocables[$lang]['monitoring_4']; ?>");
									}
								}
							});
						}
					});
				}
				setInterval(update, 10000);
			});
		});

		function graph(data) {
			Highcharts.setOptions({
				global: {
					useUTC: false
				}
			});
			$('#container').highcharts({
				chart: {
					type: 'line',
					animation: Highcharts.svg, // don't animate in old IE
					width:600,
					height:280,
					events: {
						load: function () {
							var chart = $('#container').highcharts();
							var height = chart.height;
							var width = $(".highcharts-container").outerWidth()-20;
							console.log(width);
							//chart.setSize(width, height, doAnimation = true);
							
							var series = this.series[0];
							var exist = 0;
							setInterval(function () {
								$.ajax({
									type: "POST",
									url: "./php/graph_user.php",
									data: "id=add",
									dataType: 'json',
									success: function (html) {
										var exist = 0;
										for (var i = 0; i < series.points.length; i++) {
											if(series.points[i].x === html.heure * 1000) {
												exist += 1;
											}
										}
										if(exist <= 0) {
											var	x = html.heure * 1000;
											var	y = parseFloat(html.nb_checker);
											series.addPoint([x, y], true, false);
										}
									}
								});
								if(series.points.length && series.points.length > 24) {
									series.data[0].remove();
								}
							}, 30000);
						}
					}
				},
				title: {
					text: '<?php echo $vocables[$lang]['monitoring_5']; ?>'
				},
				xAxis: {
					type: 'datetime',
					tickPixelInterval: 150,
					labels: {
						style: {
							fontWeight: 'bold'
						},
					}
				},
				yAxis: {
					title: {
						text: ''
					},
					min: 0,
					labels: {
						style: {
							fontWeight: 'bold'
						}
					}
				},
				tooltip: {
					formatter: function () {
						return '<b>'+Highcharts.numberFormat(this.y,0) + this.series.name + Highcharts.dateFormat('%B %d, %Y at %H:%M', this.x);
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
					noData: "<?php echo $vocables[$lang]['monitoring_6']; ?>"
				},
				noData: {
					style: {
						fontWeight: 'bold',
						fontSize: '15px',
						color: '#303030'
					}
				},
				series: [{
					name: '<?php echo $vocables[$lang]['monitoring_7']; ?>',
					data: data
				}]
			});
		}
		function getItemElement(pourcentage, item, glyphicon, panel, id, nb_f, nb_s, prot, port, hostname, type) {
			var nb_all = parseInt(nb_f)+parseInt(nb_s);
			var $item = $("<div class='item box panel " + panel + "' id='" + id + "'></div>");
			var picto = "";
			var type_tab = type.split("|");
			for(var i=0; i < type_tab.length; i++) {
				if(type_tab[i] == "none") {
					picto += "<img src='img/timeout.png' class='picto' title='Unreachability'/><br/>";
				}
				if(type_tab[i] == "match") {
					picto += "<img src='img/match.png' class='picto' title='Unexpected Content'/><br/>";
				}
				if(type_tab[i] == "proxy") {
					picto += "<img src='img/proxy.png' class='picto' title='Proxy Error'/><br/>";
				}
				if(type_tab[i] == "latency") {
					picto += "<img src='img/latency.png' class='picto' title='Latency Issue'/><br/>";
				}
			}
			$item.append("<div class='four'><p>"+picto+"</p></div><div class='panel-heading mark'><span class='glyphicon "+glyphicon+"'></span><div class='number'>" + pourcentage.toFixed(0) + "%</div><div id='one'><i style='font-size:12px'>("+nb_f+ "<?php echo $vocables[$lang]['monitoring_8']; ?>"+ nb_all+"<?php echo $vocables[$lang]['monitoring_9']; ?>) </i></div></div><div class='info'><div class='two' style='margin-bottom:0px;'>" + item + " (" + prot + "/" + port.toUpperCase() +")<br/><small>Hostname : "+hostname+"</small></div><div class='three'><button class='btn' onclick='window.open(\"monitoring_chart.php?md5="+id+"&i="+item+"&p="+prot+"&po="+port.toUpperCase()+"\");'><i class='fa fa-area-chart'></i></button></div></div></div>");
			return $item;
		}

		function changeItemElement(pourcentage, glyphicon, panel, id, nb_f, nb_s, type) {
			var nb_all = parseInt(nb_f)+parseInt(nb_s);
			$(id).children('.mark').children('.number').text(pourcentage.toFixed(0) + "%");
			$(id).children('.mark').children('#one').html("<i>("+nb_f+"<?php echo $vocables[$lang]['monitoring_8']; ?>"+ nb_all+"<?php echo $vocables[$lang]['monitoring_9']; ?>)</i>");
			$(id).removeClass();
			$(id).addClass('item box panel ' + panel);
			$(id).children('.mark').children('span').removeClass();
			$(id).children('.mark').children('span').addClass('glyphicon ' + glyphicon);
			//Treatment of pictograms
			$(id).children('.four').children("p").html("");
			var picto = "";
			var type_tab = type.split("|");
			for(var i=0; i < type_tab.length; i++) {
				if(type_tab[i] == "none") {
					picto += "<img src='img/timeout.png' class='picto' title='Unreachability'/><br/>";
				}
				if(type_tab[i] == "match") {
					picto += "<img src='img/match.png' class='picto' title='Unexpected Content'/><br/>";
				}
				if(type_tab[i] == "proxy") {
					picto += "<img src='img/proxy.png' class='picto' title='Proxy Error'/><br/>";
				}
				if(type_tab[i] == "latency") {
					picto += "<img src='img/latency.png' class='picto' title='Latency Issue'/><br/>";
				}
			}
			$(id).children('.four').children("p").html(picto);
		}

		function getNumberCheck() {
			$.ajax({
				type: "POST",
				url: "./php/get_info_no_alert.php",
				dataType: 'json',
				success: function (data) {
					if(parseInt(data.checker) == 1) {
						$("#checker").html("<strong>" + data.checker + "<br/><?php echo $vocables[$lang]['monitoring_10']; ?></strong>");
					} else {
						$("#checker").html("<strong>" + data.checker + "<br/><?php echo $vocables[$lang]['monitoring_11']; ?></strong>");
					}
					$("#checked").html(data.checked + "<?php echo $vocables[$lang]['monitoring_12']; ?>");
					$("#filter-items").fadeOut(function() {
						$(".global").fadeIn();
					});
				}
			});
		}
		</script>
	</body>
</html>
