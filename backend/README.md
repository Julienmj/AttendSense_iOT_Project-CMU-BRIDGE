# AttendSense Backend Setup Guide

## Overview

This backend provides a complete PHP MySQL API for the AttendSense attendance system, including:

- **RESTful API** for classes, students, sessions, and attendance
- **Arduino Integration Service** for Bluetooth device detection
- **Database Schema** with optimized queries and stored procedures
- **Authentication & Security** with JWT tokens and input validation

## Requirements

### Software Requirements
- **PHP 8.0+** with PDO extension
- **MySQL 8.0+** or MariaDB 10.5+
- **Apache/Nginx** web server
- **Composer** (for dependency management, optional)

### PHP Extensions Required
```bash
# Required extensions
pdo_mysql
json
mbstring
openssl

# For Arduino serial communication (optional)
php-serial
```

## Installation Steps

### 1. Database Setup

1. **Create Database**
```sql
CREATE DATABASE attendsense CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Import Schema**
```bash
mysql -u root -p attendsense < backend/database/schema.sql
```

3. **Verify Installation**
```sql
USE attendsense;
SHOW TABLES;
SELECT * FROM users; -- Should show admin user
```

### 2. Backend Configuration

1. **Update Database Credentials**
Edit `backend/config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendsense');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

2. **Update Security Settings**
```php
define('JWT_SECRET', 'your-super-secret-jwt-key-change-this-in-production');
define('CORS_ORIGIN', 'http://localhost:5173'); // Your frontend URL
```

### 3. Web Server Configuration

#### Apache (`.htaccess`)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ backend/api/$1.php [L,QSA]

