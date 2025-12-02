<?php
// admin/react.php – Handler like/dislike dari pengunjung

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php');
}

// CSRF check
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Token tidak valid.');
    redirect($_SERVER['HTTP_REFERER'] ?? '../index.php');
}

// Ambil data
$article_id = (int)($_POST['article_id'] ?? 0);
$reaction   = strtolower(trim($_POST['reaction'] ?? ''));
$ip         = $_SERVER['REMOTE_ADDR'] ?? '';
$user_id    = is_logged_in() ? $_SESSION['user_id'] : null;

// Validasi reaksi
if (!$article_id || !in_array($reaction, ['like', 'dislike'], true)) {
    set_flash('danger', 'Reaksi tidak valid.');
    redirect($_SERVER['HTTP_REFERER'] ?? '../index.php');
}

// Simpan reaksi (1x per IP)
insert_reaction($pdo, $article_id, $ip, $reaction, $user_id);

set_flash('success', 'Reaksi berhasil disimpan.');
redirect($_SERVER['HTTP_REFERER'] ?? '../index.php');