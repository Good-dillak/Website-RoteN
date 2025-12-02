<?php
// admin/logout.php
require '../includes/config.php';
require '../includes/auth.php'; // Tambahkan baris ini
session_destroy();
redirect('login.php');
?>