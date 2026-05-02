<?php
/**
 * Status Doughnut Chart Widget
 * 
 * Self-contained Chart.js doughnut chart showing overall validation status.
 * Fetches its own data from the reports API and uses SmartQ.charts.createDoughnutChart().
 *
 * Usage:  <div data-component="status-doughnut-chart"></div>
 *
 * Requires: Chart.js, chart-widgets.js
 */
?>

<div class="chart-card">
  <div class="chart-header">
    <span class="chart-title-text">Overall Validation Status</span>
  </div>
  <div class="chart-container">
    <canvas id="statusChart"></canvas>
  </div>
</div>

<script>
(function () {
  $.ajax({
    url: '../../../server/api/reports/get_report_data.php',
    method: 'GET',
    dataType: 'json',
    success: function (res) {
      if (res.success) {
        SmartQ.charts.createDoughnutChart(
          'statusChart',
          res.charts.status.labels,
          res.charts.status.data
        );
      }
    }
  });
})();
</script>
