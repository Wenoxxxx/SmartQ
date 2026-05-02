/**
 * ============================================================
 *  SmartQ Chart Widgets
 * ============================================================
 *
 *  Reusable Chart.js factory functions shared across Dashboard
 *  and Reports pages.
 *
 *  API:
 *    SmartQ.charts.createBarChart(canvasId, labels, data)
 *    SmartQ.charts.createDoughnutChart(canvasId, labels, data)
 *
 *  Both return the Chart instance so callers can .destroy() it.
 * ============================================================
 */

(function () {
  "use strict";

  window.SmartQ = window.SmartQ || {};
  window.SmartQ.charts = window.SmartQ.charts || {};

  // ── Shared Color Palettes ──────────────────────────────────

  const COLLEGE_COLORS = {
    'COT':  '#ff7d04',
    'CON':  '#ec57ee',
    'COB':  '#fac800',
    'COE':  '#1c5adf',
    'CPAG': '#23c7c7',
    'CAS':  '#10b981',
  };

  const STATUS_COLORS = {
    'validated':     '#22c55e',
    'pending':       '#eab308',
    'not validated': '#ef4444',
  };

  /**
   * Map an array of college labels to their brand colors.
   */
  function mapCollegeColors(labels) {
    return labels.map(function (label) {
      return COLLEGE_COLORS[label.trim().toUpperCase()] || '#2563eb';
    });
  }

  /**
   * Map an array of status labels to their semantic colors.
   */
  function mapStatusColors(labels) {
    return labels.map(function (label) {
      return STATUS_COLORS[label.trim().toLowerCase()] || '#94a3b8';
    });
  }

  // ── Bar Chart (College Distribution) ───────────────────────

  /**
   * Create an animated bar chart with staggered rising bars.
   *
   * @param {string}   canvasId  – ID of the <canvas> element
   * @param {string[]} labels    – Category labels (e.g. college abbreviations)
   * @param {number[]} data      – Corresponding values
   * @returns {Chart}  The Chart.js instance
   */
  SmartQ.charts.createBarChart = function (canvasId, labels, data) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    var barColors = mapCollegeColors(labels);
    var delayed = [];

    return new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Validated Students',
          data: data,
          backgroundColor: barColors,
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          onComplete: function () { delayed.length = 0; },
          delay: function (context) {
            var delay = 0;
            if (context.type === 'data' && context.mode === 'default' && !delayed[context.dataIndex]) {
              delay = context.dataIndex * 180 + 100;
              delayed[context.dataIndex] = true;
            }
            return delay;
          },
          duration: 800,
          easing: 'easeOutQuart'
        },
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
          x: { grid: { display: false } }
        }
      }
    });
  };

  // ── Doughnut Chart (Validation Status) ─────────────────────

  /**
   * Create an interactive doughnut chart with center-text plugin.
   * Clicking a segment updates the center label/percentage.
   *
   * @param {string}   canvasId  – ID of the <canvas> element
   * @param {string[]} labels    – Status labels
   * @param {number[]} data      – Corresponding counts
   * @returns {Chart}  The Chart.js instance
   */
  SmartQ.charts.createDoughnutChart = function (canvasId, labels, data) {
    var ctx = document.getElementById(canvasId).getContext('2d');
    var statusColors = mapStatusColors(labels);

    var total = data.reduce(function (a, b) { return a + b; }, 0);
    var validatedIndex = -1;
    for (var i = 0; i < labels.length; i++) {
      if (labels[i].trim().toLowerCase() === 'validated') { validatedIndex = i; break; }
    }
    var validatedCount = validatedIndex !== -1 ? data[validatedIndex] : 0;
    var completionRate = total > 0 ? Math.round((validatedCount / total) * 100) : 0;

    var activeLabel = "VALIDATED";
    var activeValue = completionRate;

    return new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: data,
          backgroundColor: statusColors,
          borderWidth: 0,
          hoverOffset: 10
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 800,
          easing: 'easeOutQuart'
        },
        onClick: function (evt, elements, chart) {
          if (elements.length > 0) {
            var index = elements[0].index;
            activeLabel = chart.data.labels[index].trim().toUpperCase();
            var val = chart.data.datasets[0].data[index];
            activeValue = Math.round((val / total) * 100);
          } else {
            activeLabel = "VALIDATED";
            activeValue = completionRate;
          }
          chart.draw();
        },
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 12, padding: 20, font: { size: 11 } } },
          tooltip: {
            callbacks: {
              label: function (context) {
                var label = context.label || '';
                var value = context.parsed || 0;
                var percentage = Math.round((value / total) * 100);
                return label + ': ' + value + ' (' + percentage + '%)';
              }
            }
          }
        },
        cutout: '75%',
      },
      plugins: [{
        id: 'centerText',
        beforeDraw: function (chart) {
          var ctx2 = chart.ctx;
          var area = chart.chartArea;
          ctx2.save();

          var centerX = (area.left + area.right) / 2;
          var centerY = (area.top + area.bottom) / 2;

          // Percentage
          var fontSize = (chart.height / 160).toFixed(2);
          ctx2.font = '700 ' + fontSize + 'em sans-serif';
          ctx2.textBaseline = 'middle';
          ctx2.textAlign = 'center';
          ctx2.fillStyle = '#1e293b';
          ctx2.fillText(activeValue + '%', centerX, centerY + 10);

          // Label
          ctx2.font = '600 0.75em sans-serif';
          ctx2.fillStyle = '#64748b';
          ctx2.fillText(activeLabel, centerX, centerY - 15);

          ctx2.restore();
        }
      }]
    });
  };

})();
