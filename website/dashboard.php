<?php
require_once 'config.php';
requireLogin();

$userRole = $_SESSION['user_role'] ?? 'petugas';

// ========================================================
// JIKA LOGIN SEBAGAI PELANGGAN (APK PELANGGAN)
// ========================================================
if ($userRole === 'pelanggan') {
    $pelangganId = $_SESSION['pelanggan_id'] ?? 1;

    // Ambil data detail profil pelanggan
    $stmt = $pdo->prepare("SELECT * FROM pelanggan WHERE id = ?");
    $stmt->execute([$pelangganId]);
    $myProfile = $stmt->fetch();

    // Statistik pengaduan milik pelanggan ini
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengaduan WHERE pelanggan_id = ?");
    $stmt->execute([$pelangganId]);
    $myTotal = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengaduan WHERE pelanggan_id = ? AND status = 'baru'");
    $stmt->execute([$pelangganId]);
    $myBaru = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengaduan WHERE pelanggan_id = ? AND status = 'proses'");
    $stmt->execute([$pelangganId]);
    $myProses = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengaduan WHERE pelanggan_id = ? AND status = 'selesai'");
    $stmt->execute([$pelangganId]);
    $mySelesai = $stmt->fetchColumn();

    // Pengaduan terakhir milik pelanggan ini
    $stmt = $pdo->prepare("
        SELECT p.*, k.nama_kategori, u.nama as nama_petugas
        FROM pengaduan p
        LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
        LEFT JOIN users u ON p.petugas_id = u.id
        WHERE p.pelanggan_id = ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$pelangganId]);
    $myPengaduan = $stmt->fetchAll();

    $pageTitle = 'Beranda Pelanggan';
    include 'includes/header.php';
    include 'includes/sidebar.php';
    ?>

    <div class="flex-1 min-w-0 overflow-auto">
        <?php include 'includes/topbar.php'; ?>

        <main class="p-6 space-y-6">
            <!-- Welcome Banner Pelanggan -->
            <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 rounded-3xl p-6 text-white relative overflow-hidden shadow-lg">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 text-xs font-semibold tracking-wide backdrop-blur-sm mb-2">
                            <i class="fa-solid fa-mobile-screen-button"></i> Portal Layanan Pengaduan Pelanggan
                        </span>
                        <h2 class="text-2xl font-extrabold">Halo, <?= sanitize($_SESSION['user_name']) ?> 👋</h2>
                        <p class="text-teal-100 text-sm mt-1 max-w-xl">
                            Selamat datang di aplikasi layanan pelanggan PDAM Tirta Muaro Kabupaten Bungo. Pantau kondisi aliran air dan sampaikan kendala Anda langsung kepada kami.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= BASE_URL ?>/pengaduan/tambah.php" class="bg-white hover:bg-teal-50 text-emerald-800 font-bold px-5 py-3 rounded-2xl shadow-xl flex items-center justify-center gap-2 text-sm transition-all hover:scale-105 flex-shrink-0">
                            <i class="fa-solid fa-bullhorn text-emerald-600 text-base"></i>
                            Ajukan Pengaduan
                        </a>
                        <a href="<?= WA_CS_URL ?>" target="_blank" class="bg-emerald-500 hover:bg-emerald-400 text-white font-bold px-5 py-3 rounded-2xl shadow-xl flex items-center justify-center gap-2 text-sm transition-all hover:scale-105 flex-shrink-0 border border-emerald-300/40">
                            <i class="fa-brands fa-whatsapp text-lg"></i>
                            Chat CS: <?= WA_CS_DISPLAY ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Kartu ID Pelanggan & Info Langganan -->
            <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-address-card"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-base">Informasi Sambungan Air Anda</h3>
                            <p class="text-xs text-gray-400">Data nomor pelanggan resmi terdaftar di PDAM Bungo</p>
                        </div>
                    </div>
                    <span class="bg-teal-100 text-teal-800 text-xs font-bold px-3 py-1 rounded-full">
                        Status Aktif
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-gray-400">No. Pelanggan</p>
                        <p class="text-base font-mono font-bold text-teal-700 mt-1"><?= sanitize($myProfile['no_pelanggan'] ?? 'PDM-001') ?></p>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <p class="text-xs font-medium text-gray-400">Golongan Tarif</p>
                        <p class="text-base font-bold text-gray-800 mt-1">Gol. <?= sanitize($myProfile['golongan'] ?? 'R1') ?></p>
                    </div>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 sm:col-span-2">
                        <p class="text-xs font-medium text-gray-400">Alamat Pemasangan</p>
                        <p class="text-sm font-semibold text-gray-800 mt-1 line-clamp-1"><?= sanitize($myProfile['alamat'] ?? 'Muara Bungo') ?>, Kec. <?= sanitize($myProfile['kecamatan'] ?? 'Bungo') ?></p>
                    </div>
                </div>
            </div>

            <!-- Statistik Pengaduan Saya -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-paper-plane text-sm"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800"><?= $myTotal ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-0.5">Total Pengaduan</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-clock text-sm"></i>
                    </div>
                    <p class="text-2xl font-bold text-sky-700"><?= $myBaru ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-0.5">Antrian Verifikasi</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-wrench text-sm"></i>
                    </div>
                    <p class="text-2xl font-bold text-amber-700"><?= $myProses ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-0.5">Sedang Ditangani</p>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3">
                        <i class="fa-solid fa-circle-check text-sm"></i>
                    </div>
                    <p class="text-2xl font-bold text-emerald-700"><?= $mySelesai ?></p>
                    <p class="text-xs text-gray-500 font-medium mt-0.5">Selesai Dituntaskan</p>
                </div>
            </div>

            <!-- Tabel Status Pengaduan Terakhir Pelanggan -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-base">Riwayat Laporan Saya</h3>
                        <p class="text-xs text-gray-400">Pantau proses tindak lanjut teknisi PDAM terhadap keluhan Anda</p>
                    </div>
                    <a href="<?= BASE_URL ?>/pengaduan/index.php" class="text-xs font-semibold text-teal-600 hover:text-teal-800 flex items-center gap-1">
                        Lihat Semua <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>

                <?php if (empty($myPengaduan)): ?>
                <div class="p-10 text-center">
                    <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-3 text-2xl">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <p class="font-bold text-gray-700">Tidak ada pengaduan aktif</p>
                    <p class="text-xs text-gray-400 mt-1">Layanan air PDAM Anda dalam kondisi normal. Klik tombol di atas jika mengalami kendala.</p>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">No. Pengaduan</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Keluhan & Kategori</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Status Penanganan</th>
                                <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Tanggal Masuk</th>
                                <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($myPengaduan as $p): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-teal-700 text-xs font-mono"><?= sanitize($p['no_pengaduan']) ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-gray-800 text-sm"><?= sanitize($p['judul']) ?></p>
                                    <p class="text-xs text-gray-400"><?= sanitize($p['nama_kategori']) ?></p>
                                </td>
                                <td class="px-5 py-4"><?= statusBadge($p['status']) ?></td>
                                <td class="px-5 py-4 text-xs text-gray-500"><?= formatTanggalWaktu($p['tgl_pengaduan']) ?></td>
                                <td class="px-5 py-4 text-center">
                                    <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= $p['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-700 text-xs font-semibold transition-colors">
                                        <i class="fa-solid fa-eye text-xs"></i> Lacak Status
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php
    include 'includes/footer.php';
    exit;
}

