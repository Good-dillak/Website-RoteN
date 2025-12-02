<?php
// midtrans-notification.php - Endpoint untuk menerima notifikasi server-to-server dari Midtrans
require_once 'includes/config.php';
// Pastikan Anda memuat library Midtrans di sini. Misalnya:
// require_once 'vendor/autoload.php'; 

// ---------- 1. Konfigurasi Midtrans ----------
\Midtrans\Config::$serverKey        = 'SB-Mid-server-XXXX'; // <-- HARUS SAMA DENGAN payment.php
\Midtrans\Config::$isProduction     = false;

// ---------- 2. Ambil notifikasi dari Midtrans ----------
try {
    // Memverifikasi signature/tanda tangan dari notifikasi Midtrans
    $notification = new \Midtrans\Notification();
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    die($e->getMessage());
}

$transaction_status     = $notification->transaction_status;
$fraud_status           = $notification->fraud_status;
$order_id               = $notification->order_id;

// ---------- 3. Ambil data pembayaran dari database ----------
$stmt = $pdo->prepare("SELECT status FROM payments WHERE order_id = ?");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

if (!$payment) {
    http_response_code(404); // Not Found
    die("Order ID not found in database.");
}

$new_status = $payment['status'];

// ---------- 4. Proses Status Transaksi Midtrans ----------
if ($transaction_status == 'capture') {
    if ($fraud_status == 'accept') {
        $new_status = 'success'; // Kartu kredit: Ditangkap & diterima
    }
} elseif ($transaction_status == 'settlement') {
    $new_status = 'success'; // Pembayaran non-kartu kredit: Diselesaikan
} elseif ($transaction_status == 'pending') {
    $new_status = 'pending';
} elseif ($transaction_status == 'deny' || $transaction_status == 'expire' || $transaction_status == 'cancel') {
    $new_status = 'failed';
}

// ---------- 5. Update Status di Database ----------
// Hanya update jika status berubah
if ($new_status != $payment['status']) {
    $stmt = $pdo->prepare("UPDATE payments SET status = ?, transaction_time = NOW() WHERE order_id = ?");
    $stmt->execute([$new_status, $order_id]);
}

// Beri respons HTTP 200 ke Midtrans (wajib!)
http_response_code(200);
?>