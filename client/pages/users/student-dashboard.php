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
                WHERE ql.student_id = :sid AND qs.status = 'active' AND qs.schedule_date >= CURDATE() AND ql.deleted_at IS NULL AND qs.deleted_at IS NULL
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
      <div data-component="topbar" data-props='{"title":"Student Dashboard"}'></div>

      <main class="admin-content">
        <div class="student-container">

          <!-- ── Hero / Status ── -->
          <div class="student-hero">
            <!-- Decorative Background Element -->
            <div
              style="position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; filter: blur(60px); pointer-events: none;">
            </div>

            <div class="hero-welcome">
              <h1>Welcome back, <span><?php echo htmlspecialchars($user['first_name']); ?>!</span></h1>
              <p>Your digital gateway to campus services. Keep your ID validated for full access.</p>
            </div>

            <div class="hero-status-card">
              <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <div
                  style="width: 48px; height: 48px; border-radius: 12px; background: <?php echo $status_id == 1 ? 'rgba(34, 197, 94, 0.1)' : 'rgba(245, 158, 11, 0.1)'; ?>; display: flex; align-items: center; justify-content: center;">
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
                  <span
                    style="display: block; font-size: 0.7rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Student
                    Status</span>
                  <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">
                    <?php echo $current_status['label']; ?>
                  </h3>
                </div>
              </div>
              <div style="height: 6px; background: #f1f5f9; border-radius: 3px; margin-bottom: 10px; overflow: hidden;">
                <div
                  style="width: <?php echo $status_id == 1 ? '100%' : ($status_id == 3 ? '50%' : '15%'); ?>; height: 100%; background: <?php echo $status_id == 1 ? '#22c55e' : ($status_id == 3 ? '#f59e0b' : '#ef4444'); ?>; transition: width 1s ease;">
                </div>
              </div>
              <p style="margin: 0; font-size: 0.75rem; color: #64748b; font-weight: 500;">
                <?php echo $status_id == 1 ? 'Validated & Ready' : ($status_id == 3 ? 'Verification in progress' : 'Validation required'); ?>
              </p>
            </div>
          </div>

          <!-- ── Active Queue Booking ── -->
          <?php
          if ($booking):
            $bDate = new DateTime($booking['schedule_date']);
            $bStart = new DateTime($booking['start_time']);
            $bEnd = new DateTime($booking['end_time']);

            $myNum = (int) $booking['queue_number'];
            $servingNum = (int) $booking['current_number'];

            $notifMsg = "";
            $notifClass = "";

            if ($status_id == 1) {
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
            <div class="booking-card">
              <!-- Top: Queue number + details -->
              <div class="booking-card-top">
                <div class="booking-queue-number">
                  <span class="queue-label">QUEUE</span>
                  <span class="queue-value"><?php echo $myNum; ?></span>
                </div>

                <div class="booking-info">
                  <h4 class="booking-title">Active Queue Booking</h4>
                  <div class="booking-meta">
                    <div class="booking-meta-item">
                      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                      </svg>
                      <span><?php echo $bDate->format('F d, Y'); ?></span>
                    </div>
                    <div class="booking-meta-item">
                      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                      </svg>
                      <span><?php echo $bStart->format('h:i A') . ' – ' . $bEnd->format('h:i A'); ?></span>
                    </div>
                  </div>
                </div>

                <?php if ($status_id != 1): ?>
                  <button class="btn-cancel-booking" data-id="<?php echo $booking['schedule_id']; ?>">
                    Cancel
                  </button>
                <?php endif; ?>
              </div>

              <!-- Bottom: Status notification -->
              <div class="booking-card-status <?php echo $notifClass; ?>">
                <div class="notif-dot"></div>
                <span><?php echo $notifMsg; ?></span>
              </div>
            </div>
            <?php
          else:
          ?>
            <!-- No Active Booking -->
            <div class="booking-card booking-card-empty">
              <div class="booking-empty-icon">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
              </div>
              <div class="booking-empty-text">
                <h4>No Active Booking</h4>
                <p>You don't have a queue booking yet. Book a validation slot to get started.</p>
              </div>
              <a href="book-queue.php" class="btn-book-now">Book Now</a>
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

  <!-- Mobile Bottom Navigation -->
  <div data-component="mobile-nav" data-props='{"active":"dashboard"}'></div>

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
              const $notif = $('.booking-card-status');
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
      if ($('.booking-card').length > 0) {
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