<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

// Security check
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$schedule_id = $_POST['schedule_id'] ?? '';

if (empty($schedule_id)) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $db->beginTransaction();

    // 1. Delete all related queue list entries permanently
    $stmt1 = $db->prepare("DELETE FROM queue_list WHERE schedule_id = :id");
    $stmt1->bindParam(':id', $schedule_id);
    $stmt1->execute();

    // 2. Delete the schedule itself permanently
    $stmt2 = $db->prepare("DELETE FROM queue_schedule WHERE schedule_id = :id");
    $stmt2->bindParam(':id', $schedule_id);
    
    if ($stmt2->execute()) {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Schedule permanently deleted']);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to delete schedule']);
    }

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
