// AttendSense - Arduino Bluetooth Attendance Scanner
// Hardware: Arduino Uno + HC-05 Bluetooth Module
// Purpose: Scan for registered Bluetooth devices and send MAC addresses to web interface

#include <SoftwareSerial.h>

// HC-05 Bluetooth Module Connections
#define BT_TX 10  // Arduino RX (connect to HC-05 TX)
#define BT_RX 11  // Arduino TX (connect to HC-05 RX)

SoftwareSerial bluetooth(BT_TX, BT_RX);

// Known student MAC addresses (in production, these would be stored in EEPROM/SD card)
String registeredDevices[] = {
  "A4:C1:38:2B:5E:9F",  // Alice Johnson
  "B5:D2:49:3C:6F:0A",  // Bob Smith
  "C6:E3:5A:4D:7G:1B",  // Charlie Brown
  "D7:F4:6B:5E:8H:2C"   // Diana Prince
};
const int deviceCount = 4;

// Scanning variables
unsigned long lastScanTime = 0;
const unsigned long scanInterval = 5000; // Scan every 5 seconds
bool scanningEnabled = false;

void setup() {
  // Initialize serial communication
  Serial.begin(9600);
  bluetooth.begin(9600);
  
  // Wait for Bluetooth module to initialize
  delay(2000);
  
  Serial.println("AttendSense - Bluetooth Attendance Scanner");
  Serial.println("Hardware: Arduino Uno + HC-05");
  Serial.println("Ready for commands...");
  
  // Initialize HC-05
  initBluetooth();
}

void loop() {
  // Check for commands from web interface
  if (Serial.available()) {
    handleCommand(Serial.readStringUntil('\n'));
  }
  
  // Perform Bluetooth scanning if enabled
  if (scanningEnabled && millis() - lastScanTime > scanInterval) {
    scanForDevices();
    lastScanTime = millis();
  }
  
  // Handle Bluetooth responses
  if (bluetooth.available()) {
    handleBluetoothResponse(bluetooth.readStringUntil('\n'));
  }
  
  delay(100);
}

void initBluetooth() {
  // Configure HC-05 for inquiry mode
  bluetooth.println("AT+ROLE=1");      // Set as Master
  bluetooth.println("AT+CMODE=1");     // Connect to any device
  bluetooth.println("AT+INQM=1,9,48"); // Inquiry mode: 9 max devices, 48x1.28ms search
  delay(1000);
  
  Serial.println("Bluetooth module initialized");
}

void handleCommand(String command) {
  command.trim();
  command.toLowerCase();
  
  if (command == "start_scan") {
    scanningEnabled = true;
    Serial.println("SCAN_STARTED");
    bluetooth.println("AT+INQ"); // Start inquiry
  }
  else if (command == "stop_scan") {
    scanningEnabled = false;
    Serial.println("SCAN_STOPPED");
    bluetooth.println("AT+INQC"); // Cancel inquiry
  }
  else if (command == "status") {
    Serial.print("STATUS:");
    Serial.println(scanningEnabled ? "SCANNING" : "IDLE");
  }
  else if (command.startsWith("add_device:")) {
    // Add new device to registered list (simplified for demo)
    String macAddress = command.substring(11);
    Serial.print("DEVICE_ADDED:");
    Serial.println(macAddress);
  }
  else {
    Serial.println("UNKNOWN_COMMAND");
  }
}

void scanForDevices() {
  if (!scanningEnabled) return;
  
  // Send inquiry command to HC-05
  bluetooth.println("AT+INQ");
  Serial.println("SCANNING...");
}

void handleBluetoothResponse(String response) {
  response.trim();
  
  // Parse inquiry response format: +INQ:address,device_type,RSSI
  if (response.startsWith("+INQ:")) {
    String macAddress = parseMacAddress(response);
    
    // Check if this is a registered device
    if (isRegisteredDevice(macAddress)) {
      Serial.print("DETECTED:");
      Serial.print(macAddress);
      Serial.print(",");
      Serial.println(getRSSI(response));
      
      // In production, you'd also send timestamp and session info
      Serial.print("TIMESTAMP:");
      Serial.println(millis());
    }
  }
  // Handle other AT responses
  else if (response.startsWith("OK")) {
    // Command successful
  }
  else if (response.startsWith("ERROR")) {
    Serial.print("BT_ERROR:");
    Serial.println(response);
  }
}

String parseMacAddress(String inquiryResponse) {
  // Extract MAC address from +INQ:address,type,RSSI format
  int colon1 = inquiryResponse.indexOf(':');
  int comma1 = inquiryResponse.indexOf(',', colon1);
  
  if (colon1 != -1 && comma1 != -1) {
    String mac = inquiryResponse.substring(colon1 + 1, comma1);
    return formatMacAddress(mac);
  }
  return "";
}

String formatMacAddress(String rawMac) {
  // Convert raw MAC format to standard format
  // HC-05 returns format like "A4C1382B5E9F", convert to "A4:C1:38:2B:5E:9F"
  String formatted = "";
  for (int i = 0; i < rawMac.length(); i += 2) {
    if (i > 0) formatted += ":";
    formatted += rawMac.substring(i, i + 2);
  }
  return formatted;
}

int getRSSI(String inquiryResponse) {
  // Extract RSSI from +INQ:address,type,RSSI format
  int lastComma = inquiryResponse.lastIndexOf(',');
  if (lastComma != -1) {
    return inquiryResponse.substring(lastComma + 1).toInt();
  }
  return -100; // Default weak signal
}

bool isRegisteredDevice(String macAddress) {
  // Check if MAC address is in our registered devices list
  for (int i = 0; i < deviceCount; i++) {
    if (registeredDevices[i].equalsIgnoreCase(macAddress)) {
      return true;
    }
  }
  return false;
}

// Utility functions for production use
void saveDeviceToEEPROM(String macAddress) {
  // In production, save to EEPROM or SD card
  // This is a placeholder for the actual implementation
}

void loadDevicesFromEEPROM() {
  // In production, load from EEPROM or SD card
  // This is a placeholder for the actual implementation
}

void sendDeviceList() {
  // Send all registered devices to web interface
  Serial.println("DEVICE_LIST_START");
  for (int i = 0; i < deviceCount; i++) {
    Serial.print("DEVICE:");
    Serial.println(registeredDevices[i]);
  }
  Serial.println("DEVICE_LIST_END");
}
