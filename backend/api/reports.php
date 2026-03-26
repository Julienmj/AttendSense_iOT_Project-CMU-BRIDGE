<?php
/**
 * Reports API
 * Handle reporting and analytics operations
 */

require_once '../config/config.php';

class ReportsAPI {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // GET /api/reports/attendance
    public function getAttendanceReport() {
        $classId = $_GET['class_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $format = $_GET['format'] ?? 'json';
        
        $sql = "SELECT sas.*, 
                       COUNT(CASE WHEN sas.present_count > 0 THEN 1 END) as sessions_with_attendance
                FROM session_attendance_summary sas
                WHERE 1=1";
        
        $params = [];
        
        if ($classId) {
            $sql .= " AND sas.class_id = ?";
            $params[] = $classId;
        }
        
        if ($dateFrom) {
            $sql .= " AND sas.date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND sas.date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY sas.date DESC, sas.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        if ($format === 'csv') {
            $this->exportToCSV($data, 'attendance_report');
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, 'attendance_report');
        } else {
            ApiResponse::success($data);
        }
    }

    // GET /api/reports/class-performance
    public function getClassPerformance() {
        $sql = "SELECT cs.*, 
                       COUNT(DISTINCT ses.id) as total_sessions,
                       AVG(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) * 100 as avg_attendance_rate
                FROM class_statistics cs
                LEFT JOIN sessions ses ON cs.id = ses.class_id
                LEFT JOIN attendance_records ar ON ses.id = ar.session_id
                GROUP BY cs.id, cs.name, cs.course_code
                ORDER BY cs.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        ApiResponse::success($data);
    }

