<?php
/**
 * SQLite Database Setup and Initialization
 * Creates the SQLite database and runs the schema
 */

require_once __DIR__ . '/config/config.php';

// Check if SQLite is available
if (!extension_loaded('pdo_sqlite')) {
    die("SQLite extension is not available. Please install php-sqlite or enable it in php.ini\n");
}

try {
    // Create database file and connect
    $db = Database::getInstance()->getConnection();

    // Read and execute the schema
    $schemaFile = __DIR__ . '/../database/attendsense.sqlite.sql';
    if (!file_exists($schemaFile)) {
        die("Schema file not found: $schemaFile\n");
    }

    $schema = file_get_contents($schemaFile);
    if ($schema === false) {
        die("Could not read schema file\n");
    }

    // Execute schema (SQLite doesn't support multiple statements directly)
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
            try {
                $db->exec($statement);
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Ignore duplicate table/view/index errors
                if (!preg_match('/already exists/', $e->getMessage())) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    echo "\n✅ SQLite database setup complete!\n";
    echo "Database file: " . DB_FILE . "\n";

    // Test basic functionality
    $stmt = $db->query("SELECT COUNT(*) as total_classes FROM classes");
    $result = $stmt->fetch();
    echo "Total classes in database: " . $result['total_classes'] . "\n";

    $stmt = $db->query("SELECT COUNT(*) as total_students FROM students");
    $result = $stmt->fetch();
    echo "Total students in database: " . $result['total_students'] . "\n";

    $stmt = $db->query("SELECT COUNT(*) as total_sessions FROM sessions");
    $result = $stmt->fetch();
    echo "Total sessions in database: " . $result['total_sessions'] . "\n";

    echo "\n🎉 AttendSense SQLite database is ready!\n";

} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
