<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

if (!isset($_SESSION['student'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$schedule_id = $_POST['schedule_id'] ?? '';
$student_id = $_SESSION['student']['student_id'];

if (empty($schedule_id)) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Check if schedule exists and is active
    $query = "SELECT * FROM queue_schedule WHERE schedule_id = :id AND status = 'active' AND deleted_at IS NULL LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $schedule_id);
    $stmt->execute();
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo json_encode(['success' => false, 'message' => 'Schedule not found or inactive']);
        exit;
    }

    // NEW: Check if schedule time has already passed
    date_default_timezone_set('Asia/Manila');
    $now = new DateTime();
    $scheduleEnd = new DateTime($schedule['schedule_date'] . ' ' . $schedule['end_time']);

    if ($now > $scheduleEnd) {
        echo json_encode(['success' => false, 'message' => 'This schedule is already closed/expired']);
        exit;
    }

    // NEW: Check if student is already validated
    $checkStatusQuery = "SELECT status_id FROM students WHERE student_id = :sid AND deleted_at IS NULL LIMIT 1";
    $csStmt = $db->prepare($checkStatusQuery);
    $csStmt->bindParam(':sid', $student_id);
    $csStmt->execute();
    $studentStatus = $csStmt->fetchColumn();

    if ($studentStatus == 1) { // 1 = Validated
        echo json_encode(['success' => false, 'message' => 'You are already validated and do not need to book.']);
        exit;
    }

    // 2. Check if student has ANY active booking
    $query = "SELECT 1 FROM queue_list ql 
              JOIN queue_schedule qs ON ql.schedule_id = qs.schedule_id 
              WHERE ql.student_id = :sid AND qs.status = 'active' AND qs.schedule_date >= CURDATE() AND ql.deleted_at IS NULL 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sid', $student_id);
    $stmt->execute();
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You already have an active booking and cannot book multiple schedules.']);
        exit;
    }

    // 3. Check availability
    $query = "SELECT COUNT(*) FROM queue_list WHERE schedule_id = :id AND deleted_at IS NULL";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $schedule_id);
    $stmt->execute();
    $booked_count = $stmt->fetchColumn();

    if ($booked_count >= $schedule['slot_limit']) {
        echo json_encode(['success' => false, 'message' => 'This schedule is already full']);
        exit;
    }

    // 4. Generate next queue number
    $queue_number = $booked_count + 1;

    // 5. Book the slot
    $query = "INSERT INTO queue_list (student_id, schedule_id, queue_number) 
              VALUES (:sid, :schid, :qnum)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':sid', $student_id);
    $stmt->bindParam(':schid', $schedule_id);
    $stmt->bindParam(':qnum', $queue_number);

    if ($stmt->execute()) {
        // 6. Update student status to 'Pending Review' (status_id = 3)
        // Check previous status for stats
        if ($studentStatus == 2) { // 2 = Not Validated
            $db->exec("UPDATE system_stats SET stat_value = GREATEST(0, stat_value - 1) WHERE stat_key = 'not_validated'");
        }

        $updateStatusQuery = "UPDATE students SET status_id = 3 WHERE student_id = :sid AND deleted_at IS NULL";
        $updateStatusStmt = $db->prepare($updateStatusQuery);
        $updateStatusStmt->bindParam(':sid', $student_id);
        $updateStatusStmt->execute();

        // Increment queueing count
        $db->exec("UPDATE system_stats SET stat_value = stat_value + 1 WHERE stat_key = 'queueing'");

        // Also update session to reflect status immediately
        if (isset($_SESSION['student']['student_id']) && $_SESSION['student']['student_id'] == $student_id) {
            $_SESSION['student']['status_id'] = 3;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Booking successful',
            'queue_number' => $queue_number
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to process booking']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
