<?php
// share.php – halaman share artikel
require 'includes/config.php';
require 'includes/auth.php';
require 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT a.*, c.name AS category_name, u.username
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

// Ambil data reaksi
$reactions = get_article_reactions($pdo, $article['id']);
$userIP    = $_SERVER['REMOTE_ADDR'] ?? '';
$user_reaction = user_has_reacted($pdo, $article['id'], $userIP);

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
                    <div class="mb-4 article-featured-image-container">
                        <?php 
                            $base_url_safe = rtrim(BASE_URL, '/');
                            $featured_path_safe = ltrim($article['featured_image'], '/');
                        ?>
                        <img src="<?= $base_url_safe . '/' . $featured_path_safe ?>" class="img-fluid rounded article-img img-cover" alt="<?= htmlspecialchars($article['title']) ?>">
                    </div>
                <?php endif; ?>

                <div class="article-content text-justify">
                    <?= $article['content'] ?>
                </div>

                <hr class="my-3">

                <section class="mb-4">
                    <div class="d-flex align-items-center gap-2">
                        
                        <form method="post" action="react.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                            <button type="submit" name="reaction" value="like" 
                                class="btn p-0 border-0 bg-transparent <?= $user_reaction === 'like' ? 'text-primary' : 'text-dark' ?> fs-4" title="Like">
                                <i class="bi bi-hand-thumbs-up-fill"></i>
                            </button>
                        </form>

                        <form method="post" action="react.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                            <button type="submit" name="reaction" value="dislike" 
                                class="btn p-0 border-0 bg-transparent <?= $user_reaction === 'dislike' ? 'text-danger' : 'text-dark' ?> fs-4" title="Dislike">
                                <i class="bi bi-hand-thumbs-down-fill"></i>
                            </button>
                        </form>
                        
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
                        <span class="fw-bold me-3"><i class="bi bi-hand-thumbs-up-fill text-primary"></i> <?= $reactions['like'] ?> Likes</span>
                        <span class="fw-bold me-3"><i class="bi bi-hand-thumbs-down-fill text-danger"></i> <?= $reactions['dislike'] ?> Dislikes</span>
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

<?php include 'includes/footer.php'; ?>