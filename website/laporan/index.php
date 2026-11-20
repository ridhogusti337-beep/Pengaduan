<?php
require_once '../config.php';
requireLogin();
requireRole('admin');

// Parameter filter laporan
$tgl_awal    = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir   = $_GET['tgl_akhir'] ?? date('Y-m-d');
$status      = $_GET['status'] ?? '';
$kategori_id = intval($_GET['kategori_id'] ?? 0);

$where = ["DATE(p.created_at) BETWEEN ? AND ?"];
$params = [$tgl_awal, $tgl_akhir];

if ($status) {
    $where[] = "p.status = ?";
    $params[] = $status;
}

if ($kategori_id) {
    $where[] = "p.kategori_id = ?";
    $params[] = $kategori_id;
}

$whereStr = "WHERE " . implode(" AND ", $where);

// Data laporan
$sql = "
    SELECT p.*, pl.nama as nama_pelanggan, pl.no_pelanggan, pl.alamat, pl.no_telepon,
           k.nama_kategori, u.nama as nama_petugas
    FROM pengaduan p
    LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.petugas_id = u.id
    $whereStr
    ORDER BY p.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$laporan = $stmt->fetchAll();

// Kategori untuk dropdown
$kategoriList = $pdo->query("SELECT * FROM kategori_pengaduan ORDER BY nama_kategori ASC")->fetchAll();

// Hitung rekap statistik laporan
$totalLaporan  = count($laporan);
$totalSelesai  = count(array_filter($laporan, fn($x) => $x['status'] === 'selesai'));
$totalProses   = count(array_filter($laporan, fn($x) => $x['status'] === 'proses'));
$totalBaru     = count(array_filter($laporan, fn($x) => $x['status'] === 'baru'));
$totalDitolak  = count(array_filter($laporan, fn($x) => $x['status'] === 'ditolak'));

