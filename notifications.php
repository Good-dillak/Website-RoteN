<?php
// admin/notifications.php
require '../includes/config.php';
require '../includes/auth.php';
require_admin();

if ($_POST['mark_all_read'] ?? '') {
    $pdo->query("UPDATE admin_notifications SET is_read = 1");
    set_flash('success', 'Semua notifikasi ditandai dibaca.');
    redirect('notifications.php');
}

$notifications = $pdo->query("
    SELECT * FROM admin_notifications 
    ORDER BY created_at DESC 
    LIMIT 50
")->fetchAll();

$page_title = "Notifikasi";
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between mb-4">
        <h2>Notifikasi</h2>
        <form method="post">
            <button name="mark_all_read" class="btn btn-outline-primary btn-sm">Tandai Semua Dibaca</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): ?>
                <a href="<?= $n['link'] ?? '#' ?>" class="list-group-item list-group-item-action <?= $n['is_read'] ? '' : 'bg-light' ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?= htmlspecialchars($n['type']) ?></h6>
                        <small><?= date('d M Y H:i', strtotime($n['created_at'])) ?></small>
                    </div>
                    <p class="mb-1"><?= htmlspecialchars($n['message']) ?></p>
                </a>
                <?php endforeach; ?>
                <?php if (!$notifications): ?>
                    <div class="p-3 text-center text-muted">Tidak ada notifikasi baru.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>