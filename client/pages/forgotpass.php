<?php
session_start();
require_once '../../server/config/database.php';
require_once '../../server/utils/mailer.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    try {
        $database = new Database();
        $db = $database->getConnection();

        // 1. Check if email exists in students or admin
        $stmt = $db->prepare("SELECT 'student' as role, student_id as id FROM students WHERE email = ? 
                               UNION 
                               SELECT 'admin' as role, admin_id as id FROM admin WHERE email = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $reset_code = rand(100000, 999999);
            
            // 2. Store reset_code in the correct table
            if ($user['role'] === 'student') {
                $update = $db->prepare("UPDATE students SET reset_code = ? WHERE student_id = ?");
                $update->execute([$reset_code, $user['id']]);
            } else {
                $update = $db->prepare("UPDATE admin SET reset_code = ? WHERE admin_id = ?");
                $update->execute([$reset_code, $user['id']]);
            }
            
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_role'] = $user['role'];
            
            $mailer = new Mailer();
            $subject = 'SmartQ - Password Reset Code';
            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                    <h2 style='color: #2563eb;'>SmartQ Password Reset</h2>
                    <p>Hello,</p>
                    <p>You requested a password reset for your SmartQ account. Use the code below to proceed:</p>
                    <div style='background: #f1f5f9; padding: 15px; text-align: center; font-size: 24px; font-weight: 800; letter-spacing: 5px; color: #1e293b; border-radius: 8px; margin: 20px 0;'>
                        {$reset_code}
                    </div>
                    <p style='color: #64748b; font-size: 0.9rem;'>If you didn't request this, you can safely ignore this email.</p>
                </div>
            ";
            
            $mailer->sendEmail($email, $subject, $body);

            $_SESSION['success'] = "A verification code has been sent to your email";
            header('Location: entercode.php');
            exit();
        } else {
            $_SESSION['error'] = "No user found with that email";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    header('Location: forgotpass.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>SmartQ — Forgot Password</title>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../assets/logo/sq.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/login.css" rel="stylesheet">
</head>

<body class="auth-body">
  <a href="login.php" class="back-btn">
    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M19 12H5M12 19l-7-7 7-7"></path>
    </svg>
    <span>Back to Login</span>
  </a>

  <div class="auth-card">
    <div class="auth-card-header">
      <img src="../assets/logo/sq.png" alt="SmartQ Logo">
      <h4>Forgot Password</h4>
      <span class="auth-subtitle">We'll send a verification code to your email</span>
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

      <form action="forgotpass.php" method="POST">
        <div class="form-group">
          <div class="auth-input-group">
            <span class="input-icon">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
            </span>
            <input name="email" type="email" class="form-control" placeholder="Enter Email Address" required>
          </div>
        </div>
        <button type="submit" class="auth-btn">Send Verification Code</button>
      </form>
    </div>
  </div>
</body>
</html>