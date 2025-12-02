<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT a.*, c.name AS category_name, c.slug AS category_slug, u.username
                       FROM articles a
                       LEFT JOIN categories c ON a.category_id = c.id
                       LEFT JOIN users u ON a.author_id = u.id
                       WHERE a.slug = ? AND a.status = 'published'");
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $page_title = "Artikel Tidak Ditemukan";
    include 'includes/header.php';
    echo '<div class="container mt-5"><div class="alert alert-danger">Artikel tidak ditemukan.</div></div>';
    include 'includes/footer.php';
    exit;
}

// PENAMBAHAN: Ambil gambar-gambar artikel tambahan
$article_images = [];
if ($article) {
    $images_stmt = $pdo->prepare("SELECT id, image_path FROM article_images WHERE article_id = ?");
    $images_stmt->execute([$article['id']]);
    $article_images = $images_stmt->fetchAll();
}
// END PENAMBAHAN

// Hitung view
log_page_view($pdo, $article['id']);

// Cek akses premium
$is_premium = $article['is_premium'];
$has_access = false;
if ($is_premium && is_logged_in()) {
    $stmt = $pdo->prepare("SELECT 1 FROM payments WHERE user_id = ? AND article_id = ? AND status = 'success'");
    $stmt->execute([$_SESSION['user_id'], $article['id']]);
    $has_access = (bool) $stmt->fetchColumn();
}
if (!$is_premium) $has_access = true; // gratis = boleh baca

// Ambil data reaksi
$reactions = get_article_reactions($pdo, $article['id']);
$userIP    = $_SERVER['REMOTE_ADDR'] ?? '';
$user_reaction = user_has_reacted($pdo, $article['id'], $userIP); // Hanya perlu tahu apakah sudah like

