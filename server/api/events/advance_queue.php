<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';

$schedule_id = $_POST['schedule_id'] ?? '';

if (empty($schedule_id)) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Get current status and total booked students
    $statusQuery = "SELECT qs.current_number, (SELECT COUNT(*) FROM queue_list ql WHERE ql.schedule_id = qs.schedule_id) as total_booked 
                    FROM queue_schedule qs WHERE qs.schedule_id = :id";
    $statusStmt = $db->prepare($statusQuery);
    $statusStmt->execute(['id' => $schedule_id]);
    $status = $statusStmt->fetch(PDO::FETCH_ASSOC);

    if (!$status) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found']);
        exit;
    }

    if ($status['current_number'] >= $status['total_booked']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Queue already finished', 
            'current_number' => $status['current_number'],
            'is_end' => true
        ]);
        exit;
    }

    // 2. Increment current_number
    $query = "UPDATE queue_schedule SET current_number = current_number + 1 WHERE schedule_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $schedule_id);

    if ($stmt->execute()) {
        $newNumber = $status['current_number'] + 1;

        echo json_encode([
            'success' => true,
            'message' => 'Queue advanced',
            'current_number' => $newNumber,
            'is_end' => ($newNumber >= $status['total_booked'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to advance queue']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
