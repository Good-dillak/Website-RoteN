<?php
// comment.php – handler kirim komentar (root folder)
require_once 'includes/config.php';
require_once 'includes/functions.php';

/* Redirect helper (jika belum ada) */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

/* Pastikan method POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

/* CSRF check */
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Token tidak valid.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

/* Ambil & validasi data */
$article_id = (int)($_POST['article_id'] ?? 0);
$name       = trim($_POST['name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$content    = trim($_POST['content'] ?? '');

if (!$article_id || $name === '' || $email === '' || $content === '') {
    set_flash('danger', 'Semua field wajib diisi.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Email tidak valid.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

/* Simpan ke DB (status default = pending) */
insert_comment($pdo, $article_id, $name, $email, $content);
set_flash('success', 'Komentar terkirim & menunggu moderasi.');
redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
?>