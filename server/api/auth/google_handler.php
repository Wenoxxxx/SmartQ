<?php
session_start();
header('Content-Type: application/json');

include_once '../../config/database.php';
include_once '../../config/google_config.php';

// Get the POST data
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->credential)) {
    echo json_encode(['success' => false, 'message' => 'No credential provided']);
    exit();
}

$id_token = $data->credential;

try {
    // 1. Verify the token with Google
    // Note: In production, it's better to use google-api-php-client to verify locally, 
    // but this endpoint is a valid alternative.
    $verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
    $response = file_get_contents($verify_url);
    $payload = json_decode($response, true);

    if (!$payload || isset($payload['error'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid Google token']);
        exit();
    }

    // 2. Validate Audience (ensure the token was meant for our app)
    if ($payload['aud'] !== GOOGLE_CLIENT_ID) {
        echo json_encode(['success' => false, 'message' => 'Invalid audience']);
        exit();
    }

    $email = $payload['email'];
    $first_name = $payload['given_name'] ?? '';
    $last_name = $payload['family_name'] ?? '';
    $picture = $payload['picture'] ?? '';

    $database = new Database();
    $db = $database->getConnection();

    // 3. Search for user in Admin
    $query = "SELECT admin_id as id, first_name, last_name, email, profile_image FROM admin 
              WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $role = 'admin';

    // 4. Search for user in Students
    if (!$user) {
        $query = "SELECT student_id, first_name, last_name, email, profile_image, status_id, college_id, yearlvl FROM students 
                  WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $role = 'student';
    }

    if ($user) {
        // Clear previous session
        session_unset();

        if ($role === 'admin') {
            $_SESSION['admin'] = $user;
            $_SESSION['admin_id'] = $user['id'];
            $redirect = 'client/pages/admin/dashboard.php';
        } else {
            $_SESSION['student'] = $user;
            $_SESSION['student_id'] = $user['student_id'];
            $redirect = 'client/pages/users/student-dashboard.php';
        }

        echo json_encode([
            'success' => true,
            'redirect' => $redirect,
            'message' => 'Successfully logged in with Google'
        ]);
    } else {
        // User not found in database
        echo json_encode([
            'success' => false,
            'message' => "Account not found ($email). Please register first or use an authorized email."
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
