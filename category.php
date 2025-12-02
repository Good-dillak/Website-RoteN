<?php
// category.php
require 'includes/config.php';
require 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$category = null;
$where_clause = "1=1"; // Default: Ambil semua artikel
$execute_params = [];

if ($slug) {
    // --- Logika untuk Kategori TERTENTU ---
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $category = $stmt->fetch();
    
    if (!$category) {
        // Kategori tidak ditemukan, tampilkan 404
        http_response_code(404);
        $page_title = "Kategori Tidak Ditemukan";
        include 'includes/header.php';
        echo "<div class='container py-5'><h2>Kategori tidak ditemukan.</h2></div>";
        include 'includes/footer.php';
        exit;
    }
    
    // Kategori ditemukan, filter berdasarkan ID
    $where_clause = "a.category_id = ?";
    $execute_params = [$category['id']];

    $page_title = htmlspecialchars($category['name']) . " - NusaRoteMalole"; 
    $page_description = htmlspecialchars($category['description'] ?? $page_title);

} else {
    // --- Logika untuk SEMUA ARTIKEL (Dipanggil oleh tombol dari index.php) ---
    $page_title = "Semua Artikel - NusaRoteMalole";
    $page_description = "Daftar lengkap semua artikel yang telah dipublikasikan di NusaRoteMalole.";
    // $where_clause tetap "1=1" dan $execute_params tetap []
}

log_page_view($pdo, null); // Log kunjungan kategori/list

// Ambil semua artikel (atau yang difilter oleh kategori)
$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE {$where_clause} AND (a.status = 'published' OR a.status = 'premium') 
    ORDER BY a.created_at DESC
");
$stmt->execute($execute_params);
$articles = $stmt->fetchAll();


include 'includes/header.php'; 
?>

<div class="container py-5">
    <h1 class="display-5 fw-bold mb-4">
        <?php if ($category): ?>
            <i class="bi bi-tag"></i> Kategori: <?= htmlspecialchars($category['name']) ?>
        <?php else: ?>
            <i class="bi bi-newspaper"></i> <?= $page_title ?>
        <?php endif; ?>
    </h1>

    <?php if ($category && $category['description']): ?>
        <p class="lead text-muted mb-5"><?= nl2br(htmlspecialchars($category['description'])) ?></p>
    <?php endif; ?>

    <div class="row g-4">
        <?php if (!$articles): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-inbox display-1"></i>
                <p class="mt-3">Tidak ada artikel yang dipublikasikan<?php if ($category) echo " di kategori ini"; ?>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($articles as $a): ?>
            <div class="col-md-6 col-lg-4">
                <article class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
                    <?php if ($a['featured_image']): ?>
                    <img src="<?= BASE_URL ?><?= $a['featured_image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($a['title']) ?>" style="height:200px; object-fit:cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <?php if ($a['is_premium']): ?>
                            <span class="badge bg-danger mb-2"><i class="bi bi-star-fill"></i> Premium</span>
                        <?php endif; ?>
                        <h5><a href="<?= BASE_URL ?>article.php?slug=<?= $a['slug'] ?>" class="text-dark text-decoration-none">
                            <?= htmlspecialchars($a['title']) ?>
                        </a></h5>
                        <p class="card-text text-muted small">
                            <?= substr(strip_tags($a['excerpt'] ?: $a['content']), 0, 100) ?>...
                        </p>
                        <div class="small text-muted mt-2">
                            <i class="bi bi-person"></i> <?= htmlspecialchars($a['username']) ?>
                            <span class="mx-2">|</span>
                            <i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($a['created_at'])) ?>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>