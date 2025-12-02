<?php
// admin/menu/reorder.php
require '../../includes/config.php';
require '../../includes/auth.php';
require '../../includes/functions.php';
require_admin();

$id = (int)$_POST['id'];
$menus = $pdo->query("SELECT id FROM main_menu ORDER BY sort_order")->fetchAll(PDO::FETCH_COLUMN);

$index = array_search($id, $menus);
if ($index === false) redirect('index.php');

if (isset($_POST['up']) && $index > 0) {
    [$menus[$index-1], $menus[$index]] = [$menus[$index], $menus[$index-1]];
} elseif (isset($_POST['down']) && $index < count($menus)-1) {
    [$menus[$index], $menus[$index+1]] = [$menus[$index+1], $menus[$index]];
}

foreach ($menus as $i => $mid) {
    $pdo->prepare("UPDATE main_menu SET sort_order = ? WHERE id = ?")->execute([$i, $mid]);
}

redirect('index.php');
?>