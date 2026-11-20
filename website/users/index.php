<?php
require_once '../config.php';
requireLogin();
requireRole('admin');

$search = trim($_GET['search'] ?? '');
$role   = $_GET['role'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(nama LIKE ? OR username LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}

$whereStr = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT * FROM users $whereStr ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manajemen Pengguna';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>

    <main class="p-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Manajemen Pengguna</h2>
                <p class="text-sm text-gray-500">Kelola akun admin dan petugas pengaduan PDAM Bungo</p>
            </div>
            <a href="<?= BASE_URL ?>/users/tambah.php" class="btn-primary text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 w-fit">
                <i class="fa-solid fa-user-shield"></i> Tambah User Baru
            </a>
        </div>

        <!-- Filter bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Cari nama, username, email..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-sky-400 bg-gray-50">
                </div>
                <select name="role" class="border border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:outline-none focus:border-sky-400 bg-gray-50 text-gray-600">
                    <option value="">Semua Role</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    <option value="petugas" <?= $role === 'petugas' ? 'selected' : '' ?>>Petugas Lapangan</option>
                </select>
                <button type="submit" class="btn-primary text-white px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-filter text-xs"></i> Filter
                </button>
                <?php if ($search || $role): ?>
                <a href="<?= BASE_URL ?>/users/index.php" class="border border-gray-200 text-gray-600 px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 flex items-center gap-2">
                    <i class="fa-solid fa-xmark text-xs"></i> Reset
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Pengguna -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">Daftar Akun Pengguna</h3>
                <span class="text-xs text-gray-400">Total: <?= count($users) ?> user</span>
            </div>

            <?php if (empty($users)): ?>
            <div class="p-12 text-center">
                <i class="fa-solid fa-users-gear text-gray-300 text-3xl mb-3 block"></i>
                <p class="text-gray-500 font-medium">Tidak ada data user ditemukan.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Pengguna</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Username</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Email</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Peran</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="text-left px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Dibuat Pada</th>
                            <th class="text-center px-5 py-3.5 text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-white text-xs <?= $u['role'] === 'admin' ? 'bg-gradient-to-tr from-sky-600 to-sky-400' : 'bg-gradient-to-tr from-emerald-600 to-teal-400' ?>">
                                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 text-sm"><?= sanitize($u['nama']) ?></p>
                                        <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="text-[10px] bg-sky-100 text-sky-700 font-medium px-2 py-0.5 rounded-full">Anda</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs font-mono text-gray-600">@<?= sanitize($u['username']) ?></td>
                            <td class="px-5 py-4 text-xs text-gray-600"><?= sanitize($u['email']) ?></td>
                            <td class="px-5 py-4">
                                <?php if ($u['role'] === 'admin'): ?>
                                <span class="bg-purple-50 text-purple-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-purple-100">
                                    <i class="fa-solid fa-shield-halved mr-1"></i> Admin
                                </span>
                                <?php else: ?>
                                <span class="bg-teal-50 text-teal-700 text-xs font-semibold px-2.5 py-1 rounded-full border border-teal-100">
                                    <i class="fa-solid fa-hard-hat mr-1"></i> Petugas
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4">
                                <?php if ($u['is_active']): ?>
                                <span class="inline-flex items-center gap-1.5 text-xs text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full font-medium">
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span> Aktif
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full font-medium">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span> Nonaktif
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-400"><?= formatTanggal($u['created_at']) ?></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="<?= BASE_URL ?>/users/edit.php?id=<?= $u['id'] ?>" class="w-7 h-7 bg-amber-50 hover:bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center transition-colors" title="Edit">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="hapusUser(<?= $u['id'] ?>, '<?= sanitize($u['nama']) ?>')" class="w-7 h-7 bg-red-50 hover:bg-red-100 text-red-500 rounded-lg flex items-center justify-center transition-colors" title="Hapus">
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
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Modal Hapus User -->
<div id="modal-hapus" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-red-500 text-2xl">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="font-bold text-gray-800 text-lg">Hapus Pengguna?</h3>
        <p class="text-gray-500 text-sm mt-2">Akun user <strong id="hapus-nama"></strong> akan dihapus secara permanen dari sistem.</p>
        <form method="POST" action="<?= BASE_URL ?>/users/hapus.php" class="flex gap-3 mt-6">
            <input type="hidden" name="id" id="hapus-id">
            <button type="button" onclick="document.getElementById('modal-hapus').classList.add('hidden')" class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</button>
            <button type="submit" class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl text-sm font-semibold">Ya, Hapus</button>
        </form>
    </div>
</div>

<script>
function hapusUser(id, nama) {
    document.getElementById('hapus-id').value = id;
    document.getElementById('hapus-nama').textContent = nama;
    document.getElementById('modal-hapus').classList.remove('hidden');
}
</script>

<?php include '../includes/footer.php'; ?>
