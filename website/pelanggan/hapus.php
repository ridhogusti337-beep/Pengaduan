<?php
require_once '../config.php';
requireLogin();

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    flashMessage('error', 'ID Pelanggan tidak valid.');
    redirect(BASE_URL . '/pelanggan/index.php');
}

$stmt = $pdo->prepare("SELECT nama FROM pelanggan WHERE id = ?");
$stmt->execute([$id]);
$nama = $stmt->fetchColumn();

$pdo->prepare("DELETE FROM pelanggan WHERE id = ?")->execute([$id]);

flashMessage('success', "Pelanggan $nama berhasil dihapus beserta seluruh riwayat pengaduannya.");
redirect(BASE_URL . '/pelanggan/index.php');
