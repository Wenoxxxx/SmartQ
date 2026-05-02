<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once "../../config/database.php";
$database = new Database();
$db = $database->getConnection();

$student_id = $_SESSION['student']['student_id'];

try {
    // Fetch the latest active booking for this student
    $query = "SELECT ql.queue_number, qs.current_number, qs.schedule_id
              FROM queue_list ql
              JOIN queue_schedule qs ON ql.schedule_id = qs.schedule_id
              WHERE ql.student_id = :sid 
              AND qs.status = 'active' 
              AND qs.schedule_date >= CURDATE()
              AND ql.deleted_at IS NULL
              ORDER BY qs.schedule_date ASC LIMIT 1";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sid', $student_id);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        echo json_encode(['success' => true, 'data' => $booking]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No active booking found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
