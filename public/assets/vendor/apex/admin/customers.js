var options = {
  chart: {
    height: 193,
    type: 'donut',
  },
  labels: ['New', 'Returned'],
  legend: {
    show: false,
  },
  series: [700, 300],
  stroke: {
    width: 1,
  },
  colors: ['#225de4', '#999999'],
}
var customersEl = document.querySelector("#customers");
if (customersEl) {
  var chart = new ApexCharts(customersEl, options);
  chart.render();
} else {
  console.warn("ApexCharts: #customers container not found, skipping chart render.");
}
