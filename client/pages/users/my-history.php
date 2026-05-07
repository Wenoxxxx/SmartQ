<?php
session_start();
if (!isset($_SESSION['student'])) {
  header('Location: ../login.php');
  exit();
}
require_once "../../../server/config/database.php";
$database = new Database();
$db = $database->getConnection();

$user = $_SESSION['student'];
$student_id = $user['student_id'];

// Fetch latest student status and validation info
$query = "SELECT s.*, vs.status_name, c.college_name 
          FROM students s
          LEFT JOIN validation_status vs ON s.status_id = vs.status_id
          LEFT JOIN colleges c ON s.college_id = c.college_id
          WHERE s.student_id = :sid LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':sid', $student_id);
$stmt->execute();
$student_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch queue history (active or past bookings)
$queue_query = "SELECT ql.*, qs.schedule_date, qs.start_time, qs.end_time, qs.status as schedule_status
                FROM queue_list ql
                JOIN queue_schedule qs ON ql.schedule_id = qs.schedule_id
                WHERE ql.student_id = :sid
                ORDER BY qs.schedule_date DESC";
$q_stmt = $db->prepare($queue_query);
$q_stmt->bindParam(':sid', $student_id);
$q_stmt->execute();
$history = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../../assets/logo/sq.png">
  <meta name="component-base" content="../../components/">
  <link rel="stylesheet" href="../../assets/css/main.css">
  <link rel="stylesheet" href="../../assets/css/components/components.css">
  <link rel="stylesheet" href="../../assets/css/components/navigation.css">
  <link rel="stylesheet" href="../../assets/css/users/student-dashboard.css">
  <title>SmartQ | My History</title>
</head>

<body>
  <div class="admin-layout">
    <div data-component="sidebar" data-props='{"active":"dashboard", "role":"student"}'></div>
    <div class="admin-main">
      <div data-component="topbar"
        data-props='{"title":"My History", "description":"View your validation and queue history."}'></div>
      <main class="admin-content">
        <div class="student-container">

          <a href="student-dashboard.php" class="btn-nav-back">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
          </a>

          <!-- ── History Hero ── -->
          <div class="student-hero">
            <div class="hero-welcome">
              <h1>My Validation <span>History</span></h1>
              <p>Review your previous queue entries and validation results.</p>
            </div>
            <div class="hero-status-card">
              <span class="progress-label">Total Records</span>
              <span class="progress-value" style="font-size: 1.5rem; display: block;"><?php echo count($history); ?></span>
            </div>
          </div>

          <!-- ── Quick Status Cards ── -->
          <div class="student-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="student-card">
              <div class="card-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="card-info">
                <p class="progress-label">Current Status</p>
                <p class="progress-value"><?php echo htmlspecialchars($student_data['status_name'] ?? 'Not Validated'); ?></p>
              </div>
            </div>

            <div class="student-card">
              <div class="card-icon" style="background: #fdf2f8; color: #db2777;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
              </div>
              <div class="card-info">
                <p class="progress-label">Validated On</p>
                <p class="progress-value">
                  <?php echo $student_data['validated_at'] ? date('M d, Y', strtotime($student_data['validated_at'])) : 'Pending'; ?>
                </p>
              </div>
            </div>

            <?php if ($student_data['validated_by']): ?>
            <div class="student-card">
              <div class="card-icon" style="background: #f0fdf4; color: #16a34a;">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div class="card-info">
                <p class="progress-label">Verified By</p>
                <p class="progress-value"><?php echo htmlspecialchars($student_data['validated_by']); ?></p>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- ── Queue History ── -->
          <div class="student-card" style="padding: 0; overflow: hidden;">
            <div class="booking-card-status" style="border-top: none; justify-content: space-between;">
              <h3 style="margin: 0; font-size: 1rem; font-weight: 700;">Queue History</h3>
              <span style="font-size: 0.8rem; opacity: 0.8;"><?php echo count($history); ?> entries</span>
            </div>

            <div class="history-content">
              <?php if (count($history) > 0): ?>
                
                <!-- Desktop Table -->
                <div class="desktop-only" style="overflow-x: auto;">
                  <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                      <tr style="background: #f8fafc;">
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Date</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Time Slot</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">Queue No.</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; text-align: right;">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($history as $row):
                        $date = new DateTime($row['schedule_date']);
                        $start = new DateTime($row['start_time']);
                        $end = new DateTime($row['end_time']);
                      ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                          <td style="padding: 15px 20px;">
                            <div style="font-weight: 600;"><?php echo $date->format('M d, Y'); ?></div>
                            <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo $date->format('l'); ?></div>
                          </td>
                          <td style="padding: 15px 20px; font-size: 0.9rem;">
                            <?php echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); ?>
                          </td>
                          <td style="padding: 15px 20px;">
                            <span class="queue-pill">#<?php echo str_pad($row['queue_number'], 3, '0', STR_PAD_LEFT); ?></span>
                          </td>
                          <td style="padding: 15px 20px; text-align: right;">
                            <span class="status-pill <?php echo $row['schedule_status'] == 'active' ? 'validated' : 'not-validated'; ?>">
                              <?php echo ucfirst($row['schedule_status']); ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Mobile List -->
                <div class="history-list">
                  <?php foreach ($history as $row):
                    $date = new DateTime($row['schedule_date']);
                    $start = new DateTime($row['start_time']);
                    $end = new DateTime($row['end_time']);
                  ?>
                    <div class="history-item-card">
                      <div class="history-item-info">
                        <span class="history-item-date"><?php echo $date->format('M d, Y'); ?></span>
                        <span class="history-item-time"><?php echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); ?></span>
                      </div>
                      <div class="history-item-badge">
                        <span class="queue-pill">#<?php echo str_pad($row['queue_number'], 3, '0', STR_PAD_LEFT); ?></span>
                        <span class="status-pill <?php echo $row['schedule_status'] == 'active' ? 'validated' : 'not-validated'; ?>" style="font-size: 0.65rem; padding: 2px 8px;">
                          <?php echo ucfirst($row['schedule_status']); ?>
                        </span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>

              <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                  <div style="font-size: 3rem; margin-bottom: 15px;">📜</div>
                  <p style="color: #64748b; font-weight: 500;">No queue history found for your account.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </main>
      <div data-component="footer"></div>
    </div>
  </div>

  <!-- Mobile Bottom Navigation -->
  <div data-component="mobile-nav" data-props='{"active":"dashboard"}'></div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="../../scripts/component-loader.js"></script>
</body>

</html>