$page_title = htmlspecialchars($article['title']) . " - NusaRoteMalole";
$page_description = htmlspecialchars($article['excerpt'] ?? substr(strip_tags($article['content']), 0, 150) . '...');
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <article class="article-detail">
                <header class="mb-4">
                    <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($article['title']) ?></h1>
                    <div class="text-muted small mb-3">
                        <i class="bi bi-calendar"></i> <?= date('d F Y', strtotime($article['created_at'])) ?> oleh
                        <i class="bi bi-person"></i> <?= htmlspecialchars($article['username']) ?>
                        <span class="ms-3"><i class="bi bi-tags"></i> <a href="<?= BASE_URL ?>category.php?slug=<?= $article['category_slug'] ?? '' ?>" class="text-decoration-none"><?= htmlspecialchars($article['category_name'] ?? 'Uncategorized') ?></a></span>
                        <?php
                        $views_stmt = $pdo->prepare("SELECT SUM(count) FROM article_views WHERE article_id = ?");
                        $views_stmt->execute([$article['id']]);
                        $views = $views_stmt->fetchColumn() ?: 0;
                        ?>
                        <span class="ms-3"><i class="bi bi-eye"></i> <?= $views ?> views</span>
                    </div>
                </header>

                <?php if ($article['featured_image']): ?>
                    <?php 
                        $base_url_safe = rtrim(BASE_URL, '/');
                        $featured_path_safe = ltrim($article['featured_image'], '/');
                        $full_image_url = $base_url_safe . '/' . $featured_path_safe; // URL gambar penuh
                    ?>
                    <div class="mb-4 article-featured-image-container">
                        <img 
                            src="<?= $full_image_url ?>" 
                            class="img-fluid rounded article-img img-cover cursor-pointer" 
                            alt="<?= htmlspecialchars($article['title']) ?>"
                            data-bs-toggle="modal" 
                            data-bs-target="#mediaGalleryModal"
                            data-media-type="image"
                            data-media-url="<?= $full_image_url ?>"
                        >
                    </div>
                <?php endif; ?>

                <?php if ($article['video_path']): ?>
                    <?php 
                        $video_paths = explode(',', $article['video_path']);
                        // BATASI HANYA 5 VIDEO PERTAMA (untuk 1 baris)
                        $limited_video_paths = array_slice($video_paths, 0, 5); 
                    ?>
                    <div class="mb-4">
                        <h5>Video</h5>
                        <div class="row g-2"> 
                            <?php foreach ($limited_video_paths as $video_path): ?>
                                <?php 
                                    $full_video_url = BASE_URL . 'uploads/' . trim($video_path);
                                ?>
                                <div class="col-6 col-sm-4 col-md-2">
                                    <video 
                                        width="100%" 
                                        height="120" 
                                        class="w-100 rounded article-thumb-video cursor-pointer"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#mediaGalleryModal"
                                        data-media-type="video"
                                        data-media-url="<?= $full_video_url ?>"
                                    > 
                                        <source src="<?= $full_video_url ?>" type="video/mp4">
                                        Browser Anda tidak mendukung video.
                                    </video>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($article_images)): ?>
                    <div class="mb-4">
                        <h5>Gambar</h5>
                        <div class="row image-grid g-2">
                            <?php foreach ($article_images as $img): ?>
                                <?php 
                                    $base_url_safe = rtrim(BASE_URL, '/');
                                    $image_path_safe = ltrim($img['image_path'], '/');
                                    $full_image_url = $base_url_safe . '/' . $image_path_safe;
                                ?>
                                <div class="col-6 col-sm-4 col-md-2">
                                    <img 
                                        src="<?= $full_image_url ?>" 
                                        class="img-fluid rounded article-thumb-image cursor-pointer" 
                                        alt="Gambar Tambahan"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#mediaGalleryModal"
                                        data-media-type="image"
                                        data-media-url="<?= $full_image_url ?>"
                                    >
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="article-content text-justify">
                    <?= $article['content'] ?>
                </div>

                <hr class="my-3">

                <section class="mb-4">
                    <div class="d-flex align-items-center gap-2">
                        
                        <button type="button" 
                                data-article-id="<?= $article['id'] ?>" 
                                data-reaction="like" 
                                class="btn-reaction btn p-0 border-0 bg-transparent <?= $user_reaction === 'like' ? 'text-primary' : 'text-dark' ?> fs-4" 
                                title="Super">
                            <i class="bi bi-heart-fill"></i> </button>
                        
                        <button class="btn p-0 border-0 bg-transparent text-dark fs-4 ms-2" type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#commentFormCollapse" 
                                aria-expanded="false" 
                                aria-controls="commentFormCollapse" 
                                title="Komentar">
                            <i class="bi bi-chat"></i>
                        </button>

                        <button type="button" class="btn p-0 border-0 bg-transparent text-dark fs-4 ms-2" data-bs-toggle="modal" data-bs-target="#shareModal" title="Bagikan">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                    
                    <div class="text-muted small mt-2">
                        <span class="fw-bold me-3">
                            <i class="bi bi-heart-fill text-primary"></i> 
                            <span id="likes-count"><?= $reactions['like'] ?></span> Super
                        </span>
                        
                        <span><?= count(get_comments($pdo, $article['id'])) ?> Komentar</span>
                    </div>
                </section>

                <hr class="my-5">

                <section class="mt-5" id="comments">
                    <h5>Komentar</h5>
                    <?php $comments = get_comments($pdo, $article['id']); ?>
                    <?php if ($comments): ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="card mb-2">
                                <div class="card-body py-2">
                                    <h6 class="card-subtitle mb-1 text-muted"><?= htmlspecialchars($c['name']) ?> · <small><?= date('d M Y H:i', strtotime($c['created_at'])) ?></small></h6>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Belum ada komentar.</p>
                    <?php endif; ?>
                    
                    <div class="collapse mt-4" id="commentFormCollapse">
                         <h5 id="comment-form">Kirim Komentar Anda</h5>
                        <form method="post" action="comment.php" class="mt-3">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                            <div class="mb-2"><input type="text" name="name" class="form-control" placeholder="Nama" required></div>
                            <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                            <div class="mb-2"><textarea name="content" class="form-control" rows="3" placeholder="Tulis komentar..." required></textarea></div>
                            <button type="submit" class="btn btn-primary btn-sm">Kirim Komentar</button>
                        </form>
                    </div>
                </section>

            </article>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light"><h5><i class="bi bi-file-text"></i> Artikel Terkait</h5></div>
                <div class="card-body">
                    <?php 
                    $related_stmt = $pdo->prepare("SELECT slug, title, featured_image, created_at FROM articles WHERE category_id = ? AND id != ? AND status = 'published' ORDER BY created_at DESC LIMIT 5");
                    $related_stmt->execute([$article['category_id'], $article['id']]);
                    $related = $related_stmt->fetchAll();
                    foreach ($related as $r):
                    ?>
                        <div class="d-flex mb-3">
                            <?php if ($r['featured_image']): ?>
                                <?php 
                                    $base_url_safe = rtrim(BASE_URL, '/');
                                    $related_path_safe = ltrim($r['featured_image'], '/');
                                ?>
                                <img src="<?= $base_url_safe . '/' . $related_path_safe ?>" class="me-3 rounded" width="80" height="80" style="object-fit:cover;" alt="<?= htmlspecialchars($r['title']) ?>">
                            <?php endif; ?>
                            <div>
                                <h6><a href="<?= BASE_URL ?>article.php?slug=<?= $r['slug'] ?>" class="text-dark text-decoration-none"><?= htmlspecialchars($r['title']) ?></a></h6>
                                <small class="text-muted"><i class="bi bi-clock"></i> <?= date('d M Y', strtotime($r['created_at'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mediaGalleryModal" tabindex="-1" aria-labelledby="mediaGalleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <div id="modalMediaContainer" class="d-flex justify-content-center align-items-center w-100 h-100">
                    </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shareModalLabel">Bagikan Artikel Ini</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <div class="d-flex flex-wrap justify-content-center gap-3">
          
          <?php 
          // Definisikan daftar platform dan properti mereka
          $share_links = [
              'whatsapp' => ['icon' => 'bi-whatsapp', 'class' => 'btn-success', 'label' => 'WhatsApp', 'url' => 'https://wa.me/?text=' . urlencode($article['title'] . ' - ' . $page_description . ' ' . BASE_URL . 'article.php?slug=' . $slug)],
              'facebook' => ['icon' => 'bi-facebook', 'class' => 'btn-primary', 'label' => 'Facebook', 'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode(BASE_URL . 'article.php?slug=' . $slug)],
              'twitter'  => ['icon' => 'bi-twitter-x', 'class' => 'btn-twitter', 'label' => 'Twitter', 'url' => 'https://twitter.com/intent/tweet?text=' . urlencode($article['title'] . ' - ' . $page_description . ' ' . BASE_URL . 'article.php?slug=' . $slug)],
              'instagram'=> ['icon' => 'bi-instagram-fill', 'class' => 'btn-instagram', 'label' => 'Instagram', 'url' => 'https://www.instagram.com/stories/?link=' . urlencode(BASE_URL . 'article.php?slug=' . $slug)],
              'tiktok'   => ['icon' => 'bi-tiktok', 'class' => 'btn-tiktok', 'label' => 'TikTok', 'url' => 'https://tiktok.com/@yourusername/share/video/' . urlencode($slug)],
              'email'    => ['icon' => 'bi-envelope', 'class' => 'btn-email', 'label' => 'Email', 'url' => 'mailto:?subject=' . urlencode($article['title']) . '&body=' . urlencode($article['content'] . ' ' . BASE_URL . 'article.php?slug=' . $slug)],
          ];
          ?>
          
          <?php foreach ($share_links as $key => $link): ?>
            <a href="<?= $link['url'] ?>" target="_blank" class="btn <?= $link['class'] ?> d-flex flex-column align-items-center">
                <i class="<?= $link['icon'] ?>"></i> 
                <small><?= $link['label'] ?></small>
            </a>
          <?php endforeach; ?>
          
            <button class="btn btn-copy d-flex flex-column align-items-center" onclick="copyToClipboard()">
               <i class="bi bi-clipboard"></i>
               <small>Salin Tautan</small>
            </button>

        </div>
      </div>
    </div>
  </div>
</div>
<script>
  // Script untuk Modal Galeri Media (Gambar/Video)
  document.addEventListener('DOMContentLoaded', function() {
      var mediaGalleryModal = document.getElementById('mediaGalleryModal');
      var container = document.getElementById('modalMediaContainer');
      
      mediaGalleryModal.addEventListener('show.bs.modal', function (event) {
          var button = event.relatedTarget;
          var mediaUrl = button.getAttribute('data-media-url');
          var mediaType = button.getAttribute('data-media-type');
          
          // Kosongkan container sebelum memuat media baru
          container.innerHTML = ''; 

          if (mediaType === 'image') {
              var img = document.createElement('img');
              img.src = mediaUrl;
              // PENTING: Menghapus semua style inline, biarkan CSS di style.css yang mengambil alih ukuran penuh
              img.className = 'img-fluid rounded'; 
              img.alt = 'Gambar Penuh';
              
              container.appendChild(img);
          } else if (mediaType === 'video') {
              var video = document.createElement('video');
              // PENTING: Menghapus semua style inline, biarkan CSS di style.css yang mengambil alih ukuran penuh
              video.controls = true;
              video.autoplay = true; 
              video.className = 'w-100 rounded'; 
              
              var source = document.createElement('source');
              source.src = mediaUrl;
              source.type = 'video/mp4'; 

              video.appendChild(source);
              container.appendChild(video);
          }
      });
      
      // Hentikan pemutaran video saat modal ditutup
      mediaGalleryModal.addEventListener('hidden.bs.modal', function () {
          // Mengosongkan container saat modal ditutup, ini akan menghentikan video
          container.innerHTML = ''; 
      });
  
      
      // ===========================================
      // LOGIKA AJAX UNTUK SUPER/SUKA SAJA
      // ===========================================
      const likeButton = document.querySelector('[data-reaction="like"]');
      const likesCountElement = document.getElementById('likes-count');
      
      const primaryColor = 'text-primary';
      const darkColor    = 'text-dark';

      if(likeButton) {
          likeButton.addEventListener('click', function(e) {
              e.preventDefault();

              const articleId = this.dataset.articleId;
              const currentButton = this;
              
              // Ambil Token CSRF (Jika digunakan)
              const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';
              
              const formData = new URLSearchParams();
              formData.append('csrf_token', csrfToken);
              formData.append('article_id', articleId);
              formData.append('reaction', 'like'); // Tetap kirim 'like' untuk kompatibilitas DB

              // Lakukan permintaan AJAX
              fetch('react.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: formData
              })
              .then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      
                      // 1. Update jumlah hitungan
                      likesCountElement.textContent = data.likes;
                      
                      // 2. Update status tombol
                      if (data.new_state === 'added') {
                          // Tombol saat ini menjadi aktif (warna primary)
                          currentButton.classList.remove(darkColor);
                          currentButton.classList.add(primaryColor);
                          
                      } else if (data.new_state === 'removed') {
                          // Tombol saat ini menjadi non-aktif (warna dark)
                          currentButton.classList.remove(primaryColor);
                          currentButton.classList.add(darkColor);
                      }
                      
                      if (typeof window.showToast === 'function') {
                          window.showToast(data.message, data.status);
                      } else {
                          console.log('Reaction Success:', data.message);
                      }

                  } else {
                       if (typeof window.showToast === 'function') {
                          window.showToast(data.message, 'danger');
                       } else {
                           alert(data.message);
                       }
                  }
              })
              .catch(error => {
                  console.error('Error reaction:', error);
                  if (typeof window.showToast === 'function') {
                      window.showToast('Gagal terhubung ke server reaksi.', 'danger');
                  } else {
                      alert('Gagal terhubung ke server reaksi.');
                  }
              });
          });
      }
      
  }); // End DOMContentLoaded
  
  // Script untuk Salin Tautan (EXISTING)
  function copyToClipboard() {
     const url = window.location.href; // Ganti dengan URL artikel yang benar
     navigator.clipboard.writeText(url).then(() => {
         alert('Tautan berhasil disalin'); 
     });
  }
</script>

<?php include 'includes/footer.php'; ?>