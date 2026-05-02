<?php
/**
 * College Bar Chart Widget
 * 
 * Self-contained Chart.js bar chart showing validation counts per college.
 * Fetches its own data from the reports API and uses SmartQ.charts.createBarChart().
 *
 * Usage:  <div data-component="college-bar-chart"></div>
 *
 * Requires: Chart.js, chart-widgets.js
 */
?>

<div class="chart-card">
  <div class="chart-header">
    <span class="chart-title-text">Validation by College</span>
  </div>
  <div class="chart-container">
    <canvas id="collegeChart"></canvas>
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
        SmartQ.charts.createBarChart(
          'collegeChart',
          res.charts.college.labels,
          res.charts.college.data
        );
      }
    }
  });
})();
</script>
