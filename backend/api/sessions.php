<?php
require_once '../config/config.php';

header('Content-Type: application/json');

class SessionsAPI {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getSessions() {
        $stmt = $this->db->query(
            "SELECT s.*, c.name as class_name, c.course_code
             FROM sessions s JOIN classes c ON s.class_id = c.id
             WHERE c.active = 1 ORDER BY s.date DESC, s.start_time DESC"
        );
        ApiResponse::success($stmt->fetchAll());
    }

    public function getSession($id) {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.name as class_name, c.course_code
             FROM sessions s JOIN classes c ON s.class_id = c.id
             WHERE s.id = ? AND c.active = 1"
        );
        $stmt->execute([$id]);
        $session = $stmt->fetch();
        if (!$session) ApiResponse::error('Session not found', 404);
        ApiResponse::success($session);
    }

    public function createSession() {
        $data = getJsonInput();
        foreach (['class_id','name','date','start_time','end_time'] as $f)
            if (empty($data[$f])) ApiResponse::error("$f is required");

        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM students WHERE class_id = ? AND active = 1");
        $stmt->execute([$data['class_id']]);
        $count = $stmt->fetch()['cnt'];

        $stmt = $this->db->prepare(
            "INSERT INTO sessions (class_id, name, date, start_time, end_time, checkin_window_minutes, total_students)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['class_id'], $data['name'], $data['date'],
            $data['start_time'], $data['end_time'],
            $data['checkin_window_minutes'] ?? 15, $count
        ]);
        $data['id'] = $this->db->lastInsertId();
        $data['total_students'] = $count;
        $data['present_count'] = 0;
        $data['status'] = 'upcoming';
        ApiResponse::created($data, 'Session created');
    }

    public function updateSession($id) {
        $data = getJsonInput();
        $fields = []; $values = [];
        foreach (['name','date','start_time','end_time','checkin_window_minutes','status'] as $f) {
            if (isset($data[$f])) { $fields[] = "$f = ?"; $values[] = $data[$f]; }
        }
        if (empty($fields)) ApiResponse::error('No fields to update');
        $values[] = $id;
        $this->db->prepare("UPDATE sessions SET ".implode(',',$fields)." WHERE id = ?")->execute($values);
        ApiResponse::success(['id' => $id], 'Session updated');
    }

    public function startSession($id) {
        $stmt = $this->db->prepare(
            "UPDATE sessions SET status = 'active', start_timestamp = datetime('now') WHERE id = ? AND status = 'upcoming'"
        );
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) ApiResponse::error('Session not found or already started', 404);
        ApiResponse::success(null, 'Session started');
    }

    public function endSession($id) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT * FROM sessions WHERE id = ? AND status = 'active'");
            $stmt->execute([$id]);
            $session = $stmt->fetch();
            if (!$session) { $this->db->rollBack(); ApiResponse::error('Session not active', 404); }

            // Mark absent students
            $this->db->prepare(
                "INSERT OR IGNORE INTO attendance_records (session_id, student_id, status)
                 SELECT ?, id, 'absent' FROM students
                 WHERE class_id = ? AND active = 1
                 AND id NOT IN (SELECT student_id FROM attendance_records WHERE session_id = ?)"
            )->execute([$id, $session['class_id'], $id]);

            // Update session
            $this->db->prepare(
                "UPDATE sessions SET status = 'completed', end_timestamp = datetime('now'),
                 present_count = (SELECT COUNT(*) FROM attendance_records WHERE session_id = ? AND status = 'present')
                 WHERE id = ?"
            )->execute([$id, $id]);

            $this->db->commit();
            ApiResponse::success(null, 'Session ended');
        } catch (Exception $e) {
            $this->db->rollBack();
            ApiResponse::error('Failed to end session: ' . $e->getMessage());
        }
    }

    public function getSessionAttendance($id) {
        $stmt = $this->db->prepare(
            "SELECT ar.*, s.name as student_name, s.student_id as student_number
             FROM attendance_records ar JOIN students s ON ar.student_id = s.id
             WHERE ar.session_id = ? ORDER BY ar.timestamp"
        );
        $stmt->execute([$id]);
        ApiResponse::success($stmt->fetchAll());
    }

    public function markAttendance($id) {
        $data = getJsonInput();
        if (empty($data['device_name'])) ApiResponse::error('device_name is required');

        $stmt = $this->db->prepare(
            "SELECT s.* FROM students s
             JOIN sessions ses ON s.class_id = ses.class_id
             WHERE s.device_name = ? AND s.active = 1 AND ses.id = ?"
        );
        $stmt->execute([$data['device_name'], $id]);
        $student = $stmt->fetch();
        if (!$student) ApiResponse::error('Student not found for this session', 404);

        try {
            $this->db->prepare(
                "INSERT INTO attendance_records (session_id, student_id, status, detected_at, rssi, device_address)
                 VALUES (?, ?, 'present', datetime('now'), ?, ?)"
            )->execute([$id, $student['id'], $data['rssi'] ?? null, $data['device_address'] ?? null]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false)
                ApiResponse::error('Attendance already marked', 409);
            ApiResponse::error('Failed to mark attendance');
        }

        // Update present_count
        $this->db->prepare(
            "UPDATE sessions SET present_count = (SELECT COUNT(*) FROM attendance_records WHERE session_id = ? AND status = 'present') WHERE id = ?"
        )->execute([$id, $id]);

        ApiResponse::success([
            'student_name' => $student['name'],
            'student_number' => $student['student_id'],
            'status' => 'present'
        ], 'Attendance marked');
    }

    public function processDetectedDevice($id) {
        $data = getJsonInput();
        if (empty($data['device_name'])) ApiResponse::error('device_name is required');

        $this->db->prepare(
            "INSERT INTO detected_devices (session_id, device_name, device_address, rssi) VALUES (?, ?, ?, ?)"
        )->execute([$id, $data['device_name'], $data['device_address'] ?? null, $data['rssi'] ?? null]);

        // Try to mark attendance (ignore if already marked or not found)
        $stmt = $this->db->prepare(
            "SELECT s.* FROM students s
             JOIN sessions ses ON s.class_id = ses.class_id
             WHERE s.device_name = ? AND s.active = 1 AND ses.id = ?"
        );
        $stmt->execute([$data['device_name'], $id]);
        $student = $stmt->fetch();

        if ($student) {
            try {
                $this->db->prepare(
                    "INSERT INTO attendance_records (session_id, student_id, status, detected_at, rssi, device_address)
                     VALUES (?, ?, 'present', datetime('now'), ?, ?)"
                )->execute([$id, $student['id'], $data['rssi'] ?? null, $data['device_address'] ?? null]);

                $this->db->prepare(
                    "UPDATE sessions SET present_count = (SELECT COUNT(*) FROM attendance_records WHERE session_id = ? AND status = 'present') WHERE id = ?"
                )->execute([$id, $id]);

                ApiResponse::success(['student_name' => $student['name'], 'student_number' => $student['student_id']], 'Detected and marked');
            } catch (PDOException $e) {
                // Already marked - that's fine
                ApiResponse::success(['already_marked' => true], 'Already marked');
            }
        } else {
            ApiResponse::success(['recognized' => false], 'Device not registered');
        }
    }

    public function getActiveSessions() {
        $stmt = $this->db->query(
            "SELECT s.*, c.name as class_name, c.course_code
             FROM sessions s JOIN classes c ON s.class_id = c.id
             WHERE s.status = 'active' AND c.active = 1"
        );
        ApiResponse::success($stmt->fetchAll());
    }

    public function getTodaySessions() {
        $stmt = $this->db->query(
            "SELECT s.*, c.name as class_name, c.course_code
             FROM sessions s JOIN classes c ON s.class_id = c.id
             WHERE s.date = date('now') AND c.active = 1 ORDER BY s.start_time"
        );
        ApiResponse::success($stmt->fetchAll());
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = array_map(fn($p) => preg_replace('/\.php$/', '', $p), explode('/', $path));
$si = array_search('sessions', $parts);
$seg = $si !== false ? array_slice($parts, $si) : ['sessions'];

$api = new SessionsAPI();

try {
    if ($method === 'GET' && count($seg) === 1) {
        $api->getSessions();
    } elseif ($method === 'GET' && count($seg) === 2 && $seg[1] === 'active') {
        $api->getActiveSessions();
    } elseif ($method === 'GET' && count($seg) === 2 && $seg[1] === 'today') {
        $api->getTodaySessions();
    } elseif ($method === 'GET' && count($seg) === 2 && is_numeric($seg[1])) {
        $api->getSession($seg[1]);
    } elseif ($method === 'POST' && count($seg) === 1) {
        $api->createSession();
    } elseif ($method === 'PUT' && count($seg) === 2 && is_numeric($seg[1])) {
        $api->updateSession($seg[1]);
    } elseif ($method === 'PUT' && count($seg) === 3 && $seg[2] === 'start') {
        $api->startSession($seg[1]);
    } elseif ($method === 'PUT' && count($seg) === 3 && $seg[2] === 'end') {
        $api->endSession($seg[1]);
    } elseif ($method === 'GET' && count($seg) === 3 && $seg[2] === 'attendance') {
        $api->getSessionAttendance($seg[1]);
    } elseif ($method === 'POST' && count($seg) === 3 && $seg[2] === 'attendance') {
        $api->markAttendance($seg[1]);
    } elseif ($method === 'POST' && count($seg) === 3 && $seg[2] === 'detect') {
        $api->processDetectedDevice($seg[1]);
    } else {
        ApiResponse::error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    ApiResponse::error('Server error: ' . $e->getMessage(), 500);
}
?>
