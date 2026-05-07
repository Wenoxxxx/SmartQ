<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../utils/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'message' => 'Invalid request method']);
	exit;
}

$student_id = $_POST['student_id'] ?? '';

if (empty($student_id)) {
	echo json_encode(['success' => false, 'message' => 'Student ID is required']);
	exit;
}

try {
	$database = new Database();
	$db = $database->getConnection();

	// Fetch student details
	$query = "SELECT first_name, email FROM students WHERE student_id = :id LIMIT 1";
	$stmt = $db->prepare($query);
	$stmt->bindParam(':id', $student_id);
	$stmt->execute();
	$student = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$student) {
		echo json_encode(['success' => false, 'message' => 'Student not found']);
		exit;
	}

	$email = $student['email'];
	$name = $student['first_name'];

	// Send email
	$mailer = new Mailer();

	// Embed the header image
	$headerPath = realpath(__DIR__ . '/../../../client/assets/img/sq_header.png');
	if ($headerPath) {
		$mailer->addEmbeddedImage($headerPath, 'sq_header', 'sq_header.png');
	}

	$subject = "SmartQ - ID Validation Reminder";
	$body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
        " . ($headerPath ? "<img src='cid:sq_header' alt='SmartQ Header' style='width: 100%; height: auto; display: block;'>" : "") . "
        <div style='padding: 30px; color: #333; line-height: 1.6;'>
            <h2 style='color: #2563eb; margin-top: 0;'>Hello {$name},</h2>
            <p>This is a courteous reminder from the <strong>SmartQ Admin Team</strong>.</p>
            <p>Our records indicate that your Student ID <strong>{$student_id}</strong> is currently <strong style='color: #ef4444;'>NOT VALIDATED</strong>.</p>
            <p>To ensure uninterrupted access and proper enrollment for this semester, please visit the registrar's office at your earliest convenience to complete the validation process.</p>
            <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;'>
                <p style='margin-bottom: 5px;'>Thank you for your prompt attention to this matter.</p>
                <p style='margin-top: 0;'>Best regards,<br><strong>SmartQ Team</strong></p>
            </div>
        </div>
    </div>
    ";


	if ($mailer->sendEmail($email, $subject, $body)) {
		echo json_encode(['success' => true, 'message' => 'Reminder email sent successfully to ' . $email]);
	} else {
		echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
	}

} catch (Exception $e) {
	echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

