<?php
require_once 'server/config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Connection successful!\n";
    $tables = ['queue_schedule', 'queue_list', 'system_stats', 'students', 'admin'];
    foreach ($tables as $table) {
        echo "\nTable: $table\n";
        $stmt = $db->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Default']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
