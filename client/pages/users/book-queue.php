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
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
                          <div style="display: flex; align-items: center; gap: 16px;">
                            <div class="date-badge">
                              <span class="day">' . $day . '</span>
                              <span class="month">' . $month . '</span>
                            </div>
                            <div>
                              <h4 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: #1e293b;">' . $monthYear . '</h4>
                              <p style="margin: 4px 0 0; font-size: 0.85rem; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                ' . $timeSlot . '
                              </p>
                            </div>
                          </div>
                          <span class="status-pill ' . ($available > 10 ? 'validated' : ($available > 0 ? 'pending' : 'not-validated')) . '" style="margin-top: 0;">
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
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
        </path>
      </svg>
      <span>Dashboard</span>
    </a>
    <a href="book-queue.php" class="mobile-nav-item active">
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