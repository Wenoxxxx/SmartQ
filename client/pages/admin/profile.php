<?php
session_start();
if (!isset($_SESSION['admin'])) {
  header('Location: ../login.php');
  exit();
}

$admin = $_SESSION['admin'];
$fullName = strtoupper($admin['first_name'] . ' ' . $admin['last_name']);
$initial = strtoupper(substr($admin['first_name'], 0, 1));
$adminId = $admin['id'] ?? 'N/A';
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

  <title>SmartQ | Admin Profile</title>
</head>

<body>

  <div class="admin-layout">

    <!-- Sidebar Navigation -->
    <div data-component="sidebar" data-props='{"active":"profile"}'></div>

    <!-- Main Content Area -->
    <div class="admin-main">

      <!-- Topbar -->
      <div data-component="topbar"
        data-props='{"title":"Admin Profile", "description":"Update your personal information and security settings"}'>
      </div>

      <!-- Page Content -->
      <main class="profile-wrapper">
        <div class="profile-container">

          <!-- Profile Header -->
          <div class="profile-header">
            <div class="profile-avatar-box" id="profile-avatar-main">
              <?php if (isset($admin['profile_image']) && $admin['profile_image']): ?>
                <img src="<?= htmlspecialchars($admin['profile_image']) ?>" alt="Profile" id="profile-img-preview" class="avatar-img">
              <?php else: ?>
                <span id="profile-initial-preview"><?= $initial ?></span>
              <?php endif; ?>
              <div class="avatar-overlay">
                <i class="fas fa-camera"></i>
              </div>
            </div>
            <div class="profile-meta">
              <h1><?= $fullName ?></h1>
              <p>ID: <?= $adminId ?></p>
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
                <input type="text" class="form-input" name="first_name"
                  value="<?= htmlspecialchars($admin['first_name']) ?>">
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" class="form-input" name="last_name"
                  value="<?= htmlspecialchars($admin['last_name']) ?>">
              </div>
              <div class="form-group full-width">
                <label>Email Address</label>
                <input type="email" class="form-input" name="email" value="<?= htmlspecialchars($admin['email']) ?>">
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
          url: '../../../server/api/admin/update_profile.php',
          type: 'POST',
          data: formData,
          dataType: 'json',
          success: function (response) {
            if (response.success) {
              alert('Profile updated successfully!');
              location.reload(); // Refresh to update Topbar and Sidebar info
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