<?php
/**
 * Arduino Integration Service
 * Handles communication with Arduino via Serial Port
 * Processes Bluetooth device detection and forwards to web interface
 */

require_once '../config/config.php';

class ArduinoService {
    private $db;
    private $serialPort;
    private $baudRate = 9600;
    private $isRunning = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Start Arduino communication
    public function start($port = 'COM3') {
        try {
            // For Windows, use COM ports; for Linux, use /dev/ttyUSB*
            $this->serialPort = $port;
            
            echo "Starting Arduino service on port: $port\n";
            
            // Open serial port
            if (!$this->openSerialPort()) {
                throw new Exception("Failed to open serial port: $port");
            }
            
            $this->isRunning = true;
            echo "Arduino service started successfully\n";
            
            // Main communication loop
            $this->communicationLoop();
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->stop();
        }
    }

    // Stop Arduino communication
    public function stop() {
        $this->isRunning = false;
        if ($this->serialPort) {
            $this->sendCommand('stop_scan');
            $this->closeSerialPort();
        }
        echo "Arduino service stopped\n";
    }

    // Open serial port (simplified - use php-serial in production)
    private function openSerialPort() {
        // This is a simplified implementation
        // In production, use a proper PHP serial library like php-serial
        
        // For demonstration, we'll simulate serial communication
        echo "Opening serial port {$this->serialPort} at {$this->baudRate} baud\n";
        
        // Send initial command to Arduino
        $this->sendCommand('status');
        
        return true;
    }

    // Close serial port
    private function closeSerialPort() {
        echo "Closing serial port\n";
        // Implementation depends on serial library used
    }

    // Send command to Arduino
    private function sendCommand($command) {
        $command = trim($command) . "\n";
        echo "Sending to Arduino: " . trim($command) . "\n";
        
        // Implementation depends on serial library
        // For example: $this->serial->sendMessage($command);
    }

    // Read from Arduino
    private function readFromArduino() {
        // Simulate reading from Arduino
        // In production, use: $this->serial->readPort()
        
        // For demonstration, return simulated data
        return $this->simulateArduinoResponse();
    }

    // Main communication loop
    private function communicationLoop() {
        $lastScanTime = 0;
        $scanInterval = 30; // Scan every 30 seconds
        
        while ($this->isRunning) {
            try {
                // Read from Arduino
                $response = $this->readFromArduino();
                
                if ($response) {
                    $this->processArduinoResponse($response);
                }
                
                // Periodic scanning
                if (time() - $lastScanTime > $scanInterval) {
                    $this->sendCommand('start_scan');
                    $lastScanTime = time();
                }
                
                // Small delay to prevent excessive CPU usage
                usleep(100000); // 100ms
                
            } catch (Exception $e) {
                echo "Communication error: " . $e->getMessage() . "\n";
                sleep(1); // Wait before retrying
            }
        }
    }

    // Process Arduino response
    private function processArduinoResponse($response) {
        $response = trim($response);
        
        if (empty($response)) return;
        
        echo "Arduino response: $response\n";
        
        // Parse different response types
        if (strpos($response, 'DETECTED:') === 0) {
            $this->processDetectedDevice($response);
        }
        elseif (strpos($response, 'STATUS:') === 0) {
            $this->processStatusUpdate($response);
        }
        elseif (strpos($response, 'SCAN_') === 0) {
            $this->processScanStatus($response);
        }
    }

    // Process detected device
    private function processDetectedDevice($response) {
        // Parse: DETECTED:MAC,RSSI,TIMESTAMP
        $parts = explode(':', $response, 2);
        if (count($parts) < 2) return;
        
        $data = explode(',', $parts[1]);
        if (count($data) < 2) return;
        
        $macAddress = trim($data[0]);
        $rssi = isset($data[1]) ? intval($data[1]) : null;
        $timestamp = isset($data[2]) ? $data[2] : date('Y-m-d H:i:s');
        
        // Get active session
        $activeSession = $this->getActiveSession();
        if (!$activeSession) {
            echo "No active session found\n";
            return;
        }
        
        // Store detected device and try to mark attendance
        $this->storeDetectedDevice($activeSession['id'], $macAddress, $rssi, $timestamp);
        $this->markAttendanceIfRegistered($activeSession['id'], $macAddress, $rssi);
    }

    // Process status update
    private function processStatusUpdate($response) {
        $status = substr($response, 7); // Remove "STATUS:"
        echo "Arduino status: $status\n";
        
        // Could update database with Arduino status
        // or trigger WebSocket events to web interface
    }

    // Process scan status
    private function processScanStatus($response) {
        echo "Scan status: $response\n";
        
        // Could trigger WebSocket events to update web interface
        // about scanning status
    }

