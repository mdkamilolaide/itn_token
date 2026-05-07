$(window).on("load", function () {
  "use strict";

  var $textHeadingColor = "#5e5873";
  var $white = "#fff";
  var $supportTrackerChart = document.querySelector("#support-trackers-chart");

  var supportTrackerChartOptions;

  var supportTrackerChart;

  // Support Tracker Chart
  // -----------------------------
  supportTrackerChartOptions = {
    chart: {
      height: 120,
      type: "radialBar",
    },
    plotOptions: {
      radialBar: {
        size: 150,
        offsetY: 20,
        startAngle: -150,
        endAngle: 150,
        hollow: {
          size: "65%",
        },
        track: {
          background: $white,
          strokeWidth: "100%",
        },
        dataLabels: {
          name: {
            offsetY: -5,
            color: $textHeadingColor,
            fontSize: "1rem",
          },
          value: {
            offsetY: 15,
            color: $textHeadingColor,
            fontSize: "1.714rem",
          },
        },
      },
    },
    colors: [window.colors.solid.danger],
    fill: {
      type: "gradient",
      gradient: {
        shade: "dark",
        type: "horizontal",
        shadeIntensity: 0.5,
        gradientToColors: [window.colors.solid.primary],
        inverseColors: true,
        opacityFrom: 1,
        opacityTo: 1,
        stops: [0, 100],
      },
    },
    stroke: {
      dashArray: 8,
    },
    series: [83],
    labels: ["Completed Tickets"],
  };
  supportTrackerChart = new ApexCharts(
    $supportTrackerChart,
    supportTrackerChartOptions
  );
  supportTrackerChart.render();
});
