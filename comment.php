<?php
// comment.php – handler kirim komentar (langsung tampil, tanpa AJAX)

require_once 'includes/config.php';
require_once 'includes/functions.php';

/* ---------- tambahkan ini ---------- */
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}
/* ----------------------------------- */

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

// CSRF check
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Token tidak valid.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

// Ambil & bersihkan data
$article_id = (int)($_POST['article_id'] ?? 0);
$name       = trim($_POST['name']   ?? '');
$email      = trim($_POST['email']  ?? '');
$content    = trim($_POST['content'] ?? '');

// Validasi
if (!$article_id || $name === '' || $email === '' || $content === '') {
    set_flash('danger', 'Semua field wajib diisi.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Email tidak valid.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

// Simpan komentar (langsung approved)
$stmt = $pdo->prepare("INSERT INTO comments (article_id, name, email, content, status, created_at)
                       VALUES (?, ?, ?, ?, 'approved', NOW())");
$stmt->execute([$article_id, $name, $email, $content]);

set_flash('success', 'Komentar berhasil diposting.');
redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');