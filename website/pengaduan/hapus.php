<?php
require_once '../config.php';
requireLogin();
requireRole(['admin','petugas']);

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    flashMessage('error','ID pengaduan tidak valid.');
    redirect(BASE_URL . '/pengaduan/index.php');
}

// Delete record (and optionally delete foto file)
$stmt = $pdo->prepare('SELECT foto_pengaduan FROM pengaduan WHERE id = ?');
$stmt->execute([$id]);
$foto = $stmt->fetchColumn();

$pdo->prepare('DELETE FROM pengaduan WHERE id = ?')->execute([$id]);

if ($foto && file_exists('../' . $foto)) {
    @unlink('../' . $foto);
}

flashMessage('success','Pengaduan berhasil dihapus.');
redirect(BASE_URL . '/pengaduan/index.php');
?>