<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit;
}

// STRICT: Only admins can validate students
if (!isset($_SESSION['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Only administrators can perform this action.']);
    exit;
}

$student_id = trim($_POST['student_id'] ?? '');
$action = trim(strtolower($_POST['action'] ?? '')); // 'approve' or 'reject'

if (empty($student_id)) {
  echo json_encode(['success' => false, 'message' => 'Student ID is required']);
  exit;
}

if (!in_array($action, ['approve', 'reject'])) {
  echo json_encode(['success' => false, 'message' => 'Invalid action. Must be approve or reject.']);
  exit;
}

try {
  $database = new Database();
  $db = $database->getConnection();

  // Resolve the target status_id from validation_status table
  // 'approve' → 'Validated' | 'reject' → 'Not Validated'
  $target_status = ($action === 'approve') ? 'Validated' : 'Not Validated';

  $statusQuery = "SELECT status_id FROM validation_status WHERE LOWER(status_name) = LOWER(:name) LIMIT 1";
  $statusStmt = $db->prepare($statusQuery);
  $statusStmt->bindParam(':name', $target_status);
  $statusStmt->execute();
  $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

  if (!$statusRow) {
    echo json_encode(['success' => false, 'message' => "Status '{$target_status}' not found in database."]);
    exit;
  }

  $new_status_id = $statusRow['status_id'];

  // Start transaction
  $db->beginTransaction();

  // Stamp validated_at on approve/reject with precise local timezone
  date_default_timezone_set('Asia/Manila');
  $now = date('Y-m-d H:i:s');

  // Get current logged-in admin details from session
  $admin = $_SESSION['admin'];
  $admin_id = $admin['id'] ?? null;
  $admin_first = $admin['first_name'] ?? '';
  $admin_last = $admin['last_name'] ?? '';
  $admin_name = trim($admin_first . ' ' . $admin_last);

  // If we don't have an admin name in session, we might need a fallback or error
  // But usually, an admin must be logged in to reach this API.

  // Check current status before updating
  $checkStmt = $db->prepare("SELECT status_id FROM students WHERE student_id = :id AND deleted_at IS NULL");
  $checkStmt->execute(['id' => $student_id]);
  $current_student = $checkStmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$current_student) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
  }

  $old_status_id = $current_student['status_id'];

  // Resolving Validated status_id for comparison
  $validatedStatusId = $db->query("SELECT status_id FROM validation_status WHERE status_name = 'Validated' LIMIT 1")->fetchColumn();

  $updateQuery = "UPDATE students
                    SET status_id       = :status_id,
                        validated_at    = :validated_at,
                        validated_by    = :validated_by,
                        validated_by_id = :validated_by_id
                    WHERE student_id = :student_id AND deleted_at IS NULL";
  $updateStmt = $db->prepare($updateQuery);
  $updateStmt->bindParam(':status_id', $new_status_id);
  $updateStmt->bindParam(':validated_at', $now);
  $updateStmt->bindParam(':validated_by', $admin_name);
  $updateStmt->bindParam(':validated_by_id', $admin_id);
  $updateStmt->bindParam(':student_id', $student_id);
  $updateStmt->execute();

  // Update Stats
  if ($new_status_id == $validatedStatusId && $old_status_id != $validatedStatusId) {
      // Gained Validated status
      $db->exec("UPDATE system_stats SET stat_value = stat_value + 1 WHERE stat_key = 'validated_students'");
  } else if ($old_status_id == $validatedStatusId && $new_status_id != $validatedStatusId) {
      // Lost Validated status
      $db->exec("UPDATE system_stats SET stat_value = GREATEST(0, stat_value - 1) WHERE stat_key = 'validated_students'");
  }

  // If rejected, also remove from queue_list (soft delete) so they can book again
  if ($action === 'reject') {
    $deleteQueueQuery = "UPDATE queue_list SET deleted_at = NOW() WHERE student_id = :student_id AND deleted_at IS NULL";
    $deleteQueueStmt = $db->prepare($deleteQueueQuery);
    $deleteQueueStmt->bindParam(':student_id', $student_id);
    $deleteQueueStmt->execute();
  }

  // Advance Queue Logic: Find the schedule and queue_number for this student
  $qQuery = "SELECT schedule_id, queue_number FROM queue_list WHERE student_id = :sid AND deleted_at IS NULL ORDER BY queue_id DESC LIMIT 1";
  $qStmt = $db->prepare($qQuery);
  $qStmt->execute(['sid' => $student_id]);
  $qData = $qStmt->fetch(PDO::FETCH_ASSOC);

  if ($qData) {
      $sch_id = $qData['schedule_id'];
      $q_num = $qData['queue_number'];

      // Update the schedule's current_number to the current student's number
      // This implies that anyone with this number or lower is "done"
      $updateSch = $db->prepare("UPDATE queue_schedule SET current_number = GREATEST(current_number, :qnum) WHERE schedule_id = :sid");
      $updateSch->execute(['qnum' => $q_num, 'sid' => $sch_id]);
  }

  $db->commit();

  if ($updateStmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found or status unchanged.']);
    exit;
  }

  $verb = ($action === 'approve') ? 'approved' : 'rejected';
  echo json_encode([
    'success' => true,
    'message' => "Student {$student_id} validation has been {$verb}.",
    'new_status' => $target_status,
    'action' => $action,
    'validated_at' => $now
  ]);

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
