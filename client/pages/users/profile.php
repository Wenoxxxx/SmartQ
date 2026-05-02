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
$fullName = strtoupper($user['first_name'] . ' ' . $user['last_name']);
$initial = strtoupper(substr($user['first_name'], 0, 1));
$studentId = $user['student_id'];

// Fetch colleges for the dropdown
$college_query = "SELECT * FROM colleges ORDER BY college_name ASC";
$college_stmt = $db->prepare($college_query);
$college_stmt->execute();
$colleges = $college_stmt->fetchAll(PDO::FETCH_ASSOC);
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
  <link rel="stylesheet" href="../../assets/css/admin/profile.css">

  <style>
    .profile-avatar-box:hover .avatar-overlay {
      opacity: 1 !important;
    }
  </style>
  <title>SmartQ | My Profile</title>
</head>

<body>

  <div class="admin-layout">

    <!-- Sidebar Navigation -->
    <div data-component="sidebar" data-props='{"active":"profile", "role":"student"}'></div>

    <!-- Main Content Area -->
    <div class="admin-main">

      <!-- Topbar -->
      <div data-component="topbar"
        data-props='{"title":"My Profile", "description":"Update your personal information and security settings"}'>
      </div>

      <!-- Page Content -->
      <main class="profile-wrapper">
        <div class="profile-container">

          <!-- Profile Header -->
          <div class="profile-header">
            <div class="profile-avatar-box" id="profile-avatar-main" style="cursor: pointer; position: relative; overflow: hidden; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800;">
              <?php if ($user['profile_image']): ?>
                <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile" id="profile-img-preview" style="width: 100%; height: 100%; object-fit: cover;">
              <?php else: ?>
                <span id="profile-initial-preview"><?= $initial ?></span>
              <?php endif; ?>
              <div class="avatar-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;">
                <i class="fas fa-camera" style="color: white; font-size: 1.5rem;"></i>
              </div>
            </div>
            <div class="profile-meta">
              <h1><?= $fullName ?></h1>
              <p>ID: <?= $studentId ?></p>
            </div>
          </div>

          <form id="profileForm">
            <!-- Section: Personal Information -->
            <div class="section-header">
              <i class="far fa-user"></i>
              <h2>Personal Information</h2>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" class="form-input" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" class="form-input" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
              </div>
              <div class="form-group full-width">
                <label>Email Address</label>
                <input type="email" class="form-input" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
              <div class="form-group">
                <label>Year Level</label>
                <select class="form-input form-select" name="yearlvl">
                  <option value="1" <?php echo ($user['yearlvl'] == 1) ? 'selected' : ''; ?>>1st Year</option>
                  <option value="2" <?php echo ($user['yearlvl'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
                  <option value="3" <?php echo ($user['yearlvl'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
                  <option value="4" <?php echo ($user['yearlvl'] == 4) ? 'selected' : ''; ?>>4th Year</option>
                </select>
              </div>
              <div class="form-group">
                <label>College</label>
                <select class="form-input form-select" name="college_id">
                  <?php foreach ($colleges as $college): ?>
                    <option value="<?php echo $college['college_id']; ?>" <?php echo ($user['college_id'] == $college['college_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($college['college_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Section: Change Password -->
            <div class="password-section">
              <div class="section-header">
                <i class="fas fa-lock"></i>
                <h2>Change Password (Optional)</h2>
              </div>

              <div class="form-grid">
                <div class="form-group">
                  <label>New Password</label>
                  <input type="password" class="form-input" name="new_password" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                  <label>Confirm New Password</label>
                  <input type="password" class="form-input" name="confirm_password" placeholder="Confirm your new password">
                </div>
              </div>
            </div>

            <!-- Action Button -->
            <div class="form-actions">
              <button type="submit" class="btn-save">Save Changes</button>
            </div>
          </form>

        </div>
      </main>

      <!-- Footer -->
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
    <a href="profile.php" class="mobile-nav-item active">
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

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script src="../../scripts/component-loader.js"></script>

  <script>
    $(document).ready(function () {
      $('#profileForm').on('submit', function (e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const $btn = $('.btn-save');

        $btn.prop('disabled', true).text('Updating...');

        $.ajax({
          url: '../../../server/api/students/update_profile.php',
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              alert('Profile updated successfully!');
              location.reload();
            } else {
              alert('Error: ' + response.message);
              $btn.prop('disabled', false).text('Save Changes');
            }
          },
          error: function () {
            alert('An error occurred while updating the profile.');
            $btn.prop('disabled', false).text('Save Changes');
          }
        });
      });

      // Handle Profile Picture Upload Trigger
      $('#profile-avatar-main').on('click', function() {
        $('#avatar-upload').click();
      });
    });

    SmartQ.onLoad('sidebar', function ($el) {
      $(document).on('click', '#sidebar-toggle', function () {
        $('#sidebar').toggleClass('open');
      });
    });
  </script>

</body>

</html>