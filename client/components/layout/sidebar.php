<?php
/**
 * Sidebar Component
 * 
 * Usage:  <div data-component="sidebar"></div>
 * 
 * This component renders the admin sidebar navigation.
 * It reads the current page from $_GET['active'] or defaults to 'dashboard'.
 */

$active = isset($_GET['active']) ? htmlspecialchars($_GET['active']) : 'dashboard';
$role = isset($_GET['role']) ? htmlspecialchars($_GET['role']) : 'admin';

// Helper: load an SVG file and inject a CSS class into the root <svg> element
function sidebar_icon($filename)
{
  $path = __DIR__ . '/../../assets/icons/sidebar/' . $filename;
  if (!file_exists($path))
    return '';
  $svg = file_get_contents($path);
  // Inject the sidebar-icon class into the <svg> tag
  $svg = preg_replace('/<svg\b/', '<svg class="sidebar-icon"', $svg, 1);
  // Strip the XML declaration so it doesn't render as text
  $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
  return $svg;
}
?>

<aside class="sidebar" id="sidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <!-- <img src="../../assets/logo/sq.png" alt="SmartQ" class="sidebar-logo"> -->
    <span class="sidebar-title">SMARTQ</span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">
    <ul>
      <?php if ($role === 'admin'): ?>
        <li>
          <a href="dashboard.php" class="sidebar-link <?= $active === 'dashboard' ? 'active' : '' ?>">
            <?= sidebar_icon('dashboard.svg') ?>
            <span class="sidebar-label">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="queue.php" class="sidebar-link <?= $active === 'queue' ? 'active' : '' ?>">
            <?= sidebar_icon('queue.svg') ?>
            <span class="sidebar-label">Queue</span>
          </a>
        </li>
        <li>
          <a href="students.php" class="sidebar-link <?= $active === 'students' ? 'active' : '' ?>">
            <?= sidebar_icon('students.svg') ?>
            <span class="sidebar-label">Students</span>
          </a>
        </li>
        <li>
          <a href="reports.php" class="sidebar-link <?= $active === 'reports' ? 'active' : '' ?>">
            <?= sidebar_icon('reports.svg') ?>
            <span class="sidebar-label">Reports</span>
          </a>
        </li>
        <li>
          <a href="profile.php" class="sidebar-link <?= $active === 'profile' ? 'active' : '' ?>">
            <?= sidebar_icon('profile.svg') ?>
            <span class="sidebar-label">Profile</span>
          </a>
        </li>

      <?php else: // Student Role ?>
        <li>
          <a href="student-dashboard.php" class="sidebar-link <?= $active === 'dashboard' ? 'active' : '' ?>">
            <?= sidebar_icon('dashboard.svg') ?>
            <span class="sidebar-label">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="book-queue.php" class="sidebar-link <?= $active === 'book' ? 'active' : '' ?>">
            <?= sidebar_icon('queue.svg') ?>
            <span class="sidebar-label">Queue</span>
          </a>
        </li>
        <li>
          <a href="profile.php" class="sidebar-link <?= $active === 'profile' ? 'active' : '' ?>">
            <?= sidebar_icon('profile.svg') ?>
            <span class="sidebar-label">Profile</span>
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </nav>

  <!-- Sidebar footer -->
  <div class="sidebar-footer">
    <a href="#" class="sidebar-link" id="logout-trigger"
      onclick="event.preventDefault(); document.getElementById('logout-modal').classList.add('active');">
      <?= sidebar_icon('logout.svg') ?>
      <span class="sidebar-label">Logout</span>
    </a>
  </div>

</aside>

<!-- ── Logout Confirmation Modal ── -->
<div class="logout-modal-overlay" id="logout-modal">
  <div class="logout-modal">
    <div class="logout-modal-icon">
      <?= sidebar_icon('logout.svg') ?>
    </div>
    <h3 class="logout-modal-title">Sign Out</h3>
    <p class="logout-modal-text">Are you sure you want to log out of your account?</p>
    <div class="logout-modal-actions">
      <button class="logout-btn-cancel"
        onclick="document.getElementById('logout-modal').classList.remove('active');">Cancel</button>
      <a href="../../../server/api/auth/logout.php?role=<?= $role ?>" class="logout-btn-confirm">Log Out</a>
    </div>
  </div>
</div>