// ========================================================
// JIKA LOGIN SEBAGAI ADMIN / PETUGAS
// ========================================================
$stats = [];

// Total Pengaduan
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pengaduan");
$stats['total'] = $stmt->fetch()['total'];

// Pengaduan Baru
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pengaduan WHERE status='baru'");
$stats['baru'] = $stmt->fetch()['total'];

// Sedang Diproses
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pengaduan WHERE status='proses'");
$stats['proses'] = $stmt->fetch()['total'];

// Selesai
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pengaduan WHERE status='selesai'");
$stats['selesai'] = $stmt->fetch()['total'];

// Total Pelanggan
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pelanggan");
$stats['pelanggan'] = $stmt->fetch()['total'];

// Pengaduan bulan ini
$stmt = $pdo->query("SELECT COUNT(*) as total FROM pengaduan WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stats['bulan_ini'] = $stmt->fetch()['total'];

// Data pengaduan terbaru (5 record)
$stmt = $pdo->query("
    SELECT p.*, pl.nama as nama_pelanggan, pl.no_pelanggan, k.nama_kategori, u.nama as nama_petugas
    FROM pengaduan p
    LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.petugas_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$pengaduanTerbaru = $stmt->fetchAll();

// Data per kategori untuk chart
$stmt = $pdo->query("
    SELECT k.nama_kategori, COUNT(p.id) as total
    FROM kategori_pengaduan k
    LEFT JOIN pengaduan p ON k.id = p.kategori_id
    GROUP BY k.id, k.nama_kategori
    ORDER BY total DESC
    LIMIT 6
");
$dataKategori = $stmt->fetchAll();

// Data per bulan (6 bulan terakhir)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as bulan,
           DATE_FORMAT(created_at, '%b %Y') as label,
           COUNT(*) as total
    FROM pengaduan
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b %Y')
    ORDER BY bulan ASC
");
$dataBulan = $stmt->fetchAll();

// Pengaduan prioritas tinggi
$stmt = $pdo->query("
    SELECT p.*, pl.nama as nama_pelanggan, k.nama_kategori
    FROM pengaduan p
    LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    WHERE p.prioritas = 'tinggi' AND p.status NOT IN ('selesai','ditolak')
    ORDER BY p.created_at DESC
    LIMIT 4
");
$prioritasTinggi = $stmt->fetchAll();

$pageTitle = 'Dashboard';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include 'includes/topbar.php'; ?>

    <main class="p-6 space-y-6">
        <!-- Welcome Banner Admin -->
        <div class="bg-gradient-to-r from-sky-600 via-sky-500 to-cyan-500 rounded-2xl p-6 text-white relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-sky-100 text-sm font-medium">Selamat Datang,</p>
                <h2 class="text-2xl font-bold mt-0.5"><?= sanitize($_SESSION['user_name']) ?> 👋</h2>
                <p class="text-sky-100 text-sm mt-1">
                    <?= date('l, d F Y') ?> &bull; <?= ucfirst($_SESSION['user_role']) ?>
                </p>
                <div class="flex items-center gap-4 mt-4">
                    <div class="bg-white/20 rounded-xl px-4 py-2">
                        <p class="text-xs text-sky-100">Pengaduan Baru</p>
                        <p class="text-2xl font-bold"><?= $stats['baru'] ?></p>
                    </div>
                    <div class="bg-white/20 rounded-xl px-4 py-2">
                        <p class="text-xs text-sky-100">Bulan Ini</p>
                        <p class="text-2xl font-bold"><?= $stats['bulan_ini'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kartu Statistik -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <p class="text-3xl font-bold text-gray-800"><?= $stats['total'] ?></p>
                <p class="text-sm text-gray-500 mt-1 font-medium">Total Pengaduan</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <p class="text-3xl font-bold text-sky-600"><?= $stats['baru'] ?></p>
                <p class="text-sm text-gray-500 mt-1 font-medium">Pengaduan Baru</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <p class="text-3xl font-bold text-amber-600"><?= $stats['proses'] ?></p>
                <p class="text-sm text-gray-500 mt-1 font-medium">Sedang Ditangani</p>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <p class="text-3xl font-bold text-emerald-600"><?= $stats['selesai'] ?></p>
                <p class="text-sm text-gray-500 mt-1 font-medium">Pengaduan Selesai</p>
            </div>
        </div>

        <!-- Grafik + Distribusi -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4">Tren Pengaduan (6 Bulan Terakhir)</h3>
                <canvas id="chartBulanan" height="80"></canvas>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4">Per Kategori Keluhan</h3>
                <canvas id="chartKategori" height="180"></canvas>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dataBulan = <?= json_encode(array_values($dataBulan)) ?>;
const dataKategori = <?= json_encode(array_values($dataKategori)) ?>;

const ctxBulan = document.getElementById('chartBulanan').getContext('2d');
new Chart(ctxBulan, {
    type: 'line',
    data: {
        labels: dataBulan.map(d => d.label || d.bulan),
        datasets: [{
            label: 'Pengaduan',
            data: dataBulan.map(d => parseInt(d.total)),
            borderColor: '#0ea5e9',
            backgroundColor: 'rgba(14,165,233,0.1)',
            borderWidth: 2.5,
            fill: true
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

const ctxKat = document.getElementById('chartKategori').getContext('2d');
new Chart(ctxKat, {
    type: 'doughnut',
    data: {
        labels: dataKategori.map(d => d.nama_kategori),
        datasets: [{
            data: dataKategori.map(d => parseInt(d.total)),
            backgroundColor: ['#0ea5e9','#f59e0b','#10b981','#ef4444','#8b5cf6','#f97316']
        }]
    },
    options: { responsive: true, cutout: '65%', plugins: { legend: { display: false } } }
});
</script>

<?php include 'includes/footer.php'; ?>
