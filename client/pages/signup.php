<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>SmartQ — Sign Up</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../assets/logo/sq.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
</head>

<body class="auth-body">
  <a href="../index.php" class="back-btn">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M19 12H5M12 19l-7-7 7-7"></path>
    </svg>
    <span>Back to Home</span>
  </a>

  <div class="auth-card">

    <!-- Header -->
    <div class="auth-card-header">
      <img src="../assets/logo/sq.png" alt="SmartQ Logo">
      <h4>Create Account</h4>
      <span class="auth-subtitle">Join SmartQ today</span>
    </div>

    <!-- Body -->
    <div class="auth-card-body">

      <?php if (isset($_SESSION['error'])): ?>
        <div class="auth-alert auth-alert-error">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
            <path
              d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z" />
          </svg>
          <?php echo htmlspecialchars($_SESSION['error']);
          unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="auth-alert auth-alert-success">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
            <path
              d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
          </svg>
          <?php echo htmlspecialchars($_SESSION['success']);
          unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <form action="../../server/api/auth/signup_handler.php" method="POST">
        <div class="form-row">
          <div class="form-group">
            <div class="auth-input-group">
              <span class="input-icon">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </span>
              <input type="text" class="form-control" name="firstname" placeholder="First Name" required>
            </div>
          </div>
          <div class="form-group">
            <div class="auth-input-group">
              <input type="text" class="form-control" name="lastname" placeholder="Last Name" required
                style="padding-left: 14px;">
            </div>
          </div>
        </div>

        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
              </svg>
            </span>
            <input type="text" class="form-control" name="studentid" placeholder="Student ID (10 digits)" required
              maxlength="10">
          </div>
        </div>

        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </span>
            <input type="email" class="form-control" name="email" placeholder="Email Address" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <select class="form-control" name="college" required style="padding-left: 14px;">
              <option value="" disabled selected>College</option>
              <option value="101">COT</option>
              <option value="106">CAS</option>
              <option value="103">COB</option>
              <option value="104">COE</option>
              <option value="102">CON</option>
              <option value="105">CPAG</option>
            </select>
          </div>
          <div class="form-group">
            <select class="form-control" name="yearlvl" required style="padding-left: 14px;">
              <option value="" disabled selected>Year Level</option>
              <option value="1">1st Year</option>
              <option value="2">2nd Year</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </span>
            <input type="password" class="form-control" name="password" placeholder="Password" required minlength="6">
          </div>
        </div>

        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </span>
            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required
              minlength="6">
          </div>
        </div>

        <button type="submit" class="auth-btn">Create Account</button>
      </form>

      <hr class="auth-divider">
      <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>

    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Auto-hide alerts
      const alerts = document.querySelectorAll('.auth-alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.classList.add('fade-out');
          setTimeout(() => alert.remove(), 300);
        }, 4000);
      });

      // Client-side validation
      const form = document.querySelector('form');
      const password = document.querySelector('input[name="password"]');
      const confirm = document.querySelector('input[name="confirm_password"]');

      form.addEventListener('submit', (e) => {
        if (password.value !== confirm.value) {
          e.preventDefault();
          alert("Passwords do not match!");
        }
      });
    });
  </script>
</body>

</html>