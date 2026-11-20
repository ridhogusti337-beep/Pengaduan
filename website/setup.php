<?php
/**
 * Setup Utilitas Otomatis Database PDAM Kabupaten Bungo
 * Akses URL: http://localhost/website/setup.php
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pdam_bungo');

$message = '';
$status = 'idle';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Hubungkan ke MySQL tanpa nama DB terlebih dahulu
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Baca file database.sql
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("File database.sql tidak ditemukan di $sqlFile");
        }

        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);

        $status = 'success';
        $message = "Database 'pdam_bungo' beserta tabel dan data awal berhasil di-setup!";
    } catch (Exception $e) {
        $status = 'error';
        $message = "Gagal setup database: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - PDAM Bungo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 shadow-xl border border-gray-100 text-center">
        <div class="w-20 h-20 bg-white border border-gray-100 rounded-3xl p-2 shadow-lg flex items-center justify-center mx-auto mb-4">
            <img src="assets/images/logo.png" alt="Logo" class="w-full h-full object-contain">
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Inisialisasi Database</h2>
        <p class="text-sm text-gray-500 mt-1 mb-6">Setup otomatis database MySQL untuk aplikasi Layanan Pengaduan PDAM Kabupaten Bungo.</p>

        <?php if ($status === 'success'): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-sm text-left mb-6">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="fa-solid fa-circle-check text-emerald-600"></i> Berhasil!
            </div>
            <p><?= $message ?></p>
        </div>
        <a href="login.php" class="block w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition-colors">
            Lanjut ke Halaman Login <i class="fa-solid fa-arrow-right ml-1"></i>
        </a>
        <?php elseif ($status === 'error'): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 text-sm text-left mb-6">
            <div class="flex items-center gap-2 font-bold mb-1">
                <i class="fa-solid fa-circle-xmark text-red-600"></i> Terjadi Kesalahan
            </div>
            <p><?= $message ?></p>
        </div>
        <form method="POST">
            <button type="submit" class="w-full py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold transition-colors">
                Coba Lagi
            </button>
        </form>
        <?php else: ?>
        <form method="POST">
            <button type="submit" class="w-full py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-play"></i> Mulai Inisialisasi Database
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-4">Pastikan MySQL di Laragon / XAMPP sudah dalam kondisi running.</p>
        <?php endif; ?>
    </div>
</body>
</html>
