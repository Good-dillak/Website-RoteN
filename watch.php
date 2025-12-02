<?php
// watch.php - VERSI HANYA YOUTUBE

// Fungsi bantuan untuk mendapatkan URL
function getVideoUrl($row) {
    if ($row['video_type'] === 'youtube') {
        // PERBAIKAN: Gunakan embed_url
        return "https://www.youtube.com/watch?v=" . $row['embed_url']; 
    } else {
        return '#'; // Fallback
    }
}
?>

<div class="col-md-4 mb-4">
    <div class="card h-100 shadow-sm">
        <?php 
        // PERBAIKAN: Gunakan embed_url untuk thumbnail
        if ($row['video_type'] === 'youtube' && !empty($row['embed_url'])): ?>
            <img src="https://img.youtube.com/vi/<?= $row['embed_url'] ?>/maxresdefault.jpg" 
                 class="card-img-top" alt="<?= htmlspecialchars($row['title']) ?>">
        <?php else: ?>
            <img src="<?= BASE_URL ?>assets/img/video-placeholder.jpg" 
                 class="card-img-top" alt="<?= htmlspecialchars($row['title']) ?>">
        <?php endif; ?>

        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
            <a href="<?= getVideoUrl($row) ?>" target="_blank" class="btn btn-danger mt-auto">
                <i class="bi bi-play-fill"></i> 
                Tonton di YouTube
            </a>
        </div>
    </div>
</div>