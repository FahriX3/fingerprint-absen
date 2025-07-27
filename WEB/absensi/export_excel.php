<?php
require 'vendor/autoload.php'; // Pastikan PHPOffice sudah terinstall

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$host = "localhost";
$user = "root";
$password = "";
$database = "absensi";
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'ID')->setCellValue('B1', 'NIS')->setCellValue('C1', 'Nama')
      ->setCellValue('D1', 'Keterangan')->setCellValue('E1', 'Waktu');

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue("A$rowNum", $row['id'])
          ->setCellValue("B$rowNum", $row['nis'])
          ->setCellValue("C$rowNum", $row['nama'])
          ->setCellValue("D$rowNum", $row['keterangan'])
          ->setCellValue("E$rowNum", $row['waktu']);
    $rowNum++;
}

$writer = new Xlsx($spreadsheet);
$filename = "Absensi_$tanggal.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=$filename");
$writer->save("php://output");
$conn->close();
exit();
