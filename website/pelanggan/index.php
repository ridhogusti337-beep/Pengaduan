<?php
require_once '../config.php';
requireLogin();

$search = trim($_GET['search'] ?? '');
$golongan = $_GET['golongan'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search) {
    $where[] = "(no_pelanggan LIKE ? OR nama LIKE ? OR no_telepon LIKE ? OR alamat LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($golongan) {
    $where[] = "golongan = ?";
    $params[] = $golongan;
}

$whereStr = $where ? "WHERE " . implode(" AND ", $where) : "";

// Hitung total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pelanggan $whereStr");
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch data
$sql = "SELECT * FROM pelanggan $whereStr ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pelanggan = $stmt->fetchAll();

$pageTitle = 'Data Pelanggan';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Data Pelanggan</h2>
                <p class="text-sm text-gray-500">Kelola informasi pelanggan PDAM Kabupaten Bungo</p>
            </div>
            <a href="<?= BASE_URL ?>/pelanggan/tambah.php" class="btn-primary text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 w-fit">
                <i class="fa-solid fa-user-plus"></i> Tambah Pelanggan
            </a>
        </div>

        <!-- Filter bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama, no pelanggan, no HP, alamat..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-sky-400 bg-gray-50">
                </div>
                <select name="golongan" class="border border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:outline-none focus:border-sky-400 bg-gray-50 text-gray-600">
                    <option value="">Semua Golongan</option>
                    <?php foreach(['R1','R2','R3','S1','S2','B1','B2','I1'] as $g): ?>
                    <option value="<?= $g ?>" <?= $golongan === $g ? 'selected' : '' ?>>Golongan <?= $g ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-primary text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-filter text-xs"></i> Filter
                </button>
                <?php if ($search || $golongan): ?>
                <a href="<?= BASE_URL ?>/pelanggan/index.php" class="border border-gray-200 text-gray-600 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                    <i class="fa-solid fa-xmark text-xs"></i> Reset
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Pelanggan -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Daftar Pelanggan Terdaftar</h3>
                <span class="text-xs text-gray-400">Total: <?= $total ?> pelanggan</span>
            </div>

            <?php if (empty($pelanggan)): ?>
            <div class="p-12 text-center">
                <i class="fa-solid fa-users-slash text-gray-300 text-3xl mb-3 block"></i>
                <p class="text-gray-500 font-medium">Belum ada data pelanggan.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">No</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">No. Pelanggan</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama Pelanggan</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Golongan</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Alamat / Wilayah</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Kontak</th>
                            <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($pelanggan as $i => $row): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-4 text-xs text-gray-400"><?= $offset + $i + 1 ?></td>
                            <td class="px-5 py-4 font-semibold text-sky-600 text-xs"><?= sanitize($row['no_pelanggan']) ?></td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-800 text-sm"><?= sanitize($row['nama']) ?></p>
                                <p class="text-xs text-gray-400"><?= sanitize($row['email'] ?? '-') ?></p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="bg-sky-50 text-sky-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-sky-100">Gol. <?= sanitize($row['golongan']) ?></span>
                            </td>
                            <td class="px-5 py-4 max-w-xs text-xs text-gray-600">
                                <p class="font-medium text-gray-700 line-clamp-1"><?= sanitize($row['alamat']) ?></p>
                                <p class="text-gray-400"><?= sanitize($row['kelurahan']) ?>, Kec. <?= sanitize($row['kecamatan']) ?></p>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-600">
                                <i class="fa-solid fa-phone text-gray-400 mr-1"></i><?= sanitize($row['no_telepon'] ?? '-') ?>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="<?= BASE_URL ?>/pelanggan/edit.php?id=<?= $row['id'] ?>" class="w-7 h-7 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                    <button onclick="hapusPelanggan(<?= $row['id'] ?>, '<?= sanitize($row['nama']) ?>')" class="w-7 h-7 bg-red-50 hover:bg-red-100 text-red-500 rounded-lg flex items-center justify-center transition-colors" title="Hapus">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-500">Halaman <?= $page ?> dari <?= $totalPages ?></p>
                <div class="flex items-center gap-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="w-8 h-8 <?= $i === $page ? 'bg-sky-500 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-600' ?> rounded-lg flex items-center justify-center text-xs font-medium transition-colors">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Hapus -->
<div id="modal-hapus" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-red-500 text-2xl">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="font-bold text-gray-800 text-lg">Hapus Pelanggan?</h3>
        <p class="text-gray-500 text-sm mt-2">Pelanggan <strong id="hapus-nama"></strong> beserta semua riwayat pengaduannya akan dihapus permanen.</p>
        <form method="POST" action="<?= BASE_URL ?>/pelanggan/hapus.php" class="flex gap-3 mt-6">
            <input type="hidden" name="id" id="hapus-id">
            <button type="button" onclick="document.getElementById('modal-hapus').classList.add('hidden')" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
            <button type="submit" class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-semibold">Ya, Hapus</button>
        </form>
    </div>
</div>

<script>
function hapusPelanggan(id, nama) {
    document.getElementById('hapus-id').value = id;
    document.getElementById('hapus-nama').textContent = nama;
    document.getElementById('modal-hapus').classList.remove('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
