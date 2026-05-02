<?php
include_once '../../config/database.php';
include_once '../../utils/cors.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)) {
    // Check if the user is an admin
    $query = "SELECT admin_id as id, first_name, last_name, email, admin_pass as password FROM admin WHERE email = :email LIMIT 0,1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    
    $role = 'admin';
    
    // If not admin, check if student
    if($stmt->rowCount() == 0) {
        $query = "SELECT student_id as id, first_name, last_name, email, student_pass as password FROM students WHERE email = :email LIMIT 0,1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $data->email);
        $stmt->execute();
        $role = 'student';
    }
    
    if($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Comparing plaintext password or hashed if you implemented hashing setup
        if($data->password === $row['password'] || password_verify($data->password, $row['password'])) {
            http_response_code(200);
            echo json_encode(array(
                "message" => "Login successful.",
                "user" => array(
                    "id" => $row['id'],
                    "first_name" => $row['first_name'],
                    "last_name" => $row['last_name'],
                    "email" => $row['email'],
                    "role" => $role
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Login failed. Incorrect password."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Login failed. User not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Incomplete login data. Email and password required."));
}
?>
