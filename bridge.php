<?php
/**
 * AttendSense Arduino Serial Bridge
 * Reads device names from Arduino and marks attendance
 * Usage: php bridge.php COM3 (Windows) or php bridge.php /dev/ttyUSB0 (Linux/Mac)
 */

require_once 'backend/config/config.php';

class ArduinoBridge {
    private $db;
    private $port;
    private $running = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function start($port) {
        $this->port = $port;
        $this->running = true;

        echo "=================================\n";
        echo " AttendSense Arduino Bridge\n";
        echo "=================================\n";
        echo "Port: $port\n";
        echo "Commands: start, stop, quit\n";
        echo "Or paste: DETECTED:DeviceName,MAC,RSSI\n\n";

        $this->showActiveSession();
        $this->showRegisteredDevices();

        while ($this->running) {
            $input = trim(fgets(STDIN));

            switch ($input) {
                case 'quit':
                    $this->running = false;
                    echo "Bridge stopped.\n";
                    break;

                case 'start':
                    echo "Sending START to Arduino...\n";
                    echo "Waiting for DETECTED: messages from Arduino...\n";
                    break;

                case 'stop':
                    echo "Sending STOP to Arduino...\n";
                    break;

                case 'session':
                    $this->showActiveSession();
                    break;

                case 'devices':
                    $this->showRegisteredDevices();
                    break;

                default:
                    if (strpos($input, 'DETECTED:') === 0) {
                        $this->processDetection($input);
                    } elseif (!empty($input)) {
                        echo "Unknown command. Use: start, stop, quit, session, devices\n";
                    }
            }
        }
    }

    private function processDetection($raw) {
        // Format: DETECTED:DeviceName,MAC,RSSIdBm
        $payload = substr($raw, 9); // Remove "DETECTED:"
        $parts   = explode(',', $payload);

        if (count($parts) < 3) {
            echo "Invalid format. Expected: DETECTED:DeviceName,MAC,RSSI\n";
            return;
        }

        $deviceName = trim($parts[0]);
        $mac        = trim($parts[1]);
        $rssi       = intval($parts[2]);

        echo "\n[DETECTED] Name: '$deviceName' | MAC: $mac | RSSI: {$rssi}dBm\n";

        $session = $this->getActiveSession();
        if (!$session) {
            echo "❌ No active session. Start a session in the web interface.\n";
            return;
        }

        $student = $this->findStudent($deviceName, $session['class_id']);
        if (!$student) {
            echo "❌ '$deviceName' not registered in class '{$session['class_name']}'\n";
            echo "   Register this device name in the web interface first.\n";
            return;
        }

        if ($this->alreadyMarked($session['id'], $student['id'])) {
            echo "ℹ️  {$student['name']} already marked present.\n";
            return;
        }

        $this->markAttendance($session['id'], $student['id'], $rssi, $mac);
        echo "✅ PRESENT: {$student['name']} ({$student['student_id']})\n";
    }

    private function getActiveSession() {
        $stmt = $this->db->query(
            "SELECT s.*, c.name as class_name FROM sessions s
             JOIN classes c ON s.class_id = c.id
             WHERE s.status = 'active'
             ORDER BY s.start_timestamp DESC LIMIT 1"
        );
        return $stmt->fetch();
    }

    private function findStudent($deviceName, $classId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM students WHERE device_name = ? AND class_id = ? AND active = 1"
        );
        $stmt->execute([$deviceName, $classId]);
        return $stmt->fetch();
    }

    private function alreadyMarked($sessionId, $studentId) {
        $stmt = $this->db->prepare(
            "SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?"
        );
        $stmt->execute([$sessionId, $studentId]);
        return (bool) $stmt->fetch();
    }

    private function markAttendance($sessionId, $studentId, $rssi, $mac) {
        $this->db->prepare(
            "INSERT INTO attendance_records (session_id, student_id, status, detected_at, rssi, device_address)
             VALUES (?, ?, 'present', datetime('now'), ?, ?)"
        )->execute([$sessionId, $studentId, $rssi, $mac]);

        $this->db->prepare(
            "UPDATE sessions SET present_count =
             (SELECT COUNT(*) FROM attendance_records WHERE session_id = ? AND status = 'present')
             WHERE id = ?"
        )->execute([$sessionId, $sessionId]);
    }

    private function showActiveSession() {
        $session = $this->getActiveSession();
        if ($session) {
            echo "📋 Active Session: {$session['name']} | Class: {$session['class_name']} | Present: {$session['present_count']}/{$session['total_students']}\n\n";
        } else {
            echo "⚠️  No active session found. Create and start one in the web interface.\n\n";
        }
    }

    private function showRegisteredDevices() {
        $session = $this->getActiveSession();
        if (!$session) return;

        $stmt = $this->db->prepare(
            "SELECT name, student_id, device_name FROM students WHERE class_id = ? AND active = 1"
        );
        $stmt->execute([$session['class_id']]);
        $students = $stmt->fetchAll();

        if (empty($students)) {
            echo "⚠️  No students registered for this class.\n\n";
            return;
        }

        echo "📱 Registered Device Names:\n";
        foreach ($students as $s) {
            echo "   - {$s['name']} ({$s['student_id']}): '{$s['device_name']}'\n";
        }
        echo "\n";
    }
}

if (php_sapi_name() !== 'cli') {
    die("Run from command line: php bridge.php COM3\n");
}

$port = $argv[1] ?? 'COM3';
(new ArduinoBridge())->start($port);
?>