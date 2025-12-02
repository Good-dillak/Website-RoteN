<?php
// subscribe.php
require 'includes/config.php';
require 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    echo json_encode(['status' => 'error', 'message' => 'Email tidak valid']);
    exit;
}

// Coba masukkan, gunakan INSERT IGNORE agar tidak error jika duplikat
$stmt = $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
$success = $stmt->execute([$email]);

if ($success && $stmt->rowCount() > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Berlangganan berhasil!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar atau gagal menyimpan']);
}