    // GET /api/reports/student-attendance/{student_id}
    public function getStudentAttendance($studentId) {
        $sql = "SELECT ar.*, ses.name as session_name, ses.date, ses.start_time,
                       c.name as class_name, c.course_code
                FROM attendance_records ar
                JOIN sessions ses ON ar.session_id = ses.id
                JOIN classes c ON ses.class_id = c.id
                WHERE ar.student_id = ?
                ORDER BY ses.date DESC, ses.start_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentId]);
        $data = $stmt->fetchAll();
        
        ApiResponse::success($data);
    }

    // GET /api/reports/summary
    public function getSummaryReport() {
        $sql = "SELECT 
                    COUNT(DISTINCT c.id) as total_classes,
                    COUNT(DISTINCT s.id) as total_students,
                    COUNT(DISTINCT ses.id) as total_sessions,
                    COUNT(DISTINCT CASE WHEN ses.status = 'completed' THEN ses.id END) as completed_sessions,
                    COUNT(ar.id) as total_attendance_records,
                    COUNT(CASE WHEN ar.status = 'present' THEN 1 END) as total_present,
                    COUNT(CASE WHEN ar.status = 'absent' THEN 1 END) as total_absent,
                    COUNT(CASE WHEN ar.status = 'late' THEN 1 END) as total_late,
                    ROUND(COUNT(CASE WHEN ar.status = 'present' THEN 1 END) / COUNT(ar.id) * 100, 2) as overall_attendance_rate
                FROM classes c
                LEFT JOIN students s ON c.id = s.class_id AND s.active = TRUE
                LEFT JOIN sessions ses ON c.id = ses.class_id
                LEFT JOIN attendance_records ar ON ses.id = ar.session_id
                WHERE c.active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch();
        
        ApiResponse::success($data);
    }

    // GET /api/reports/export/sessions/{session_id}
    public function exportSessionAttendance($sessionId) {
        $sql = "SELECT ar.*, s.name as student_name, s.student_id, s.email, s.phone,
                       ses.name as session_name, ses.date, ses.start_time,
                       c.name as class_name, c.course_code
                FROM attendance_records ar
                JOIN students s ON ar.student_id = s.id
                JOIN sessions ses ON ar.session_id = ses.id
                JOIN classes c ON ses.class_id = c.id
                WHERE ar.session_id = ?
                ORDER BY s.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);
        $data = $stmt->fetchAll();
        
        $format = $_GET['format'] ?? 'csv';
        $filename = "session_{$sessionId}_attendance_" . date('Y-m-d');
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename);
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename);
        } else {
            ApiResponse::success($data);
        }
    }

    // GET /api/reports/export/class/{class_id}
    public function exportClassAttendance($classId) {
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $format = $_GET['format'] ?? 'csv';
        
        $sql = "SELECT ar.*, s.name as student_name, s.student_id, s.email, s.phone,
                       ses.name as session_name, ses.date, ses.start_time,
                       c.name as class_name, c.course_code
                FROM attendance_records ar
                JOIN students s ON ar.student_id = s.id
                JOIN sessions ses ON ar.session_id = ses.id
                JOIN classes c ON ses.class_id = c.id
                WHERE c.id = ? AND c.active = TRUE";
        
        $params = [$classId];
        
        if ($dateFrom) {
            $sql .= " AND ses.date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND ses.date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY ses.date DESC, ses.start_time DESC, s.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        $filename = "class_{$classId}_attendance_" . date('Y-m-d');
        
        if ($format === 'csv') {
            $this->exportToCSV($data, $filename);
        } elseif ($format === 'pdf') {
            $this->exportToPDF($data, $filename);
        } else {
            ApiResponse::success($data);
        }
    }

    // Export to CSV
    private function exportToCSV($data, $filename) {
        if (empty($data)) {
            ApiResponse::error('No data to export');
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    // Export to PDF (simple implementation - use TCPDF in production)
    private function exportToPDF($data, $filename) {
        if (empty($data)) {
            ApiResponse::error('No data to export');
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        
        // Simple PDF generation (for production, use TCPDF or FPDF)
        echo '<!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .header { text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>AttendSense Attendance Report</h1>
                <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
            </div>
            <table>';
        
        // Header row
        if (!empty($data)) {
            echo '<tr>';
            foreach (array_keys($data[0]) as $header) {
                echo '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
            }
            echo '</tr>';
            
            // Data rows
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $cell) {
                    echo '<td>' . htmlspecialchars($cell) . '</td>';
                }
                echo '</tr>';
            }
        }
        
        echo '</table>
        </body>
        </html>';
        
        exit;
    }

    // GET /api/reports/dashboard-stats
    public function getDashboardStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM classes WHERE active = TRUE) as total_classes,
                    (SELECT COUNT(*) FROM students WHERE active = TRUE) as total_students,
                    (SELECT COUNT(*) FROM sessions WHERE DATE(date) = CURDATE()) as today_sessions,
                    (SELECT COUNT(*) FROM sessions WHERE status = 'active') as active_sessions,
                    (SELECT COUNT(*) FROM attendance_records WHERE DATE(timestamp) = CURDATE()) as today_attendance,
                    (SELECT ROUND(AVG(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) * 100, 2) 
                     FROM attendance_records ar 
                     WHERE ar.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as weekly_attendance_rate";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch();
        
        ApiResponse::success($data);
    }
}

// Handle API requests
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

$reportsAPI = new ReportsAPI();

try {
    // Route the request
    if ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'reports' && $pathParts[2] === 'attendance') {
        $reportsAPI->getAttendanceReport();
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'reports' && $pathParts[2] === 'class-performance') {
        $reportsAPI->getClassPerformance();
    }
    elseif ($method === 'GET' && count($pathParts) === 4 && $pathParts[1] === 'reports' && $pathParts[2] === 'student-attendance') {
        $reportsAPI->getStudentAttendance($pathParts[3]);
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'reports' && $pathParts[2] === 'summary') {
        $reportsAPI->getSummaryReport();
    }
    elseif ($method === 'GET' && count($pathParts) === 5 && $pathParts[1] === 'reports' && $pathParts[2] === 'export' && $pathParts[3] === 'sessions') {
        $reportsAPI->exportSessionAttendance($pathParts[4]);
    }
    elseif ($method === 'GET' && count($pathParts) === 5 && $pathParts[1] === 'reports' && $pathParts[2] === 'export' && $pathParts[3] === 'class') {
        $reportsAPI->exportClassAttendance($pathParts[4]);
    }
    elseif ($method === 'GET' && count($pathParts) === 3 && $pathParts[1] === 'reports' && $pathParts[2] === 'dashboard-stats') {
        $reportsAPI->getDashboardStats();
    }
    else {
        ApiResponse::error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    ApiResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
