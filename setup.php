<?php
/**
 * AttendSense Database Initialization
 * Run once to set up the SQLite database
 * Visit: http://localhost/attendsense/setup.php
 */

require_once 'backend/config/config.php';

try {
    $db = Database::getInstance()->getConnection();

    // Disable foreign keys for clean setup
    $db->exec('PRAGMA foreign_keys = OFF;');

    // Drop existing tables
    foreach (['attendance_records', 'detected_devices', 'sessions', 'students', 'classes'] as $table) {
        $db->exec("DROP TABLE IF EXISTS $table");
    }

    $db->exec('PRAGMA foreign_keys = ON;');

    // Run schema
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
        $db->exec($stmt);
    }

    echo json_encode(['success' => true, 'message' => 'Database initialized successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>