    // Get active session
    private function getActiveSession() {
        $sql = "SELECT * FROM sessions 
                WHERE status = 'active' 
                ORDER BY start_timestamp DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Store detected device in database
    private function storeDetectedDevice($sessionId, $macAddress, $rssi, $timestamp) {
        $sql = "INSERT INTO detected_devices (session_id, mac_address, rssi, detected_at) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId, $macAddress, $rssi, $timestamp]);
    }

    // Mark attendance if device is registered
    private function markAttendanceIfRegistered($sessionId, $macAddress, $rssi) {
        // Find student by MAC address
        $sql = "SELECT s.*, ses.class_id 
                FROM students s
                JOIN sessions ses ON s.class_id = ses.class_id
                WHERE s.mac_address = ? AND s.active = TRUE AND ses.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$macAddress, $sessionId]);
        $student = $stmt->fetch();
        
        if (!$student) {
            echo "Device $macAddress not registered for this session\n";
            return;
        }
        
        // Check if already marked
        $checkSql = "SELECT id FROM attendance_records 
                    WHERE session_id = ? AND student_id = ?";
        $stmt = $this->db->prepare($checkSql);
        $stmt->execute([$sessionId, $student['id']]);
        
        if ($stmt->fetch()) {
            echo "Attendance already marked for student {$student['name']}\n";
            return;
        }
        
        // Mark attendance
        $markSql = "CALL MarkAttendance(?, ?, 'present', CURRENT_TIMESTAMP, ?)";
        $stmt = $this->db->prepare($markSql);
        
        if ($stmt->execute([$sessionId, $student['id'], $rssi])) {
            echo "Attendance marked for student: {$student['name']} ({$student['student_id']})\n";
            
            // Trigger WebSocket event to update web interface
            $this->triggerWebSocketEvent('attendance_marked', [
                'session_id' => $sessionId,
                'student_id' => $student['id'],
                'student_name' => $student['name'],
                'student_number' => $student['student_id'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    // Trigger WebSocket event (simplified)
    private function triggerWebSocketEvent($event, $data) {
        // In production, use a WebSocket server like Ratchet
        // For now, just log the event
        echo "WebSocket Event: $event - " . json_encode($data) . "\n";
    }

    // Simulate Arduino response (for testing without hardware)
    private function simulateArduinoResponse() {
        static $counter = 0;
        $counter++;
        
        // Simulate different responses
        if ($counter % 20 === 0) {
            // Simulate device detection every 20 iterations
            $mockDevices = [
                'A4:C1:38:2B:5E:9F',
                'B5:D2:49:3C:6F:0A',
                'C6:E3:5A:4D:7G:1B',
                'D7:F4:6B:5E:8H:2C'
            ];
            
            $mac = $mockDevices[array_rand($mockDevices)];
            $rssi = rand(-80, -30);
            return "DETECTED:$mac,$rssi," . date('Y-m-d H:i:s');
        }
        
        if ($counter % 50 === 0) {
            return "STATUS:SCANNING";
        }
        
        return null;
    }

    // Add new device to Arduino's registered list
    public function addDevice($macAddress) {
        $command = "add_device:$macAddress";
        $this->sendCommand($command);
    }

    // Get Arduino status
    public function getStatus() {
        $this->sendCommand('status');
    }
}

// WebSocket Server (simplified implementation)
class WebSocketServer {
    private $clients = [];
    private $server;

    public function start($port = 8080) {
        echo "Starting WebSocket server on port $port\n";
        
        // In production, use Ratchet or similar WebSocket library
        // This is a simplified placeholder
        
        while (true) {
            // Handle WebSocket connections and messages
            // Broadcast attendance updates to connected clients
            sleep(1);
        }
    }

    public function broadcast($event, $data) {
        $message = json_encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => time()
        ]);
        
        // Send message to all connected clients
        foreach ($this->clients as $client) {
            // Implementation depends on WebSocket library
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $arduinoService = new ArduinoService();
    
    // Parse command line arguments
    $options = getopt('p:h', ['port:', 'help']);
    
    if (isset($options['h']) || isset($options['help'])) {
        echo "AttendSense Arduino Integration Service\n";
        echo "Usage: php arduino_service.php [options]\n";
        echo "Options:\n";
        echo "  -p, --port PORT    Serial port (default: COM3)\n";
        echo "  -h, --help         Show this help\n";
        exit;
    }
    
    $port = $options['p'] ?? $options['port'] ?? 'COM3';
    
    // Handle signals for graceful shutdown
    function signalHandler($signal) {
        global $arduinoService;
        echo "\nReceived signal $signal, shutting down...\n";
        $arduinoService->stop();
        exit;
    }
    
    pcntl_signal(SIGTERM, 'signalHandler');
    pcntl_signal(SIGINT, 'signalHandler');
    
    // Start the service
    $arduinoService->start($port);
}
?>
