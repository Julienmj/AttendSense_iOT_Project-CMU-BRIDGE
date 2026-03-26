# AttendSense - Smart Bluetooth Attendance System

A modern, IoT-based attendance tracking system that uses Bluetooth technology to automatically mark student presence in classrooms. Built with Vue.js, Arduino, and HC-05 Bluetooth modules to solve fake attendance problems in educational institutions.

## 🎯 Problem Solved

Traditional attendance systems are prone to fake attendance, time-consuming, and inefficient. AttendSense automates the entire process using Bluetooth device detection, ensuring accurate attendance tracking while saving valuable class time.

## ✨ Key Features

### 🏫 Class Management
- Create and manage multiple classes
- Register students with comprehensive information (name, ID, email, phone)
- Assign Bluetooth devices to students for tracking

### 📱 Real-time Attendance Scanning
- Bluetooth-based automatic detection
- Time-windowed check-in system
- Live attendance monitoring during class sessions
- Privacy-focused (only student ID and name displayed during scanning)

### 📊 Session Management
- Create attendance sessions with defined time windows
- Active session monitoring with countdown timers
- Automatic marking of absent students after window closes

### 📈 Reports & Analytics
- Class performance metrics
- Attendance rate tracking
- Export functionality (CSV/PDF)
- Historical attendance data

### 🎨 Modern UI/UX
- Clean, professional light theme
- Responsive design for all devices
- Intuitive navigation and user experience
- Real-time status indicators

## 🛠 Technology Stack

### Frontend
- **Vue.js 3** - Modern JavaScript framework
- **Vue Router** - Client-side routing
- **Pinia** - State management
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Build tool and development server

### Backend (Planned)
- **Node.js** - Server environment
- **WebSocket** - Real-time communication
- **MySQL** - Database for attendance records

### Hardware
- **Arduino Uno** - Microcontroller
- **HC-05 Bluetooth Module** - Bluetooth scanning
- **ESP32 (Alternative)** - WiFi + Bluetooth solution

## 🚀 Getting Started

### Prerequisites
- Node.js 16+ installed
- Arduino IDE (for hardware setup)
- HC-05 Bluetooth module
- Arduino Uno board

### Frontend Setup

1. **Clone the repository**
```bash
git clone https://github.com/Julienmj/AttendSense_iOT_Project.git
cd AttendSense_iOT_Project
```

2. **Install dependencies**
```bash
npm install
```

3. **Start development server**
```bash
npm run dev
```

4. **Open your browser**
Navigate to `http://localhost:5173`

### Build for Production
```bash
npm run build
```

## 🔧 Hardware Setup

### Arduino + HC-05 Configuration

**Wiring Connections:**
```
Arduino    HC-05
5V      →  VCC
GND     →  GND
TX      →  RX
RX      →  TX
```

**Setup Steps:**
1. Connect HC-05 to Arduino as shown above
2. Upload Arduino sketch (see `/hardware/arduino_sketch.ino`)
3. Power on Arduino and HC-05
4. Put HC-05 in pairing mode (hold button while powering)
5. Test Bluetooth scanning functionality

### Connection Methods

#### Option 1: Serial Bridge (Recommended)
- Arduino scans for devices via HC-05
- Sends detected MAC addresses via USB serial
- Node.js server reads serial and forwards to web interface

#### Option 2: Web Bluetooth API
- Direct browser-to-HC-05 connection
- Limited browser compatibility (Chrome/Edge only)
- Requires HTTPS environment

#### Option 3: ESP32 WiFi
- Replace Arduino+HC-05 with ESP32
- Built-in Bluetooth + WiFi capabilities
- Most robust production solution

## 📱 Usage Guide

### 1. Create Classes
- Navigate to **Classes** tab
- Click "Create New Class"
- Enter class name and course code
- Click "Register Students" to add students

### 2. Register Students
- Fill in student information (name, ID, email, phone)
- Add device name and MAC address
- Students can only be detected in their registered classes

### 3. Create Attendance Sessions
- Go to **Sessions** tab
- Select class and set date/time
- Define check-in window (5-60 minutes)
- Click "Create Session"

### 4. Start Attendance Scanning
- In active sessions, click "Start Scan"
- System detects registered students automatically
- Only shows student ID and name (privacy-focused)
- Real-time attendance updates

