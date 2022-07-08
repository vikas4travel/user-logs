<?php
if ( empty( $graph['data_json'] ) || empty( $graph['ticks_json'] ) ) {
	return;
}
?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<div id="wsi_login_chart"></div>

<script language="JavaScript">
	google.charts.load('current', {packages: ['corechart', 'line']});
	google.charts.setOnLoadCallback(drawBasic);

	function drawBasic() {

		var data = new google.visualization.DataTable();

		data.addColumn('date', 'Date');
		data.addColumn('number', "User Registrations" );

		data.addRows(<?php echo $graph['data_json']; ?>)

		var options = {
			animation: {
				duration: 1000,
				startup: true,
				easing: 'out'
			},
			chart: {
				title: '',
				subtitle: ''
			},
			chartArea: {
				left: 50,
				width: '93%',
				height: '80%',
				top: 30
			},
			legend: {
				position: 'top'
			},
			height: 300,
			vAxis: {
				textPosition: 'out'

			},
			hAxis: {
				format: 'd MMM',
				textStyle: {
					color: '#000',
					fontName: 'Arial',
					fontSize: 10,
					bold: false,
					italic: true
				},
				ticks: <?php echo $graph['ticks_json']; ?>
			},
		};

		var chart = new google.visualization.LineChart( document.getElementById('wsi_login_chart') );
		chart.draw(data, options);
	}

</script>