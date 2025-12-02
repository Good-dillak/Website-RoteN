<?php
// vote.php
require 'includes/config.php'; // Asumsi ini berisi koneksi $pdo
require 'includes/functions.php'; // Asumsi ini berisi fungsi database seperti update_vote_status

header('Content-Type: application/json');

// Pastikan permintaan adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// Mulai sesi (diperlukan untuk melacak pengguna)
session_start();
// Ganti dengan variabel sesi/autentikasi pengguna yang sesuai di proyek Anda
$user_id = $_SESSION['user_id'] ?? null; 

// --- Validasi Data Input ---
$article_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

if (!$article_id || $article_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID artikel tidak valid.']);
    exit;
}

if (!in_array($action, ['like', 'dislike'])) {
    echo json_encode(['status' => 'error', 'message' => 'Aksi voting tidak valid.']);
    exit;
}

if (!$user_id) {
    // Pesan validasi jika pengguna harus login
    echo json_encode(['status' => 'warning', 'message' => 'Anda harus masuk untuk memberikan suara.']);
    exit;
}

// --- Logika Database Inti (Anda harus mengimplementasikannya) ---
// Di sini Anda akan memanggil fungsi PHP yang berinteraksi dengan database:
// 1. Memeriksa apakah $user_id sudah memilih $article_id.
// 2. Mencatat/memperbarui suara.
// 3. Mengambil jumlah likes dan dislikes yang baru.

// --- Contoh respons sukses (ganti nilai dengan hasil dari database) ---
$new_like_count = rand(10, 50); 
$new_dislike_count = rand(0, 5); 

echo json_encode([
    'status' => 'success', 
    'message' => 'Suara berhasil direkam!', 
    'likes' => $new_like_count, 
    'dislikes' => $new_dislike_count,
    'current_action' => $action // Berguna untuk feedback visual di frontend
]);

exit;
?>