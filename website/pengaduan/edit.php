<?php
require_once '../config.php';
requireLogin();
requireRole(['admin','petugas']);

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/pengaduan/index.php');

// Ambil data pengaduan
$stmt = $pdo->prepare("SELECT * FROM pengaduan WHERE id = ?");
$stmt->execute([$id]);
$pengaduan = $stmt->fetch();
if (!$pengaduan) redirect(BASE_URL . '/pengaduan/index.php');

// Data master
$pelanggan = $pdo->query("SELECT * FROM pelanggan ORDER BY nama ASC")->fetchAll();
$kategori  = $pdo->query("SELECT * FROM kategori_pengaduan ORDER BY nama_kategori ASC")->fetchAll();
$petugas   = $pdo->query("SELECT * FROM users WHERE role != 'pelanggan' AND is_active = 1 ORDER BY nama ASC")->fetchAll();

$errors = [];
$old    = $pengaduan;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pelanggan_id = intval($_POST['pelanggan_id'] ?? 0);
    $kategori_id  = intval($_POST['kategori_id']  ?? 0);
    $judul        = trim($_POST['judul'] ?? '');
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $lokasi       = trim($_POST['lokasi'] ?? '');
    $prioritas    = $_POST['prioritas'] ?? 'sedang';
    $petugas_id   = !empty($_POST['petugas_id']) ? intval($_POST['petugas_id']) : null;

    // Validasi
    if (!$pelanggan_id) $errors[] = 'Pelanggan wajib dipilih.';
    if (!$kategori_id)  $errors[] = 'Kategori wajib dipilih.';
    if (empty($judul))  $errors[] = 'Judul pengaduan wajib diisi.';
    if (empty($deskripsi)) $errors[] = 'Deskripsi wajib diisi.';

    // Upload foto (opsional)
    $foto = $pengaduan['foto_pengaduan'];
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $maxSize = 5 * 1024 * 1024;
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Format foto tidak diizinkan.';
        } elseif ($_FILES['foto']['size'] > $maxSize) {
            $errors[] = 'Ukuran foto maksimal 5MB.';
        } else {
            $foto = 'uploads/pengaduan/' . time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], '../' . $foto);
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE pengaduan SET pelanggan_id=?, kategori_id=?, judul=?, deskripsi=?, lokasi=?, prioritas=?, petugas_id=?, foto_pengaduan=? WHERE id=?");
        $stmt->execute([$pelanggan_id,$kategori_id,$judul,$deskripsi,$lokasi,$prioritas,$petugas_id,$foto,$id]);
        flashMessage('success','Pengaduan berhasil diperbarui.');
        redirect(BASE_URL.'/pengaduan/detail.php?id='.$id);
    }
    $old = $_POST;
}

$pageTitle = 'Edit Pengaduan';
include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="flex-1 min-w-0 overflow-auto">
    <?php include '../includes/topbar.php'; ?>
    <main class="p-6">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center gap-4 mb-6">
                <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= $id ?>" class="w-9 h-9 bg-white hover:bg-gray-50 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 transition-colors">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Edit Pengaduan</h2>
                    <p class="text-sm text-gray-500">Perbarui data pengaduan</p>
                </div>
            </div>
            <?php if(!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5">
                <ul class="list-disc list-inside space-y-1 text-sm text-red-600">
                    <?php foreach($errors as $e): ?>
                    <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-sky-500 to-sky-600 px-6 py-4">
                    <h3 class="text-white font-semibold flex items-center gap-2"><i class="fa-solid fa-pen"></i> Formulir Edit Pengaduan</h3>
                </div>
                <form method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Pelanggan <span class="text-red-500">*</span></label>
                            <select name="pelanggan_id" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50">
                                <option value="">-- Pilih Pelanggan --</option>
                                <?php foreach($pelanggan as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($old['pelanggan_id'] ?? $pengaduan['pelanggan_id']) == $p['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($p['no_pelanggan']) ?> - <?= sanitize($p['nama']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kategori <span class="text-red-500">*</span></label>
                            <select name="kategori_id" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach($kategori as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= ($old['kategori_id'] ?? $pengaduan['kategori_id']) == $k['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($k['nama_kategori']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Judul <span class="text-red-500">*</span></label>
                        <input type="text" name="judul" value="<?= sanitize($old['judul'] ?? $pengaduan['judul']) ?>" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Deskripsi <span class="text-red-500">*</span></label>
                        <textarea name="deskripsi" rows="4" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50 resize-none"><?= sanitize($old['deskripsi'] ?? $pengaduan['deskripsi']) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Lokasi Kejadian</label>
                        <input type="text" name="lokasi" value="<?= sanitize($old['lokasi'] ?? $pengaduan['lokasi']) ?>" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Prioritas</label>
                            <select name="prioritas" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50">
                                <option value="rendah" <?= ($old['prioritas'] ?? $pengaduan['prioritas']) === 'rendah' ? 'selected' : '' ?>>Rendah</option>
                                <option value="sedang" <?= ($old['prioritas'] ?? $pengaduan['prioritas']) === 'sedang' ? 'selected' : '' ?>>Sedang</option>
                                <option value="tinggi" <?= ($old['prioritas'] ?? $pengaduan['prioritas']) === 'tinggi' ? 'selected' : '' ?>>Tinggi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Petugas</label>
                            <select name="petugas_id" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-gray-50">
                                <option value="">-- Belum Ditugaskan --</option>
                                <?php foreach($petugas as $pt): ?>
                                <option value="<?= $pt['id'] ?>" <?= ($old['petugas_id'] ?? $pengaduan['petugas_id']) == $pt['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($pt['nama']) ?> (<?= ucfirst($pt['role']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Pengaduan (opsional)</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-5 text-center cursor-pointer" onclick="document.getElementById('foto-input').click()">
                            <i class="fa-solid fa-cloud-arrow-up text-gray-400 text-2xl mb-2 block"></i>
                            <p class="text-sm text-gray-500">Klik untuk upload foto baru</p>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF (maks 5MB)</p>
                            <input type="file" id="foto-input" name="foto" accept="image/*" class="hidden" onchange="previewFoto(this)">
                        </div>
                        <?php if($pengaduan['foto_pengaduan']): ?>
                        <div class="mt-3 text-center">
                            <p class="text-xs text-gray-500 mb-1">Foto saat ini:</p>
                            <img src="<?= BASE_URL ?>/<?= $pengaduan['foto_pengaduan'] ?>" alt="Foto" class="max-h-48 mx-auto rounded-xl border border-gray-200">
                        </div>
                        <?php endif; ?>
                        <div id="foto-preview" class="hidden mt-3">
                            <img id="foto-img" src="" class="max-h-48 mx-auto rounded-xl border border-gray-200">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="<?= BASE_URL ?>/pengaduan/detail.php?id=<?= $id ?>" class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="btn-primary text-white px-6 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2"><i class="fa-solid fa-save"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
function previewFoto(input){
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById('foto-img').src = e.target.result;
            document.getElementById('foto-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<?php include '../includes/footer.php'; ?>
