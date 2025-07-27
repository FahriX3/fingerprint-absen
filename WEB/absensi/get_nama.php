<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "absensi";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// API Key dari header
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

// Gunakan prepared statement untuk keamanan SQL
$query = "SELECT nama FROM siswa WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_siswa);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $nama = $result->fetch_assoc()['nama'];
    echo $nama;
} else {
    echo "❌ Tidak Ditemukan";
}

$stmt->close();
$conn->close();
?>
