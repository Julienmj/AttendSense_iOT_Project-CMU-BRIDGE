# Hardware Setup Guide

## Required Components

### Essential Hardware
- **Arduino Uno** (or compatible board)
- **HC-05 Bluetooth Module**
- **Jumper Wires**
- **USB Cable** (for Arduino programming)
- **Breadboard** (optional, for prototyping)

### Tools
- **Arduino IDE** (installed on computer)
- **USB to Serial Adapter** (if not using Arduino's built-in USB)

## Wiring Diagram

```
Arduino Uno    →    HC-05 Bluetooth Module
─────────────────────────────────────────────
5V              →    VCC
GND             →    GND
Pin 10 (RX)     →    TXD
Pin 11 (TX)     →    RXD
```

### Detailed Connections

| Arduino Pin | HC-05 Pin | Description |
|-------------|-----------|-------------|
| 5V          | VCC       | Power supply (3.6V-6V) |
| GND         | GND       | Common ground |
| D10         | TXD       | Arduino receives data |
| D11         | RXD       | Arduino sends data |

## Setup Instructions

### 1. Hardware Assembly

1. **Connect Power**
   - Connect HC-05 VCC to Arduino 5V
   - Connect HC-05 GND to Arduino GND

2. **Connect Data Lines**
   - Cross-connect TX/RX: Arduino RX → HC-05 TX, Arduino TX → HC-05 RX
   - Use SoftwareSerial pins D10 (RX) and D11 (TX) as defined in sketch

3. **Secure Connections**
   - Ensure all connections are secure
   - Avoid loose wires that could cause intermittent connections

### 2. Software Setup

1. **Install Arduino IDE**
   - Download from [arduino.cc](https://www.arduino.cc/en/software)
   - Install drivers for your Arduino board

2. **Configure Arduino IDE**
   - Open Arduino IDE
   - Go to Tools → Board → Select "Arduino Uno"
   - Go to Tools → Port → Select your Arduino's COM port

3. **Upload the Sketch**
   - Open `hardware/arduino_sketch.ino`
   - Click "Upload" button
   - Wait for upload to complete

### 3. HC-05 Configuration

1. **Enter AT Command Mode**
   - Hold the HC-05 button while connecting power
   - LED should blink slowly (2Hz) indicating AT mode
   - If LED blinks quickly, power off and retry button press

2. **Test Communication**
   - Open Arduino IDE Serial Monitor
   - Set baud rate to 9600
   - Type "AT" - should receive "OK" response

3. **Configure HC-05 Settings**
   - The sketch automatically configures these settings:
   - `AT+ROLE=1` - Set as Master mode
   - `AT+CMODE=1` - Connect to any device
   - `AT+INQM=1,9,48` - Inquiry mode configuration

### 4. Testing the System

1. **Power On Test**
   - Connect Arduino to USB power
   - HC-05 LED should start blinking
   - Open Serial Monitor (9600 baud)

2. **Command Test**
   - Send "status" command - should respond with "STATUS:IDLE"
   - Send "start_scan" - should respond with "SCAN_STARTED"
   - Send "stop_scan" - should respond with "SCAN_STOPPED"

3. **Bluetooth Detection Test**
   - Enable Bluetooth on a smartphone
   - Place phone within 10 meters of HC-05
   - Start scanning with "start_scan" command
   - Watch for "DETECTED:" messages in Serial Monitor

## Troubleshooting

### Common Issues

**HC-05 Not Responding**
- Check wiring connections (especially TX/RX cross-connection)
- Verify HC-05 is in AT mode (slow blinking LED)
- Try different baud rates (38400, 115200)
- Check power supply (HC-05 needs 3.6V-6V)

**No Devices Detected**
- Ensure test device has Bluetooth enabled and is discoverable
- Check distance (maximum range ~10 meters)
- Verify HC-05 is in Master mode
- Try resetting the system

**Serial Communication Issues**
- Verify correct COM port selected in Arduino IDE
- Check baud rate (9600 for both Arduino and HC-05)
- Ensure no other programs are using the COM port
- Try different USB cable or port

**Power Issues**
- Use external power supply if USB power is insufficient
- Check for short circuits
- Verify voltage levels (5V for Arduino, 3.3V-6V for HC-05)

### Advanced Troubleshooting

**HC-05 LED Indicators**
- **Fast Blinking (1Hz)**: Normal pairing mode
- **Slow Blinking (2Hz)**: AT command mode
- **Solid On**: Connected to another device
- **Off**: No power or module failure

**AT Command Reference**
```
AT              - Test communication
AT+VERSION?     - Get firmware version
AT+ADDR?        - Get module address
AT+ROLE=1       - Set as Master
AT+CMODE=1      - Connect to any device
AT+INQ          - Start device inquiry
AT+INQC         - Cancel inquiry
```

## Production Considerations

### Power Management
- Use regulated power supply for consistent operation
- Consider battery backup for portability
- Add power-on LED indicator

### Signal Optimization
- Place HC-05 in elevated position for better range
- Avoid metal obstructions
- Consider external antenna for extended range

### Security
- Change default HC-05 PIN (default: 1234)
- Implement device whitelist in Arduino code
- Add encryption for data transmission

### Scalability
- Multiple HC-05 modules for larger classrooms
- Network multiple Arduino boards
- Consider ESP32 for integrated WiFi+Bluetooth solution

## Integration with Web Interface

### Serial Communication Protocol

**Commands from Web Interface → Arduino:**
```
start_scan      - Begin Bluetooth scanning
stop_scan       - Stop scanning
status          - Get current status
add_device:MAC  - Register new device
```

**Data from Arduino → Web Interface:**
```
DETECTED:MAC,RSSI     - Device found
SCAN_STARTED          - Scanning began
SCAN_STOPPED          - Scanning ended
STATUS:STATE          - Current status
TIMESTAMP:MS          - Time reference
```

### Node.js Bridge (Optional)
For production deployment, create a Node.js service that:
- Reads Arduino serial port
- Exposes WebSocket API for web interface
- Handles multiple Arduino connections
- Provides data persistence

## Safety and Compliance

### Electrical Safety
- Use proper voltage levels
- Avoid short circuits
- Ensure proper grounding
- Use insulated connections

### RF Compliance
- HC-05 operates in 2.4GHz ISM band
- Follow local regulations for Bluetooth devices
- Consider interference with other wireless devices
- Implement proper shielding if needed

### Data Privacy
- Secure MAC address storage
- Implement data encryption
- Follow GDPR/student privacy regulations
- Provide data deletion capabilities

---

**Next Steps**: After completing hardware setup, proceed to software integration and testing with the web interface.
