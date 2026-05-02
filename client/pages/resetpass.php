<?php
session_start();
require_once '../../server/config/database.php';

if (empty($_SESSION['reset_code_verified']) || $_SESSION['reset_code_verified'] !== true) {
    header('Location: entercode.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = $_POST['newpass'];
    $confirmPassword = $_POST['confirmpass'];
    $email = $_SESSION['reset_email'];
    $role = $_SESSION['reset_role'];

    if (strlen($newPassword) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters";
    } elseif ($newPassword === $confirmPassword) {
        try {
            $database = new Database();
            $db = $database->getConnection();

            $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
            
            if ($role === 'student') {
                $stmt = $db->prepare("UPDATE students SET student_pass = ?, reset_code = NULL WHERE email = ?");
            } else {
                $stmt = $db->prepare("UPDATE admin SET admin_pass = ?, reset_code = NULL WHERE email = ?");
            }
            
            $stmt->execute([$hashed_password, $email]);

            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_role']);
            unset($_SESSION['reset_code_verified']);

            $_SESSION['success'] = "Password reset successful. Please login with your new password.";
            header('Location: login.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Passwords do not match";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>SmartQ — Reset Password</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../assets/logo/sq.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
</head>

<body class="auth-body">
  <div class="auth-card">
    <div class="auth-card-header">
      <img src="../assets/logo/sq.png" alt="SmartQ Logo">
      <h4>Reset Password</h4>
      <span class="auth-subtitle">Choose a strong new password for your account</span>
    </div>

    <div class="auth-card-body">
      <?php if (isset($_SESSION['error'])): ?>
        <div class="auth-alert auth-alert-error">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z" />
          </svg>
          <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <form action="resetpass.php" method="POST">
        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </span>
            <input name="newpass" type="password" class="form-control" placeholder="New Password" required minlength="6">
          </div>
        </div>

        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </span>
            <input name="confirmpass" type="password" class="form-control" placeholder="Confirm New Password" required minlength="6">
          </div>
        </div>

        <button type="submit" class="auth-btn">Reset Password</button>
      </form>
    </div>
  </div>
</body>
</html>