# Enable CORS
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
```

#### Nginx
```nginx
location /api/ {
    try_files $uri $uri/ /backend/api$uri.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 4. Directory Structure
```
attendsense/
├── backend/
│   ├── config/
│   │   └── config.php
│   ├── database/
│   │   └── schema.sql
│   ├── api/
│   │   ├── classes.php
│   │   ├── sessions.php
│   │   └── reports.php
│   └── services/
│       └── arduino_service.php
├── src/ (Vue.js frontend)
└── public/
```

## API Endpoints

### Classes API
```
GET    /api/classes              # Get all classes
GET    /api/classes/{id}         # Get specific class
POST   /api/classes              # Create class
PUT    /api/classes/{id}         # Update class
DELETE /api/classes/{id}         # Delete class
GET    /api/classes/{id}/students # Get class students
POST   /api/classes/{id}/students # Add student to class
PUT    /api/classes/{cid}/students/{sid} # Update student
DELETE /api/classes/{cid}/students/{sid} # Remove student
```

### Sessions API
```
GET    /api/sessions             # Get all sessions
GET    /api/sessions/{id}        # Get specific session
POST   /api/sessions             # Create session
PUT    /api/sessions/{id}        # Update session
PUT    /api/sessions/{id}/start   # Start session
PUT    /api/sessions/{id}/end     # End session
GET    /api/sessions/{id}/attendance # Get attendance
POST   /api/sessions/{id}/attendance # Mark attendance
POST   /api/sessions/{id}/detect   # Process detected device
GET    /api/sessions/active      # Get active sessions
GET    /api/sessions/today       # Get today's sessions
```

### Reports API
```
GET    /api/reports/attendance            # Get attendance report
GET    /api/reports/class-performance     # Get class performance
GET    /api/reports/student-attendance/{id} # Get student attendance
GET    /api/reports/summary             # Get summary stats
GET    /api/reports/export/sessions/{id} # Export session
GET    /api/reports/export/class/{id}    # Export class
GET    /api/reports/dashboard-stats      # Dashboard stats
```

## Arduino Integration

### 1. Hardware Setup
- Connect Arduino + HC-05 as documented in `hardware/SETUP.md`
- Upload Arduino sketch from `hardware/arduino_sketch.ino`

### 2. Start Arduino Service
```bash
# Start service (CLI)
php backend/services/arduino_service.php --port COM3

# Or in background
nohup php backend/services/arduino_service.php --port COM3 > arduino.log 2>&1 &
```

### 3. Service Features
- **Automatic Device Detection**: Scans for registered Bluetooth devices
- **Real-time Attendance**: Marks attendance when devices detected
- **Session Management**: Only scans during active sessions
- **Error Handling**: Robust error handling and logging

## Testing the API

### 1. Test with curl
```bash
# Get all classes
curl -X GET http://localhost/attendsense/api/classes

# Create a class
curl -X POST http://localhost/attendsense/api/classes \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Class","course_code":"TEST101","description":"Test description"}'

# Add student to class
curl -X POST http://localhost/attendsense/api/classes/1/students \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","student_id":"STU001","email":"john@test.com","mac_address":"AA:BB:CC:DD:EE:FF"}'
```

### 2. Test with Postman
Import the provided Postman collection (if available) or manually configure requests:
- Set base URL: `http://localhost/attendsense/api`
- Configure headers: `Content-Type: application/json`

## Frontend Integration

### 1. Update API Base URL
In your Vue.js frontend, update the API base URL:

```javascript
// src/services/api.js
const API_BASE_URL = 'http://localhost/attendsense/api';

// Example API call
export const getClasses = async () => {
  const response = await fetch(`${API_BASE_URL}/classes`);
  return response.json();
};
```

### 2. Replace Pinia Store
Update your attendance store to use the real API:

```javascript
// src/stores/attendance.js
import { defineStore } from 'pinia';

export const useAttendanceStore = defineStore('attendance', {
  state: () => ({
    classes: [],
    sessions: [],
    // ... other state
  }),
  
  actions: {
    async fetchClasses() {
      const response = await fetch(`${API_BASE_URL}/classes`);
      this.classes = await response.json();
    },
    
    async createClass(classData) {
      const response = await fetch(`${API_BASE_URL}/classes`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(classData)
      });
      return response.json();
    },
    
    // ... other actions
  }
});
```

## Production Deployment

### 1. Security Considerations
- **Database Security**: Use prepared statements (already implemented)
- **Input Validation**: Comprehensive validation (already implemented)
- **CORS**: Restrict to your domain in production
- **HTTPS**: Use SSL certificates
- **Authentication**: Implement JWT-based authentication

### 2. Performance Optimization
- **Database Indexing**: Already included in schema
- **Caching**: Implement Redis for frequently accessed data
- **Connection Pooling**: Configure database connection limits
- **Load Balancing**: Use multiple web servers for high traffic

### 3. Monitoring & Logging
```php
// Add to config.php
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/attendsense/error.log');

// Custom logging function
function logEvent($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message\n";
    file_put_contents('/var/log/attendsense/app.log', $logMessage, FILE_APPEND);
}
```

## Troubleshooting

### Common Issues

**Database Connection Failed**
```bash
# Check MySQL service
sudo systemctl status mysql

# Check credentials
mysql -u username -p -e "SHOW DATABASES;"
```

**API Returns 404**
- Check web server configuration
- Verify `.htaccess` rules
- Check file permissions

**CORS Errors**
- Update `CORS_ORIGIN` in config.php
- Check web server headers

**Arduino Service Not Working**
```bash
# Check serial port permissions
ls -la /dev/ttyUSB*  # Linux
# or check COM ports in Windows Device Manager

# Test Arduino communication
php backend/services/arduino_service.php --help
```

### Debug Mode
Enable debug mode in `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Next Steps

1. **Complete Frontend Integration**: Update Vue.js to use real API
2. **Implement Authentication**: Add user login/registration
3. **Setup WebSocket**: For real-time attendance updates
4. **Deploy to Production**: Configure production server
5. **Add Monitoring**: Implement logging and alerts

---

**Support**: Check the GitHub repository for updates and issues.
