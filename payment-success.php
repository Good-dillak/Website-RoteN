<?php
// payment-success.php (Dibersihkan dari logika update status yang tidak aman)
require 'includes/config.php';
require 'includes/auth.php';
require_login();

// LOGIKA UPDATE STATUS INSECURE TELAH DIHAPUS DARI SINI

$page_title = "Pembayaran Selesai";
?>
<?php include 'includes/header.php'; ?>

<div class="container mt-5 text-center">
    <div class="alert alert-success">
        <h4>Pembayaran Selesai!</h4>
        <p>Kami sedang memproses konfirmasi pembayaran Anda dari Midtrans. Mohon tunggu sebentar. Status dapat dicek di riwayat transaksi atau halaman status.</p>
        <a href="<?= BASE_URL ?>" class="btn btn-dark">Kembali ke Beranda</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>