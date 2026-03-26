<?php
/**
 * AttendSense Configuration Test
 * Test database connection and API setup
 */

echo "<h1>AttendSense Configuration Test</h1>\n";

// Test 1: Database Connection
echo "<h2>1. Testing Database Connection</h2>\n";

try {
    // Include the Database class
    require_once 'backend/config/config.php';
    
    // Test database connection manually
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "<p style='color: green;'>✅ Database connection successful!</p>\n";
        
        // Test tables exist
        $tables = ['classes', 'students', 'sessions', 'attendance_records', 'users'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✅ Table '$table' exists</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' missing</p>\n";
            }
        }
        
    } catch (PDOException $e) {
        throw new Exception($e->getMessage());
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Please check:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>MySQL server is running (check XAMPP control panel)</li>\n";
    echo "<li>Database credentials in config.php are correct</li>\n";
    echo "<li>Database 'attendsense' exists</li>\n";
    echo "</ul>\n";
}

// Test 2: PHP Extensions
echo "<h2>2. Testing PHP Extensions</h2>\n";

$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ Extension '$ext' is loaded</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Extension '$ext' is missing</p>\n";
    }
}

// Test 3: File Permissions
echo "<h2>3. Testing File Permissions</h2>\n";

$paths = [
    'backend/config/config.php',
    'backend/api/classes.php',
    'backend/api/sessions.php',
    'backend/api/reports.php'
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        if (is_readable($path)) {
            echo "<p style='color: green;'>✅ '$path' is readable</p>\n";
        } else {
            echo "<p style='color: red;'>❌ '$path' is not readable</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ '$path' does not exist</p>\n";
    }
}

// Test 4: API Endpoints
echo "<h2>4. Testing API Endpoints</h2>\n";

$base_url = 'http://localhost/attendsense/api';
$endpoints = [
    'classes' => 'GET',
    'sessions' => 'GET',
    'reports/dashboard-stats' => 'GET'
];

foreach ($endpoints as $endpoint => $method) {
    $url = "$base_url/$endpoint";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        echo "<p style='color: green;'>✅ GET /api/$endpoint - Working ($http_code)</p>\n";
    } else {
        echo "<p style='color: red;'>❌ GET /api/$endpoint - Failed ($http_code)</p>\n";
        if ($response) {
            echo "<p style='color: orange;'>Response: " . htmlspecialchars(substr($response, 0, 200)) . "...</p>\n";
        }
    }
}

// Test 5: Configuration Values
echo "<h2>5. Configuration Summary</h2>\n";

echo "<table border='1' style='border-collapse: collapse; width: 50%;'>\n";
echo "<tr><th>Setting</th><th>Value</th></tr>\n";
echo "<tr><td>DB_HOST</td><td>" . DB_HOST . "</td></tr>\n";
echo "<tr><td>DB_NAME</td><td>" . DB_NAME . "</td></tr>\n";
echo "<tr><td>DB_USER</td><td>" . DB_USER . "</td></tr>\n";
echo "<tr><td>DB_PASS</td><td>" . (empty(DB_PASS) ? '<span style="color: red;">EMPTY - Please set!</span>' : '***') . "</td></tr>\n";
echo "<tr><td>JWT_SECRET</td><td>" . (JWT_SECRET === 'generate-secure-random-key-here' ? '<span style="color: red;">NOT SET - Please change!</span>' : '***') . "</td></tr>\n";
echo "</table>\n";

// Next Steps
echo "<h2>6. Next Steps</h2>\n";

echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>\n";
echo "<h3>If all tests pass:</h3>\n";
echo "<ol>\n";
echo "<li>Update your Vue.js frontend to use the API endpoints</li>\n";
echo "<li>Test the complete system with your Arduino hardware</li>\n";
echo "<li>Deploy to production server when ready</li>\n";
echo "</ol>\n";

echo "<h3>If tests fail:</h3>\n";
echo "<ol>\n";
echo "<li>Fix database connection issues first</li>\n";
echo "<li>Install missing PHP extensions</li>\n";
echo "<li>Check web server configuration</li>\n";
echo "<li>Verify file permissions</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
