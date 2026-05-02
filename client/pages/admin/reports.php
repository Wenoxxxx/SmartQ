<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: ../login.php');
  exit();
}

require_once "../../../server/config/database.php";
$database = new Database();
$db = $database->getConnection();

// Quick Stats Data
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM students) as total,
    (SELECT COUNT(*) FROM students s JOIN validation_status vs ON s.status_id = vs.status_id WHERE vs.status_name = 'Validated') as validated,
    (SELECT COUNT(*) FROM students s JOIN validation_status vs ON s.status_id = vs.status_id WHERE vs.status_name = 'Pending') as pending,
    (SELECT COUNT(*) FROM students s JOIN validation_status vs ON s.status_id = vs.status_id WHERE vs.status_name = 'Not Validated') as not_validated";
$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Colleges for Filter
$colleges_sql = "SELECT * FROM colleges ORDER BY college_name";
$colleges_stmt = $db->prepare($colleges_sql);
$colleges_stmt->execute();
$colleges = $colleges_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Validation Statuses for Filter
$statuses_sql = "SELECT * FROM validation_status";
$statuses_stmt = $db->prepare($statuses_sql);
$statuses_stmt->execute();
$statuses = $statuses_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../../assets/logo/sq.png">

  <!-- Tell the component loader where to find components -->
  <meta name="component-base" content="../../components/">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="../../assets/css/main.css">
  <link rel="stylesheet" href="../../assets/css/components/components.css">
  <link rel="stylesheet" href="../../assets/css/admin/reports.css">
  <link rel="stylesheet" href="../../assets/css/admin/students.css">

  <title>SmartQ | Reports & Analytics</title>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

  <!-- =============================================
       ADMIN LAYOUT
       ============================================= -->
  <div class="admin-layout">

    <!-- ── Sidebar (loaded dynamically) ── -->
    <div data-component="sidebar" data-props='{"active":"reports"}'></div>

    <!-- ── Main Area ── -->
    <div class="admin-main">

      <!-- Topbar -->
      <div data-component="topbar"
        data-props='{"title":"Reports & Analytics", "description":"View student validation statistics and download reports."}'>
      </div>

      <!-- Page Content -->
      <main class="admin-content">
        <div class="reports-container">

          <!-- ── Filters ── -->
          <div class="reports-controls">
            <div class="controls-header">
              <h2 class="controls-title">Report Filters</h2>
            </div>
            <div class="filter-row">
              <div class="filter-item">
                <label class="filter-label">Year Level</label>
                <select id="year-filter" class="filter-select">
                  <option value="">All Year Levels</option>
                  <option value="1">1st Year</option>
                  <option value="2">2nd Year</option>
                  <option value="3">3rd Year</option>
                  <option value="4">4th Year</option>
                </select>
              </div>
              <div class="filter-item">
                <label class="filter-label">College</label>
                <select id="college-filter" class="filter-select">
                  <option value="">All Colleges</option>
                  <?php foreach ($colleges as $c): ?>
                    <option value="<?php echo $c['college_id']; ?>"><?php echo htmlspecialchars($c['college_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="filter-item">
                <label class="filter-label">Validation Status</label>
                <select id="status-filter" class="filter-select">
                  <option value="">All Statuses</option>
                  <?php foreach ($statuses as $s): ?>
                    <option value="<?php echo $s['status_id']; ?>"><?php echo htmlspecialchars($s['status_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="action-group">
              <button class="btn-download btn-primary" id="btn-open-export" style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 0 24px; border-radius: 12px; height: 44px; font-weight: 700; width: auto;">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export Report
              </button>
            </div>
          </div>

          <!-- ── Visualizations ── -->
          <div class="reports-visuals">
            <div class="chart-card">
              <div class="chart-title">
                <span>Validation Distribution by College</span>
                <span style="font-size: 12px; font-weight: normal; color: var(--text-muted);">Real-time Data</span>
              </div>
              <div class="chart-container">
                <canvas id="collegeChart"></canvas>
              </div>
            </div>
            <div class="chart-card">
              <div class="chart-title">
                <span>Overall Status</span>
              </div>
              <div class="chart-container">
                <canvas id="statusChart"></canvas>
              </div>
            </div>
          </div>

          <!-- ── Preview Table ── -->
          <div class="report-preview" style="padding: 0; border: none; background: transparent; box-shadow: none;">
            <div class="chart-title" style="margin-bottom: 16px; padding: 0 4px;">
              <span>Data Preview (Top 50 Matching Records)</span>
            </div>
            <div class="students-table-container">
              <table class="students-table">
                <thead>
                  <tr>
                    <th>Student ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Year</th>
                    <th>College</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="preview-body">
                  <!-- Loaded via AJAX -->
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- ── Export Report Modal ── -->
        <div id="export-modal" class="modal-overlay" style="display: none; align-items: center; justify-content: center;">
          <div class="modal-backdrop export-modal-close"></div>
          <div class="modal-card" style="max-width: 450px; text-align: left; padding: 30px;">
            <div class="modal-header" style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px;">
              <div class="modal-icon" style="background: rgba(28, 90, 223, 0.1); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="24" height="24" fill="none" stroke="#1c5adf" stroke-width="2.5" viewBox="0 0 24 24">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
              </div>
              <div>
                <h3 class="modal-title" style="margin: 0; font-size: 1.25rem; font-weight: 800; color: #1e293b;">Export Data</h3>
                <p class="modal-desc" style="margin: 4px 0 0; font-size: 0.85rem; color: #64748b;">Select the format and criteria for your CSV report.</p>
              </div>
            </div>

            <form id="export-form" class="download-form" style="display: flex; flex-direction: column; gap: 20px;">
              
              <div class="filter-group" style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase;">Report Type</label>
                <select id="export-type" class="filter-select" style="padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 0.95rem;">
                  <option value="filtered">Detailed Student List (Current Filters)</option>
                  <option value="college_specific">Specific College Only</option>
                  <option value="college">Grouped by College (All)</option>
                  <option value="year">Grouped by Year Level (All)</option>
                  <option value="comparison_summary">Validation Summary & Comparison</option>
                </select>
              </div>

              <!-- Hidden College Select -->
              <div id="export-college-group" class="filter-group" style="display: none; flex-direction: column; gap: 8px;">
                <label style="font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase;">Select Target College</label>
                <select id="export-college-id" class="filter-select" style="padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 0.95rem;">
                  <?php foreach ($colleges as $c): ?>
                    <option value="<?php echo $c['college_id']; ?>"><?php echo htmlspecialchars($c['college_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="modal-actions" style="margin-top: 10px; display: flex; gap: 12px;">
                <button type="button" class="modal-btn-cancel export-modal-close" style="flex: 1; padding: 12px; border-radius: 10px; font-weight: 700; background: #f1f5f9; color: #64748b; border: none; cursor: pointer;">Cancel</button>
                <button type="submit" class="modal-btn-confirm" style="flex: 1; padding: 12px; border-radius: 10px; font-weight: 700; background: #1c5adf; color: white; border: none; cursor: pointer;">Download CSV</button>
              </div>
            </form>
          </div>
        </div>
      </main>

      <!-- Footer -->
      <div data-component="footer"></div>

    </div>
  </div>

  <!-- =============================================
       SCRIPTS
       ============================================= -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="../../scripts/component-loader.js"></script>
  <script src="../../scripts/chart-widgets.js"></script>

  <script>
    $(document).ready(function () {
      let collegeChart, statusChart;

      function updateCharts() {
        const filters = {
          year: $('#year-filter').val(),
          college: $('#college-filter').val(),
          status: $('#status-filter').val()
        };

        $.ajax({
          url: '../../../server/api/reports/get_report_data.php',
          method: 'GET',
          data: filters,
          dataType: 'json',
          success: function (res) {
            if (res.success) {
              // Update Preview Table
              let html = '';
              if (res.preview.length > 0) {
                const collegeColors = {
                  'COT': { bg: '#fff7ed', text: '#ff7d04' },
                  'CON': { bg: '#fdf2f8', text: '#ec57ee' },
                  'COB': { bg: '#fffbeb', text: '#fac800' },
                  'COE': { bg: '#eff6ff', text: '#1c5adf' },
                  'CPAG': { bg: '#f0fdfa', text: '#23c7c7' },
                  'CAS': { bg: '#f0fdf4', text: '#10b981' },
                };

                res.preview.forEach(s => {
                  const colors = collegeColors[s.college_name] || { bg: '#f1f5f9', text: '#64748b' };
                  const statusClass = s.status_name.toLowerCase().replace(' ', '-');

                  html += `<tr>
                    <td class="student-id-cell">${s.student_id}</td>
                    <td class="student-name-cell">${s.first_name}</td>
                    <td class="student-name-cell">${s.last_name}</td>
                    <td class="email-cell">${s.email}</td>
                    <td><span class="year-badge-small">${s.year_display}</span></td>
                    <td>
                      <span class="college-badge-small" style="background:${colors.bg}; color:${colors.text}; border-color:${colors.text}20;">
                        ${s.college_name}
                      </span>
                    </td>
                    <td><span class="status-badge badge-${statusClass}">${s.status_name}</span></td>
                  </tr>`;
                });
              } else {
                html = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">No records found matching your filters</td></tr>';
              }
              $('#preview-body').html(html);

              // Charts — destroy old instances, create new via shared widget
              if (collegeChart) collegeChart.destroy();
              collegeChart = SmartQ.charts.createBarChart(
                'collegeChart',
                res.charts.college.labels,
                res.charts.college.data
              );

              if (statusChart) statusChart.destroy();
              statusChart = SmartQ.charts.createDoughnutChart(
                'statusChart',
                res.charts.status.labels,
                res.charts.status.data
              );
            }
          }
        });
      }

      // Initial Load
      updateCharts();

      // Filter changes
      $('.filter-select').on('change', updateCharts);

      // ── Export Modal Logic ──
      const $exportModal = $('#export-modal');
      
      $('#btn-open-export').on('click', function() {
        $exportModal.css('display', 'flex').hide().fadeIn(200);
      });

      $('.export-modal-close').on('click', function() {
        $exportModal.fadeOut(200);
      });

      $('#export-type').on('change', function() {
        if ($(this).val() === 'college_specific') {
          $('#export-college-group').slideDown(200);
        } else {
          $('#export-college-group').slideUp(200);
        }
      });

      $('#export-form').on('submit', function(e) {
        e.preventDefault();
        
        const type = $('#export-type').val();
        let url = '../../../server/api/reports/download.php?type=' + type;

        if (type === 'filtered') {
          url += '&year=' + $('#year-filter').val() +
                 '&college=' + $('#college-filter').val() +
                 '&status=' + $('#status-filter').val();
        } else if (type === 'college_specific') {
          url += '&college_id=' + $('#export-college-id').val();
        }

        window.location.href = url;
        $exportModal.fadeOut(200);
      });
    });
  </script>

</body>

</html>