<?php
/**
 * Classes API
 * Handle class management operations
 */

require_once '../config/config.php';

// Include Database class if it doesn't exist
if (!class_exists('Database')) {
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
}

class ClassesAPI {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api/classes
    public function getClasses() {
        $sql = "SELECT c.*, 
                       COUNT(s.id) as student_count,
                       COUNT(CASE WHEN s.active = TRUE THEN 1 END) as active_student_count
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id
                WHERE c.active = TRUE
                GROUP BY c.id
                ORDER BY c.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $classes = $stmt->fetchAll();
        
        ApiResponse::success($classes);
    }

    // GET /api/classes/{id}
    public function getClass($id) {
        $sql = "SELECT c.*, 
                       COUNT(s.id) as student_count,
                       COUNT(CASE WHEN s.active = TRUE THEN 1 END) as active_student_count
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id
                WHERE c.id = ? AND c.active = TRUE
                GROUP BY c.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $class = $stmt->fetch();
        
        if (!$class) {
            ApiResponse::error('Class not found', 404);
        }
        
        ApiResponse::success($class);
    }

    // POST /api/classes
    public function createClass() {
        $data = getJsonInput();
        
        $rules = [
            'name' => ['required', 'min' => 2, 'max' => 255],
            'course_code' => ['required', 'min' => 2, 'max' => 50],
            'description' => ['max' => 1000]
        ];
        
        $data = validateInput($data, $rules);
        
        $sql = "INSERT INTO classes (name, course_code, description) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$data['name'], $data['course_code'], $data['description'] ?? null])) {
            $classId = $this->db->lastInsertId();
            $data['id'] = $classId;
            ApiResponse::created($data, 'Class created successfully');
        }
        
        ApiResponse::error('Failed to create class');
    }

    // PUT /api/classes/{id}
    public function updateClass($id) {
        $data = getJsonInput();
        
        $rules = [
            'name' => ['min' => 2, 'max' => 255],
            'course_code' => ['min' => 2, 'max' => 50],
            'description' => ['max' => 1000]
        ];
        
        $data = validateInput($data, $rules);
        
        // Build dynamic update query
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            ApiResponse::error('No valid fields to update');
        }
        
        $values[] = $id;
        $sql = "UPDATE classes SET " . implode(', ', $fields) . " WHERE id = ? AND active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($values)) {
            $data['id'] = $id;
            ApiResponse::success($data, 'Class updated successfully');
        }
        
        ApiResponse::error('Failed to update class');
    }

    // DELETE /api/classes/{id}
    public function deleteClass($id) {
        $sql = "UPDATE classes SET active = FALSE WHERE id = ? AND active = TRUE";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            ApiResponse::success(null, 'Class deleted successfully');
        }
        
        ApiResponse::error('Failed to delete class');
    }

    // GET /api/classes/{id}/students
    public function getClassStudents($id) {
        $sql = "SELECT s.* FROM students s 
                WHERE s.class_id = ? AND s.active = TRUE
                ORDER BY s.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $students = $stmt->fetchAll();
        
        ApiResponse::success($students);
    }

    // POST /api/classes/{id}/students
    public function addStudentToClass($id) {
        $data = getJsonInput();
        
        $rules = [
            'name' => ['required', 'min' => 2, 'max' => 255],
            'student_id' => ['required', 'min' => 2, 'max' => 50],
            'email' => ['email'],
            'phone' => ['max' => 20],
            'device_name' => ['max' => 255],
            'mac_address' => ['required', 'mac_address']
        ];
        
        $data = validateInput($data, $rules);
        $data['class_id'] = $id;
        
        $sql = "INSERT INTO students (class_id, name, student_id, email, phone, device_name, mac_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $data['class_id'],
            $data['name'],
            $data['student_id'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['device_name'] ?? null,
            $data['mac_address']
        ])) {
            $studentId = $this->db->lastInsertId();
            $data['id'] = $studentId;
            ApiResponse::created($data, 'Student added to class successfully');
        }
        
        ApiResponse::error('Failed to add student to class');
    }

    // PUT /api/classes/{class_id}/students/{student_id}
    public function updateStudentInClass($classId, $studentId) {
        $data = getJsonInput();
        
        $rules = [
            'name' => ['min' => 2, 'max' => 255],
            'student_id' => ['min' => 2, 'max' => 50],
            'email' => ['email'],
            'phone' => ['max' => 20],
            'device_name' => ['max' => 255],
            'mac_address' => ['mac_address']
        ];
        
        $data = validateInput($data, $rules);
        
        // Build dynamic update query
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            ApiResponse::error('No valid fields to update');
        }
        
        $values[] = $studentId;
        $values[] = $classId;
        $sql = "UPDATE students SET " . implode(', ', $fields) . " 
                WHERE id = ? AND class_id = ? AND active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($values)) {
            $data['id'] = $studentId;
            ApiResponse::success($data, 'Student updated successfully');
        }
        
        ApiResponse::error('Failed to update student');
    }

    // DELETE /api/classes/{class_id}/students/{student_id}
    public function removeStudentFromClass($classId, $studentId) {
        $sql = "UPDATE students SET active = FALSE 
                WHERE id = ? AND class_id = ? AND active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$studentId, $classId])) {
            ApiResponse::success(null, 'Student removed from class successfully');
        }
        
        ApiResponse::error('Failed to remove student from class');
    }

    // GET /api/classes/stats
    public function getClassStats() {
        $sql = "SELECT * FROM class_statistics ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetchAll();
        
        ApiResponse::success($stats);
    }
}

// Handle API requests
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

$classesAPI = new ClassesAPI();

try {
    // Route the request
    if ($method === 'GET' && count($pathParts) === 2 && $pathParts[1] === 'classes') {
        $classesAPI->getClasses();
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'classes') {
        $classesAPI->getClass($pathParts[2]);
    }
    elseif ($method === 'POST' && count($pathParts) === 2 && $pathParts[1] === 'classes') {
        $classesAPI->createClass();
    }
    elseif ($method === 'PUT' && count($pathParts) === 3 && $pathParts[1] === 'classes') {
        $classesAPI->updateClass($pathParts[2]);
    }
    elseif ($method === 'DELETE' && count($pathParts) === 3 && $pathParts[1] === 'classes') {
        $classesAPI->deleteClass($pathParts[2]);
    }
    elseif ($method === 'GET' && count($pathParts) === 4 && $pathParts[1] === 'classes' && $pathParts[3] === 'students') {
        $classesAPI->getClassStudents($pathParts[2]);
    }
    elseif ($method === 'POST' && count($pathParts) === 4 && $pathParts[1] === 'classes' && $pathParts[3] === 'students') {
        $classesAPI->addStudentToClass($pathParts[2]);
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'classes' && $pathParts[3] === 'stats') {
        $classesAPI->getClassStats();
    }
    elseif ($method === 'PUT' && count($pathParts) === 5 && $pathParts[1] === 'classes' && $pathParts[3] === 'students') {
        $classesAPI->updateStudentInClass($pathParts[2], $pathParts[4]);
    }
    elseif ($method === 'DELETE' && count($pathParts) === 5 && $pathParts[1] === 'classes' && $pathParts[3] === 'students') {
        $classesAPI->removeStudentFromClass($pathParts[2], $pathParts[4]);
    }
    else {
        ApiResponse::error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    ApiResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
