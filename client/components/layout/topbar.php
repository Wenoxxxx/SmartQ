<?php
session_start();
/**
 * Topbar Component
 * 
 * Usage:  <div data-component="topbar" data-props='{"title":"Dashboard"}'></div>
 * 
 * Accepts:
 *   - title : The page heading displayed in the topbar
 */

$title = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : 'Dashboard';
$description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '';

function get_icon($filename)
{
  // Try sidebar folder first, then base icons folder
  $sidebar_path = __DIR__ . '/../../assets/icons/sidebar/' . $filename;
  $base_path = __DIR__ . '/../../assets/icons/' . $filename;

  $path = file_exists($sidebar_path) ? $sidebar_path : (file_exists($base_path) ? $base_path : '');

  if ($path === '')
    return '';

  $svg = file_get_contents($path);
  // Inject the icon class into the <svg> tag
  $svg = preg_replace('/<svg\b/', '<svg class="topbar-icon"', $svg, 1);
  // Strip the XML declaration
  $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
  return $svg;
}
?>

<?php
$admin_data = $_SESSION['admin'] ?? null;
$student_data = $_SESSION['student'] ?? null;
$current_path = $_SERVER['PHP_SELF'];
$referer = $_SERVER['HTTP_REFERER'] ?? '';

$full_name = 'User';
$initial = 'U';
$avatar_url = null;
$user_role = 'Guest';

// Determine role based on current path and active session
$is_admin_path = (strpos($current_path, '/admin/') !== false);
$is_student_path = (strpos($current_path, '/users/') !== false);

if ($is_admin_path && $admin_data) {
  $full_name = $admin_data['first_name'] . ' ' . $admin_data['last_name'];
  $initial = strtoupper(substr($admin_data['first_name'], 0, 1));
  $avatar_url = $admin_data['profile_image'] ?? null;
  $user_role = 'Super Admin';
} elseif ($is_student_path && $student_data) {
  $full_name = $student_data['first_name'] . ' ' . $student_data['last_name'];
  $initial = strtoupper(substr($student_data['first_name'], 0, 1));
  $avatar_url = $student_data['profile_image'] ?? null;
  $user_role = 'Student';
} elseif ($admin_data) {
  // Fallback to admin if session exists
  $full_name = $admin_data['first_name'] . ' ' . $admin_data['last_name'];
  $initial = strtoupper(substr($admin_data['first_name'], 0, 1));
  $avatar_url = $admin_data['profile_image'] ?? null;
  $user_role = 'Super Admin';
} elseif ($student_data) {
  // Fallback to student
  $full_name = $student_data['first_name'] . ' ' . $student_data['last_name'];
  $initial = strtoupper(substr($student_data['first_name'], 0, 1));
  $avatar_url = $student_data['profile_image'] ?? null;
  $user_role = 'Student';
}
?>

<header class="topbar" id="topbar">

  <!-- Left: Title Area -->
  <div class="topbar-content">
    <h1 class="topbar-title">
      <?= $title ?>
    </h1>
    <?php if ($description): ?>
      <p class="topbar-subtitle">
        <?= $description ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- Right: Actions Area -->
  <div class="topbar-actions">
    <!-- Search Bar -->
    <div class="topbar-search-wrapper">
      <i class="fas fa-search"></i>
      <input type="text" id="global-search" placeholder="Search services..." autocomplete="off">

      <!-- Search Results Dropdown -->
      <div id="search-results" class="search-results-dropdown">
        <!-- Results will be injected here -->
      </div>
    </div>

    <!-- User Profile -->
    <div class="topbar-user-profile" id="user-menu">
      <a href="profile.php" class="topbar-user-link" title="Go to Profile">
        <div class="topbar-avatar" id="avatar-container" title="Click to upload profile picture">
          <?php if ($avatar_url): ?>
            <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar" class="avatar-img" id="current-avatar">
          <?php else: ?>
            <span id="avatar-initial"><?= $initial ?></span>
          <?php endif; ?>
          <div class="avatar-overlay">
            <i class="fas fa-camera"></i>
          </div>
        </div>
        <input type="file" id="avatar-upload" style="display: none;" accept="image/*">
        <div class="topbar-user-info">
          <span class="topbar-username"><?= htmlspecialchars($full_name) ?></span>
          <span class="topbar-user-role"><?= htmlspecialchars($user_role) ?></span>
        </div>
      </a>
    </div>
  </div>

