<?php
require_once 'server/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $student_id = $_GET['id'] ?? null;
    
    if (!$student_id) {
        die("Please provide a student ID. Example: ?id=2023-0001");
    }

    $db->beginTransaction();
    
    // 1. Reset Student Table
    $stmt1 = $db->prepare("UPDATE students SET status_id = 2, validated_at = NULL, validated_by = NULL, validated_by_id = NULL WHERE student_id = :id");
    $stmt1->execute(['id' => $student_id]);
    
    // 2. Delete Queue History permanently for this student
    $stmt2 = $db->prepare("DELETE FROM queue_list WHERE student_id = :id");
    $stmt2->execute(['id' => $student_id]);
    
    $db->commit();
    echo "<h1>Student {$student_id} has been completely reset!</h1>";
    echo "<p>They are now 'Not Validated', have no timestamps, and their queue history is wiped.</p>";
    echo "<a href='client/pages/admin/students.php'>Go back to Admin Dashboard</a>";
    
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "Error: " . $e->getMessage();
}
