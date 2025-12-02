<?php
// admin/analytics.php – dashboard ringan untuk admin
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_admin();

// ---------- data ringan ----------
$views_7    = get_analytics_summary($pdo, 7);
$views_30   = get_analytics_summary($pdo, 30);
$top_list   = get_top_articles($pdo, 10);   // 10 artikel terpopuler

$page_title = 'Analytics';
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>
</div>

    <h2 class="mb-4"><i class="bi bi-graph-up"></i> Analytics Dashboard</h2>

    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h4 class="card-title fw-bold text-primary"><?= number_format($views_7) ?></h4>
                    <p class="card-text text-muted small">Views 7 Hari Terakhir</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h4 class="card-title fw-bold text-success"><?= number_format($views_30) ?></h4>
                    <p class="card-text text-muted small">Views 30 Hari Terakhir</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            </div>
        <div class="col-sm-6 col-lg-3">
            </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-star"></i> 10 Artikel Terpopuler</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Judul Artikel</th>
                            <th class="text-center" style="width: 150px;">Total Views</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_list)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                Belum ada data tampilan.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($top_list as $idx => $a): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <a href="<?= BASE_URL ?>article.php?slug=<?= $a['slug'] ?>" target="_blank" class="text-decoration-none">
                                        <?= htmlspecialchars($a['title']) ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= number_format($a['views']) ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= BASE_URL ?>article.php?slug=<?= $a['slug'] ?>" class="btn btn-sm btn-outline-primary" title="Buka artikel">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


<?php include '../includes/footer.php'; ?>