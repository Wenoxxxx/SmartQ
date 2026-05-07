<?php
session_start();
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../../config/database.php';
include_once '../../config/google_config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = trim($_POST['studentid']);
    $password = $_POST['password'];

    // reCAPTCHA Verification
    if (!isset($_POST['g-recaptcha-response']) || empty($_POST['g-recaptcha-response'])) {
        $_SESSION['error'] = "Please complete the reCAPTCHA.";
        header('Location: ../../../client/pages/login.php');
        exit();
    }

    $recaptcha_secret = RECAPTCHA_SECRET_KEY;
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}";
    
    $verify_response = file_get_contents($verify_url);
    $response_data = json_decode($verify_response);

    if (!$response_data->success) {
        $_SESSION['error'] = "reCAPTCHA verification failed. Please try again.";
        header('Location: ../../../client/pages/login.php');
        exit();
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        // 1. Try checking Admin table (STRICT: email only for admin)
        $query = "SELECT admin_id as id, first_name, last_name, email, profile_image, admin_pass as password FROM admin 
                  WHERE email = :input OR admin_id = :input LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':input', $login_input);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $role = 'admin';

        // 2. If not found in Admin, try Students table (Check email OR student_id)
        if (!$user) {
            $query = "SELECT student_id, first_name, last_name, email, profile_image, student_pass as password, status_id, college_id, yearlvl FROM students 
                      WHERE email = :input OR student_id = :input LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':input', $login_input);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $role = 'student';
        }

        // 3. Verify password
        if ($user) {
            $is_plain = ($password === $user['password']);
            $is_hashed = @password_verify($password, $user['password']);
            
            if ($is_plain || $is_hashed) {
                // Remove password from the array before storing in session for security
                unset($user['password']);

                // Clear previous session data to prevent role conflicts
                session_unset();

                if ($role === 'admin') {
                    $_SESSION['admin'] = $user;
                    $_SESSION['admin_id'] = $user['id'];
                    header('Location: ../../../client/pages/admin/dashboard.php');
                } else {
                    $_SESSION['student'] = $user;
                    $_SESSION['student_id'] = $user['student_id'];
                    header('Location: ../../../client/pages/users/student-dashboard.php');
                }
                exit();
            }
        }

        // If we reach here, login failed
        $_SESSION['error'] = "Invalid ID/Email or Password";
        header('Location: ../../../client/pages/login.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Login Error: " . $e->getMessage();
        header('Location: ../../../client/pages/login.php');
        exit();
    }
}
?>