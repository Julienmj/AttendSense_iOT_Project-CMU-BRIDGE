<?php
/**
 * Simple Database Test
 * Debug step by step
 */

echo "<h1>Simple Database Test</h1>\n";

// Step 1: Check if config file exists
echo "<h2>Step 1: Check Config File</h2>\n";
if (file_exists('backend/config/config.php')) {
    echo "<p style='color: green;'>✅ Config file exists</p>\n";
} else {
    echo "<p style='color: red;'>❌ Config file missing</p>\n";
    exit;
}

// Step 2: Include config and check constants
echo "<h2>Step 2: Check Database Constants</h2>\n";
require_once 'backend/config/config.php';

echo "<p>DB_HOST: " . DB_HOST . "</p>\n";
echo "<p>DB_NAME: " . DB_NAME . "</p>\n";
echo "<p>DB_USER: " . DB_USER . "</p>\n";
echo "<p>DB_PASS: " . (empty(DB_PASS) ? '(empty)' : '***') . "</p>\n";

// Step 3: Test MySQL connection without database
echo "<h2>Step 3: Test MySQL Server Connection</h2>\n";
try {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "<p style='color: green;'>✅ MySQL server connection successful!</p>\n";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ MySQL server connection failed: " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Solution:</strong> Check if MySQL is running in XAMPP control panel</p>\n";
    exit;
}

// Step 4: Test database connection
echo "<h2>Step 4: Test Database Connection</h2>\n";
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "<p style='color: green;'>✅ Database connection successful!</p>\n";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Solution:</strong> Database 'attendsense' doesn't exist. Create it in phpMyAdmin.</p>\n";
    exit;
}

// Step 5: List all databases
echo "<h2>Step 5: Available Databases</h2>\n";
try {
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>\n";
    foreach ($databases as $db) {
        if ($db !== 'information_schema' && $db !== 'mysql' && $db !== 'performance_schema') {
            echo "<li>" . htmlspecialchars($db) . "</li>\n";
        }
    }
    echo "</ul>\n";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Cannot list databases: " . $e->getMessage() . "</p>\n";
}

// Step 6: Check tables if database exists
echo "<h2>Step 6: Check Tables</h2>\n";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "<p style='color: orange;'>⚠️ No tables found in database</p>\n";
        echo "<p><strong>Solution:</strong> Import the schema.sql file</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($tables) . " tables:</p>\n";
        echo "<ul>\n";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>\n";
        }
        echo "</ul>\n";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Cannot check tables: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Next Steps</h2>\n";
echo "<ol>\n";
echo "<li>If MySQL connection fails: Start MySQL in XAMPP control panel</li>\n";
echo "<li>If database connection fails: Create 'attendsense' database in phpMyAdmin</li>\n";
echo "<li>If no tables found: Import backend/database/schema.sql in phpMyAdmin</li>\n";
echo "</ol>\n";
?>
