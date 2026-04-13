#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <time.h>

const char* ssid = "WIFI_SSID";
const char* password = "WIFI_PASSWORD";

const char* serverUrl = "http://10.7.7.250:80/github/novagate_elektro_uai/api/v1/access-logs.php";
const char* apiKey = "NOVAGATE_DEBUG_DEVICE_2026";

const char* deviceMac = "AA:BB:CC:DD:EE:FF";

String getCurrentTimestamp();
void sendAccessLog(String rfidCode, String statusType);

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connected");

  configTime(7 * 3600, 0, "pool.ntp.org", "time.nist.gov");
}

void loop() {
  // Contoh: kirim data RFID
  String rfidCode = "A1B2C3D4"; // Ganti dengan kode RFID yang dibaca
  String statusType = "enter";   // atau "exit"
  
  sendAccessLog(rfidCode, statusType);
  
  delay(60000); // Kirim setiap 60 detik (bisa diubah)
}

void sendAccessLog(String rfidCode, String statusType) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-Key", apiKey);
    
    StaticJsonDocument<256> doc;
    doc["rfid_code"] = rfidCode;
    doc["sent_at"] = getCurrentTimestamp();
    doc["mac_address"] = deviceMac;
    doc["status_type"] = statusType;
    
    String jsonStr;
    serializeJson(doc, jsonStr);
    
    int httpCode = http.POST(jsonStr);
    
    if (httpCode > 0) {
      String response = http.getString();
      Serial.println("Response: " + response);
      
      StaticJsonDocument<256> responseDoc;
      deserializeJson(responseDoc, response);
      
      bool success = responseDoc["success"];
      if (success) {
        Serial.println("Access logged successfully!");
        String accessStatus = responseDoc["data"]["access_status"];
        String authMode = responseDoc["data"]["auth_mode"] | "unknown";
        Serial.println("Status: " + accessStatus);
        Serial.println("Auth mode: " + authMode);
      } else {
        String message = responseDoc["message"];
        Serial.println("Error: " + message);
      }
    } else {
      Serial.println("HTTP Error: " + String(httpCode));
    }
    
    http.end();
  } else {
    Serial.println("WiFi not connected");
  }
}

String getCurrentTimestamp() {
  time_t now = time(nullptr);
  struct tm* timeinfo = localtime(&now);
  
  static char buffer[30];
  strftime(buffer, sizeof(buffer), "%Y-%m-%dT%H:%M:%S", timeinfo);
  
  // Tambahkan timezone offset +07:00 (untuk WITA)
  return String(buffer) + "+07:00";
}
