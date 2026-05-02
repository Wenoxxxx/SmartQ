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

// Get current college name and year text for display
$current_college_name = 'N/A';
foreach ($colleges as $college) {
  if ($college['college_id'] == $user['college_id']) {
    $current_college_name = $college['college_name'];
    break;
  }
}

$year_mapping = [
  1 => '1st Year',
  2 => '2nd Year',
  3 => '3rd Year',
  4 => '4th Year'
];
$current_year_text = $year_mapping[$user['yearlvl']] ?? ($user['yearlvl'] ? $user['yearlvl'] . 'th Year' : 'N/A');
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
            <div class="profile-avatar-box" id="profile-avatar-main"
              style="cursor: pointer; position: relative; overflow: hidden; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800;">
              <?php if ($user['profile_image']): ?>
                <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile" id="profile-img-preview"
                  style="width: 100%; height: 100%; object-fit: cover;">
              <?php else: ?>
                <span id="profile-initial-preview"><?= $initial ?></span>
              <?php endif; ?>
              <div class="avatar-overlay"
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;">
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
              <span class="info-hint">(Contact Admin to change)</span>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" class="form-input readonly-field" name="first_name"
                  value="<?= htmlspecialchars($user['first_name']) ?>" readonly>
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" class="form-input readonly-field" name="last_name"
                  value="<?= htmlspecialchars($user['last_name']) ?>" readonly>
              </div>
              <div class="form-group full-width">
                <label>Email Address</label>
                <input type="email" class="form-input readonly-field" name="email" value="<?= htmlspecialchars($user['email']) ?>"
                  readonly>
              </div>
              <div class="form-group">
                <label>Year Level</label>
                <input type="text" class="form-input readonly-field" value="<?= htmlspecialchars($current_year_text) ?>" readonly>
              </div>
              <div class="form-group">
                <label>College</label>
                <input type="text" class="form-input readonly-field" value="<?= htmlspecialchars($current_college_name) ?>" readonly>
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
                  <input type="password" class="form-input" name="new_password"
                    placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                  <label>Confirm New Password</label>
                  <input type="password" class="form-input" name="confirm_password"
                    placeholder="Confirm your new password">
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

  <!-- Mobile Bottom Navigation -->
  <div data-component="mobile-nav" data-props='{"active":"profile"}'></div>

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
      $('#profile-avatar-main').on('click', function () {
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