<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	echo json_encode(['success' => false, 'message' => 'Invalid request method']);
	exit;
}

$student_id = trim($_GET['student_id'] ?? '');

if (empty($student_id)) {
	echo json_encode(['success' => false, 'message' => 'Student ID is required']);
	exit;
}

try {
	$database = new Database();
	$db = $database->getConnection();

	$query = "
        SELECT
            s.student_id,
            s.first_name,
            s.last_name,
            s.email,
            s.yearlvl,
            s.validated_at,
            s.validated_by,
            s.validated_by_id,
            c.college_name,
            vs.status_name,
            a.admin_id        AS admin_id,
            a.first_name      AS admin_first_name,
            a.last_name       AS admin_last_name
        FROM students s
        LEFT JOIN colleges c ON s.college_id = c.college_id
        LEFT JOIN validation_status vs ON s.status_id = vs.status_id
        LEFT JOIN admin a ON s.validated_by_id = a.admin_id
        WHERE s.student_id = :id
        LIMIT 1
    ";

	$stmt = $db->prepare($query);
	$stmt->bindParam(':id', $student_id);
	$stmt->execute();
	$student = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$student) {
		echo json_encode(['success' => false, 'message' => 'Student not found']);
		exit;
	}

	$year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
	$student['year_display'] = $year_labels[$student['yearlvl']] ?? ($student['yearlvl'] . 'th Year');

	// Build admin display name from JOIN (live from admin table)
	if (!empty($student['admin_first_name']) || !empty($student['admin_last_name'])) {
		$student['validated_by'] = trim($student['admin_first_name'] . ' ' . $student['admin_last_name']);
	} elseif (empty($student['validated_by'])) {
		$student['validated_by'] = null;
	}

	// Format validated_at nicely
	if ($student['validated_at']) {
		$dt = new DateTime($student['validated_at']);
		$student['validated_at_formatted'] = $dt->format('F j, Y') . ' at ' . $dt->format('g:i A');
	} else {
		$student['validated_at_formatted'] = null;
	}

	echo json_encode(['success' => true, 'student' => $student]);

} catch (Exception $e) {
	echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
