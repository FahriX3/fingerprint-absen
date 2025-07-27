<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "absensi";
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// Ambil tanggal dari input, default ke 30 hari terakhir
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-d', strtotime('-30 days'));
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Ambil data siswa
$id_siswa = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_siswa == 0) { die("ID Siswa tidak ditemukan!"); }

$siswa_query = "SELECT * FROM siswa WHERE id = $id_siswa";
$siswa_result = $conn->query($siswa_query);
$siswa = $siswa_result->fetch_assoc();
if (!$siswa) { die("Siswa tidak ditemukan!"); }

// Ambil data absensi siswa dalam rentang waktu
$absen_query = "SELECT DATE(waktu) AS tanggal, 
    CASE WHEN TIME(waktu) <= '06:45:00' THEN 'Tepat Waktu' ELSE 'Terlambat' END AS status 
    FROM absen WHERE id_siswa = $id_siswa 
    AND DATE(waktu) BETWEEN '$tanggal_awal' AND '$tanggal_akhir' 
    ORDER BY waktu ASC";
$absen_result = $conn->query($absen_query);

// Buat daftar tanggal untuk menampilkan semua hari kecuali Sabtu-Minggu
$hari_ini = strtotime($tanggal_awal);
$tanggal_data = [];
while ($hari_ini <= strtotime($tanggal_akhir)) {
    if (date('N', $hari_ini) < 6) { // 1-5 adalah Senin-Jumat
        $tanggal_data[date('Y-m-d', $hari_ini)] = "Tidak Hadir";
    }
    $hari_ini = strtotime('+1 day', $hari_ini);
}

// Update daftar tanggal dengan data absensi
while ($row = $absen_result->fetch_assoc()) {
    $tanggal_data[$row['tanggal']] = $row['status'];
}

// Hitung statistik
$total_hari = count($tanggal_data);
$hadir = count(array_filter($tanggal_data, fn($v) => $v !== "Tidak Hadir"));
$telat = count(array_filter($tanggal_data, fn($v) => $v === "Terlambat"));
$tidak_hadir = $total_hari - $hadir;

$persentase_hadir = round(($hadir / $total_hari) * 100, 2);
$persentase_telat = round(($telat / $total_hari) * 100, 2);
$persentase_tidak_hadir = round(($tidak_hadir / $total_hari) * 100, 2);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Absen - <?= $siswa['nama'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-3xl font-bold text-center mb-6 text-blue-600">📋 Detail Absensi</h2>
        <p class="text-lg"><strong>Nama:</strong> <?= $siswa['nama'] ?></p>
        <p class="text-lg"><strong>NIS:</strong> <?= $siswa['nis'] ?></p>
        <p class="text-lg"><strong>Kelas:</strong> <?= $siswa['kelas'] ?></p>
        <br/>

        <label class="text-gray-700 font-semibold">📅 Pilih Rentang Tanggal:</label>
        <form method="GET" class="mb-4 flex gap-2">
            <input type="hidden" name="id" value="<?= $id_siswa ?>">
            <input type="date" name="tanggal_awal" value="<?= $tanggal_awal ?>" class="border p-2 rounded">
            <h3>_</h3>
            <input type="date" name="tanggal_akhir" value="<?= $tanggal_akhir ?>" class="border p-2 rounded">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded">🔍 Tampilkan</button>
        </form>

        <div class="bg-gray-100 p-4 rounded-lg mt-4">
            <p><strong>Total Hadir:</strong> <?= $hadir ?> kali</p>
            <p><strong>Total Telat:</strong> <?= $telat ?> kali</p>
            <p><strong>Total Tidak Hadir:</strong> <?= $tidak_hadir ?> kali</p>
            <div class="mt-2">
                <p>Persentase Kehadiran:</p>
                <div class="w-full bg-gray-300 rounded-full h-5">
                    <div class="bg-green-500 h-5 rounded-full text-white text-sm text-center" style="width: <?= $persentase_hadir ?>%">
                        <?= $persentase_hadir ?>%
                    </div>
                </div>
                <p>Persentase Telat:</p>
                <div class="w-full bg-gray-300 rounded-full h-5">
                    <div class="bg-yellow-500 h-5 rounded-full text-white text-sm text-center" style="width: <?= $persentase_telat ?>%">
                        <?= $persentase_telat ?>%
                    </div>
                </div>
                <p>Persentase Tidak Hadir:</p>
                <div class="w-full bg-gray-300 rounded-full h-5">
                    <div class="bg-red-500 h-5 rounded-full text-white text-sm text-center" style="width: <?= $persentase_tidak_hadir ?>%">
                        <?= $persentase_tidak_hadir ?>%
                    </div>
                </div>
            </div>
        </div>

        <h3 class="text-2xl font-bold mt-6">📅 Riwayat Absensi</h3>
        <table class="w-full border-collapse border border-gray-300 shadow-md rounded-lg mt-2">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <th class="border p-3">Tanggal</th>
                    <th class="border p-3">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tanggal_data as $tanggal => $status) { ?>
                    <tr class="border text-center <?= $status == 'Tepat Waktu' ? 'bg-green-100' : ($status == 'Terlambat' ? 'bg-yellow-100' : 'bg-red-100') ?>">
                        <td class="border p-3"> <?= date("d M Y", strtotime($tanggal)) ?> </td>
                        <td class="border p-3"> <?= $status ?> </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <a href="index.php" class="mt-4 inline-block bg-gray-500 text-white p-2 rounded shadow hover:bg-gray-600 transition">
            ⬅️ Kembali ke Daftar Absensi
        </a>
    </div>
</body>
</html>
<?php $conn->close(); ?>
