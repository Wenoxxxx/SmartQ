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

// Always fetch the latest status from the database to reflect admin changes immediately
$query = "SELECT status_id FROM students WHERE student_id = :sid LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(':sid', $student_id);
$stmt->execute();
$db_status = $stmt->fetch(PDO::FETCH_ASSOC);

$status_id = $db_status['status_id'] ?? 2; // Default to Not Validated
$name = $user['first_name'] . ' ' . $user['last_name'];

// ── Fetch Active Booking Data ──
$booking = null;
try {
  $q_booking = "SELECT ql.queue_number, ql.schedule_id, qs.schedule_date, qs.start_time, qs.end_time, qs.current_number 
                FROM queue_list ql
                JOIN queue_schedule qs ON ql.schedule_id = qs.schedule_id
                WHERE ql.student_id = :sid AND qs.status = 'active' AND qs.schedule_date >= CURDATE() AND ql.deleted_at IS NULL
                ORDER BY qs.schedule_date ASC LIMIT 1";
  $stmt_b = $db->prepare($q_booking);
  $stmt_b->bindParam(':sid', $user['student_id']);
  $stmt_b->execute();
  $booking = $stmt_b->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  // Silent fail
}

// Map status to labels and classes
$status_map = [
  1 => ['label' => 'Validated', 'class' => 'validated'],
  2 => ['label' => 'Not Validated', 'class' => 'not-validated'],
  3 => ['label' => 'Pending Review', 'class' => 'pending']
];
$current_status = $status_map[$status_id] ?? $status_map[2];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../../assets/logo/sq.png">

  <!-- Component Loader Meta -->
  <meta name="component-base" content="../../components/">

  <!-- Stylesheets -->
  <link rel="stylesheet" href="../../assets/css/main.css">
  <link rel="stylesheet" href="../../assets/css/components/components.css">
  <link rel="stylesheet" href="../../assets/css/users/student-dashboard.css">

  <title>SmartQ | My Dashboard</title>
</head>

