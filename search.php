<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isset($_GET['q']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(live_search_articles($pdo, trim($_GET['q'])));
    exit;
}

$q       = trim($_GET['q'] ?? '');
$results = $q ? live_search_articles($pdo, $q) : [];

$page_title = $q ? 'Hasil Pencarian: ' . htmlspecialchars($q) : 'Pencarian';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="mb-3">
                <a href="javascript:history.back()" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <h2 class="mb-4"><i class="bi bi-search"></i> Hasil Pencarian
                <?php if ($q): ?>
                    <span class="text-secondary">“<?= htmlspecialchars($q) ?>”</span>
                <?php endif; ?>
            </h2>

            <form method="get" class="mb-5">
                <div class="input-group">
                    <input type="search" name="q" class="form-control form-control-lg" placeholder="Cari artikel..." value="<?= htmlspecialchars($q) ?>" required>
                    <button class="btn btn-outline-dark" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <?php if (!$q): ?>
                <div class="text-center text-muted"><i class="bi bi-search display-1"></i><p class="mt-3">Masukkan kata kunci di atas.</p></div>
            <?php elseif (empty($results)): ?>
                <div class="text-center text-muted"><i class="bi bi-inbox display-1"></i><p class="mt-3">Tidak ada hasil.</p></div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($results as $r): ?>
                        <div class="col-md-6 col-lg-4">
                            <article class="card h-100 shadow-sm border-0">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><a href="<?= BASE_URL ?>article.php?slug=<?= $r['slug'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($r['title']) ?></a></h5>
                                    <p class="card-text text-muted small flex-grow-1"><?= substr(strip_tags($r['excerpt']), 0, 120) ?>...</p>
                                    <div class="mt-auto"><a href="<?= BASE_URL ?>article.php?slug=<?= $r['slug'] ?>" class="btn btn-outline-dark btn-sm">Baca</a></div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>