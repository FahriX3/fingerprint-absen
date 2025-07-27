<?php
require('fpdf.php');

$host = "localhost";
$user = "root";
$password = "";
$database = "absensi";

// Koneksi ke database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil tanggal dari input, default ke hari ini
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Query untuk mengambil semua siswa dan status kehadiran mereka
$sql = "SELECT s.id, s.nis, s.nama, 
        COALESCE(a.waktu, '-') AS waktu, 
        CASE 
            WHEN a.waktu IS NULL THEN 'Tidak Hadir'
            WHEN TIME(a.waktu) <= '06:45:00' THEN 'Tepat Waktu'
            ELSE 'Terlambat'
        END AS keterangan
        FROM siswa s
        LEFT JOIN absen a ON s.id = a.id_siswa AND DATE(a.waktu) = '$tanggal'
        ORDER BY s.id";

$result = $conn->query($sql);

if (!$result) {
    die("Query gagal: " . $conn->error);
}

// Mulai membuat file PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 10, 'Laporan Absensi Siswa - ' . $tanggal, 0, 1, 'C');
$pdf->Ln(10);

// Header tabel
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(20, 10, 'ID', 1);
$pdf->Cell(30, 10, 'NIS', 1);
$pdf->Cell(60, 10, 'Nama', 1);
$pdf->Cell(40, 10, 'Keterangan', 1);
$pdf->Cell(40, 10, 'Waktu', 1);
$pdf->Ln();

// Isi tabel
$pdf->SetFont('Arial', '', 12);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(20, 10, $row['id'], 1);
    $pdf->Cell(30, 10, $row['nis'], 1);
    $pdf->Cell(60, 10, $row['nama'], 1);
    $pdf->Cell(40, 10, $row['keterangan'], 1);
    $pdf->Cell(40, 10, $row['waktu'], 1);
    $pdf->Ln();
}

// Output PDF
$pdf->Output('D', 'Laporan_Absensi_' . $tanggal . '.pdf');
?>
