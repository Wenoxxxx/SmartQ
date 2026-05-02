<?php
session_start();
if (!isset($_SESSION['student'])) {
  header('Location: ../login.php');
  exit();
}
$user = $_SESSION['student'];
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
  <title>SmartQ | Book Validation</title>
</head>

<body>
  <div class="admin-layout">
    <div data-component="sidebar" data-props='{"active":"book", "role":"student"}'></div>
    <div class="admin-main">
      <div data-component="topbar"
        data-props='{"title":"Book Validation", "description":"Select a schedule to validate your ID."}'></div>
      <main class="admin-content">
        <div class="student-container">
          <!-- ── Booking Hero ── -->
          <div class="student-hero">
            <div class="hero-welcome">
              <h1>Book Validation Slot</h1>
              <p>Choose a convenient date and time to validate your ID.</p>
            </div>
            <div class="status-pill" style="margin-top: 0; background: rgba(255, 255, 255, 0.15);">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                style="margin-right: 8px;">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Real-time Availability
            </div>
          </div>

          <?php
          require_once "../../../server/config/database.php";
          $database = new Database();
          $db = $database->getConnection();

          date_default_timezone_set('Asia/Manila');
          $now = new DateTime();

          try {
            // Check if student is already validated
            $status_query = "SELECT status_id FROM students WHERE student_id = :sid LIMIT 1";
            $status_stmt = $db->prepare($status_query);
            $status_stmt->bindParam(':sid', $user['student_id']);
            $status_stmt->execute();
            $is_validated = ($status_stmt->fetchColumn() == 1);

            if ($is_validated) {
              echo '
                <div class="booking-alert success">
                  <div class="alert-icon">✅</div>
                  <div class="alert-content">
                    <h3>You are already Validated!</h3>
                    <p>Your ID is active. No further validation is required at this time.</p>
                  </div>
                </div>';
            } else {
              // Check if student has ANY active booking
              $active_booking_query = "SELECT 1 FROM queue_list ql 
                                       JOIN queue_schedule qs ON ql.schedule_id = qs.schedule_id 
                                       WHERE ql.student_id = :sid AND qs.status = 'active' AND qs.schedule_date >= CURDATE() 
                                       LIMIT 1";
              $ab_stmt = $db->prepare($active_booking_query);
              $ab_stmt->bindParam(':sid', $user['student_id']);
              $ab_stmt->execute();
              if ($ab_stmt->fetch()) {
                echo '
                  <div class="booking-alert warning">
                    <div class="alert-icon">🕒</div>
                    <div class="alert-content">
                      <h3>Active Booking Found</h3>
                      <p>You have an upcoming validation. Check your dashboard for details.</p>
                    </div>
                  </div>';
              }
            }
            ?>

            <!-- ── Schedule Grid ── -->
            <div class="student-grid" id="booking-grid">
              <?php
              // Fetch only active schedules
              $query = "SELECT qs.*, 
                          (SELECT COUNT(*) FROM queue_list ql WHERE ql.schedule_id = qs.schedule_id) as booked_count
                          FROM queue_schedule qs 
                          WHERE qs.status = 'active' AND qs.schedule_date >= CURDATE()
                          ORDER BY qs.schedule_date ASC, qs.start_time ASC";
              $stmt = $db->prepare($query);
              $stmt->execute();
              $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

              if (count($schedules) > 0) {
                foreach ($schedules as $row) {
                  $date = new DateTime($row['schedule_date']);
                  $day = $date->format('d');
                  $month = $date->format('M');
                  $monthYear = $date->format('F Y');

                  $startTime = new DateTime($row['start_time']);
                  $endTime = new DateTime($row['end_time']);
                  $timeSlot = $startTime->format('h:i A') . ' - ' . $endTime->format('h:i A');

                  $eventEndTime = new DateTime($row['schedule_date'] . ' ' . $row['end_time']);
                  $is_expired = ($now > $eventEndTime);

                  $booked = $row['booked_count'];
                  $limit = $row['slot_limit'];
                  $available = $limit - $booked;
                  $percentage = ($limit > 0) ? ($booked / $limit) * 100 : 0;

                  // Check if student already has a booking
                  $check_query = "SELECT 1 FROM queue_list WHERE student_id = :sid AND schedule_id = :schid";
                  $check_stmt = $db->prepare($check_query);
                  $check_stmt->bindParam(':sid', $user['student_id']);
                  $check_stmt->bindParam(':schid', $row['schedule_id']);
                  $check_stmt->execute();
                  $is_booked = $check_stmt->fetch();

                  $cardClass = $is_expired ? 'expired' : '';
                  $barColor = ($percentage >= 90) ? '#ef4444' : (($percentage >= 70) ? '#f59e0b' : 'var(--student-primary)');

                  echo '
                    <div class="student-card schedule-card premium ' . $cardClass . '">
                      <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
                          <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <div class="date-badge" style="flex-shrink: 0;">
                              <span class="day">' . $day . '</span>
                              <span class="month">' . $month . '</span>
                            </div>
                            <div style="min-width: 0;">
                              <h4 style="margin: 0; font-size: 1.05rem; font-weight: 800; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . $monthYear . '</h4>
                              <p style="margin: 4px 0 0; font-size: 0.8rem; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                ' . $timeSlot . '
                              </p>
                            </div>
                          </div>
                          <span class="status-pill ' . ($available > 10 ? 'validated' : ($available > 0 ? 'pending' : 'not-validated')) . '" style="margin-top: 0; padding: 6px 12px; font-size: 0.75rem; font-weight: 700; white-space: nowrap; flex-shrink: 0; height: fit-content;">
                            ' . ($is_expired ? 'Closed' : ($available . ' Slots')) . '
                          </span>
                        </div>

                        <div class="progress-section">
                          <div class="progress-header">
                            <span class="progress-label">Availability Status</span>
                            <span class="progress-value">' . $booked . ' / ' . $limit . ' booked</span>
                          </div>
                          <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: ' . $percentage . '%; background: ' . ($is_expired ? '#cbd5e1' : $barColor) . ';"></div>
                          </div>
                        </div>
                      </div>

                      <div class="card-footer">';

                  if ($is_booked) {
                    echo '<button disabled style="width: 100%; background: #f0fdf4; color: #16a34a; border: 1px solid #bdf4d4; padding: 12px; border-radius: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Booking Confirmed
                          </button>';
                  } elseif ($is_expired) {
                    echo '<button disabled style="width: 100%; background: #f1f5f9; color: #94a3b8; border: none; padding: 12px; border-radius: 12px; font-weight: 700;">Closed</button>';
                  } elseif ($available <= 0) {
                    echo '<button disabled style="width: 100%; background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; padding: 12px; border-radius: 12px; font-weight: 700;">Fully Booked</button>';
                  } elseif ($is_validated) {
                    echo '<button disabled style="width: 100%; background: #f1f5f9; color: #94a3b8; border: none; padding: 12px; border-radius: 12px; font-weight: 700;">Validated</button>';
                  } else {
                    echo '<button class="btn-book-now" data-id="' . $row['schedule_id'] . '" style="width: 100%; background: var(--student-primary); color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                            Book This Slot
                          </button>';
                  }

                  echo '</div></div>';
                }
              } else {
                echo '<div style="grid-column: 1/-1; text-align: center; padding: 80px 20px; background: white; border-radius: 32px; border: 2px dashed #e2e8f0; animation: slideDown 0.5s ease-out;">
                        <div style="font-size: 5rem; margin-bottom: 24px;"></div>
                        <h3 style="color: #1e293b; margin-bottom: 12px; font-size: 1.6rem; font-weight: 800;">No Schedules Today</h3>
                        <p style="color: #64748b; font-size: 1.1rem; max-width: 400px; margin: 0 auto;">Validation slots are currently closed. Please check again tomorrow morning for updates.</p>
                      </div>';
              }
          } catch (Exception $e) {
            echo '<div style="grid-column: 1/-1;" class="booking-alert warning">Error: ' . $e->getMessage() . '</div>';
          }
          ?>
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
    <a href="book-queue.php" class="mobile-nav-item active">
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
      // Handle Booking
      $('.btn-book-now').click(function () {
        const scheduleId = $(this).data('id');
        const $btn = $(this);

        if (confirm('Are you sure you want to book this validation slot?')) {
          $btn.prop('disabled', true).text('Booking...');

          $.ajax({
            url: '../../../server/api/queue/book_schedule.php',
            method: 'POST',
            data: { schedule_id: scheduleId },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                alert('Success! Your queue number is: ' + response.queue_number);
                location.href = 'student-dashboard.php';
              } else {
                alert('Error: ' + response.message);
                $btn.prop('disabled', false).text('Book This Slot');
              }
            },
            error: function () {
              alert('Failed to connect to the server.');
              $btn.prop('disabled', false).text('Book This Slot');
            }
          });
        }
      });
    });
  </script>
</body>

</html>