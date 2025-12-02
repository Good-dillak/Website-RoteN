<?php
// admin/login.php  (taruh di folder admin, bukan di root)
require '../includes/config.php';
require '../includes/auth.php';

if (is_logged_in()) redirect('index.php');

$error = '';
if ($_POST) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Token tidak valid!';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            redirect('index.php');
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .login-card {
            max-width: 400px;
        }
        .btn-gradient {
            /* Anda bisa menghapus ini jika tidak digunakan */
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="container">
    <div class="row">
        <div class="col-12 mx-auto">
            <div class="card shadow-lg border-0 login-card mx-auto">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-lock-fill text-primary display-4"></i>
                        <h3 class="mt-3 fw-bold">Login Admin</h3>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <div class="mb-3">
                            <label class="form-label visually-hidden">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" class="form-control form-control-lg" placeholder="Username" required autofocus>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label visually-hidden">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                            <i class="bi bi-box-arrow-in-right me-2"></i> LOGIN
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>