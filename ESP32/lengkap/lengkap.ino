#include <HardwareSerial.h>
#include <Adafruit_Fingerprint.h>
#include <LiquidCrystal_I2C.h>
#include <WiFi.h>
#include <WebServer.h>
#include <EEPROM.h>
#include <Wire.h>
#include "setting.h"
#include <HTTPClient.h>

// Pin Konfigurasi
#define BUZZER 13
#define LED_R 25
#define LED_G 33
#define LED_B 32
#define BUTTON_OK 27
#define BUTTON_UP 14
#define BUTTON_DOWN 26
#define BUTTON_BACK 26
#define EEPROM_SIZE 160

const char* apSSID = "Absen-P4";
const char* apPassword = "12345678";

uint8_t id;
bool isDefaultMode = true;
bool isAPMode = false;

WebServer server(80);
String ssid = "", password = "", serverUrl = "", apiKey = "";
HardwareSerial mySerial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);
LiquidCrystal_I2C lcd(0x27, 16, 2);

void showDefaultScreen() {
  lcd.clear();
  lcd.print("Silahkan Absen");
  isDefaultMode = true;
  isAPMode = false;
}

uint8_t getAvailableID() {
  for (uint8_t i = 1; i <= 127; i++) {
    if (finger.loadModel(i) != FINGERPRINT_OK) {
      return i;
    }
  }
  return 0;  // Full
}

void processFingerprint() {
  int id = getFingerprintID();

  if (id == -1) {
    lcd.clear();
    lcd.print("Sidik jari");
    lcd.setCursor(0, 1);
    lcd.print("tidak dikenali");
    digitalWrite(LED_R, HIGH);
    tone(BUZZER, 500, 500);
    delay(1000);
    digitalWrite(LED_R, LOW);
    return;
  }

  // Kirim ID ke server & ambil respon
  String response = sendAttendance(id);

  lcd.clear();
  lcd.print(response.substring(2, response.indexOf("|")));  // Nama Siswa
  lcd.setCursor(0, 1);
  lcd.print(response.substring(response.indexOf("|") + 1));  // Status Absen

  // Cek apakah sukses atau gagal
  if (response.startsWith("✅")) {
    digitalWrite(LED_G, HIGH);
    tone(BUZZER, 1000, 500);
  } else {
    digitalWrite(LED_R, HIGH);
    tone(BUZZER, 500, 500);
  }

  delay(2000);
  digitalWrite(LED_R, LOW);
  digitalWrite(LED_G, LOW);

  unsigned long timeout = millis();
  while (millis() - timeout < 3000) {
    if (finger.getImage() == FINGERPRINT_OK) {
      processFingerprint();  // Bisa langsung absen lagi tanpa nunggu 3 detik
      return;
    }
  }
  showDefaultScreen();
}


int getFingerprintID() {
  int p = finger.getImage();
  if (p != FINGERPRINT_OK) return -1;

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) return -1;

  p = finger.fingerFastSearch();
  if (p != FINGERPRINT_OK) return -1;

  return finger.fingerID;
}

String sendAttendance(int id) {
  if (WiFi.status() != WL_CONNECTED) return "❌ Tidak Ada Koneksi";

  HTTPClient http;
  String url = serverUrl + "absen.php?id_siswa=" + String(id);

  http.begin(url);
  http.addHeader("API-Key", apiKey);  // 🔥 Kirim API Key
  http.setTimeout(1000);              // 🔥 Timeout biar gak kelamaan
  int httpCode = http.GET();

  String response = (httpCode == 200) ? http.getString() : "❌ Gagal Terhubung";
  http.end();

  return response;
}


bool isAlreadyCheckedIn(int id) {
  // Bisa diganti dengan pengecekan dari server
  return false;
}

String formatURL(String baseURL, String endpoint) {
  if (!baseURL.endsWith("/")) {
    baseURL += "/";
  }
  return baseURL + endpoint;
}

