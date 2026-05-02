<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$admin_id = $_SESSION['admin']['id'];
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if email is already taken by another admin
    $checkEmailQuery = "SELECT admin_id FROM admin WHERE email = :email AND admin_id != :aid";
    $ceStmt = $db->prepare($checkEmailQuery);
    $ceStmt->bindParam(':email', $email);
    $ceStmt->bindParam(':aid', $admin_id);
    $ceStmt->execute();
    if ($ceStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email is already in use by another administrator']);
        exit;
    }

    // Build the update query
    $sql = "UPDATE admin SET 
            first_name = :first_name, 
            last_name = :last_name, 
            email = :email";
    
    $params = [
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':aid' => $admin_id
    ];

    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql .= ", admin_pass = :password";
        $params[':password'] = $hashed_password;
    }

    $sql .= " WHERE admin_id = :aid";

    $stmt = $db->prepare($sql);
    
    if ($stmt->execute($params)) {
        // Update session data
        $_SESSION['admin']['first_name'] = $first_name;
        $_SESSION['admin']['last_name'] = $last_name;
        $_SESSION['admin']['email'] = $email;

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
