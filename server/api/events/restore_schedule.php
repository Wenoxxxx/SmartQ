<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

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

    // Check if the schedule exists in archive
    $check_stmt = $db->prepare("SELECT status FROM queue_schedule WHERE schedule_id = :id AND deleted_at IS NOT NULL");
    $check_stmt->bindParam(':id', $schedule_id);
    $check_stmt->execute();
    $schedule = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found in archive']);
        $db->rollBack();
        exit;
    }

    $is_active = ($schedule['status'] === 'active');

    // Restore related queue entries (optional but recommended if they were soft deleted)
    $stmt1 = $db->prepare("UPDATE queue_list SET deleted_at = NULL WHERE schedule_id = :id AND deleted_at IS NOT NULL");
    $stmt1->bindParam(':id', $schedule_id);
    $stmt1->execute();

    // Restore the schedule
    $stmt2 = $db->prepare("UPDATE queue_schedule SET deleted_at = NULL WHERE schedule_id = :id");
    $stmt2->bindParam(':id', $schedule_id);
    
    if ($stmt2->execute()) {
        if ($is_active) {
            $db->exec("UPDATE system_stats SET stat_value = stat_value + 1 WHERE stat_key = 'active_schedules'");
        }
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Schedule restored successfully']);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to restore schedule']);
    }

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