String getStudentName(int id) {
  if (WiFi.status() != WL_CONNECTED) return "Sistem Offline";

  HTTPClient http;
  String url = serverUrl + "get_nama.php?id_siswa=" + String(id);

  http.begin(url);
  http.addHeader("API-Key", apiKey);  // 🔥 Kirim API Key biar aman
  http.setTimeout(1000);              // 🔥 Timeout biar gak kelamaan
  int httpCode = http.GET();

  String response = (httpCode == 200) ? http.getString() : "❌ Tidak Ditemukan";
  http.end();

  return response;
}




uint8_t getFingerprintEnroll(uint8_t id) {
  int p;
  do {
    p = finger.getImage();
    if (digitalRead(BUTTON_BACK) == LOW) {  // Jika tombol BACK ditekan, keluar ke mode default
      showDefaultScreen();
      return 0;
    }
  } while (p != FINGERPRINT_OK);

  if (finger.image2Tz(1) != FINGERPRINT_OK) return 0;

  lcd.clear();
  lcd.print("Lepaskan jari");
  delay(2000);

  while (finger.getImage() != FINGERPRINT_NOFINGER);  // Tunggu jari dilepas

  lcd.clear();
  lcd.print("Letakkan lagi");
  do {
    p = finger.getImage();
    if (digitalRead(BUTTON_BACK) == LOW) {
      showDefaultScreen();
      return 0;
    }
  } while (p != FINGERPRINT_OK);

  if (finger.image2Tz(2) != FINGERPRINT_OK) return 0;
  if (finger.createModel() != FINGERPRINT_OK) return 0;

  return (finger.storeModel(id) == FINGERPRINT_OK);
}

void enrollFingerprint() {
  while (true) {  // Tetap dalam mode registrasi
    isDefaultMode = false;
    id = getAvailableID();
    if (id == 0) {
      lcd.clear();
      lcd.print("Memori Penuh!");
      delay(2000);
      showDefaultScreen();
      return;
    }

    lcd.clear();
    lcd.print("Pilih ID: ");
    lcd.setCursor(0, 1);
    lcd.print(id);
    delay(1000);

    while (true) {  // Pilih ID
      if (digitalRead(BUTTON_UP) == LOW) id++;
      if (digitalRead(BUTTON_DOWN) == LOW) id--;
      id = constrain(id, 1, 127);
      lcd.setCursor(0, 1);
      lcd.print("    ");
      lcd.setCursor(0, 1);
      lcd.print(id);

      if (digitalRead(BUTTON_OK) == LOW) break;
      delay(200);
    }

    if (finger.loadModel(id) == FINGERPRINT_OK) {
      lcd.clear();
      lcd.print("ID sudah ada!");
      delay(2000);
      continue;  // Kembali ke awal loop, biar bisa pilih ulang
    }

    lcd.clear();
    lcd.print("Letakkan jari");
    if (getFingerprintEnroll(id)) {
      lcd.clear();
      lcd.print("Registrasi OK");
      digitalWrite(LED_G, HIGH);
      tone(BUZZER, 1000, 500);
      delay(2000);
      digitalWrite(LED_G, LOW);
    } else {
      lcd.clear();
      lcd.print("Gagal daftar!");
      digitalWrite(LED_R, HIGH);
      tone(BUZZER, 500, 500);
      delay(2000);
      digitalWrite(LED_R, LOW);
    }

    // Pilihan setelah registrasi
    while (true) {
      lcd.clear();
      lcd.print("Letakkan jari");
      lcd.setCursor(0, 1);
      lcd.print("OK=Keluar  →=Lanjut");

      if (digitalRead(BUTTON_OK) == LOW) {  // Jika tombol OK ditekan, keluar ke mode default
        lcd.clear();
        lcd.print("Kembali ke mode");
        lcd.setCursor(0, 1);
        lcd.print("default...");
        delay(1000);
        showDefaultScreen();
        return;
      }

      if (digitalRead(BUTTON_UP) == LOW) {  // Jika tombol kanan ditekan, lanjut registrasi lagi
        delay(300); // Debounce tombol
        break;  // Kembali ke awal loop untuk pilih ID baru
      }

      delay(100);
    }
  }
}



