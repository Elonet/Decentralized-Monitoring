$(function () {
	$(document).ready(function () {
		$.ajax({
			type: "POST",
			url: "./php/graph_item.php",
			data: "id=restore&md5="+$("#md5").html(),
			dataType: 'json',
			success: function (html) {
				var data = [];
				var i = 0;
				var time = (new Date()).getTime();
				for (html[i]; i < html.length; i++) {
					alert(html[i].timestamp);
					data.push({
						x: html[i].timestamp * 1000,
						y: parseFloat(html[i].nb_f)
					});
				}
				graph(data);
			}
		});
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
			type: 'spline',
			animation: Highcharts.svg, // don't animate in old IE
			marginRight: 5,
			events: {
				load: function () {
					var series = this.series[0];
					setInterval(function () {
						$.ajax({
							type: "POST",
							url: "./php/graph_item.php",
							data: "id=add&md5="+("#md5").html(),
							dataType: 'json',
							success: function (html) {
								var	x = html.timestamp * 1000;
								var	y = parseFloat(html.nb_f);
								series.addPoint([x, y], true, false);
							}
						});
						if(series.data.length && series.data.length > 24) {
							series.data[0].remove();
						}
					}, 30000);
				}
			}
		},
		title: {
			text: 'Number of users using the application'
		},
		xAxis: {
			type: 'datetime',
			tickPixelInterval: 150
		},
		yAxis: {
			title: {
				text: ''
			},
			plotLines: [{
				value: 0,
					color: '#808080'
			}]
		},
		tooltip: {
			formatter: function () {
				return '<b>' + this.series.name + '</b><br/>' + Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x) + '<br/>' + Highcharts.numberFormat(this.y, 2);
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
            noData: "No data collected"
        },
        noData: {
            style: {
                fontWeight: 'bold',
                fontSize: '15px',
                color: '#303030'
            }
        },
		series: [{
			name: 'Number of Users at',
			data: data
		}]
	});
}