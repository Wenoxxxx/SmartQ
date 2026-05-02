<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

require_once "../../config/database.php";
$database = new Database();
$db = $database->getConnection();

$type = isset($_GET['type']) ? $_GET['type'] : 'filtered';
$filename = "SmartQ_Report_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'filtered') {
    // ── Filtered Detailed Report ──
    $year = isset($_GET['year']) ? $_GET['year'] : '';
    $college = isset($_GET['college']) ? $_GET['college'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';

    $sql = "SELECT s.student_id, s.first_name, s.last_name, s.email, s.yearlvl, c.college_name, vs.status_name, s.validated_at, s.validated_by 
            FROM students s
            LEFT JOIN colleges c ON s.college_id = c.college_id
            LEFT JOIN validation_status vs ON s.status_id = vs.status_id
            WHERE 1=1";
    $params = [];

    if ($year !== '') { $sql .= " AND s.yearlvl = :year"; $params[':year'] = $year; }
    if ($college !== '') { $sql .= " AND s.college_id = :college"; $params[':college'] = $college; }
    if ($status !== '') { $sql .= " AND s.status_id = :status"; $params[':status'] = $status; }

    $sql .= " ORDER BY s.last_name ASC, s.first_name ASC";
    
    fputcsv($output, ['Student ID', 'First Name', 'Last Name', 'Email', 'Year Level', 'College', 'Status', 'Validated At', 'Validated By']);
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
        $row['yearlvl'] = $year_labels[$row['yearlvl']] ?? $row['yearlvl'] . 'th Year';
        fputcsv($output, $row);
    }

} elseif ($type === 'college') {
    // ── Detailed Report Grouped by College, Sorted by Last Name ──
    $sql = "SELECT s.student_id, s.first_name, s.last_name, s.email, s.yearlvl, c.college_name, vs.status_name, s.validated_at, s.validated_by 
            FROM students s
            LEFT JOIN colleges c ON s.college_id = c.college_id
            LEFT JOIN validation_status vs ON s.status_id = vs.status_id
            ORDER BY c.college_name ASC, s.last_name ASC, s.first_name ASC";
    
    fputcsv($output, ['College', 'Student ID', 'First Name', 'Last Name', 'Email', 'Year Level', 'Status', 'Validated At', 'Validated By']);
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
        $row['yearlvl'] = $year_labels[$row['yearlvl']] ?? $row['yearlvl'] . 'th Year';
        fputcsv($output, $row);
    }

} elseif ($type === 'year') {
    // ── Detailed Report Grouped by Year, Sorted by Last Name ──
    $sql = "SELECT s.student_id, s.first_name, s.last_name, s.email, s.yearlvl, c.college_name, vs.status_name, s.validated_at, s.validated_by 
            FROM students s
            LEFT JOIN colleges c ON s.college_id = c.college_id
            LEFT JOIN validation_status vs ON s.status_id = vs.status_id
            ORDER BY s.yearlvl ASC, s.last_name ASC, s.first_name ASC";
    
    fputcsv($output, ['Year Level', 'Student ID', 'First Name', 'Last Name', 'Email', 'College', 'Status', 'Validated At', 'Validated By']);
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['yearlvl'] = $year_labels[$row['yearlvl']] ?? $row['yearlvl'] . 'th Year';
        fputcsv($output, $row);
    }

} elseif ($type === 'college_specific') {
    // ── Detailed Report for Specific College ──
    $college_id = isset($_GET['college_id']) ? $_GET['college_id'] : '';
    $sql = "SELECT s.student_id, s.first_name, s.last_name, s.email, s.yearlvl, c.college_name, vs.status_name, s.validated_at, s.validated_by 
            FROM students s
            LEFT JOIN colleges c ON s.college_id = c.college_id
            LEFT JOIN validation_status vs ON s.status_id = vs.status_id
            WHERE s.college_id = :cid
            ORDER BY s.last_name ASC, s.first_name ASC";
    
    fputcsv($output, ['College', 'Student ID', 'First Name', 'Last Name', 'Email', 'Year Level', 'Status', 'Validated At', 'Validated By']);
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':cid' => $college_id]);
    
    $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['yearlvl'] = $year_labels[$row['yearlvl']] ?? $row['yearlvl'] . 'th Year';
        fputcsv($output, $row);
    }

} elseif ($type === 'comparison_summary') {
    // ── Validation Summary & Comparison Report ──
    
    // 1. Overall Summary
    $sql_overall = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN vs.status_name = 'Validated' THEN 1 ELSE 0 END) as validated,
        SUM(CASE WHEN vs.status_name = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN vs.status_name = 'Not Validated' THEN 1 ELSE 0 END) as not_validated
        FROM students s LEFT JOIN validation_status vs ON s.status_id = vs.status_id";
    $stmt = $db->query($sql_overall);
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $overall['total'] ?: 1;
    
    fputcsv($output, ['--- OVERALL VALIDATION SUMMARY ---']);
    fputcsv($output, ["{$overall['validated']} out of {$overall['total']} students are Validated (" . round(($overall['validated']/$total)*100, 2) . "%)"]);
    fputcsv($output, ["{$overall['not_validated']} out of {$overall['total']} students are Not Validated (" . round(($overall['not_validated']/$total)*100, 2) . "%)"]);
    fputcsv($output, ["{$overall['pending']} out of {$overall['total']} students are Pending (" . round(($overall['pending']/$total)*100, 2) . "%)"]);
    fputcsv($output, []);

    // 2. College Comparison
    fputcsv($output, ['--- COLLEGE COMPARISON ---']);
    fputcsv($output, ['College', 'Total Students', 'Validated', 'Pending', 'Not Validated', 'Validation Rate']);
    
    $sql_college = "SELECT 
        c.college_name,
        COUNT(s.student_id) as total,
        SUM(CASE WHEN vs.status_name = 'Validated' THEN 1 ELSE 0 END) as validated,
        SUM(CASE WHEN vs.status_name = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN vs.status_name = 'Not Validated' THEN 1 ELSE 0 END) as not_validated
        FROM colleges c
        LEFT JOIN students s ON c.college_id = s.college_id
        LEFT JOIN validation_status vs ON s.status_id = vs.status_id
        GROUP BY c.college_id, c.college_name
        ORDER BY c.college_name ASC";
    
    $stmt = $db->query($sql_college);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $c_total = $row['total'] ?: 1;
        $rate = round(($row['validated'] / $c_total) * 100, 2) . '%';
        if ($row['total'] == 0) $rate = 'N/A';
        fputcsv($output, [$row['college_name'], $row['total'], $row['validated'] ?: 0, $row['pending'] ?: 0, $row['not_validated'] ?: 0, $rate]);
    }
    fputcsv($output, []);

    // 3. Year Level Comparison
    fputcsv($output, ['--- YEAR LEVEL COMPARISON ---']);
    fputcsv($output, ['Year Level', 'Total Students', 'Validated', 'Pending', 'Not Validated', 'Validation Rate']);
    
    $sql_year = "SELECT 
        s.yearlvl,
        COUNT(s.student_id) as total,
        SUM(CASE WHEN vs.status_name = 'Validated' THEN 1 ELSE 0 END) as validated,
        SUM(CASE WHEN vs.status_name = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN vs.status_name = 'Not Validated' THEN 1 ELSE 0 END) as not_validated
        FROM students s
        LEFT JOIN validation_status vs ON s.status_id = vs.status_id
        GROUP BY s.yearlvl
        ORDER BY s.yearlvl ASC";
        
    $stmt = $db->query($sql_year);
    $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!$row['yearlvl']) continue;
        $y_total = $row['total'] ?: 1;
        $rate = round(($row['validated'] / $y_total) * 100, 2) . '%';
        $label = $year_labels[$row['yearlvl']] ?? $row['yearlvl'] . 'th Year';
        fputcsv($output, [$label, $row['total'], $row['validated'] ?: 0, $row['pending'] ?: 0, $row['not_validated'] ?: 0, $rate]);
    }
}

fclose($output);
exit();