### 5. View Reports
- Navigate to **Reports** tab
- Filter by class and date range
- Export attendance data as CSV/PDF
- View class performance metrics

## 🔒 Privacy & Security

- **MAC Address Protection**: MAC addresses stored securely, never displayed during scanning
- **Class-Based Detection**: Students only detected in their registered classes
- **Time-Windowed Access**: Attendance only recorded during defined sessions
- **Data Encryption**: All data transmission encrypted (production deployment)

## 📊 System Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Web Frontend  │◄──►│  Node.js Server  │◄──►│  Arduino + HC-05│
│   (Vue.js)      │    │   (WebSocket)    │    │   (Scanner)     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   User Interface│    │  Business Logic  │    │  Bluetooth Scan │
│   & Display     │    │   & Database     │    │   & Detection   │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 🎯 Benefits

### For Educational Institutions
- **Eliminates Fake Attendance**: Bluetooth device verification ensures physical presence
- **Saves Class Time**: Automatic marking takes seconds instead of minutes
- **Accurate Records**: Digital records eliminate manual errors
- **Real-time Monitoring**: Teachers can see attendance live during class

### For Students
- **Convenient**: No manual roll call needed
- **Private**: Only necessary information displayed
- **Fair**: Equal treatment for all students
- **Reliable**: Consistent attendance tracking

### For Administrators
- **Data Analytics**: Attendance trends and insights
- **Export Capabilities**: Easy reporting for compliance
- **Scalable**: Works for small to large institutions
- **Cost-Effective**: Affordable hardware solution

## 🧰 Development

### Project Structure
```
AttendSense_iOT_Project/
├── src/
│   ├── components/          # Vue components
│   ├── views/              # Page components
│   ├── stores/             # Pinia state management
│   ├── router/             # Vue Router config
│   └── style.css           # Global styles
├── public/                 # Static assets
├── hardware/               # Arduino sketches & docs
├── docs/                   # Additional documentation
└── package.json            # Dependencies & scripts
```

### Key Components
- **Dashboard.vue**: Main overview and quick actions
- **Classes.vue**: Class and student management
- **Sessions.vue**: Attendance session creation and scanning
- **Reports.vue**: Analytics and export functionality
- **attendance.js**: Core state management

### Environment Variables
Create `.env.local` for development:
```env
VITE_API_URL=http://localhost:3000
VITE_WS_URL=ws://localhost:3001
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow Vue.js best practices
- Use Tailwind CSS for styling
- Write meaningful commit messages
- Test hardware integration thoroughly
- Document new features

## 📋 Todo / Future Enhancements

- [ ] Complete backend API development
- [ ] MySQL database integration
- [ ] WebSocket real-time communication
- [ ] Mobile app version (React Native)
- [ ] ESP32 hardware integration
- [ ] Advanced analytics dashboard
- [ ] Multi-tenant support
- [ ] Biometric verification options
- [ ] SMS/email notifications
- [ ] Cloud deployment options

## 🐛 Troubleshooting

### Common Issues

**HC-05 Not Connecting**
- Check wiring connections
- Ensure HC-05 is in pairing mode
- Verify baud rate (default: 9600)
- Check power supply

**Bluetooth Detection Not Working**
- Verify student devices have Bluetooth enabled
- Check MAC address format in registration
- Ensure students are within range (~10 meters)
- Test with known devices first

**Web Interface Issues**
- Clear browser cache
- Check Node.js server status
- Verify WebSocket connection
- Check browser console for errors

### Support
For issues and questions:
1. Check the troubleshooting section
2. Review GitHub Issues
3. Create a new issue with detailed description
4. Include hardware setup details

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Vue.js Team** - Excellent frontend framework
- **Arduino Community** - Hardware inspiration and support
- **Tailwind CSS** - Beautiful utility-first CSS framework
- **IoT Program** - Project guidance and support

## 📞 Contact

**Project Maintainer**: Julien M.  
**GitHub**: [@Julienmj](https://github.com/Julienmj)  
**Project**: AttendSense IoT Project  

---

⭐ **Star this repository if you find it helpful!**

🚀 **Ready to transform attendance tracking? Start with the Hardware Setup section above!**