$pageTitle = 'Laporan Pengaduan';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Style khusus print / cetak PDF -->
<style>
@media print {
    body { background: white !important; font-size: 11pt; }
    #sidebar, header, .no-print { display: none !important; }
    .print-only { display: block !important; }
    .page-break { page-break-after: always; }
    .shadow-sm, .rounded-2xl { box-shadow: none !important; border: none !important; }
    table { width: 100% !important; border-collapse: collapse !important; }
    th, td { border: 1px solid #999 !important; padding: 6px 8px !important; }
}
.print-only { display: none; }
</style>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6 space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Laporan Rekapitulasi Pengaduan</h2>
                <p class="text-sm text-gray-500">Filter, analisis, dan cetak laporan pengaduan pelanggan PDAM Bungo</p>
            </div>
            <button onclick="window.print()" class="btn-primary text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 w-fit">
                <i class="fa-solid fa-print"></i> Cetak / Ekspor PDF
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 no-print">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Dari Tanggal</label>
                    <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 text-gray-700">
                        <option value="">Semua Status</option>
                        <option value="baru" <?= $status === 'baru' ? 'selected' : '' ?>>Baru</option>
                        <option value="proses" <?= $status === 'proses' ? 'selected' : '' ?>>Proses</option>
                        <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="ditolak" <?= $status === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Kategori</label>
                    <select name="kategori_id" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50 text-gray-700">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoriList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $kategori_id == $k['id'] ? 'selected' : '' ?>><?= sanitize($k['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary text-white flex-1 py-2 rounded-xl text-sm font-semibold flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-filter text-xs"></i> Terapkan
                    </button>
                    <a href="<?= BASE_URL ?>/laporan/index.php" class="border border-gray-200 text-gray-600 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 flex items-center justify-center">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Rekap Statistik Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 no-print">
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm text-center">
                <p class="text-xs text-gray-400 font-medium">Total Terfilter</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?= $totalLaporan ?></p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-blue-100 bg-blue-50/30 text-center">
                <p class="text-xs text-blue-600 font-medium">Baru</p>
                <p class="text-2xl font-bold text-blue-700 mt-1"><?= $totalBaru ?></p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-amber-100 bg-amber-50/30 text-center">
                <p class="text-xs text-amber-600 font-medium">Proses</p>
                <p class="text-2xl font-bold text-amber-700 mt-1"><?= $totalProses ?></p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-emerald-100 bg-emerald-50/30 text-center">
                <p class="text-xs text-emerald-600 font-medium">Selesai</p>
                <p class="text-2xl font-bold text-emerald-700 mt-1"><?= $totalSelesai ?></p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-red-100 bg-red-50/30 text-center">
                <p class="text-xs text-red-600 font-medium">Ditolak</p>
                <p class="text-2xl font-bold text-red-700 mt-1"><?= $totalDitolak ?></p>
            </div>
        </div>

        <!-- KOP SURAT UNTUK CETAK (PRINT ONLY) -->
        <div class="print-only mb-6 text-center border-b-2 border-black pb-4">
            <div class="flex items-center justify-center gap-4 mb-2">
                <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="w-16 h-16 object-contain">
                <div>
                    <h1 class="text-xl font-bold uppercase tracking-wider text-black">PERUMDA AIR MINUM PANCURAN TELAGO</h1>
                    <h2 class="text-lg font-bold uppercase text-black">KABUPATEN BUNGO</h2>
                    <p class="text-xs text-gray-600">Jl. RM. Thaher No. 45 Muara Bungo - Jambi 37214 | Telp: (0747) 21025</p>
                </div>
            </div>
            <h3 class="text-md font-bold underline mt-4 uppercase">LAPORAN REKAPITULASI PENGADUAN PELANGGAN</h3>
            <p class="text-xs text-gray-700">Periode: <?= formatTanggal($tgl_awal) ?> s/d <?= formatTanggal($tgl_akhir) ?></p>
        </div>

        <!-- Tabel Laporan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between no-print">
                <h3 class="font-semibold text-gray-800 text-sm">Data Rekap Pengaduan</h3>
                <span class="text-xs text-gray-400">Periode: <?= formatTanggal($tgl_awal) ?> s/d <?= formatTanggal($tgl_akhir) ?></span>
            </div>

            <?php if (empty($laporan)): ?>
            <div class="p-12 text-center">
                <i class="fa-solid fa-folder-open text-gray-300 text-3xl mb-3 block"></i>
                <p class="text-gray-500 font-medium">Tidak ada data pengaduan pada filter ini.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">No. Pengaduan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Pelanggan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Kategori</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Keluhan</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Tgl Masuk</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-600 uppercase">Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($laporan as $i => $row): ?>
                        <tr>
                            <td class="px-4 py-3 text-xs text-gray-500"><?= $i + 1 ?></td>
                            <td class="px-4 py-3 font-semibold text-sky-600 text-xs"><?= sanitize($row['no_pengaduan']) ?></td>
                            <td class="px-4 py-3 text-xs">
                                <p class="font-semibold text-gray-800"><?= sanitize($row['nama_pelanggan']) ?></p>
                                <p class="text-gray-400"><?= sanitize($row['no_pelanggan']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-700"><?= sanitize($row['nama_kategori']) ?></td>
                            <td class="px-4 py-3 text-xs text-gray-600 max-w-xs">
                                <p class="font-medium text-gray-800 line-clamp-1"><?= sanitize($row['judul']) ?></p>
                                <p class="text-gray-400 text-[11px] line-clamp-1"><?= sanitize($row['lokasi']) ?></p>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap"><?= date('d/m/Y', strtotime($row['tgl_pengaduan'])) ?></td>
                            <td class="px-4 py-3 text-xs"><?= statusBadge($row['status']) ?></td>
                            <td class="px-4 py-3 text-xs text-gray-600"><?= sanitize($row['nama_petugas'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tanda Tangan Cetak (Print Only) -->
            <div class="print-only mt-10 grid grid-cols-2 gap-8 text-center text-xs">
                <div>
                    <p>Mengetahui,</p>
                    <p class="font-bold">Direktur PERUMDA Air Minum Tirta Muaro</p>
                    <div class="h-20"></div>
                    <p class="font-bold underline">( .................................................. )</p>
                    <p>NIP. ..........................................</p>
                </div>
                <div>
                    <p>Muara Bungo, <?= formatTanggal(date('Y-m-d')) ?></p>
                    <p class="font-bold">Kepala Bagian Hubungan Langganan</p>
                    <div class="h-20"></div>
                    <p class="font-bold underline"><?= sanitize($_SESSION['user_name']) ?></p>
                    <p>NIP. ..........................................</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
