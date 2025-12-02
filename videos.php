<?php
// videos.php - VERSI HANYA YOUTUBE
require 'includes/config.php';
require 'includes/functions.php';

// PERBAIKAN: Hanya ambil video yang tipenya 'youtube'
$stmt = $pdo->query("SELECT * FROM videos WHERE video_type = 'youtube' AND embed_url IS NOT NULL ORDER BY sort_order ASC, created_at DESC");
$videos = $stmt->fetchAll();

$page_title = "Video - NusaRoteMalole";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h2 class="mb-4 text-center fw-bold">
                Koleksi Video Terbaru
            </h2>
            <p class="text-center text-muted mb-5">Liputan NusaRoteMalole</p>

            <?php if (empty($videos)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-film display-1 text-muted"></i>
                    <p class="mt-3 text-muted">Belum ada video YouTube tersedia.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4">
                    <?php foreach ($videos as $video): ?>
                        <?php
                        $youtube_id = $video['embed_url'];
                        $thumb = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
                        $url   = "https://www.youtube.com/watch?v={$youtube_id}";
                        $btn_text = 'Tonton di YouTube';
                        $btn_class = 'btn-danger';
                        ?>

                        <div class="col">
                            <div class="card h-100 shadow-sm border-0 overflow-hidden rounded-4 hover-shadow">
                                <div class="position-relative">
                                    <img src="<?= htmlspecialchars($thumb) ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($video['title']) ?>"
                                         style="height: 120px; object-fit: cover;"> 
                                    <div class="position-absolute top-50 start-50 translate-middle">
                                        <i class="bi bi-play-circle-fill text-white" style="font-size: 3rem; opacity: 0.9; text-shadow: 0 0 20px rgba(0,0,0,0.7);"></i>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column p-2">
                                    <h6 class="card-title fw-bold mb-1" style="line-height: 1.3;">
                                        <?= htmlspecialchars(mb_substr($video['title'], 0, 45)) ?>
                                        <?= strlen($video['title']) > 45 ? '...' : '' ?>
                                    </h6>
                                    <p class="text-muted" style="font-size: 0.75rem; margin-bottom: 0.5rem;"> 
                                        <i class="bi bi-calendar3"></i> 
                                        <?= date('d M Y', strtotime($video['created_at'])) ?>
                                    </p>
                                    <a href="<?= $url ?>" 
                                       target="_blank" 
                                       class="btn <?= $btn_class ?> btn-sm mt-auto fw-semibold">
                                        <i class="bi bi-play-fill me-1"></i>
                                        <?= $btn_text ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
}
</style>

<?php include 'includes/footer.php'; ?>