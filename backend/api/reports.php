<?php
require_once '../config/config.php';

header('Content-Type: application/json');

class ReportsAPI {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAttendanceReport() {
        $classId = $_GET['class_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $sql = "SELECT ses.id, ses.name, ses.date, ses.start_time, ses.end_time, ses.status,
                       c.id as class_id, c.name as class_name, c.course_code,
                       ses.total_students,
                       ses.present_count,
                       (ses.total_students - ses.present_count) as absent_count,
                       ROUND(CASE WHEN ses.total_students > 0
                             THEN ses.present_count * 100.0 / ses.total_students
                             ELSE 0 END, 2) as attendance_rate
                FROM sessions ses
                JOIN classes c ON ses.class_id = c.id
                WHERE c.active = 1";
        $params = [];
        if ($classId) { $sql .= " AND c.id = ?"; $params[] = $classId; }
        if ($dateFrom) { $sql .= " AND ses.date >= ?"; $params[] = $dateFrom; }
        if ($dateTo) { $sql .= " AND ses.date <= ?"; $params[] = $dateTo; }
        $sql .= " ORDER BY ses.date DESC, ses.start_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        if (($_GET['format'] ?? 'json') === 'csv') {
            $this->exportCSV($data, 'attendance_report');
        }
        ApiResponse::success($data);
    }

    public function getClassPerformance() {
        $stmt = $this->db->query(
            "SELECT c.id, c.name, c.course_code,
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT ses.id) as total_sessions,
                    ROUND(CASE WHEN COUNT(ar.id) > 0
                          THEN COUNT(CASE WHEN ar.status = 'present' THEN 1 END) * 100.0 / COUNT(ar.id)
                          ELSE 0 END, 2) as avg_attendance_rate
             FROM classes c
             LEFT JOIN students s ON c.id = s.class_id AND s.active = 1
             LEFT JOIN sessions ses ON c.id = ses.class_id
             LEFT JOIN attendance_records ar ON ses.id = ar.session_id
             WHERE c.active = 1
             GROUP BY c.id ORDER BY c.name"
        );
        ApiResponse::success($stmt->fetchAll());
    }

    public function getStudentAttendance($studentId) {
        $stmt = $this->db->prepare(
            "SELECT ar.*, ses.name as session_name, ses.date, ses.start_time,
                    c.name as class_name, c.course_code
             FROM attendance_records ar
             JOIN sessions ses ON ar.session_id = ses.id
             JOIN classes c ON ses.class_id = c.id
             WHERE ar.student_id = ? ORDER BY ses.date DESC"
        );
        $stmt->execute([$studentId]);
        ApiResponse::success($stmt->fetchAll());
    }

    public function getSummaryReport() {
        $stmt = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM classes WHERE active = 1) as total_classes,
                (SELECT COUNT(*) FROM students WHERE active = 1) as total_students,
                (SELECT COUNT(*) FROM sessions WHERE date = date('now')) as today_sessions,
                (SELECT COUNT(*) FROM sessions WHERE status = 'active') as active_sessions,
                (SELECT COUNT(*) FROM attendance_records WHERE date(timestamp) = date('now')) as today_attendance,
                (SELECT ROUND(COUNT(CASE WHEN status='present' THEN 1 END)*100.0/MAX(COUNT(*),1),2)
                 FROM attendance_records WHERE timestamp >= datetime('now','-7 days')) as weekly_attendance_rate"
        );
        ApiResponse::success($stmt->fetch());
    }

    public function getDashboardStats() {
        $this->getSummaryReport();
    }

    public function exportSessionAttendance($sessionId) {
        $stmt = $this->db->prepare(
            "SELECT s.name as student_name, s.student_id, s.email, s.phone,
                    ses.name as session_name, ses.date, ses.start_time,
                    c.name as class_name, c.course_code,
                    ar.status, ar.timestamp
             FROM attendance_records ar
             JOIN students s ON ar.student_id = s.id
             JOIN sessions ses ON ar.session_id = ses.id
             JOIN classes c ON ses.class_id = c.id
             WHERE ar.session_id = ? ORDER BY s.name"
        );
        $stmt->execute([$sessionId]);
        $data = $stmt->fetchAll();
        $this->exportCSV($data, "session_{$sessionId}_attendance_".date('Y-m-d'));
    }

    public function exportClassAttendance($classId) {
        $sql = "SELECT s.name as student_name, s.student_id, s.email,
                       ses.name as session_name, ses.date, ses.start_time,
                       c.name as class_name, c.course_code, ar.status, ar.timestamp
                FROM attendance_records ar
                JOIN students s ON ar.student_id = s.id
                JOIN sessions ses ON ar.session_id = ses.id
                JOIN classes c ON ses.class_id = c.id
                WHERE c.id = ? AND c.active = 1";
        $params = [$classId];
        if (!empty($_GET['date_from'])) { $sql .= " AND ses.date >= ?"; $params[] = $_GET['date_from']; }
        if (!empty($_GET['date_to'])) { $sql .= " AND ses.date <= ?"; $params[] = $_GET['date_to']; }
        $sql .= " ORDER BY ses.date DESC, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $this->exportCSV($stmt->fetchAll(), "class_{$classId}_attendance_".date('Y-m-d'));
    }

    private function exportCSV($data, $filename) {
        if (empty($data)) ApiResponse::error('No data to export');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys($data[0]));
        foreach ($data as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts = array_map(fn($p) => preg_replace('/\.php$/', '', $p), explode('/', $path));
$ri = array_search('reports', $parts);
$seg = $ri !== false ? array_slice($parts, $ri) : ['reports'];

$api = new ReportsAPI();

try {
    if ($method === 'GET' && count($seg) >= 2) {
        switch ($seg[1]) {
            case 'attendance':       $api->getAttendanceReport(); break;
            case 'class-performance': $api->getClassPerformance(); break;
            case 'summary':          $api->getSummaryReport(); break;
            case 'dashboard-stats':  $api->getDashboardStats(); break;
            case 'student-attendance':
                if (!empty($seg[2])) $api->getStudentAttendance($seg[2]);
                else ApiResponse::error('Student ID required', 400);
                break;
            case 'export':
                if (!empty($seg[2]) && !empty($seg[3])) {
                    if ($seg[2] === 'sessions') $api->exportSessionAttendance($seg[3]);
                    elseif ($seg[2] === 'class') $api->exportClassAttendance($seg[3]);
                }
                break;
            default: ApiResponse::error('Endpoint not found', 404);
        }
    } else {
        ApiResponse::error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    ApiResponse::error('Server error: ' . $e->getMessage(), 500);
}
?>
