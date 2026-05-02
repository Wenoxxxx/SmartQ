<?php
session_start();
require_once '../../server/config/database.php';

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgotpass.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enteredCode = trim($_POST['code']);
    $email = $_SESSION['reset_email'];
    $role = $_SESSION['reset_role'];

    try {
        $database = new Database();
        $db = $database->getConnection();

        $table = ($role === 'student') ? 'students' : 'admin';
        $stmt = $db->prepare("SELECT reset_code FROM $table WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $enteredCode == $user['reset_code']) {
            $_SESSION['reset_code_verified'] = true;
            header('Location: resetpass.php');
            exit();
        } else {
            $_SESSION['error'] = "Invalid verification code. Please try again.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>SmartQ — Verify Code</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../assets/logo/sq.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
</head>

<body class="auth-body">
  <a href="forgotpass.php" class="back-btn">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M19 12H5M12 19l-7-7 7-7"></path>
    </svg>
    <span>Back</span>
  </a>

  <div class="auth-card">
    <div class="auth-card-header">
      <img src="../assets/logo/sq.png" alt="SmartQ Logo">
      <h4>Verify Code</h4>
      <span class="auth-subtitle">Enter the 6-digit code sent to your email</span>
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

      <?php if (isset($_SESSION['success'])): ?>
        <div class="auth-alert auth-alert-success">
          <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z" />
          </svg>
          <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <form action="entercode.php" method="POST">
        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </span>
            <input name="code" type="number" class="form-control" placeholder="Enter Verification Code" required>
          </div>
        </div>
        <button type="submit" class="auth-btn">Verify Code</button>
      </form>
    </div>
  </div>
</body>
</html>