<body>

  <div class="admin-layout">
    <!-- Sidebar (Desktop) -->
    <div data-component="sidebar" data-props='{"active":"dashboard", "role":"student"}'></div>

    <div class="admin-main">
      <!-- Topbar -->
      <div data-component="topbar"
        data-props='{"title":"Student Dashboard"}'></div>

      <main class="admin-content">
        <div class="student-container">

          <!-- ── Hero / Status ── -->
          <div class="student-hero">
            <!-- Decorative Background Element -->
            <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; filter: blur(60px); pointer-events: none;"></div>

            <div class="hero-welcome">
              <h1>Welcome back, <span><?php echo htmlspecialchars($user['first_name']); ?>!</span></h1>
              <p>Your digital gateway to campus services. Keep your ID validated for full access.</p>
            </div>

            <div class="hero-status-card">
              <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: <?php echo $status_id == 1 ? 'rgba(34, 197, 94, 0.1)' : 'rgba(245, 158, 11, 0.1)'; ?>; display: flex; align-items: center; justify-content: center;">
                  <?php if ($status_id == 1): ?>
                    <svg width="24" height="24" fill="none" stroke="#22c55e" stroke-width="2.5" viewBox="0 0 24 24">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  <?php elseif ($status_id == 3): ?>
                    <svg width="24" height="24" fill="none" stroke="#f59e0b" stroke-width="2.5" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                  <?php else: ?>
                    <svg width="24" height="24" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24">
                      <line x1="18" y1="6" x2="6" y2="18"></line>
                      <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                  <?php endif; ?>
                </div>
                <div>
                  <span style="display: block; font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Student Status</span>
                  <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">
                    <?php echo $current_status['label']; ?>
                  </h3>
                </div>
              </div>
              <div style="height: 6px; background: #f1f5f9; border-radius: 3px; margin-bottom: 10px; overflow: hidden;">
                <div style="width: <?php echo $status_id == 1 ? '100%' : ($status_id == 3 ? '50%' : '15%'); ?>; height: 100%; background: <?php echo $status_id == 1 ? '#22c55e' : ($status_id == 3 ? '#f59e0b' : '#ef4444'); ?>; transition: width 1s ease;"></div>
              </div>
              <p style="margin: 0; font-size: 0.75rem; color: #64748b; font-weight: 500;">
                <?php echo $status_id == 1 ? 'Validated & Ready' : ($status_id == 3 ? 'Verification in progress' : 'Validation required'); ?>
              </p>
            </div>
          </div>

          <!-- ── Quick Stats ── -->
          <div class="student-stats-grid">
            <?php if (isset($booking['queue_number'])): ?>
              <div data-component="stat-card"
                data-props='{"label":"Queue Number", "value":"#<?php echo str_pad($booking['queue_number'], 3, '0', STR_PAD_LEFT); ?>"}'>
              </div>
              <div data-component="stat-card"
                data-props='{"label":"Appointment", "value":"<?php echo date('h:i A', strtotime($booking['start_time'])); ?>"}'>
              </div>
            <?php else: ?>
              <div data-component="stat-card" data-props='{"label":"Active Booking", "value":"None"}'>
              </div>
            <?php endif; ?>
          </div>

          <!-- ── Active Queue (If booked) ── -->
          <?php
            if ($booking):
              $bDate = new DateTime($booking['schedule_date']);
              $bStart = new DateTime($booking['start_time']);
              $bEnd = new DateTime($booking['end_time']);

              $myNum = (int) $booking['queue_number'];
              $servingNum = (int) $booking['current_number'];

              $notifMsg = "";
              $notifClass = "";

              if ($status_id == 1) { // 1 = Validated
                $notifMsg = "You are validated! Your ID is now active.";
                $notifClass = "notif-now";
              } else if ($servingNum == 0) {
                $notifMsg = "Waiting for validation to start.";
              } else if ($myNum == $servingNum) {
                $notifMsg = "It's your turn! Please proceed to the counter.";
                $notifClass = "notif-now";
              } else if ($myNum == $servingNum + 1) {
                $notifMsg = "You are next! Please prepare your documents.";
                $notifClass = "notif-next";
              } else if ($myNum > $servingNum) {
                $ahead = $myNum - $servingNum;
                $notifMsg = "Currently serving: No. " . $servingNum . " (" . $ahead . " " . ($ahead == 1 ? 'person' : 'people') . " ahead)";
              } else {
                $notifMsg = "Your number has passed.";
              }
              ?>
              <div class="queue-highlight">
                <div class="queue-content">
                  <div class="queue-main">
                    <div class="queue-num-box">
                      <span style="font-size: 0.75rem; letter-spacing: 0.05em;">NO.</span>
                      <span style="font-size: 1.75rem; line-height: 1;"><?php echo $myNum; ?></span>
                    </div>
                    <div class="queue-details">
                      <h4 style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 6px;">Active Queue Booking</h4>
                      <div style="display: flex; flex-direction: column; gap: 4px;">
                        <p style="font-size: 0.85rem; font-weight: 600; color: #64748b; margin: 0; display: flex; align-items: center; gap: 6px;">
                          <i class="fas fa-calendar-alt"></i> <?php echo $bDate->format('F d, Y'); ?>
                        </p>
                        <p style="font-size: 0.85rem; font-weight: 600; color: #94a3b8; margin: 0; display: flex; align-items: center; gap: 6px;">
                          <i class="fas fa-clock"></i> <?php echo $bStart->format('h:i A') . ' - ' . $bEnd->format('h:i A'); ?>
                        </p>
                      </div>
                    </div>
                  </div>
                  <?php if ($status_id != 1): ?>
                    <button class="btn-cancel-booking" data-id="<?php echo $booking['schedule_id']; ?>" style="padding: 10px 20px; border-radius: 12px; font-weight: 700;">
                      Cancel Booking
                    </button>
                  <?php endif; ?>
                </div>

                <!-- Queue Status Notification -->
                <div class="queue-notif <?php echo $notifClass; ?>">
                  <div class="notif-dot"></div>
                  <span><?php echo $notifMsg; ?></span>
                </div>
              </div>
              <?php
            endif;
          ?>

          <!-- ── Action Grid ── -->
          <div class="student-grid">

            <!-- Book Validation -->
            <div class="student-card">
              <div class="card-icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
              </div>
              <h3 class="card-title">Book Validation</h3>
              <p class="card-desc">Check available time slots and book your validation schedule to avoid long lines.</p>
              <a href="book-queue.php" class="btn-student">Browse Slots</a>
            </div>

            <!-- My History -->
            <div class="student-card">
              <div class="card-icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M12 8v4l3 3"></path>
                  <circle cx="12" cy="12" r="9"></circle>
                </svg>
              </div>
              <h3 class="card-title">My History</h3>
              <p class="card-desc">View your previous validation logs and queue history for this semester.</p>
              <a href="my-history.php" class="btn-student">View History</a>
            </div>

            <!-- Profile Settings -->
            <div class="student-card">
              <div class="card-icon">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </div>
              <h3 class="card-title">My Profile</h3>
              <p class="card-desc">Update your personal information and ensure your email is verified.</p>
              <a href="profile.php" class="btn-student">Edit Profile</a>
            </div>

          </div>

        </div>
      </main>

      <div data-component="footer"></div>
    </div>
  </div>

  <!-- ── Mobile Bottom Navigation ── -->
  <nav class="mobile-nav">
    <a href="student-dashboard.php" class="mobile-nav-item active">
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
  <script>
    $(document).ready(function () {
      // ── Live Queue Polling ──
      function updateQueueStatus() {
        $.ajax({
          url: '../../../server/api/queue/get_active_booking.php',
          method: 'GET',
          dataType: 'json',
          success: function (response) {
            if (response.success && response.data) {
              const data = response.data;
              const myNum = parseInt(data.queue_number);
              const servingNum = parseInt(data.current_number);
              const $notif = $('.queue-notif');
              const $notifText = $notif.find('span');
              
              let msg = "";
              let statusClass = "";

              if (servingNum === 0) {
                msg = "Waiting for validation to start.";
              } else if (myNum === servingNum) {
                msg = "It's your turn! Please proceed to the counter.";
                statusClass = "notif-now";
              } else if (myNum === servingNum + 1) {
                msg = "You are next! Please prepare your documents.";
                statusClass = "notif-next";
              } else if (myNum > servingNum) {
                const ahead = myNum - servingNum;
                msg = "Currently serving: No. " + servingNum + " (" + ahead + " " + (ahead === 1 ? 'person' : 'people') + " ahead)";
              } else {
                msg = "Your number has passed.";
              }

              $notifText.text(msg);
              $notif.removeClass('notif-now notif-next').addClass(statusClass);
            }
          }
        });
      }

      // Poll every 10 seconds
      if ($('.queue-highlight').length > 0) {
        setInterval(updateQueueStatus, 10000);
      }

      $('.btn-cancel-booking').click(function () {
        const scheduleId = $(this).data('id');
        const $btn = $(this);

        if (confirm('Are you sure you want to cancel your booking?')) {
          $btn.prop('disabled', true).text('Cancelling...');

          $.ajax({
            url: '../../../server/api/queue/cancel_booking.php',
            method: 'POST',
            data: { schedule_id: scheduleId },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                alert('Booking cancelled successfully.');
                location.reload();
              } else {
                alert('Error: ' + response.message);
                $btn.prop('disabled', false).text('Cancel');
              }
            },
            error: function () {
              alert('Failed to connect to the server.');
              $btn.prop('disabled', false).text('Cancel');
            }
          });
        }
      });
    });
  </script>

</body>

</html>