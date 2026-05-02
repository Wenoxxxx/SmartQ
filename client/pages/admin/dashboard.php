<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: ../login.php');
  exit();
}

require_once '../../../server/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Fetch Dynamic Stats from students table
// 1. Total Students
$total_students = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();

// 2. Pending (Status ID 3)
$pending_students = $db->query("SELECT COUNT(*) FROM students WHERE status_id = 3")->fetchColumn();

// 3. Validated (Status ID 1)
$validated = $db->query("SELECT COUNT(*) FROM students WHERE status_id = 1")->fetchColumn();

// 4. Not Validated (Status ID 2)
$not_validated = $db->query("SELECT COUNT(*) FROM students WHERE status_id = 2")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="SmartQ Admin Dashboard - Student Queue Management">
  <link rel="icon" type="image/png" href="../../assets/logo/sq.png">

  <!-- Component loader base -->
  <meta name="component-base" content="../../components/">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="../../assets/css/main.css">
  <link rel="stylesheet" href="../../assets/css/components/components.css">
  <link rel="stylesheet" href="../../assets/css/admin/dashboard.css">

  <title>SmartQ | Admin Dashboard</title>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
  <div class="admin-layout">

    <!-- Sidebar Navigation -->
    <div data-component="sidebar" data-props='{"active":"dashboard"}'></div>

    <!-- Main Content Area -->
    <div class="admin-main">

      <!-- Page Header -->
      <div data-component="topbar" data-props='{"title":"Dashboard","description":"Welcome back, Admin!"}'></div>

      <!-- ═══════════════════════════════════════════════════════
           DASHBOARD CONTENT
           ═══════════════════════════════════════════════════════ -->
      <main class="admin-content dash">

        <!-- ROW 1: Stat Cards (Updated with Reports UI & Icons) -->
        <section class="dash-row dash-stats" id="dash-stats">
          <div class="stat-card">
            <div class="stat-card-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
              <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
            <div class="stat-card-content">
              <div class="stat-card-label">Total Students</div>
              <div class="stat-card-value"><?= number_format($total_students) ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-icon" style="background: rgba(234, 179, 8, 0.1); color: #ca8a04;">
              <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
            </div>
            <div class="stat-card-content">
              <div class="stat-card-label">Pending Review</div>
              <div class="stat-card-value"><?= number_format($pending_students) ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-icon" style="background: rgba(34, 197, 94, 0.1); color: #16a34a;">
              <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <div class="stat-card-content">
              <div class="stat-card-label">Validated</div>
              <div class="stat-card-value"><?= number_format($validated) ?></div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-card-icon" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;">
              <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </div>
            <div class="stat-card-content">
              <div class="stat-card-label">Not Validated</div>
              <div class="stat-card-value"><?= number_format($not_validated) ?></div>
            </div>
          </div>
        </section>

        <!-- ROW 2: Interactive Analytics -->
        <div class="dash-row dash-panels">
          <div data-component="college-bar-chart"></div>
          <div data-component="status-doughnut-chart"></div>
        </div>


      </main>

    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="../../scripts/component-loader.js"></script>
  <script src="../../scripts/chart-widgets.js"></script>

</body>

</html>