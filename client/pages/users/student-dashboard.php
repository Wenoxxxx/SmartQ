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
        data-props='{"title":"Student Dashboard", "description":"Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!"}'>
      </div>

      <main class="admin-content">
        <div class="student-container">

          <!-- ── Hero / Status ── -->
          <div class="student-hero">
            <!-- Decorative Background Element -->
            <div style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; filter: blur(60px); pointer-events: none;"></div>

            <div class="hero-welcome">
              <h1>
                Welcome back,
                <span><?php echo htmlspecialchars($user['first_name']); ?></span>!
              </h1>
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
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
        </path>
      </svg>
      <span>Dashboard</span>
    </a>
    <a href="book-queue.php" class="mobile-nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
      </svg>
      <span>Booking</span>
    </a>
    <a href="profile.php" class="mobile-nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
      </svg>
      <span>Profile</span>
    </a>
    <a href="../../../server/api/auth/logout.php" class="mobile-nav-item logout-nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
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