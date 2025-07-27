<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "absensi";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// API Key dari header (gunakan HTTP_API_KEY untuk kompatibilitas luas)
$allowed_api_key = "ANJAY";
$api_key = isset($_SERVER['HTTP_API_KEY']) ? $_SERVER['HTTP_API_KEY'] : '';

if ($api_key !== $allowed_api_key) {
    die("❌ Akses Ditolak! API Key salah.");
}

// Ambil ID siswa dengan validasi
$id_siswa = isset($_GET['id_siswa']) ? intval($_GET['id_siswa']) : 0;
if ($id_siswa <= 0) {
    die("❌ ID Siswa tidak valid!");
}

// Cek apakah siswa sudah absen hari ini
$query = "SELECT s.nama, a.id 
          FROM siswa s 
          LEFT JOIN absen a ON s.id = a.id_siswa AND DATE(a.waktu) = CURDATE()
          WHERE s.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nama = $row['nama'];

    if (!empty($row['id'])) {
        echo "❌ $nama|Sudah absen!";
    } else {
        // Simpan absen dengan prepared statement
        $insert = "INSERT INTO absen (id_siswa, waktu) VALUES (?, NOW())";
        $stmt_insert = $conn->prepare($insert);
        $stmt_insert->bind_param("i", $id_siswa);

        if ($stmt_insert->execute()) {
            echo "✅ $nama|Absen berhasil!";
        } else {
            echo "❌ $nama|Gagal absen!";
        }
        $stmt_insert->close();
    }
} else {
    echo "❌ Tidak Ditemukan|ID Siswa tidak ditemukan!";
}

$stmt->close();
$conn->close();
?>
