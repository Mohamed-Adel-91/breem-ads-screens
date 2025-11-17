// Africa
$(function(){
	$('#mapAfrica').vectorMap({
		map: 'africa_mill',
		backgroundColor: 'transparent',
		scaleColors: ['#FF7E39'],
		zoomOnScroll:false,
		zoomMin: 1,
		hoverColor: true,
		series: {
			regions: [{
				values: gdpData,
				scale: ['#225de4', '#1146bf', '#edf3ff'],
				normalizeFunction: 'polynomial'
			}]
		},
	});
});