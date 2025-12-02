<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_admin();

$id = (int)$_GET['id'];
$pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
set_flash('success', 'Artikel dihapus.');
redirect('index.php');