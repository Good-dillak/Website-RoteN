<?php
require '../../includes/config.php';
require '../../includes/auth.php';
require '../../includes/functions.php';
require_admin();

$articles = $pdo->query("SELECT a.*, u.username, c.name AS category_name FROM articles a LEFT JOIN users u ON a.author_id = u.id LEFT JOIN categories c ON a.category_id = c.id ORDER BY a.created_at DESC")->fetchAll();

$page_title = "Kelola Artikel";
include '../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="mb-3">
        <a href="../index.php" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>
        <a href="add.php" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle"></i> Tambah Artikel
        </a>
    </div>

    <h2><i class="bi bi-file-text"></i> Kelola Artikel</h2>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Judul</th>
                            <th>Penulis</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                            <td><?= htmlspecialchars($a['username']) ?></td>
                            <td><?= htmlspecialchars($a['category_name'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $a['status'] === 'published' ? 'success' : ($a['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                    <?= ucfirst($a['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                            <td>
                                <a href="edit.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus artikel ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>