<?php
/**
 * AttendSense API Configuration
 * Database connection and API settings
 */

// Database Configuration
// TODO: Update these values with your actual database credentials
define('DB_HOST', 'localhost');           // Usually 'localhost' or '127.0.0.1'
define('DB_NAME', 'attendsense');         // Database name created in Step 1
define('DB_USER', 'root');               // Your MySQL username
define('DB_PASS', '');  // Your MySQL password - EMPTY since no password set
define('DB_CHARSET', 'utf8mb4');

// API Configuration
define('API_BASE_URL', 'http://localhost/attendsense/backend/api');
define('CORS_ORIGIN', '*'); // Set to your frontend domain in production

// Security
// TODO: Generate a secure random JWT secret key
define('JWT_SECRET', 'feed-world-width');  // CHANGE THIS to a random string!
define('HASH_ALGO', 'PASSWORD_DEFAULT');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers
header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database Connection Class
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

// API Response Helper
class ApiResponse {
    public static function success($data = null, $message = 'Success') {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public static function error($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }

    public static function created($data = null, $message = 'Resource created successfully') {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

// JWT Helper (Simple implementation - use firebase/php-jwt in production)
class JWT {
    public static function encode($payload, $secret, $alg = 'HS256') {
        $header = json_encode(['typ' => 'JWT', 'alg' => $alg]);
        $payload = json_encode($payload);
        
        $headerEncoded = self::base64UrlEncode($header);
        $payloadEncoded = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    public static function decode($token, $secret) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        $header = self::base64UrlDecode($parts[0]);
        $payload = self::base64UrlDecode($parts[1]);
        $signature = self::base64UrlDecode($parts[2]);
        
        $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }
        
        return json_decode($payload, true);
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

// Authentication Middleware
function requireAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        ApiResponse::error('Authorization token required', 401);
    }
    
    $token = $matches[1];
    $payload = JWT::decode($token, JWT_SECRET);
    
    if (!$payload) {
        ApiResponse::error('Invalid or expired token', 401);
    }
    
    return $payload;
}

// Input Validation
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $fieldRules) {
        $value = $data[$field] ?? null;
        
        if (in_array('required', $fieldRules) && empty($value)) {
            $errors[$field] = ucfirst($field) . ' is required';
            continue;
        }
        
        if (empty($value)) continue;
        
        if (in_array('email', $fieldRules) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = 'Invalid email format';
        }
        
        if (in_array('mac_address', $fieldRules) && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value)) {
            $errors[$field] = 'Invalid MAC address format';
        }
        
        if (isset($fieldRules['min']) && strlen($value) < $fieldRules['min']) {
            $errors[$field] = ucfirst($field) . ' must be at least ' . $fieldRules['min'] . ' characters';
        }
        
        if (isset($fieldRules['max']) && strlen($value) > $fieldRules['max']) {
            $errors[$field] = ucfirst($field) . ' must not exceed ' . $fieldRules['max'] . ' characters';
        }
    }
    
    if (!empty($errors)) {
        ApiResponse::error('Validation failed', 400, ['errors' => $errors]);
    }
    
    return $data;
}

// Get JSON input
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}
?>
