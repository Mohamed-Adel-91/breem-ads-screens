// Morris Days
var day_data = [
	{"period": "2016-10-01", "licensed": 3213, "Skyline": 887},
	{"period": "2016-09-30", "licensed": 3321, "Skyline": 776},
	{"period": "2016-09-29", "licensed": 3671, "Skyline": 884},
	{"period": "2016-09-20", "licensed": 3176, "Skyline": 448},
	{"period": "2016-09-19", "licensed": 3376, "Skyline": 565},
	{"period": "2016-09-18", "licensed": 3976, "Skyline": 627},
	{"period": "2016-09-17", "licensed": 2239, "Skyline": 660},
	{"period": "2016-09-16", "licensed": 3871, "Skyline": 676},
	{"period": "2016-09-15", "licensed": 3659, "Skyline": 656},
	{"period": "2016-09-10", "licensed": 3380, "Skyline": 663}
];
Morris.Line({
	element: 'dayData',
	data: day_data,
	xkey: 'period',
	ykeys: ['licensed', 'Skyline'],
	labels: ['Licensed', 'Skyline'],
	resize: true,
	hideHover: "auto",
	gridLineColor: "#e1e5f1",
	pointFillColors:['#ffffff'],
	pointStrokeColors: ['#ee0000'],
	lineColors:['#225de4', '#1146bf', '#edf3ff'],
});