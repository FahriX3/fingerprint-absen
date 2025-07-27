Bismilah
# 📚 Fingerprint Attendance System
Sistem absensi siswa menggunakan **sensor sidik jari (AS608)** + **ESP32**, dan **dashboard web** berbasis PHP + MySQL.

---

## 🔧 Struktur Proyek
- `arduino/` → Program ESP32 untuk membaca dan mengirim data sidik jari
- `web/` → Dashboard web untuk melihat data absensi

---

## 🔧 Komponen Hardware

| Komponen                      | Keterangan                            |
|------------------------------|---------------------------------------|
| ESP32 WROOM-32U              | Mikrokontroler utama                  |
| Sensor Sidik Jari AS608      | Untuk identifikasi pengguna          |
| LCD 1602 I2C                 | Menampilkan status & menu            |
| Buzzer                       | Memberi feedback bunyi               |
| LED RGB                      | Indikator status (merah, hijau, biru)|
| Push Button (3x)             | Navigasi mode & kontrol menu         |

---

## ⚙️ Fitur Utama

- ✅ **Mode Absensi** (default)
  - LED Biru = Standby
  - LED Hijau = Absen berhasil
  - LED Merah = Gagal / Sudah absen / Tidak dikenali

- 👤 **Mode Enroll Sidik Jari** (Tombol Kanan >3 detik)
  - Otomatis menyimpan ID baru sesuai sidik jari

- ❌ **Mode Hapus Sidik Jari** (Tombol Kiri >3 detik)
  - Pilih dan hapus ID tertentu dengan konfirmasi

- 🌐 **Mode Setting WiFi** (Tombol OK >3 detik)
  - ESP32 berubah jadi Access Point
  - Bisa atur SSID, Password, Server URL, API Key dari browser

- 💾 Data tersimpan di EEPROM internal

---

## 🖥️ Dashboard Web (PHP + MySQL)

### Fitur Web:
- Menampilkan data absensi siswa
- Statistik: Hadir, Telat, Tidak Hadir
- Riwayat harian
- Export ke Excel/PDF

---

## 🌐 Alur Komunikasi

1. User menempelkan sidik jari
2. ESP32 cocokkan dengan data
3. Jika cocok → kirim data ke server via HTTP POST
4. Server simpan ke database

---

## 🧪 Cara Pakai

1. Upload sketch ke ESP32
2. Jalankan `XAMPP` dan aktifkan `Apache + MySQL`
3. Akses `localhost/absensi` (atau IP server)
4. Tekan dan tahan tombol sesuai mode:
   - OK >3s → Setting WiFi
   - Kanan >3s → Enroll sidik jari
   - Kiri >3s → Hapus sidik jari

---

## 🛠️ Setup Arduino
1. Download program di ESP32\Lengkap
2. Upload \Lengkap\lengkap.ino kedalam esp32
3. Pastikan koneksi serial & library sudah sesuai

## 🧷 Pinout ESP32

| Fungsi         | GPIO         | Keterangan                                              |
|----------------|--------------|----------------------------------------------------------|
| **Buzzer**     | GPIO 13      | Indikator suara (absen sukses/gagal)                    |
| **LED Merah**  | GPIO 25      | Indikator error / gagal                                 |
| **LED Hijau**  | GPIO 33      | Indikator berhasil                                      |
| **LED Biru**   | GPIO 32      | Indikator mode standby/default                          |
| **Tombol OK**  | GPIO 27      | Masuk mode setting WiFi (tekan >3s)                     |
| **Tombol UP**  | GPIO 14      | Masuk mode pendaftaran sidik jari (>3s)                 |
| **Tombol DOWN / BACK** | GPIO 26 | Masuk mode hapus sidik jari (>3s)                       |
| **Sensor Sidik Jari (AS608)** | TX: GPIO 17, RX: GPIO 16 | Komunikasi serial sensor sidik jari                      |
| **LCD 1602 I2C** | SDA: GPIO 21, SCL: GPIO 22 | Menampilkan status absensi & info lainnya     

---

## 🌐 Setup Web Dashboard
1. Install XAMPP
2. Copy folder `absensi/` ke `htdocs/`
4. Jalankan Apache & MySQL
5. Buka `localhost/absensi/`

---

## 🛢️ Setup Database MySQL

```sql
CREATE DATABASE absensi;
USE absensi;

CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nama VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    kelas VARCHAR(50) NOT NULL
);

CREATE TABLE absen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_siswa INT NOT NULL,
    waktu DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Contoh data dummy:
INSERT INTO siswa (id, nis, nama, jenis_kelamin, kelas) VALUES
(1, '21760', 'Nama Siswa', 'L', 'X PPLG 2');

INSERT INTO absen (id_siswa, waktu) VALUES
(1, '2025-02-19 06:30:00');

```

## Tambahkan auto EXCEL
// cek apaksh sudah memiliki composer
composer --version
jika belum bisa install di
https://getcomposer.org/

# Modifikasi file
// lokasi file
C:\xampp\php\php.ini

// cari ini
;extension=gd
;extension=zip

hapus tanda ; di depannya
menjadi seperti ini
;extension=gd
;extension=zip

# Buka CMD
cd C:\xampp\htdocs\absensi

// masukkan perintah
composer require phpoffice/phpspreadsheet

---

# Tambahkan fitur auto PDF
ekstrak fpdf186.zip

---

## 📌 Catatan

- Pastikan koneksi dengan server stabil
- Jarak jari harus tepat saat scan sidik jari
- Gunakan power supply 5V 2A untuk kestabilan

---

## 🧑‍💻 Author

**Nama:** Fahri Azzam Mandriva
**IG:** @fahri_man007

---

## 📜 License

Proyek ini open-source, silakan digunakan/diubah dengan mencantumkan atribusi. 🙏