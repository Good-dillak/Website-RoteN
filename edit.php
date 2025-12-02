<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();
if (!$article) {
    set_flash('danger', 'Artikel tidak ditemukan.');
    redirect('index.php');
}

// Ambil gambar tambahan
$images_stmt = $pdo->prepare("SELECT id, image_path FROM article_images WHERE article_id = ? ORDER BY id ASC");
$images_stmt->execute([$id]);
$article_images = $images_stmt->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = "Token tidak valid!";
    } else {
        $title       = trim($_POST['title']);
        $slug        = slugify($title);
        $excerpt     = trim($_POST['excerpt']);
        $content     = trim($_POST['content']);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status      = $_POST['status'] ?? 'draft';
        $is_premium  = isset($_POST['is_premium']) ? 1 : 0;

        // Gambar utama
        $featured_image = $article['featured_image'];
        if (!empty($_FILES['featured_image']['name'])) {
            $path = upload_image($_FILES['featured_image'], '../../uploads/');
            if ($path) {
                delete_old_file($article['featured_image']);
                $featured_image = $path;
            } else {
                $error = 'Gagal upload gambar utama.';
            }
        }
        if (isset($_POST['delete_featured_image']) && $featured_image) {
            delete_old_file($featured_image);
            $featured_image = '';
        }

        // Video (bisa multiple)
        $video_paths = $article['video_path'] ? explode(',', $article['video_path']) : [];

        // Hapus video yang dicentang
        $videos_to_delete = $_POST['delete_videos'] ?? [];
        foreach ($videos_to_delete as $vpath) {
            delete_old_file($vpath);
            $video_paths = array_filter($video_paths, fn($x) => $x !== $vpath);
        }

        // Upload video baru (multiple)
        if (!$error && !empty($_FILES['videos']['name'][0])) {
            $totalNow = count($video_paths);
            $totalNew = count($_FILES['videos']['name']);
            $canAdd   = 10 - $totalNow;
            if ($totalNew > $canAdd) {
                $error = "Maksimal 10 video per artikel. Anda sudah punya $totalNow video.";
            } else {
                $newVideos = upload_videos($_FILES['videos'], '../../uploads/');
                if ($newVideos) {
                    $video_paths = array_merge($video_paths, $newVideos);
                } else {
                    $error = "Gagal upload salah satu video.";
                }
            }
        }

        // Hapus gambar tambahan yang dicentang
        $images_to_delete = $_POST['delete_images'] ?? [];
        foreach ($images_to_delete as $imgId) {
            $stmt = $pdo->prepare("SELECT image_path FROM article_images WHERE id = ?");
            $stmt->execute([$imgId]);
            $imgPath = $stmt->fetchColumn();
            delete_old_file($imgPath);
            $pdo->prepare("DELETE FROM article_images WHERE id = ?")->execute([$imgId]);
        }

        // Upload gambar tambahan baru
        if (!$error && !empty($_FILES['article_images']['name'][0])) {
            $total_files = count($_FILES['article_images']['name']);
            $limit = min($total_files, 10);
            for ($i = 0; $i < $limit; $i++) {
                $file = [
                    'name'     => $_FILES['article_images']['name'][$i],
                    'type'     => $_FILES['article_images']['type'][$i],
                    'tmp_name' => $_FILES['article_images']['tmp_name'][$i],
                    'error'    => $_FILES['article_images']['error'][$i],
                    'size'     => $_FILES['article_images']['size'][$i]
                ];
                $imgPath = upload_image($file, '../../uploads/');
                if ($imgPath) {
                    $ins = $pdo->prepare("INSERT INTO article_images (article_id, image_path) VALUES (?, ?)");
                    $ins->execute([$id, $imgPath]);
                }
            }
        }

        // Simpan perubahan artikel
        $video_path = implode(',', $video_paths);
        $stmt = $pdo->prepare("UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, category_id = ?, status = ?, featured_image = ?, is_premium = ?, video_path = ? WHERE id = ?");
        if ($stmt->execute([$title, $slug, $excerpt, $content, $category_id, $status, $featured_image, $is_premium, $video_path, $id])) {
            set_flash('success', 'Artikel berhasil diperbarui.');
            redirect('index.php');
        } else {
            $error = 'Gagal memperbarui artikel.';
        }
    }
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$page_title = 'Edit Artikel';
include '../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="mb-3">
        <a href="index.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <h2>Edit Artikel</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="mb-3">
            <label>Judul</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($article['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Kategori</label>
            <select name="category_id" class="form-select">
                <option value="">-- Pilih --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $article['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Ringkasan</label>
            <textarea name="excerpt" class="form-control" rows="3"><?= htmlspecialchars($article['excerpt'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label>Konten</label>
            <textarea name="content" class="form-control" rows="10" required><?= htmlspecialchars($article['content']) ?></textarea>
        </div>

        <!-- Gambar Utama -->
        <div class="mb-3">
            <label>Gambar Utama</label>
            <?php if ($article['featured_image']): ?>
                <div class="mb-2">
                    <?php $base = rtrim(BASE_URL, '/'); $path = ltrim($article['featured_image'], '/'); ?>
                    <img src="<?= $base . '/' . $path ?>" width="200" class="rounded shadow-sm border">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="delete_featured_image" id="dfi">
                    <label class="form-check-label text-danger" for="dfi">Hapus gambar utama</label>
                </div>
            <?php endif; ?>
            <input type="file" name="featured_image" class="form-control" accept="image/*">
        </div>

        <!-- Video (multiple) -->
        <div class="mb-3">
            <label>Video (Maks. 10)</label>
            <?php
            $video_paths = $article['video_path'] ? explode(',', $article['video_path']) : [];
            if ($video_paths):
            ?>
                <div class="row g-3 mb-3">
                    <?php foreach ($video_paths as $vpath): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="card p-2 border shadow-sm">
                                <?php $base = rtrim(BASE_URL, '/'); $vp = ltrim($vpath, '/'); ?>
                                <video width="100%" controls class="rounded mb-2">
                                    <source src="<?= $base . '/' . $vp ?>" type="video/mp4">
                                    Browser Anda tidak mendukung video.
                                </video>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="delete_videos[]" value="<?= htmlspecialchars($vpath) ?>" id="delv_<?= md5($vpath) ?>">
                                    <label class="form-check-label text-danger" for="delv_<?= md5($vpath) ?>">Hapus video ini</label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <input type="file" name="videos[]" class="form-control" accept="video/*" multiple>
            <small class="form-text text-muted">Pilih banyak file sekaligus (Max 10 total).</small>
        </div>

        <!-- Gambar Tambahan -->
        <div class="mb-3">
            <label>Gambar Konten Tambahan (Max 10)</label>
            <?php if ($article_images): ?>
                <div class="row g-3 mb-3">
                    <?php foreach ($article_images as $img): ?>
                        <div class="col-sm-4 col-md-3">
                            <div class="card p-2 border shadow-sm">
                                <?php $base = rtrim(BASE_URL, '/'); $ip = ltrim($img['image_path'], '/'); ?>
                                <img src="<?= $base . '/' . $ip ?>" class="card-img-top rounded" style="object-fit:cover;height:100px;">
                                <div class="card-footer text-center p-1 bg-white">
                                    <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>" id="deli_<?= $img['id'] ?>">
                                    <label class="form-check-label text-danger" for="deli_<?= $img['id'] ?>">Hapus</label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <input type="file" name="article_images[]" class="form-control" accept="image/*" multiple>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Published</option>
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="is_premium" class="form-check-input" <?= $article['is_premium'] ? 'checked' : '' ?>>
            <label class="form-check-label">Konten Premium</label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>