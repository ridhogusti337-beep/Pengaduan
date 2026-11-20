<?php
require_once '../config.php';
requireLogin();
requireRole('admin');

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    flashMessage('error', 'ID pengguna tidak valid.');
    redirect(BASE_URL . '/users/index.php');
}

// Jangan biarkan admin menghapus dirinya sendiri
if ($id == $_SESSION['user_id']) {
    flashMessage('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
    redirect(BASE_URL . '/users/index.php');
}

$stmt = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
$stmt->execute([$id]);
$nama = $stmt->fetchColumn();

$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

flashMessage('success', "Akun pengguna $nama berhasil dihapus.");
redirect(BASE_URL . '/users/index.php');
