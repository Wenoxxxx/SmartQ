<?php
session_start();
require_once "../../server/config/google_config.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>SmartQ — Login</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../assets/logo/sq.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
      <h4>Welcome Back</h4>
      <span class="auth-subtitle">Sign in to your account</span>
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

      <form action="../../server/api/auth/login_handler.php" method="POST">
        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </span>
            <input name="studentid" type="text" class="form-control" placeholder="Email or Student ID" required>
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
            <input name="password" type="password" class="form-control" placeholder="Password" required minlength="6">
          </div>
        </div>

        <div class="recaptcha-outer">
          <div class="recaptcha-inner">
            <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
          </div>
        </div>

        <button type="submit" class="auth-btn">Login</button>
      </form>

      <div class="google-auth-divider">Or continue with</div>

      <div class="google-auth-container">
        <div id="g_id_onload" data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>"
          data-callback="handleCredentialResponse" data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="filled_blue" data-text="signin_with"
          data-shape="pill" data-logo_alignment="left" data-width="100%">
        </div>
      </div>

      <hr class="auth-divider">
      <p class="auth-link"><a href="forgotpass.php">Forgot password?</a></p>
      <p class="auth-link">Don't have an account? <a href="signup.php">Sign Up</a></p>

    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const alerts = document.querySelectorAll('.auth-alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.classList.add('fade-out');
          setTimeout(() => alert.remove(), 300);
        }, 4000);
      });

      // Show reCAPTCHA when typing password
      const passwordInput = document.querySelector('input[name="password"]');
      const recaptchaContainer = document.querySelector('.recaptcha-outer');

      if (passwordInput && recaptchaContainer) {
        passwordInput.addEventListener('input', () => {
          if (passwordInput.value.length >= 1) {
            recaptchaContainer.classList.add('visible');
          }
        });

        // Also show if field is already filled (e.g., autocomplete)
        if (passwordInput.value.length >= 1) {
          recaptchaContainer.classList.add('visible');
        }
      }
    });

    function handleCredentialResponse(response) {
      // Show loading state if needed
      fetch('../../server/api/auth/google_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          credential: response.credential
        })
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            window.location.href = '../../' + data.redirect;
          } else {
            showAlert(data.message, 'error');
          }
        })
        .catch(err => {
          showAlert('An error occurred during Google Sign-In.', 'error');
        });
    }

    function showAlert(message, type) {
      const alertContainer = document.querySelector('.auth-card-body');
      const alert = document.createElement('div');
      alert.className = `auth-alert auth-alert-${type}`;
      alert.innerHTML = `
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
          <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z" />
        </svg>
        ${message}
      `;
      alertContainer.prepend(alert);
      setTimeout(() => {
        alert.classList.add('fade-out');
        setTimeout(() => alert.remove(), 300);
      }, 4000);
    }
  </script>
</body>

</html>