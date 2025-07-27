<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "absensi";

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
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Siswa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .status-hadir {
            background: #d4edda;
            color: #155724;
        }

        .status-tidak-hadir {
            background: #f8d7da;
            color: #721c24;
        }

        .status-terlambat {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>

<body class="bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto bg-white p-6 rounded-lg shadow-lg">
        <h2 class="text-3xl font-bold text-center mb-6 text-blue-600">📋 Absensi Siswa</h2>

        <form method="GET" class="mb-4 flex items-center gap-2">
            <label for="tanggal" class="text-gray-700 font-semibold">📅 Pilih Tanggal:</label>
            <input type="date" id="tanggal" name="tanggal" value="<?= $tanggal ?>" class="p-2 border rounded-lg">
            <button type="submit" class="bg-blue-500 text-white p-2 rounded-lg shadow hover:bg-blue-600 transition">
                🔍 Tampilkan
            </button>
        </form>

        <div class="mb-4 flex gap-2">
            <a href="export_excel.php?tanggal=<?= $tanggal ?>" class="bg-green-500 text-white p-2 rounded-lg shadow hover:bg-green-600 transition">📊 Export Excel</a>
            <a href="export_pdf.php?tanggal=<?= $tanggal ?>" class="bg-red-500 text-white p-2 rounded-lg shadow hover:bg-red-600 transition">📄 Export PDF</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300 shadow-md rounded-lg">
                <thead>
                    <tr class="bg-blue-500 text-white">
                        <th class="border p-3">ID</th>
                        <th class="border p-3">NIS</th>
                        <th class="border p-3">Nama</th>
                        <th class="border p-3">Keterangan</th>
                        <th class="border p-3">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr class="border text-center <?=
                            ($row['keterangan'] == 'Tepat Waktu') ? 'status-hadir' :
                            (($row['keterangan'] == 'Tidak Hadir') ? 'status-tidak-hadir' : 'status-terlambat') ?>">
                            <td class="border p-3"><?= $row['id'] ?></td>
                            <td class="border p-3"><?= $row['nis'] ?></td>
                            <td class="border p-3 font-semibold">
                                <a href="siswa.php?id=<?= $row['id'] ?>" class="text-blue-600 hover:underline">
                                    <?= $row['nama'] ?>
                                </a>
                            </td>
                            <td class="border p-3 font-bold"><?= $row['keterangan'] ?></td>
                            <td class="border p-3"><?= $row['waktu'] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>

<?php $conn->close(); ?>