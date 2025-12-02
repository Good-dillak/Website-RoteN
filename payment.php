<?php
// payment.php – buat transaksi Midtrans lalu tampilkan Snap.js
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_login();

// NOTE PENTING: Ganti 'SB-Mid-server-XXXX' dan 'SB-Mid-client-XXXX' dengan kunci Anda.

// ---------- 1. Ambil & validasi input ----------
$article_id = isset($_POST['article_id']) ? (int)$_POST['article_id'] : 0;
if (!$article_id) {
    set_flash('danger', 'Artikel tidak dikenal.');
    redirect('index.php');
}

// ---------- 2. Pastikan artikel memang premium ----------
$stmt = $pdo->prepare("SELECT is_premium, slug FROM articles WHERE id = ? AND status IN ('published','premium')");
$stmt->execute([$article_id]);
$article = $stmt->fetch();
if (!$article || !$article['is_premium']) {
    set_flash('danger', 'Artikel ini tidak memerlukan pembayaran.');
    redirect('index.php');
}

// ---------- 3. Cek apakah user sudah pernah bayar (opsional) ----------
$stmt = $pdo->prepare("SELECT 1 FROM payments
                       WHERE user_id = ? AND article_id = ? AND status = 'success'
                       LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $article_id]);
if ($stmt->fetchColumn()) {
    set_flash('info', 'Anda sudah memiliki akses premium ke artikel ini.');
    redirect("article.php?slug=" . $article['slug']);
}

// ---------- 4. Buat order baru (Status Awal 'pending') ----------
$amount   = 10000;
$order_id = 'PREMIUM-' . time() . '-' . $_SESSION['user_id'];
$pdo->prepare("INSERT INTO payments (user_id, article_id, order_id, amount, status, created_at)
               VALUES (?, ?, ?, ?, 'pending', NOW())")
    ->execute([$_SESSION['user_id'], $article_id, $order_id, $amount]);

// ---------- 5. Konfigurasi Midtrans ----------
\Midtrans\Config::$serverKey        = 'SB-Mid-server-XXXX'; // <-- GANTI DENGAN SERVER KEY ANDA
\Midtrans\Config::$isProduction     = false;
\Midtrans\Config::$isSanitized      = true;
\Midtrans\Config::$is3ds            = true;

$transaction = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => $amount,
    ],
    'customer_details' => [
        'first_name' => $_SESSION['username'],
        'email'      => $_SESSION['email']   ?? 'user@example.com',
    ],
];

try {
    $snapToken = \Midtrans\Snap::getSnapToken($transaction);
} catch (Exception $e) {
    set_flash('danger', 'Gagal membuat token pembayaran: ' . $e->getMessage());
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

// ---------- 6. Tampilkan Snap ----------
$page_title = 'Pembayaran Premium';
include 'includes/header.php';
?>

<div class="container text-center py-5">
    <h2>Selesaikan Pembayaran</h2>
    <p>Anda akan membeli akses premium untuk artikel ini.</p>

    <div class="my-4">
        <button id="pay-button" class="btn btn-warning btn-lg">Bayar Sekarang (Rp <?= number_format($amount, 0, ',', '.') ?>)</button>
    </div>

    <p class="small text-muted">Anda akan diarahkan ke halaman Midtrans untuk menyelesaikan pembayaran.</p>
</div>

<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-XXXX"></script>
<script>
    const payButton = document.getElementById('pay-button');
    payButton.addEventListener('click', function () {
        snap.pay(<?= json_encode($snapToken) ?>, {
            onSuccess: function (result) {
                // PERBAIKAN: Redirect ke halaman status yang aman
                alert('Pembayaran sukses! Mohon tunggu konfirmasi server.');
                window.location = 'payment-status-check.php?order_id=<?= htmlspecialchars($order_id) ?>';
            },
            onPending: function (result) {
                // PERBAIKAN: Redirect ke halaman status yang aman
                alert('Menunggu pembayaran...');
                window.location = 'payment-status-check.php?order_id=<?= htmlspecialchars($order_id) ?>';
            },
            onError: function (result) {
                alert('Pembayaran gagal!');
                window.location.reload();
            },
            onClose: function () {
                alert('Anda menutup jendela pembayaran.');
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>