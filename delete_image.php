<?php
// admin/articles/delete_image.php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_admin();

$image_id = (int)($_GET['image_id'] ?? 0);

if (!$image_id) {
    set_flash('danger', 'ID gambar tidak valid.');
    // Kembali ke halaman sebelumnya jika ID tidak valid
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

$pdo->beginTransaction();
try {
    // 1. Ambil data gambar untuk mendapatkan path file dan article_id
    $stmt = $pdo->prepare("SELECT article_id, image_path FROM article_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$image_data) {
        throw new Exception('Gambar tidak ditemukan.');
    }

    $article_id = $image_data['article_id'];
    $image_path = $image_data['image_path'];

    // 2. Hapus file dari server menggunakan fungsi delete_old_file()
    delete_old_file($image_path);

    // 3. Hapus record dari database
    $del = $pdo->prepare("DELETE FROM article_images WHERE id = ?");
    if (!$del->execute([$image_id])) {
        throw new Exception('Gagal menghapus record gambar dari database.');
    }

    $pdo->commit();
    set_flash('success', 'Gambar konten berhasil dihapus.');

    // Redirect kembali ke halaman edit artikel
    redirect('edit.php?id=' . $article_id);

} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('danger', 'Gagal menghapus gambar: ' . $e->getMessage());
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}