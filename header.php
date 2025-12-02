<?php
// admin/includes/header.php – NAVBAR SERAGAM DENGAN FRONTEND (MENU SAJA, TANPA SEARCH & TOM BOL LAIN)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(__DIR__ . '/../..'));
}
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';
require_admin();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// BASE_URL otomatis
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    define('BASE_URL', $base === '' ? '/' : $base . '/');
}

$page_title = $page_title ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - NusaRoteMalole</title>

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- CSS Frontend -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
    
    <style>
        /* NAVBAR MIRIP FRONTEND: PUTIH, MENU RAPI */
        .navbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e0e0e0e0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 0.8rem 0;
        }
        .navbar-brand img {
            height: 40px;
            border-radius: 50%; /* Bulat seperti di gambar */
        }
        .navbar-brand span {
            font-weight: bold;
            color: #212529;
            font-size: 1.2rem;
            margin-left: 0.5rem;
        }
        .navbar .nav-link {
            color: #212529 !important;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.5rem 1rem !important;
        }
        .navbar .nav-link:hover,
        .navbar .nav-link.active {
            color: #D61355 !important; /* Magenta hover */
        }
        .user-dropdown {
            cursor: pointer;
        }
        .user-dropdown .bi {
            font-size: 1.5rem;
            color: #495057;
        }
        .dropdown-menu {
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0,0.1);
        }
        .dropdown-item:hover {
            background-color: rgba(214, 19, 85, 0.05);
            color: #D61355;
        }
        .content-wrapper {
            min-height: calc(100vh - 200px);
            padding: 2rem 0;
        }
    </style>
</head>
<body>

<!-- NAVBAR SERAGAM: LOGO + MENU + USER DROPDOWN (HAPUS SEARCH & TOM BOL LAIN) -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>">
            <img src="<?= BASE_URL ?>assets/image/1.png" alt="NusaRoteMalole Logo" class="me-2">
            <span>NusaRoteMalole</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Menu Utama (Hanya yang diminta) -->
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/">Beranda</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Kategori
                    </a>
                    <ul class="dropdown-menu border-0 shadow" aria-labelledby="navbarDropdown">
                        <?php foreach ($_SESSION['categories'] as $c): ?>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>category.php?slug=<?= htmlspecialchars($c['slug']) ?>"><?= htmlspecialchars($c['name']) ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-bold" href="<?= BASE_URL ?>category.php">Lihat Semua</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>admin/videos/">Video</a></li>
            </ul>

            <!-- User Dropdown (Halo + Logout, tanpa tombol lain) -->
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <div class="user-dropdown d-flex align-items-center" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-2"></i>
                        <span class="me-2">Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                        <i class="bi bi-caret-down-fill"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>admin/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid content-wrapper mt-4">
    <!-- KONTEN ADMIN MULAI DARI SINI -->