</header>

<script>
  $(document).ready(function () {
    const userRole = `<?= $user_role ?>`;
    let services = [];

    if (userRole === 'Super Admin') {
      services = [
        { name: 'Dashboard', url: 'dashboard.php', icon: `<?= get_icon('dashboard.svg') ?>`, desc: 'System overview and statistics' },
        { name: 'Queue', url: 'queue.php', icon: `<?= get_icon('queue.svg') ?>`, desc: 'Manage validation dates and queue slots' },
        { name: 'Students', url: 'students.php', icon: `<?= get_icon('students.svg') ?>`, desc: 'Manage students and ID validation' },
        { name: 'Reports', url: 'reports.php', icon: `<?= get_icon('reports.svg') ?>`, desc: 'View validation and queue data' },
        { name: 'Profile', url: 'profile.php', icon: `<?= get_icon('profile.svg') ?>`, desc: 'Update your account info' },
      ];
    } else {
      services = [
        { name: 'Student Dashboard', url: 'student-dashboard.php', icon: `<?= get_icon('dashboard.svg') ?>`, desc: 'View your queue status and validation info' },
        { name: 'Book Validation', url: 'book-queue.php', icon: `<?= get_icon('queue.svg') ?>`, desc: 'Schedule a validation appointment in the queue' },
        { name: 'View History', url: 'my-history.php', icon: `<?= get_icon('queue.svg') ?>`, desc: 'View your past validation records' },
        { name: 'My Profile', url: 'profile.php', icon: `<?= get_icon('profile.svg') ?>`, desc: 'Update your personal details' },
      ];
    }

    const $search = $('#global-search');
    const $results = $('#search-results');

    $search.on('input', function () {
      const query = $(this).val().toLowerCase().trim();
      $results.empty();

      if (query.length < 1) {
        $results.hide();
        return;
      }

      const filtered = services.filter(s =>
        s.name.toLowerCase().includes(query) ||
        s.desc.toLowerCase().includes(query)
      );

      if (filtered.length > 0) {
        filtered.forEach(s => {
          $results.append(`
            <a href="${s.url}" class="search-result-item">
              <div class="result-icon">${s.icon}</div>
              <div class="result-content">
                <div class="result-name">${s.name}</div>
                <div class="result-desc">${s.desc}</div>
              </div>
            </a>
          `);
        });
        $results.show();
      } else {
        $results.append('<div class="search-no-results">No services found...</div>').show();
      }
    });

    // Close dropdown when clicking outside
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.topbar-search-wrapper').length) {
        $results.hide();
      }
    });

    // Avatar Upload Logic (Use Event Delegation)
    $(document).on('click', '#avatar-container', function (e) {
      e.preventDefault();
      e.stopPropagation();
      $('#avatar-upload').click();
    });

    $(document).on('change', '#avatar-upload', function () {
      const file = this.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('avatar', file);

      const $container = $('#avatar-container');
      $container.addClass('uploading');

      $.ajax({
        url: '../../../server/api/users/upload_avatar.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (response) {
          $container.removeClass('uploading');
          if (response.success) {
            // Re-render the topbar avatar
            $container.html(`
              <img src="${response.avatar_url}" alt="Avatar" class="avatar-img" id="current-avatar">
              <div class="avatar-overlay">
                <i class="fas fa-camera"></i>
              </div>
            `);
            
            // Also update the main profile page avatar if we are on profile.php
            const $mainProfile = $('#profile-avatar-main');
            if ($mainProfile.length > 0) {
              if ($('#profile-img-preview').length > 0) {
                $('#profile-img-preview').attr('src', response.avatar_url);
              } else {
                $mainProfile.html(`
                  <img src="${response.avatar_url}" alt="Profile" id="profile-img-preview" style="width: 100%; height: 100%; object-fit: cover;">
                  <div class="avatar-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;">
                    <i class="fas fa-camera" style="color: white; font-size: 1.5rem;"></i>
                  </div>
                `);
              }
            }
            
            alert('Profile picture updated successfully!');
          } else {
            alert('Error: ' + response.message);
          }
        },
        error: function () {
          $container.removeClass('uploading');
          alert('Failed to upload image. Please check your connection or file size.');
        }
      });
    });
  });
</script>