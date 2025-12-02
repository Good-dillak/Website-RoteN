<?php
// verify.php
require 'includes/config.php';
require 'includes/functions.php';

// Security headers
header("Content-Security-Policy: default-src 'self'");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("X-Frame-Options: DENY");

// Clean input
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$msg = $class = '';
$icon = '';

try {
    if ($token && strlen($token) === 32) { // Validasi panjang token
        $verificationResult = verify_email($pdo, $token);
        
        if ($verificationResult === true) {
            $msg = "Email berhasil diverifikasi!";
            $class = "alert-success";
            $icon = "check-circle-fill";
        } elseif ($verificationResult === 'expired') {
            $msg = "Token telah kadaluarsa. Silakan request verifikasi ulang.";
            $class = "alert-warning";
            $icon = "exclamation-triangle-fill";
        } else {
            throw new Exception("Token tidak valid");
        }
    } else {
        throw new InvalidArgumentException("Token tidak valid");
    }
} catch (InvalidArgumentException $e) {
    $msg = "Format token tidak valid";
    $class = "alert-danger";
    $icon = "x-circle-fill";
    error_log("Invalid token format: " . $e->getMessage());
} catch (Exception $e) {
    $msg = "Verifikasi gagal: Token tidak ditemukan atau sudah digunakan.";
    $class = "alert-danger";
    $icon = "x-circle-fill";
    error_log("Verification failed: " . $e->getMessage());
}


$page_title = 'Verifikasi Akun';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">
                    <h3 class="card-title text-center mb-4">Hasil Verifikasi</h3>
                    
                    <div class="alert <?= htmlspecialchars($class) ?> d-flex align-items-center" role="alert">
                        <i class="bi bi-<?= htmlspecialchars($icon) ?> flex-shrink-0 me-3" style="font-size: 1.5rem;"></i>
                        <div>
                            <?= htmlspecialchars($msg) ?>
                            <?php if ($class === 'alert-danger'): ?>
                                <p class="mt-2 mb-0 small">Jika Anda yakin ini error, silakan hubungi <a href="mailto:support@itaesa.com" class="alert-link">tim support</a>.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-center gap-3">
                        <?php if ($class === 'alert-success'): ?>
                            <a href="<?= htmlspecialchars(BASE_URL) ?>login.php" class="btn btn-success px-4">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login Sekarang
                            </a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars(BASE_URL) ?>register.php" class="btn btn-outline-dark">
                                <i class="bi bi-arrow-repeat me-2"></i>Daftar Ulang
                            </a>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars(BASE_URL) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-house-door me-2"></i>Beranda
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4 small text-muted">
                <i class="bi bi-shield-lock"></i> Sistem keamanan otomatis akan memblokir percobaan verifikasi mencurigakan
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>