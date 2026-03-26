<?php
/**
 * Sessions API
 * Handle session management and attendance operations
 */

require_once '../config/config.php';

class SessionsAPI {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api/sessions
    public function getSessions() {
        $sql = "SELECT s.*, c.name as class_name, c.course_code
                FROM sessions s
                JOIN classes c ON s.class_id = c.id
                WHERE c.active = TRUE
                ORDER BY s.date DESC, s.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $sessions = $stmt->fetchAll();
        
        ApiResponse::success($sessions);
    }

    // GET /api/sessions/{id}
    public function getSession($id) {
        $sql = "SELECT s.*, c.name as class_name, c.course_code
                FROM sessions s
                JOIN classes c ON s.class_id = c.id
                WHERE s.id = ? AND c.active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $session = $stmt->fetch();
        
        if (!$session) {
            ApiResponse::error('Session not found', 404);
        }
        
        ApiResponse::success($session);
    }

    // POST /api/sessions
    public function createSession() {
        $data = getJsonInput();
        
        $rules = [
            'class_id' => ['required'],
            'name' => ['required', 'min' => 2, 'max' => 255],
            'date' => ['required'],
            'start_time' => ['required'],
            'end_time' => ['required'],
            'checkin_window_minutes' => ['required']
        ];
        
        $data = validateInput($data, $rules);
        
        // Get student count for the class
        $studentCountSql = "SELECT COUNT(*) as count FROM students WHERE class_id = ? AND active = TRUE";
        $stmt = $this->db->prepare($studentCountSql);
        $stmt->execute([$data['class_id']]);
        $studentCount = $stmt->fetch()['count'];
        
        $sql = "INSERT INTO sessions (class_id, name, date, start_time, end_time, checkin_window_minutes, total_students) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $data['class_id'],
            $data['name'],
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $data['checkin_window_minutes'],
            $studentCount
        ])) {
            $sessionId = $this->db->lastInsertId();
            $data['id'] = $sessionId;
            $data['total_students'] = $studentCount;
            ApiResponse::created($data, 'Session created successfully');
        }
        
        ApiResponse::error('Failed to create session');
    }

    // PUT /api/sessions/{id}
    public function updateSession($id) {
        $data = getJsonInput();
        
        $rules = [
            'name' => ['min' => 2, 'max' => 255],
            'date' => [],
            'start_time' => [],
            'end_time' => [],
            'checkin_window_minutes' => []
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
        $sql = "UPDATE sessions SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($values)) {
            $data['id'] = $id;
            ApiResponse::success($data, 'Session updated successfully');
        }
        
        ApiResponse::error('Failed to update session');
    }

    // PUT /api/sessions/{id}/start
    public function startSession($id) {
        $sql = "UPDATE sessions 
                SET status = 'active', start_timestamp = CURRENT_TIMESTAMP 
                WHERE id = ? AND status = 'upcoming'";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$id])) {
            if ($stmt->rowCount() > 0) {
                ApiResponse::success(null, 'Session started successfully');
            } else {
                ApiResponse::error('Session not found or already started', 404);
            }
        }
        
        ApiResponse::error('Failed to start session');
    }

    // PUT /api/sessions/{id}/end
    public function endSession($id) {
        $this->db->beginTransaction();
        
        try {
            // Get session details
            $sessionSql = "SELECT s.*, c.id as class_id 
                          FROM sessions s 
                          JOIN classes c ON s.class_id = c.id 
                          WHERE s.id = ? AND s.status = 'active'";
            $stmt = $this->db->prepare($sessionSql);
            $stmt->execute([$id]);
            $session = $stmt->fetch();
            
            if (!$session) {
                $this->db->rollBack();
                ApiResponse::error('Session not found or not active', 404);
            }
            
            // Mark absent students who weren't detected
            $absentSql = "INSERT INTO attendance_records (session_id, student_id, status)
                          SELECT ?, id, 'absent'
                          FROM students 
                          WHERE class_id = ? AND active = TRUE
                          AND id NOT IN (
                              SELECT student_id FROM attendance_records 
                              WHERE session_id = ?
                          )";
            
            $stmt = $this->db->prepare($absentSql);
            $stmt->execute([$id, $session['class_id'], $id]);
            
            // Update session status
            $updateSql = "UPDATE sessions 
                          SET status = 'completed', end_timestamp = CURRENT_TIMESTAMP,
                          present_count = (
                              SELECT COUNT(*) FROM attendance_records 
                              WHERE session_id = ? AND status = 'present'
                          )
                          WHERE id = ?";
            
            $stmt = $this->db->prepare($updateSql);
            $stmt->execute([$id, $id]);
            
            $this->db->commit();
            ApiResponse::success(null, 'Session ended successfully');
            
        } catch (Exception $e) {
            $this->db->rollBack();
            ApiResponse::error('Failed to end session: ' . $e->getMessage());
        }
    }

    // GET /api/sessions/{id}/attendance
    public function getSessionAttendance($id) {
        $sql = "SELECT ar.*, s.name as student_name, s.student_id, s.mac_address
                FROM attendance_records ar
                JOIN students s ON ar.student_id = s.id
                WHERE ar.session_id = ?
                ORDER BY ar.timestamp";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $attendance = $stmt->fetchAll();
        
        ApiResponse::success($attendance);
    }

    // POST /api/sessions/{id}/attendance
    public function markAttendance($id) {
        $data = getJsonInput();
        
        $rules = [
            'mac_address' => ['required', 'mac_address'],
            'rssi' => []
        ];
        
        $data = validateInput($data, $rules);
        
        // Find student by MAC address
        $studentSql = "SELECT s.*, ses.class_id 
                      FROM students s
                      JOIN sessions ses ON s.class_id = ses.class_id
                      WHERE s.mac_address = ? AND s.active = TRUE AND ses.id = ?";
        
        $stmt = $this->db->prepare($studentSql);
        $stmt->execute([$data['mac_address'], $id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            ApiResponse::error('Student not found or not registered for this session', 404);
        }
        
        // Check if already marked
        $checkSql = "SELECT id FROM attendance_records 
                     WHERE session_id = ? AND student_id = ?";
        $stmt = $this->db->prepare($checkSql);
        $stmt->execute([$id, $student['id']]);
        
        if ($stmt->fetch()) {
            ApiResponse::error('Attendance already marked for this student', 409);
        }
        
        // Mark attendance using stored procedure
        $callSql = "CALL MarkAttendance(?, ?, 'present', CURRENT_TIMESTAMP, ?)";
        $stmt = $this->db->prepare($callSql);
        
        if ($stmt->execute([$id, $student['id'], $data['rssi'] ?? null])) {
            $response = [
                'student_id' => $student['id'],
                'student_name' => $student['name'],
                'student_number' => $student['student_id'],
                'status' => 'present',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            ApiResponse::success($response, 'Attendance marked successfully');
        }
        
        ApiResponse::error('Failed to mark attendance');
    }

    // POST /api/sessions/{id}/detect
    public function processDetectedDevice($id) {
        $data = getJsonInput();
        
        $rules = [
            'mac_address' => ['required', 'mac_address'],
            'rssi' => []
        ];
        
        $data = validateInput($data, $rules);
        
        // Store detected device
        $detectSql = "INSERT INTO detected_devices (session_id, mac_address, rssi) 
                      VALUES (?, ?, ?)";
        
        $stmt = $this->db->prepare($detectSql);
        $stmt->execute([$id, $data['mac_address'], $data['rssi'] ?? null]);
        
        // Try to mark attendance
        $this->markAttendance($id);
    }

    // GET /api/sessions/active
    public function getActiveSessions() {
        $sql = "SELECT s.*, c.name as class_name, c.course_code
                FROM sessions s
                JOIN classes c ON s.class_id = c.id
                WHERE s.status = 'active' AND c.active = TRUE
                ORDER BY s.date DESC, s.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $sessions = $stmt->fetchAll();
        
        ApiResponse::success($sessions);
    }

    // GET /api/sessions/today
    public function getTodaySessions() {
        $sql = "SELECT s.*, c.name as class_name, c.course_code
                FROM sessions s
                JOIN classes c ON s.class_id = c.id
                WHERE s.date = CURDATE() AND c.active = TRUE
                ORDER BY s.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $sessions = $stmt->fetchAll();
        
        ApiResponse::success($sessions);
    }

    // GET /api/sessions/stats
    public function getSessionStats() {
        $sql = "SELECT * FROM session_attendance_summary 
                ORDER BY date DESC, start_time DESC
                LIMIT 50";
        
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

$sessionsAPI = new SessionsAPI();

try {
    // Route the request
    if ($method === 'GET' && count($pathParts) === 2 && $pathParts[1] === 'sessions') {
        $sessionsAPI->getSessions();
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'sessions') {
        $sessionsAPI->getSession($pathParts[2]);
    }
    elseif ($method === 'POST' && count($pathParts) === 2 && $pathParts[1] === 'sessions') {
        $sessionsAPI->createSession();
    }
    elseif ($method === 'PUT' && count($pathParts) === 3 && $pathParts[1] === 'sessions') {
        $sessionsAPI->updateSession($pathParts[2]);
    }
    elseif ($method === 'PUT' && count($pathParts) === 4 && $pathParts[1] === 'sessions' && $pathParts[3] === 'start') {
        $sessionsAPI->startSession($pathParts[2]);
    }
    elseif ($method === 'PUT' && count($pathParts) === 4 && $pathParts[1] === 'sessions' && $pathParts[3] === 'end') {
        $sessionsAPI->endSession($pathParts[2]);
    }
    elseif ($method === 'GET' && count($pathParts) === 4 && $pathParts[1] === 'sessions' && $pathParts[3] === 'attendance') {
        $sessionsAPI->getSessionAttendance($pathParts[2]);
    }
    elseif ($method === 'POST' && count($pathParts) === 4 && $pathParts[1] === 'sessions' && $pathParts[3] === 'attendance') {
        $sessionsAPI->markAttendance($pathParts[2]);
    }
    elseif ($method === 'POST' && count($pathParts) === 4 && $pathParts[1] === 'sessions' && $pathParts[3] === 'detect') {
        $sessionsAPI->processDetectedDevice($pathParts[2]);
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'sessions' && $pathParts[2] === 'active') {
        $sessionsAPI->getActiveSessions();
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'sessions' && $pathParts[2] === 'today') {
        $sessionsAPI->getTodaySessions();
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'sessions' && $pathParts[2] === 'stats') {
        $sessionsAPI->getSessionStats();
    }
    else {
        ApiResponse::error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    ApiResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
