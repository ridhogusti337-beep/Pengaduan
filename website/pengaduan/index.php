<?php
require_once '../config.php';
requireLogin();

// Filter & Search
$status    = $_GET['status'] ?? '';
$prioritas = $_GET['prioritas'] ?? '';
$search    = $_GET['search'] ?? '';
$page      = max(1, intval($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

// Build query
$where  = [];
$params = [];

if ($status) {
    $where[]  = "p.status = ?";
    $params[] = $status;
}
if ($prioritas) {
    $where[]  = "p.prioritas = ?";
    $params[] = $prioritas;
}
if ($search) {
    $where[]  = "(p.no_pengaduan LIKE ? OR p.judul LIKE ? OR pl.nama LIKE ? OR pl.no_pelanggan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($_SESSION['user_role'] === 'petugas') {
    $where[]  = "(p.petugas_id = ? OR p.petugas_id IS NULL)";
    $params[] = $_SESSION['user_id'];
}
if ($_SESSION['user_role'] === 'pelanggan') {
    $where[]  = "p.pelanggan_id = ?";
    $params[] = $_SESSION['pelanggan_id'] ?? 1;
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countSql = "SELECT COUNT(*) FROM pengaduan p LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id $whereStr";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch data
$sql = "
    SELECT p.*, pl.nama as nama_pelanggan, pl.no_pelanggan, k.nama_kategori, u.nama as nama_petugas
    FROM pengaduan p
    LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id
    LEFT JOIN kategori_pengaduan k ON p.kategori_id = k.id
    LEFT JOIN users u ON p.petugas_id = u.id
    $whereStr
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pengaduan = $stmt->fetchAll();

// Stat counts untuk tab
if ($_SESSION['user_role'] === 'pelanggan') {
    $stmtStat = $pdo->prepare("SELECT status, COUNT(*) as n FROM pengaduan WHERE pelanggan_id = ? GROUP BY status");
    $stmtStat->execute([$_SESSION['pelanggan_id'] ?? 1]);
    $statCounts = $stmtStat->fetchAll(PDO::FETCH_KEY_PAIR);
} else {
    $statCounts = $pdo->query("SELECT status, COUNT(*) as n FROM pengaduan GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
}

$pageTitle = 'Daftar Pengaduan';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6 space-y-5">
        <!-- Header + Tambah -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Daftar Pengaduan</h2>
                <p class="text-sm text-gray-500">Kelola semua pengaduan pelanggan PDAM Bungo</p>
            </div>
            <?php if($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'pelanggan'): ?>
            <a href="<?= BASE_URL ?>/pengaduan/tambah.php"
               class="btn-primary text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 w-fit">
                <i class="fa-solid fa-plus"></i>
                Tambah Pengaduan
            </a>
            <?php endif; ?>
        </div>

        <!-- Kartu Status Tab -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <?php
            $tabs = [
                ['label' => 'Semua', 'value' => '', 'icon' => 'fa-list', 'color' => 'sky', 'count' => array_sum($statCounts)],
                ['label' => 'Baru', 'value' => 'baru', 'icon' => 'fa-bell', 'color' => 'blue', 'count' => $statCounts['baru'] ?? 0],
                ['label' => 'Proses', 'value' => 'proses', 'icon' => 'fa-clock', 'color' => 'amber', 'count' => $statCounts['proses'] ?? 0],
                ['label' => 'Selesai', 'value' => 'selesai', 'icon' => 'fa-check-circle', 'color' => 'emerald', 'count' => $statCounts['selesai'] ?? 0],
            ];
            foreach($tabs as $tab):
            $isActive = ($status === $tab['value'] || ($tab['value'] === '' && !$status));
            ?>
            <a href="?status=<?= $tab['value'] ?>"
               class="bg-white rounded-xl p-4 border-2 <?= $isActive ? 'border-'.$tab['color'].'-400 shadow-md' : 'border-gray-100 hover:border-gray-200' ?> transition-all">
                <div class="flex items-center justify-between">
                    <i class="fa-solid <?= $tab['icon'] ?> text-<?= $tab['color'] ?>-500"></i>
                    <span class="text-lg font-bold text-gray-800"><?= $tab['count'] ?></span>
                </div>
                <p class="text-xs font-medium text-gray-500 mt-2"><?= $tab['label'] ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="status" value="<?= sanitize($status) ?>">

                <!-- Search -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input type="text" name="search" value="<?= sanitize($search) ?>"
                           placeholder="Cari no. pengaduan, nama pelanggan..."
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100 bg-gray-50">
                </div>

                <!-- Filter Prioritas -->
                <select name="prioritas" class="border border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:outline-none focus:border-sky-400 bg-gray-50 text-gray-600">
                    <option value="">Semua Prioritas</option>
                    <option value="tinggi" <?= $prioritas === 'tinggi' ? 'selected' : '' ?>>Prioritas Tinggi</option>
                    <option value="sedang" <?= $prioritas === 'sedang' ? 'selected' : '' ?>>Prioritas Sedang</option>
                    <option value="rendah" <?= $prioritas === 'rendah' ? 'selected' : '' ?>>Prioritas Rendah</option>
                </select>

                <button type="submit" class="btn-primary text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-filter text-xs"></i>
                    Filter
                </button>
                <?php if($search || $prioritas): ?>
                <a href="?status=<?= sanitize($status) ?>" class="border border-gray-200 text-gray-600 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                    <i class="fa-solid fa-xmark text-xs"></i>
                    Reset
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="font-semibold text-gray-800 text-sm">Data Pengaduan</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Menampilkan <?= count($pengaduan) ?> dari <?= $total ?> pengaduan</p>
                </div>
            </div>

            <?php if(empty($pengaduan)): ?>
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-clipboard-list text-gray-400 text-2xl"></i>
                </div>
                <p class="text-gray-500 font-medium">Tidak ada data pengaduan</p>
                <p class="text-gray-400 text-sm mt-1">Coba ubah filter atau tambahkan pengaduan baru</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">No</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">No. Pengaduan</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pelanggan</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Judul / Kategori</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Prioritas</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Petugas</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Tanggal</th>
                            <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($pengaduan as $i => $p): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-4 text-xs text-gray-400 font-medium"><?= $offset + $i + 1 ?></td>
                            <td class="px-5 py-4">
                                <span class="font-semibold text-sky-600 text-xs"><?= sanitize($p['no_pengaduan']) ?></span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-800 text-sm"><?= sanitize($p['nama_pelanggan']) ?></p>
                                <p class="text-xs text-gray-400"><?= sanitize($p['no_pelanggan']) ?></p>
                            </td>
                            <td class="px-5 py-4 max-w-xs">
                                <p class="font-medium text-gray-800 line-clamp-1 text-sm"><?= sanitize($p['judul']) ?></p>
                                <p class="text-xs text-gray-400 mt-0.5"><?= sanitize($p['nama_kategori']) ?></p>
                            </td>
                            <td class="px-5 py-4"><?= prioritasBadge($p['prioritas']) ?></td>
                            <td class="px-5 py-4"><?= statusBadge($p['status']) ?></td>
                            <td class="px-5 py-4 text-xs text-gray-500">
                                <?= $p['nama_petugas'] ? sanitize($p['nama_petugas']) : '<span class="text-gray-300 italic">Belum ditugaskan</span>' ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-500 whitespace-nowrap">
                                <?= formatTanggal($p['tgl_pengaduan']) ?>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= $p['id'] ?>"
                                       class="w-7 h-7 bg-sky-50 hover:bg-sky-100 text-sky-600 rounded-lg flex items-center justify-center transition-colors" title="Detail">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>
                                    <?php if($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="<?= BASE_URL ?>/pengaduan/edit.php?id=<?= $p['id'] ?>"
                                       class="w-7 h-7 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                    <button onclick="hapusPengaduan(<?= $p['id'] ?>, '<?= sanitize($p['no_pengaduan']) ?>')"
                                            class="w-7 h-7 bg-red-50 hover:bg-red-100 text-red-500 rounded-lg flex items-center justify-center transition-colors" title="Hapus">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    Halaman <?= $page ?> dari <?= $totalPages ?>
                </p>
                <div class="flex items-center gap-1">
                    <?php if($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                       class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center text-gray-600 transition-colors">
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </a>
                    <?php endif; ?>
                    <?php for($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="w-8 h-8 <?= $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-600' ?> rounded-lg flex items-center justify-center text-xs font-medium transition-colors">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    <?php if($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                       class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-lg flex items-center justify-center text-gray-600 transition-colors">
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="modal-hapus" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
        <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-trash text-red-500 text-2xl"></i>
        </div>
        <h3 class="text-center font-bold text-gray-800 text-lg">Hapus Pengaduan?</h3>
        <p class="text-center text-gray-500 text-sm mt-2">
            Pengaduan <strong id="modal-no-pengaduan"></strong> akan dihapus secara permanen.
        </p>
        <form id="form-hapus" method="POST" action="<?= BASE_URL ?>/pengaduan/hapus.php" class="flex gap-3 mt-6">
            <input type="hidden" name="id" id="hapus-id">
            <button type="button" onclick="tutupModal()" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                Batal
            </button>
            <button type="submit" class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-semibold transition-colors">
                Ya, Hapus
            </button>
        </form>
    </div>
</div>

<script>
function hapusPengaduan(id, no) {
    document.getElementById('hapus-id').value = id;
    document.getElementById('modal-no-pengaduan').textContent = no;
    document.getElementById('modal-hapus').classList.remove('hidden');
}
function tutupModal() {
    document.getElementById('modal-hapus').classList.add('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