void deleteFingerprint() {
  isDefaultMode = false;
  id = 1;
  lcd.clear();
  lcd.print("Hapus ID: ");
  lcd.setCursor(0, 1);
  lcd.print(id);
  delay(1000);

  while (true) {
    if (digitalRead(BUTTON_UP) == LOW) id++;
    if (digitalRead(BUTTON_DOWN) == LOW) id--;
    id = constrain(id, 1, 127);
    lcd.setCursor(0, 1);
    lcd.print("    ");
    lcd.setCursor(0, 1);
    lcd.print(id);

    if (digitalRead(BUTTON_OK) == LOW) break;
    delay(200);
  }

  if (finger.loadModel(id) != FINGERPRINT_OK) {
    lcd.clear();
    lcd.print("ID tidak ditemukan!");
    delay(2000);
    showDefaultScreen();
    return;
  }

  lcd.clear();
  lcd.print("Tekan OK untuk");
  lcd.setCursor(0, 1);
  lcd.print("hapus ID ");
  lcd.print(id);
  delay(2000);

  while (true) {
    if (digitalRead(BUTTON_BACK) == LOW) {
      showDefaultScreen();
      return;
    }
    if (digitalRead(BUTTON_OK) == LOW) break;
    delay(200);
  }

  if (finger.deleteModel(id) == FINGERPRINT_OK) {
    lcd.clear();
    lcd.print("ID ");
    lcd.print(id);
    lcd.print(" terhapus");
    digitalWrite(LED_R, HIGH);
    tone(BUZZER, 500, 500);
    delay(2000);
    digitalWrite(LED_R, LOW);
  } else {
    lcd.clear();
    lcd.print("Gagal menghapus!");
    delay(2000);
  }
  showDefaultScreen();
}



void saveConfig(String newSSID, String newPass, String newServer, String newKey) {
  EEPROM.begin(EEPROM_SIZE);
  for (int i = 0; i < 32; i++) EEPROM.write(i, i < newSSID.length() ? newSSID[i] : 0);
  for (int i = 32; i < 64; i++) EEPROM.write(i, i - 32 < newPass.length() ? newPass[i - 32] : 0);
  for (int i = 64; i < 128; i++) EEPROM.write(i, i - 64 < newServer.length() ? newServer[i - 64] : 0);
  for (int i = 128; i < 160; i++) EEPROM.write(i, i - 128 < newKey.length() ? newKey[i - 128] : 0);
  EEPROM.commit();
  EEPROM.end();
}

void loadConfig() {
  EEPROM.begin(EEPROM_SIZE);
  char ssidBuf[33], passBuf[33], urlBuf[65], keyBuf[33];
  for (int i = 0; i < 32; i++) ssidBuf[i] = EEPROM.read(i);
  for (int i = 32; i < 64; i++) passBuf[i - 32] = EEPROM.read(i);
  for (int i = 64; i < 128; i++) urlBuf[i - 64] = EEPROM.read(i);
  for (int i = 128; i < 160; i++) keyBuf[i - 128] = EEPROM.read(i);
  ssidBuf[32] = passBuf[32] = urlBuf[64] = keyBuf[32] = '\0';
  ssid = String(ssidBuf);
  password = String(passBuf);
  serverUrl = String(urlBuf);
  apiKey = String(keyBuf);
  EEPROM.end();
}

void handleRoot() {
  String page = settingsPage;
  page.replace("%SSID%", ssid);
  page.replace("%PASSWORD%", password);
  page.replace("%SERVER%", serverUrl);
  page.replace("%APIKEY%", apiKey);
  server.send(200, "text/html", page);
}

void handleSave() {
  if (server.hasArg("ssid") && server.hasArg("password") && server.hasArg("serverUrl") && server.hasArg("apiKey")) {
    saveConfig(server.arg("ssid"), server.arg("password"), server.arg("serverUrl"), server.arg("apiKey"));
    server.send(200, "text/html", "<h1>✅ Konfigurasi Disimpan! Restart ESP32...</h1>");
    lcd.clear();
    lcd.print("Konfigurasi Disimpan");
    delay(2000);
    ESP.restart();  // 🔥 Restart ESP setelah user menyimpan pengaturan
  } else {
    server.send(400, "text/html", "<h1>❌ Semua kolom harus diisi!</h1>");
    lcd.clear();
    lcd.print("Semua kolom wajib");
    lcd.setCursor(0, 1);
    lcd.print("diisi!");
    delay(2000);
  }
}


