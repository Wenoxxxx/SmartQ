<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: ../login.php');
  exit();
}
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
  <link rel="stylesheet" href="../../assets/css/components/navigation.css">
  <link rel="stylesheet" href="../../assets/css/admin/queue.css">

  <title>SmartQ | Queue Management</title>
</head>

<body>

  <div class="admin-layout">
    <!-- Sidebar -->
    <div data-component="sidebar" data-props='{"active":"queue"}'></div>

    <div class="admin-main">
      <!-- Topbar -->
      <div data-component="topbar"
        data-props='{"title":"Queue Management","description":"Create and manage ID validation schedules." }'></div>

      <main class="admin-content">
        <?php
        date_default_timezone_set('Asia/Manila');
        require_once "../../../server/config/database.php";
        $database = new Database();
        $db = $database->getConnection();

        // Fetch colleges for filter
        $collegesQuery = "SELECT * FROM colleges ORDER BY college_name ASC";
        $collegesStmt = $db->prepare($collegesQuery);
        $collegesStmt->execute();
        $allColleges = $collegesStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="queue-container">

          <?php
          $showArchived = isset($_GET['view']) && $_GET['view'] === 'archived';

          // Count active schedules (excluding archived/deleted)
          $activeCount = $db->query("SELECT COUNT(*) FROM queue_schedule WHERE status = 'active' AND deleted_at IS NULL")->fetchColumn() ?: 0;

          // Total slots (active/closed only)
          $totalSlots = $db->query("SELECT SUM(slot_limit) AS total_slots FROM queue_schedule WHERE deleted_at IS NULL")
            ->fetch(PDO::FETCH_ASSOC)['total_slots'] ?? 0;

          // Students booked (active/closed only)
          $studentsBooked = $db->query("SELECT COUNT(student_id) AS students_booked FROM queue_list WHERE deleted_at IS NULL")
            ->fetch(PDO::FETCH_ASSOC)['students_booked'] ?? 0;

          // Remaining
          $remaining = $totalSlots - $studentsBooked;
          ?>

          <!-- ── Header ── -->
          <header class="queue-header">
            <div class="queue-title">
              <h2><?= $showArchived ? 'Archived Schedules' : 'Active Schedules' ?></h2>
              <p>
                <?= $showArchived ? 'Viewing historical data and past validation events.' : 'You have ' . $activeCount . ' active validation schedule' . ($activeCount != 1 ? 's' : '') . ' currently.' ?>
              </p>
            </div>
            <div class="header-actions" style="display: flex; gap: 12px; align-items: center;">
              <?php if ($showArchived): ?>
                <a href="?view=active" class="btn-nav-back">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                  </svg>
                  Back to Active
                </a>
              <?php else: ?>
                <a href="?view=archived" class="btn-nav-back">
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                  </svg>
                  View Archives
                </a>
                <button class="btn-add-schedule" id="openModal">
                  <span>+</span> Create Schedule
                </button>
              <?php endif; ?>
            </div>
          </header>

          <div class="queue-stats-grid">
            <div data-component="stat-card" data-props='{"label":"Total Slots","value":"<?= $totalSlots ?>"}'></div>
            <div data-component="stat-card" data-props='{"label":"Students Booked","value":"<?= $studentsBooked ?>"}'>
            </div>
            <div data-component="stat-card" data-props='{"label":"Remaining","value":"<?= $remaining ?>"}'></div>
          </div>

          <!-- ── Schedule Grid ── -->
          <div class="schedule-grid" id="schedule-grid">
            <?php
            $showArchived = isset($_GET['view']) && $_GET['view'] === 'archived';

            try {
              $whereClause = $showArchived ? "qs.deleted_at IS NOT NULL" : "qs.deleted_at IS NULL";
              // Fetch schedules
              $query = "SELECT qs.*, 
                          (SELECT COUNT(*) FROM queue_list ql WHERE ql.schedule_id = qs.schedule_id AND ql.deleted_at IS NULL) as booked_count
                          FROM queue_schedule qs 
                          WHERE $whereClause
                          ORDER BY (CASE WHEN qs.status = 'active' THEN 0 ELSE 1 END), qs.schedule_date DESC, qs.start_time DESC";
              $stmt = $db->prepare($query);
              $stmt->execute();
              $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

              if (count($schedules) > 0) {
                foreach ($schedules as $row) {
                  $date = new DateTime($row['schedule_date']);
                  $day = $date->format('d');
                  $monthYear = $date->format('F Y');

                  $startTime = new DateTime($row['start_time']);
                  $endTime = new DateTime($row['end_time']);
                  $timeSlot = $startTime->format('h:i A') . ' - ' . $endTime->format('h:i A');

                  $booked = $row['booked_count'];
                  $limit = $row['slot_limit'];
                  $percentage = ($limit > 0) ? ($booked / $limit) * 100 : 0;

                  $status = $row['status'] ?? 'active';
                  $currentTime = new DateTime();
                  $eventEndTime = new DateTime($row['schedule_date'] . ' ' . $row['end_time']);

                  // Auto-close if time has passed
                  if ($status === 'active' && $currentTime > $eventEndTime) {
                    $status = 'closed';
                  }

                  $statusClass = 'status-active';
                  $statusText = 'Active';

                  if ($status === 'cancelled') {
                    $statusClass = 'status-full'; // Red
                    $statusText = 'Cancelled';
                  } elseif ($status === 'closed') {
                    $statusClass = 'status-closed'; // Grey
                    $statusText = 'Closed';
                  } elseif ($percentage >= 100) {
                    $statusClass = 'status-full';
                    $statusText = 'Full';
                  } elseif ($percentage >= 80) {
                    $statusClass = 'status-full';
                    $statusText = 'Almost Full';
                  }

                  echo '
                        <div class="schedule-card ' . ($status === 'cancelled' || $status === 'closed' ? 'cancelled' : '') . '" data-id="' . $row['schedule_id'] . '">
                          <div class="schedule-card-header">
                            <div class="schedule-date">
                              <span class="date-day">' . $day . '</span>
                              <span class="date-month">' . $monthYear . '</span>
                            </div>
                            <span class="schedule-status ' . $statusClass . '">' . $statusText . '</span>
                          </div>
                          <div class="schedule-info">
                            <div class="info-item">
                              <span class="info-label">Time Slot</span>
                              <span class="info-value">' . $timeSlot . '</span>
                            </div>
                            <div class="info-item">
                              <span class="info-label">Catered</span>
                              <span class="info-value">' . $limit . ' Students</span>
                            </div>
                          </div>
                          <div class="schedule-progress">
                            <div class="progress-labels">
                              <span>Booking Progress</span>
                              <span>' . $booked . ' / ' . $limit . '</span>
                            </div>
                            <div class="progress-track">
                              <div class="progress-bar" style="width: ' . $percentage . '%; ' . ($percentage >= 90 ? 'background: var(--warning);' : '') . ' ' . ($status === 'cancelled' || $status === 'closed' ? 'background: #cbd5e1;' : '') . '"></div>
                            </div>
                          </div>
                          <div class="schedule-actions" style="gap: 10px; flex-wrap: wrap;">
'; ?>
                  <?php
                  if ($status === 'active') {
                    if (!$showArchived) {
                      echo '<div style="display: flex; flex-direction: column; width: 100%; gap: 10px;">
                              <div style="display: flex; gap: 10px; width: 100%;">
                                <a href="manage-queue.php?id=' . $row['schedule_id'] . '" class="btn-manage" style="text-align: center; text-decoration: none; flex: 1; min-width: 100px; display: flex; align-items: center; justify-content: center;">Manage</a>
                                <button class="btn-archive-schedule" data-id="' . $row['schedule_id'] . '" style="background: #f1f5f9; color: #64748b; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; flex: 1; min-width: 100px;">Archive</button>
                              </div>
                              <button class="btn-cancel-schedule" data-id="' . $row['schedule_id'] . '" style="width: 100%; background: #fee2e2; color: #ef4444; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;">Cancel Schedule</button>
                            </div>';
                    }
                  } elseif ($status === 'cancelled' || $status === 'closed') {
                    echo '<div style="display: flex; flex-direction: column; width: 100%; gap: 10px;">
                                    <div style="display: flex; gap: 10px; width: 100%;">
                                      <button class="btn-download btn-open-report-modal" data-id="' . $row['schedule_id'] . '" style="background: #dcfce7; color: #16a34a; border: none; text-align: center; padding: 10px; border-radius: 8px; font-weight: 600; flex: 1; cursor: pointer;">Report</button>';

                    if (!$showArchived) {
                      echo '<button class="btn-archive-schedule" data-id="' . $row['schedule_id'] . '" style="background: #f1f5f9; color: #64748b; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; flex: 1;">Archive</button>';
                    }

                    echo '</div>';

                    if ($showArchived) {
                      echo '<div style="display: flex; gap: 10px; width: 100%;">
                              <button class="btn-restore-schedule" data-id="' . $row['schedule_id'] . '" style="background: #f0f9ff; color: #1c5adf; border: 1px solid #1c5adf30; padding: 10px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; flex: 2;">Restore</button>
                              <button class="btn-delete-permanent" data-id="' . $row['schedule_id'] . '" style="background: #fff1f2; color: #e11d48; border: 1px solid #e11d4820; padding: 10px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; flex: 1;" title="Delete Permanently">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: block; margin: 0 auto;">
                                  <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                              </button>
                            </div>';
                    } else {
                      echo '<button disabled style="width: 100%; background: #f1f5f9; color: #94a3b8; border: none; padding: 10px; border-radius: 8px; font-weight: 600;">Schedule ' . ucfirst($status) . '</button>';
                    }

                    echo '</div>';
                  }


                  echo '</div>
                        </div>';
                }
              } else {
                echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--text-muted);">No schedules created yet.</div>';
              }
            } catch (Exception $e) {
              echo '<div style="grid-column: 1/-1; color: var(--error);">Error loading schedules: ' . $e->getMessage() . '</div>';
            }
            ?>
          </div>
        </div>

        <!-- ── Download Filter Modal ── -->
        <div id="download-modal" class="modal-overlay">
          <div class="modal-backdrop download-modal-close"></div>
          <div class="modal-card" style="max-width: 400px; text-align: center;">
            <div class="modal-icon" style="background: rgba(28, 90, 223, 0.1);">
              <svg width="24" height="24" fill="none" stroke="#1c5adf" stroke-width="2.5" viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
              </svg>
            </div>
            <h3 class="modal-title">Download Options</h3>
            <p class="modal-desc">Select filters for the student list export.</p>

            <form id="download-form" class="download-form" action="../../../server/api/events/download_report.php" method="GET">
              <input type="hidden" name="id" id="download-schedule-id" value="">
              
              <div class="filter-group">
                <label>Filter by College</label>
                <select name="college_id" class="filter-select">
                  <option value="">All Colleges</option>
                  <?php foreach ($allColleges as $college): ?>
                    <option value="<?= $college['college_id'] ?>"><?= htmlspecialchars($college['college_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="filter-group">
                <label>Filter by Year Level</label>
                <select name="yearlvl" class="filter-select">
                  <option value="">All Years</option>
                  <option value="1">1st Year</option>
                  <option value="2">2nd Year</option>
                  <option value="3">3rd Year</option>
                  <option value="4">4th Year</option>
                </select>
              </div>

              <label class="checkbox-group" style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #475569; font-weight: 600; cursor: pointer; margin-top: 4px; text-align: left;">
                <input type="checkbox" name="sort_surname" value="1" style="width: 16px; height: 16px;">
                <span>Sort alphabetically (Surname)</span>
              </label>

              <div class="modal-actions" style="margin-top: 20px;">
                <button type="button" class="modal-btn-cancel download-modal-close">Cancel</button>
                <button type="submit" class="modal-btn-confirm" style="background: #1c5adf;">Download CSV</button>
              </div>
            </form>
          </div>
        </div>

        <style>
          /* Download Modal Specific */
          .download-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: left;
            margin-top: 20px;
          }

          .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
          }

          .filter-group label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.02em;
          }

          .filter-select {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: #1e293b;
            outline: none;
          }
        </style>
      </main>

      <div data-component="footer"></div>
    </div>
  </div>

  <!-- ── Create Schedule Modal ── -->
  <div class="schedule-modal" id="scheduleModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Create New Schedule</h3>
        <button class="btn-close" id="closeModal">&times;</button>
      </div>
      <form class="schedule-form" id="createScheduleForm">
        <div class="form-group">
          <label>Validation Date</label>
          <input type="date" name="date" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Start Time</label>
            <input type="time" name="start_time" required>
          </div>
          <div class="form-group">
            <label>End Time</label>
            <input type="time" name="end_time" required>
          </div>
        </div>
        <div class="form-group">
          <label>Max Students to Cater</label>
          <input type="number" name="slot_limit" placeholder="e.g. 100" required>
        </div>
        <button type="submit" class="btn-add-schedule" id="btn-save-schedule"
          style="width: 100%; justify-content: center; margin-top: 10px;">
          Save Schedule
        </button>
      </form>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="../../scripts/component-loader.js"></script>

  <script>
    $(document).ready(function () {
      // Modal Logic
      const modal = $('#scheduleModal');
      $('#openModal').click(() => modal.css('display', 'flex'));
      $('#closeModal').click(() => modal.hide());

      $(window).click((e) => {
        if (e.target == modal[0]) modal.hide();
      });

      // Handle Form Submit
      $('#createScheduleForm').submit(function (e) {
        e.preventDefault();

        const $btn = $('#btn-save-schedule');
        const formData = $(this).serialize();

        $btn.prop('disabled', true).text('Saving...');

        $.ajax({
          url: '../../../server/api/events/create_schedule.php',
          method: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              alert('Success: ' + response.message);
              location.reload(); // Reload to see the new schedule
            } else {
              alert('Error: ' + response.message);
              $btn.prop('disabled', false).text('Save Schedule');
            }
          },
          error: function () {
            alert('Failed to connect to the server.');
            $btn.prop('disabled', false).text('Save Schedule');
          }
        });
      });

      // Handle Cancel Schedule
      $(document).on('click', '.btn-cancel-schedule', function () {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to cancel this schedule? This action cannot be undone.')) {
          const $btn = $(this);
          $btn.prop('disabled', true).text('...');

          $.ajax({
            url: '../../../server/api/events/cancel_schedule.php',
            method: 'POST',
            data: { schedule_id: id },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
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

      // Handle Archive Schedule
      $(document).on('click', '.btn-archive-schedule', function () {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to ARCHIVE this schedule? It will be moved to history but data remains safe.')) {
          const $btn = $(this);
          $btn.prop('disabled', true).text('...');

          $.ajax({
            url: '../../../server/api/events/delete_schedule.php',
            method: 'POST',
            data: { schedule_id: id },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                location.reload();
              } else {
                alert('Error: ' + response.message);
                $btn.prop('disabled', false).text('Archive');
              }
            },
            error: function () {
              alert('Failed to connect to the server.');
              $btn.prop('disabled', false).text('Archive');
            }
          });
        }
      });

      // Download Modal Logic
      $(document).on('click', '.btn-open-report-modal', function() {
        const id = $(this).data('id');
        $('#download-schedule-id').val(id);
        $('#download-modal').css('display', 'flex');
      });

      $('.download-modal-close').on('click', function() {
        $('#download-modal').fadeOut(150);
      });

      $('#download-form').on('submit', function() {
        $('#download-modal').fadeOut(150);
      });

      // Handle Restore Schedule
      $(document).on('click', '.btn-restore-schedule', function () {
        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).text('Restoring...');

        $.ajax({
          url: '../../../server/api/events/restore_schedule.php',
          method: 'POST',
          data: { schedule_id: id },
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              location.reload();
            } else {
              alert('Error: ' + response.message);
              $btn.prop('disabled', false).text('Restore Schedule');
            }
          },
          error: function () {
            alert('Failed to connect to the server.');
            $btn.prop('disabled', false).text('Restore Schedule');
          }
        });
      });

      // Handle Permanent Delete Schedule
      $(document).on('click', '.btn-delete-permanent', function () {
        const id = $(this).data('id');
        if (confirm('WARNING: Are you sure you want to PERMANENTLY delete this schedule and all its student list? This action cannot be undone and will be removed from the database.')) {
          const $btn = $(this);
          $btn.prop('disabled', true).html('...');

          $.ajax({
            url: '../../../server/api/events/permanent_delete_schedule.php',
            method: 'POST',
            data: { schedule_id: id },
            dataType: 'json',
            success: function (response) {
              if (response.success) {
                location.reload();
              } else {
                alert('Error: ' + response.message);
                $btn.prop('disabled', false).html('<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: block; margin: 0 auto;"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>');
              }
            },
            error: function () {
              alert('Failed to connect to the server.');
              $btn.prop('disabled', false).html('<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: block; margin: 0 auto;"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>');
            }
          });
        }
      });
    });
  </script>

</body>

</html>