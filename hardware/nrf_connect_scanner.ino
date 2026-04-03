/*
 * AttendSense NRF Connect Scanner
 * Arduino Nano 33 BLE - Scans for device names from NRF Connect app
 * Sends device names and addresses to PHP backend
 */

#include <ArduinoBLE.h>

const int SCAN_DURATION = 5000;  // 5 seconds per scan
const int LED_PIN = LED_BUILTIN;
bool isScanning = false;

void setup() {
  Serial.begin(9600);
  pinMode(LED_PIN, OUTPUT);
  
  // Wait for serial connection
  while (!Serial && millis() < 5000) delay(10);
  
  Serial.println("AttendSense NRF Connect Scanner Ready");
  Serial.println("Scanning for device names from NRF Connect app");
  
  // Initialize BLE
  if (!BLE.begin()) {
    Serial.println("ERROR: BLE failed to initialize");
    while (1) {
      digitalWrite(LED_PIN, HIGH);
      delay(100);
      digitalWrite(LED_PIN, LOW);
      delay(100);
    }
  }
  
  Serial.println("BLE initialized successfully");
  Serial.println("Commands: start, stop, scan, status");
  
  // Ready indicator
  for (int i = 0; i < 3; i++) {
    digitalWrite(LED_PIN, HIGH);
    delay(200);
    digitalWrite(LED_PIN, LOW);
    delay(200);
  }
}

void loop() {
  // Check for commands
  if (Serial.available()) {
    String command = Serial.readStringUntil('\n');
    command.trim();
    command.toLowerCase();
    
    if (command == "start") {
      isScanning = true;
      Serial.println("SCAN_STARTED");
      Serial.println("Scanning for NRF Connect device names...");
      digitalWrite(LED_PIN, HIGH);
    } 
    else if (command == "stop") {
      isScanning = false;
      BLE.stopScan();
      Serial.println("SCAN_STOPPED");
      digitalWrite(LED_PIN, LOW);
    }
    else if (command == "scan") {
      performScan();
    }
    else if (command == "status") {
      Serial.println(isScanning ? "STATUS:SCANNING" : "STATUS:IDLE");
    }
  }
  
  // Continuous scanning
  if (isScanning) {
    performScan();
    delay(3000); // Wait 3 seconds between scans
  }
  
  delay(100);
}

void performScan() {
  Serial.println("Scanning for BLE devices...");
  
  BLE.scan();
  unsigned long scanStart = millis();
  int deviceCount = 0;
  
  while (millis() - scanStart < SCAN_DURATION) {
    BLEDevice peripheral = BLE.available();
    
    if (peripheral) {
      String address = peripheral.address();
      String name = peripheral.localName();
      int rssi = peripheral.rssi();
      
      // Only report devices with names (ignore unnamed devices)
      if (name.length() > 0) {
        deviceCount++;
        
        // Send to PHP backend in new format
        Serial.print("DETECTED:");
        Serial.print(name);           // Device name first (primary identifier)
        Serial.print(",");
        Serial.print(address);        // MAC address second (for reference)
        Serial.print(",");
        Serial.print(rssi);
        Serial.println("dBm");
        
        // LED blink for detection
        digitalWrite(LED_PIN, LOW);
        delay(50);
        digitalWrite(LED_PIN, isScanning ? HIGH : LOW);
      }
    }
    delay(10);
  }
  
  BLE.stopScan();
  Serial.print("SCAN_COMPLETE:");
  Serial.print(deviceCount);
  Serial.println(" named devices found");
  
  if (deviceCount == 0) {
    Serial.println("No named devices detected. Make sure NRF Connect devices are advertising with names.");
  }
}