void setup() {
  Serial.begin(115200);
  lcd.init();
  lcd.backlight();
  pinMode(BUZZER, OUTPUT);
  pinMode(LED_R, OUTPUT);
  pinMode(LED_G, OUTPUT);
  pinMode(LED_B, OUTPUT);
  pinMode(BUTTON_OK, INPUT_PULLUP);
  pinMode(BUTTON_UP, INPUT_PULLUP);
  pinMode(BUTTON_DOWN, INPUT_PULLUP);
  pinMode(BUTTON_BACK, INPUT_PULLUP);

  mySerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);

  if (!finger.verifyPassword()) {
    lcd.print("Sensor Error!");
    while (1)
      ;
  }

  loadConfig();

  lcd.clear();
  lcd.print("Cek Tombol...");
  unsigned long startPress = millis();
  while (digitalRead(BUTTON_OK) == LOW) {
    if (millis() - startPress > 3000) {
      setupWiFi();
      return;
    }
  }

  if (ssid.length() > 0) {
    WiFi.begin(ssid.c_str(), password.c_str());
    lcd.clear();
    lcd.print("Menghubungkan:");
    lcd.setCursor(0, 1);
    lcd.print(ssid);
    int attempt = 0;
    while (WiFi.status() != WL_CONNECTED && attempt < 10) {
      delay(1000);
      attempt++;
    }
    if (WiFi.status() == WL_CONNECTED) {
      lcd.clear();
      lcd.print("WiFi Terhubung!");
      lcd.setCursor(0, 1);
      lcd.print(WiFi.localIP());
      delay(2000);
    } else {
      lcd.clear();
      lcd.print("Gagal Konek WiFi");
      delay(2000);
      setupWiFi();
      return;
    }
  } else {
    setupWiFi();
    return;
  }

  showDefaultScreen();
}

void setupWiFi() {
  isDefaultMode = false;
  isAPMode = true;  // 🔥 Tandai Mode AP aktif

  lcd.clear();
  lcd.print("Setting WiFi");
  WiFi.softAP(apSSID, apPassword);
  lcd.setCursor(0, 1);
  lcd.print("IP:");
  lcd.print(WiFi.softAPIP());

  server.on("/", handleRoot);
  server.on("/save", HTTP_POST, handleSave);
  server.begin();

  // 🔥 Cek tombol kiri untuk keluar dari Mode AP
  unsigned long startPress = 0;
  while (isAPMode) {
    server.handleClient();

    if (digitalRead(BUTTON_BACK) == LOW) {         // Jika tombol kiri ditekan
      if (startPress == 0) startPress = millis();  // Mulai hitung waktu tekan
      if (millis() - startPress > 3000) {          // Jika lebih dari 3 detik
        lcd.clear();
        lcd.print("Keluar Mode AP...");
        delay(2000);
        ESP.restart();  // 🔥 Restart ESP untuk kembali ke Default Mode
      }
    } else {
      startPress = 0;  // Reset hitungan jika tombol dilepas
    }
  }
}



void loop() {
  if (isDefaultMode) {
    if (digitalRead(BUTTON_UP) == LOW) {
      delay(3000);
      if (digitalRead(BUTTON_UP) == LOW) enrollFingerprint();
    }
    if (digitalRead(BUTTON_DOWN) == LOW) {
      delay(3000);
      if (digitalRead(BUTTON_DOWN) == LOW) deleteFingerprint();
    }
    if (digitalRead(BUTTON_OK) == LOW) {
      delay(3000);
      if (digitalRead(BUTTON_OK) == LOW) setupWiFi();
    }

    // 🔥 Cek Sidik Jari (Absen)
    if (finger.getImage() == FINGERPRINT_OK) {
      processFingerprint();
    }
  }

  if (isAPMode) {
    server.handleClient();
  }
}
