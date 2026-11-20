<?php $flash = $flash ?? getFlash(); ?>
<!-- Topbar -->
<header class="bg-white border-b border-gray-100 px-6 py-3.5 flex items-center justify-between shadow-sm sticky top-0 z-40">
    <div class="flex items-center gap-4">
        <!-- Toggle Sidebar (Mobile) -->
        <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700 p-1">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>

        <!-- Breadcrumb / Page Title -->
        <div>
            <h2 class="text-base font-bold text-gray-800"><?= $pageTitle ?? 'Dashboard' ?></h2>
            <p class="text-xs text-gray-400">
                Layanan Pengaduan &rsaquo; <?= $pageTitle ?? 'Dashboard' ?>
            </p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <!-- Tombol WhatsApp CS -->
        <a href="<?= WA_CS_URL ?>" target="_blank"
           class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-semibold transition-all hover:shadow-sm">
            <i class="fa-brands fa-whatsapp text-emerald-500 text-base"></i>
            <span>WA CS: <strong class="font-bold"><?= WA_CS_DISPLAY ?></strong></span>
        </a>

        <!-- Notifikasi -->
        <div class="relative">
            <button onclick="toggleNotif()" class="relative w-9 h-9 bg-gray-100 hover:bg-gray-200 rounded-xl flex items-center justify-center text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fa-solid fa-bell text-sm"></i>
                <?php
                $newPengaduan = $pdo->query("SELECT COUNT(*) FROM pengaduan WHERE status='baru'")->fetchColumn();
                if($newPengaduan > 0):
                ?>
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center"><?= min($newPengaduan, 9) ?></span>
                <?php endif; ?>
            </button>

            <!-- Dropdown Notifikasi -->
            <div id="notif-dropdown" class="hidden absolute right-0 top-11 w-80 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-sm text-gray-800">Notifikasi</h3>
                    <span class="text-xs text-sky-600 cursor-pointer hover:text-sky-800">Tandai semua dibaca</span>
                </div>
                <?php
                $notifs = $pdo->query("
                    SELECT p.no_pengaduan, p.judul, p.status, p.created_at, pl.nama
                    FROM pengaduan p
                    LEFT JOIN pelanggan pl ON p.pelanggan_id = pl.id
                    WHERE p.status = 'baru'
                    ORDER BY p.created_at DESC LIMIT 5
                ")->fetchAll();
                ?>
                <div class="max-h-64 overflow-y-auto custom-scrollbar">
                    <?php if(empty($notifs)): ?>
                    <div class="p-6 text-center text-sm text-gray-400">
                        <i class="fa-regular fa-bell-slash text-2xl mb-2 block"></i>
                        Tidak ada notifikasi baru
                    </div>
                    <?php else: ?>
                    <?php foreach($notifs as $n): ?>
                    <a href="<?= BASE_URL ?>/pengaduan/index.php" class="flex items-start gap-3 px-4 py-3 hover:bg-sky-50 transition-colors border-b border-gray-50 last:border-0">
                        <div class="w-8 h-8 bg-sky-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-bell text-sky-500 text-xs"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-700 truncate"><?= sanitize($n['judul']) ?></p>
                            <p class="text-xs text-gray-500">dari <?= sanitize($n['nama']) ?></p>
                            <p class="text-xs text-gray-400 mt-0.5"><?= formatTanggalWaktu($n['created_at']) ?></p>
                        </div>
                        <div class="w-2 h-2 bg-sky-500 rounded-full flex-shrink-0 mt-1.5"></div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="px-4 py-2.5 border-t border-gray-100 text-center">
                    <a href="<?= BASE_URL ?>/pengaduan/index.php" class="text-xs text-sky-600 font-medium hover:text-sky-800">Lihat semua pengaduan</a>
                </div>
            </div>
        </div>

        <!-- User Menu -->
        <div class="relative">
            <button onclick="toggleUserMenu()" class="flex items-center gap-2 hover:bg-gray-50 rounded-xl px-3 py-2 transition-colors">
                <div class="w-8 h-8 bg-gradient-to-br from-sky-500 to-sky-700 rounded-full flex items-center justify-center">
                    <span class="text-white text-xs font-bold"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></span>
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-sm font-semibold text-gray-700 leading-tight"><?= sanitize($_SESSION['user_name']) ?></p>
                    <p class="text-xs text-gray-400 capitalize leading-tight"><?= $_SESSION['user_role'] ?></p>
                </div>
                <i class="fa-solid fa-chevron-down text-xs text-gray-400 ml-1"></i>
            </button>

            <!-- Dropdown User -->
            <div id="user-menu" class="hidden absolute right-0 top-12 w-52 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50">
                <div class="p-4 border-b border-gray-100">
                    <p class="font-semibold text-sm text-gray-800"><?= sanitize($_SESSION['user_name']) ?></p>
                    <p class="text-xs text-gray-400 capitalize"><?= $_SESSION['user_role'] ?></p>
                </div>
                <div class="p-2">
                    <a href="<?= BASE_URL ?>/profil.php" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-user text-gray-400 w-4 text-center"></i>
                        Profil Saya
                    </a>
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-gear text-gray-400 w-4 text-center"></i>
                        Pengaturan
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="<?= BASE_URL ?>/logout.php" onclick="return confirm('Yakin ingin keluar?')"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <i class="fa-solid fa-right-from-bracket text-red-400 w-4 text-center"></i>
                        Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Flash Message -->
<?php if ($flash): ?>
<div class="flash-message mx-6 mt-4">
    <?php
    $flashColors = [
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
        'error'   => 'bg-red-50 border-red-200 text-red-700',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-700',
        'info'    => 'bg-sky-50 border-sky-200 text-sky-700',
    ];
    $flashIcons = [
        'success' => 'fa-circle-check',
        'error'   => 'fa-circle-xmark',
        'warning' => 'fa-triangle-exclamation',
        'info'    => 'fa-circle-info',
    ];
    $type = $flash['type'];
    $colorClass = $flashColors[$type] ?? $flashColors['info'];
    $iconClass  = $flashIcons[$type]  ?? $flashIcons['info'];
    ?>
    <div class="border rounded-xl px-4 py-3 flex items-center gap-3 <?= $colorClass ?>">
        <i class="fa-solid <?= $iconClass ?>"></i>
        <span class="text-sm font-medium flex-1"><?= sanitize($flash['message']) ?></span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>
</div>
<?php endif; ?>

<script>
function toggleNotif() {
    document.getElementById('notif-dropdown').classList.toggle('hidden');
    document.getElementById('user-menu').classList.add('hidden');
}
function toggleUserMenu() {
    document.getElementById('user-menu').classList.toggle('hidden');
    document.getElementById('notif-dropdown').classList.add('hidden');
}
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('-translate-x-full');
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('[onclick="toggleNotif()"]') && !e.target.closest('#notif-dropdown')) {
        document.getElementById('notif-dropdown')?.classList.add('hidden');
    }
    if (!e.target.closest('[onclick="toggleUserMenu()"]') && !e.target.closest('#user-menu')) {
        document.getElementById('user-menu')?.classList.add('hidden');
    }
});
</script>
