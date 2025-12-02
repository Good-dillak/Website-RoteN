<?php
// includes/auth.php

if (!function_exists('redirect')) {
    function redirect($url) {
        $base = defined('BASE_URL') ? BASE_URL : '/tourism-news-portal/';
        header("Location: " . rtrim($base, '/') . '/' . ltrim($url, '/'));
        exit;
    }
}

function is_logged_in() { return isset($_SESSION['user_id']); }
function require_login() { if (!is_logged_in()) redirect('login.php'); }
function is_admin() { return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin'; }
function require_admin() {
    if (!is_admin()) {
        set_flash('danger', 'Akses ditolak!');
        redirect('index.php');
    }
}
?>