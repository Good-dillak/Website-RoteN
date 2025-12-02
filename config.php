<?php
session_start();
ob_start();

define('BASE_URL', '/tourism-news-portal/'); // Sesuaikan folder

$host = 'localhost';
$db   = 'tourism_news';
$user = 'root'; // Ganti
$pass = '';     // Ganti

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

require 'functions.php';
?>