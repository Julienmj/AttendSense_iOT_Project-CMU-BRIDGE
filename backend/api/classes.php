<?php
require_once '../config/config.php';

header('Content-Type: application/json');

class ClassesAPI {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getClasses() {
        $stmt = $this->db->query(
            "SELECT c.*, COUNT(CASE WHEN s.active=1 THEN 1 END) as student_count
             FROM classes c LEFT JOIN students s ON c.id = s.class_id
             WHERE c.active = 1 GROUP BY c.id ORDER BY c.created_at DESC"
        );
        ApiResponse::success($stmt->fetchAll());
    }

    public function getClass($id) {
        $stmt = $this->db->prepare(
            "SELECT c.*, COUNT(CASE WHEN s.active=1 THEN 1 END) as student_count
             FROM classes c LEFT JOIN students s ON c.id = s.class_id
             WHERE c.id = ? AND c.active = 1 GROUP BY c.id"
        );
        $stmt->execute([$id]);
        $class = $stmt->fetch();
        if (!$class) ApiResponse::error('Class not found', 404);
        ApiResponse::success($class);
    }

    public function createClass() {
        $data = getJsonInput();
        if (empty($data['name'])) ApiResponse::error('name is required');
        if (empty($data['course_code'])) ApiResponse::error('course_code is required');

        $stmt = $this->db->prepare("INSERT INTO classes (name, course_code, description) VALUES (?, ?, ?)");
        $stmt->execute([$data['name'], $data['course_code'], $data['description'] ?? null]);
        $data['id'] = $this->db->lastInsertId();
        $data['student_count'] = 0;
        ApiResponse::created($data, 'Class created successfully');
    }

    public function updateClass($id) {
        $data = getJsonInput();
        $fields = []; $values = [];
        foreach (['name','course_code','description'] as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $values[] = $data[$f]; }
        }
        if (empty($fields)) ApiResponse::error('No fields to update');
        $values[] = $id;
        $this->db->prepare("UPDATE classes SET ".implode(',',$fields)." WHERE id = ? AND active = 1")->execute($values);
        ApiResponse::success(['id' => $id], 'Class updated');
    }

    public function deleteClass($id) {
        $this->db->prepare("UPDATE classes SET active = 0 WHERE id = ?")->execute([$id]);
        ApiResponse::success(null, 'Class deleted');
    }

    public function getClassStudents($id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM students WHERE class_id = ? AND active = 1 ORDER BY name"
        );
        $stmt->execute([$id]);
        ApiResponse::success($stmt->fetchAll());
    }

    public function addStudentToClass($id) {
        $data = getJsonInput();
        if (empty($data['name'])) ApiResponse::error('name is required');
        if (empty($data['student_id'])) ApiResponse::error('student_id is required');
        if (empty($data['device_name'])) ApiResponse::error('device_name is required');

        $stmt = $this->db->prepare(
            "INSERT INTO students (class_id, name, student_id, email, phone, device_name)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        try {
            $stmt->execute([$id, $data['name'], $data['student_id'], $data['email'] ?? null,
                $data['phone'] ?? null, $data['device_name']]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false)
                ApiResponse::error('Device name already registered', 409);
            ApiResponse::error('Failed to add student: ' . $e->getMessage());
        }
        $data['id'] = $this->db->lastInsertId();
        $data['class_id'] = $id;
        ApiResponse::created($data, 'Student added');
    }

    public function updateStudentInClass($classId, $studentId) {
        $data = getJsonInput();
        $fields = []; $values = [];
        foreach (['name','student_id','email','phone','device_name'] as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $values[] = $data[$f]; }
        }
        if (empty($fields)) ApiResponse::error('No fields to update');
        $values[] = $studentId; $values[] = $classId;
        $this->db->prepare("UPDATE students SET ".implode(',',$fields)." WHERE id = ? AND class_id = ?")->execute($values);
        ApiResponse::success(['id' => $studentId], 'Student updated');
    }

    public function removeStudentFromClass($classId, $studentId) {
        $this->db->prepare("UPDATE students SET active = 0 WHERE id = ? AND class_id = ?")->execute([$studentId, $classId]);
        ApiResponse::success(null, 'Student removed');
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = array_map(fn($p) => preg_replace('/\.php$/', '', $p), explode('/', $path));
$ci = array_search('classes', $parts);
$segments = $ci !== false ? array_slice($parts, $ci) : ['classes'];

$api = new ClassesAPI();

try {
    if ($method === 'GET' && count($segments) === 1) {
        $api->getClasses();
    } elseif ($method === 'GET' && count($segments) === 2 && is_numeric($segments[1])) {
        $api->getClass($segments[1]);
    } elseif ($method === 'POST' && count($segments) === 1) {
        $api->createClass();
    } elseif ($method === 'PUT' && count($segments) === 2 && is_numeric($segments[1])) {
        $api->updateClass($segments[1]);
    } elseif ($method === 'DELETE' && count($segments) === 2 && is_numeric($segments[1])) {
        $api->deleteClass($segments[1]);
    } elseif ($method === 'GET' && count($segments) === 3 && $segments[2] === 'students') {
        $api->getClassStudents($segments[1]);
    } elseif ($method === 'POST' && count($segments) === 3 && $segments[2] === 'students') {
        $api->addStudentToClass($segments[1]);
    } elseif ($method === 'PUT' && count($segments) === 4 && $segments[2] === 'students') {
        $api->updateStudentInClass($segments[1], $segments[3]);
    } elseif ($method === 'DELETE' && count($segments) === 4 && $segments[2] === 'students') {
        $api->removeStudentFromClass($segments[1], $segments[3]);
    } else {
        ApiResponse::error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    ApiResponse::error('Server error: ' . $e->getMessage(), 500);
}
?>
