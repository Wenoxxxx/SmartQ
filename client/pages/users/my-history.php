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
          
          <!-- ── History Hero / Status Summary ── -->
          <div class="student-hero" style="background: linear-gradient(135deg, var(--student-primary) 0%, #1d4ed8 100%); padding: 30px; border-radius: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; color: white; box-shadow: 0 15px 35px -5px rgba(59, 130, 246, 0.25);">
             <div>
                <h2 style="margin: 0; font-size: 1.6rem; font-weight: 800; letter-spacing: -0.5px;">My Validation History</h2>
                <p style="margin: 5px 0 0; color: rgba(255,255,255,0.8); font-size: 0.95rem;">Review your previous queue entries and validation results.</p>
             </div>
             <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 15px 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.2); text-align: right;">
                <span style="display: block; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; opacity: 0.9;">Total Records</span>
                <span style="font-size: 1.8rem; font-weight: 800;"><?php echo count($history); ?></span>
             </div>
          </div>

          <!-- ── Quick Status Cards ── -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
              <div class="student-card" style="padding: 20px; border: 1px solid #e2e8f0; border-radius: 16px; background: white; display: flex; align-items: center; gap: 15px;">
                 <div style="width: 40px; height: 40px; border-radius: 10px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: var(--student-primary);">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                 </div>
                 <div>
                    <p style="margin: 0; font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Current Status</p>
                    <p style="margin: 2px 0 0; font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($student_data['status_name'] ?? 'Not Validated'); ?></p>
                 </div>
              </div>
              <div class="student-card" style="padding: 20px; border: 1px solid #e2e8f0; border-radius: 16px; background: white; display: flex; align-items: center; gap: 15px;">
                 <div style="width: 40px; height: 40px; border-radius: 10px; background: #fdf2f8; display: flex; align-items: center; justify-content: center; color: #db2777;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                 </div>
                 <div>
                    <p style="margin: 0; font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Validated On</p>
                    <p style="margin: 2px 0 0; font-weight: 700; color: #1e293b;"><?php echo $student_data['validated_at'] ? date('M d, Y', strtotime($student_data['validated_at'])) : 'Pending'; ?></p>
                 </div>
              </div>
              <?php if($student_data['validated_by']): ?>
              <div class="student-card" style="padding: 20px; border: 1px solid #e2e8f0; border-radius: 16px; background: white; display: flex; align-items: center; gap: 15px;">
                 <div style="width: 40px; height: 40px; border-radius: 10px; background: #f0fdf4; display: flex; align-items: center; justify-content: center; color: #16a34a;">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                 </div>
                 <div>
                    <p style="margin: 0; font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Verified By</p>
                    <p style="margin: 2px 0 0; font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($student_data['validated_by']); ?></p>
                 </div>
              </div>
              <?php endif; ?>
          </div>

          <!-- ── Queue History ── -->
          <div class="student-card" style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0;">
            <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">Queue History</h3>
                <span style="font-size: 0.8rem; color: #64748b; font-weight: 500;"><?php echo count($history); ?> total entries</span>
            </div>
            <div style="padding: 0;">
              <?php if (count($history) > 0): ?>
                <div style="overflow-x: auto;">
                  <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                      <tr style="background: #f8fafc;">
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Date</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Time Slot</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">Queue No.</th>
                        <th style="padding: 15px 20px; color: #64748b; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; text-align: right;">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($history as $row): 
                        $date = new DateTime($row['schedule_date']);
                        $start = new DateTime($row['start_time']);
                        $end = new DateTime($row['end_time']);
                      ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                          <td style="padding: 15px 20px;">
                             <div style="font-weight: 600; color: #1e293b;"><?php echo $date->format('M d, Y'); ?></div>
                             <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo $date->format('l'); ?></div>
                          </td>
                          <td style="padding: 15px 20px; color: #475569; font-size: 0.9rem;"><?php echo $start->format('h:i A') . ' - ' . $end->format('h:i A'); ?></td>
                          <td style="padding: 15px 20px;">
                             <span style="font-weight: 800; color: var(--student-primary); background: rgba(59, 130, 246, 0.1); padding: 4px 8px; border-radius: 6px;">#<?php echo str_pad($row['queue_number'], 3, '0', STR_PAD_LEFT); ?></span>
                          </td>
                          <td style="padding: 15px 20px; text-align: right;">
                            <span class="status-pill <?php echo $row['schedule_status'] == 'active' ? 'validated' : 'not-validated'; ?>" style="font-size: 0.7rem; padding: 4px 10px;">
                              <?php echo ucfirst($row['schedule_status']); ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
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

  <nav class="mobile-nav">
    <a href="student-dashboard.php" class="mobile-nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <rect x="4" y="4" width="16" height="16" rx="2"></rect>
        <line x1="4" y1="9" x2="20" y2="9"></line>
        <line x1="9" y1="10" x2="9" y2="20"></line>
      </svg>
      <span>Dashboard</span>
    </a>
    <a href="book-queue.php" class="mobile-nav-item">
      <svg viewBox="0 0 24 24" fill="currentColor">
        <path fill-rule="evenodd" d="M23,19 C23,20.1045695 22.1045695,21 21,21 L9,21 C7.8954305,21 7,20.1045695 7,19 L7,5 C7,3.8954305 7.8954305,3 9,3 L21,3 C22.1045695,3 23,3.8954305 23,5 L23,19 Z M6,20 C4.8954305,20 4,19.1045695 4,18 L4,6 C4,4.8954305 4.8954305,4 6,4 L6,20 Z M3,19 C1.8954305,19 1,18.1045695 1,17 L1,7 C1,5.8954305 1.8954305,5 3,5 L3,19 Z M21,19 L21,5 L9,5 L9,19 L21,19 Z M13,9 L17,12 L13,15 L13,9 Z"/>
      </svg>
      <span>Booking</span>
    </a>
    <a href="profile.php" class="mobile-nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21C20 18.2386 17.7614 16 15 16H9C6.23858 16 4 18.2386 4 21"/>
        <circle cx="12" cy="8" r="4"/>
      </svg>
      <span>Profile</span>
    </a>
    <a href="../../../server/api/auth/logout.php" class="mobile-nav-item logout-nav-item">
      <svg viewBox="0 0 320.002 320.002" fill="currentColor">
        <path d="M51.213,175.001h173.785c8.284,0,15-6.716,15-15c0-8.284-6.716-15-15-15H51.213l19.394-19.394 c5.858-5.858,5.858-15.355,0-21.213c-5.857-5.858-15.355-5.858-21.213,0L4.396,149.393c-0.351,0.351-0.683,0.719-0.997,1.103 c-0.137,0.167-0.256,0.344-0.385,0.515c-0.165,0.22-0.335,0.435-0.488,0.664c-0.14,0.209-0.261,0.426-0.389,0.64 c-0.123,0.206-0.252,0.407-0.365,0.619c-0.118,0.22-0.217,0.446-0.323,0.67c-0.104,0.219-0.213,0.435-0.306,0.659 c-0.09,0.219-0.164,0.442-0.243,0.664c-0.087,0.24-0.179,0.477-0.253,0.722c-0.067,0.222-0.116,0.447-0.172,0.672 c-0.063,0.249-0.133,0.497-0.183,0.751c-0.051,0.259-0.082,0.521-0.119,0.782c-0.032,0.223-0.075,0.443-0.097,0.669 c-0.048,0.484-0.073,0.971-0.074,1.457c0,0.007-0.001,0.015-0.001,0.022c0,0.007,0.001,0.015,0.001,0.022 c0.001,0.487,0.026,0.973,0.074,1.458c0.022,0.223,0.064,0.44,0.095,0.661c0.038,0.264,0.069,0.528,0.121,0.79 c0.05,0.252,0.119,0.496,0.182,0.743c0.057,0.227,0.107,0.456,0.175,0.681c0.073,0.241,0.164,0.474,0.248,0.71 c0.081,0.226,0.155,0.453,0.247,0.675c0.091,0.22,0.198,0.431,0.3,0.646c0.108,0.229,0.21,0.46,0.33,0.685 c0.11,0.205,0.235,0.4,0.354,0.599c0.131,0.221,0.256,0.444,0.4,0.659c0.146,0.219,0.309,0.424,0.466,0.635 c0.136,0.181,0.262,0.368,0.407,0.544c0.299,0.364,0.616,0.713,0.947,1.048c0.016,0.016,0.029,0.034,0.045,0.05l45,45.001 c2.93,2.929,6.768,4.394,10.607,4.394c3.838-0.001,7.678-1.465,10.606-4.393c5.858-5.858,5.858-15.355,0.001-21.213L51.213,175.001 z"/>
        <path d="M305.002,25h-190c-8.284,0-15,6.716-15,15v60c0,8.284,6.716,15,15,15s15-6.716,15-15V55h160v210.001h-160 v-45.001c0-8.284-6.716-15-15-15s-15,6.716-15,15v60.001c0,8.284,6.716,15,15,15h190c8.284,0,15-6.716,15-15V40 C320.002,31.716,313.286,25,305.002,25z"/>
      </svg>
      <span>Logout</span>
    </a>
  </nav>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="../../scripts/component-loader.js"></script>
</body>

</html>
