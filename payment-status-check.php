<?php
// payment-status-check.php - Halaman status sementara setelah kembali dari Snap
require 'includes/config.php';
require 'includes/auth.php';
require_login();

$order_id = $_GET['order_id'] ?? '';

// Ambil status saat ini dan article_id dari database
$stmt = $pdo->prepare("SELECT status, article_id FROM payments WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$payment = $stmt->fetch();

$page_title = "Status Pembayaran";
include 'includes/header.php';
?>

<div class="container mt-5 text-center">
    <?php if ($payment && $payment['status'] == 'success'): ?>
        <div class="alert alert-success">
            <h4>✅ Pembayaran Berhasil Dikonfirmasi!</h4>
            <p>Akses premium artikel telah dibuka.</p>
            <?php 
            // Cari slug artikel untuk redirection
            $stmt_article = $pdo->prepare("SELECT slug FROM articles WHERE id = ?");
            $stmt_article->execute([$payment['article_id']]);
            $slug = $stmt_article->fetchColumn();
            ?>
            <a href="<?= BASE_URL ?>article.php?slug=<?= $slug ?>" class="btn btn-primary">Lihat Artikel Premium</a>
        </div>
    <?php elseif ($payment && $payment['status'] == 'pending'): ?>
        <div class="alert alert-warning">
            <h4>⏳ Menunggu Pembayaran</h4>
            <p>Pembayaran Anda sedang dalam proses. Kami akan membuka akses premium setelah Midtrans mengirimkan konfirmasi berhasil.</p>
            <p class="small">Order ID: **<?= htmlspecialchars($order_id) ?>**</p>
            <a href="<?= BASE_URL ?>" class="btn btn-secondary mt-3">Kembali ke Beranda</a>
        </div>
    <?php elseif ($payment && $payment['status'] == 'failed'): ?>
        <div class="alert alert-danger">
            <h4>❌ Pembayaran Gagal</h4>
            <p>Transaksi ini dibatalkan, kadaluarsa, atau ditolak.</p>
            <a href="<?= BASE_URL ?>" class="btn btn-dark">Coba Pembayaran Lagi</a>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <h4>❌ Status Pembayaran Tidak Dikenal</h4>
            <p>Kami tidak dapat menemukan atau memvalidasi pembayaran Anda. Silakan hubungi dukungan.</p>
            <a href="<?= BASE_URL ?>" class="btn btn-dark">Kembali ke Beranda</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>