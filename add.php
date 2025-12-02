<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_admin();

$error = '';
if ($_POST) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = "Token tidak valid!";
    } else {
        $title       = trim($_POST['title']);
        $slug        = slugify($title);
        $excerpt     = trim($_POST['excerpt']);
        $content     = trim($_POST['content']);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status      = $_POST['status'] ?? 'draft';
        $is_premium  = !empty($_POST['is_premium']) ? 1 : 0;

        // Gambar utama
        $featured_image = '';
        if (!empty($_FILES['featured_image']['name'])) {
            $featured_image = upload_image($_FILES['featured_image'], '../../uploads/');
            if (!$featured_image) {
                $error = 'Gagal upload gambar utama.';
            }
        }

        // Video (multiple)
        $video_paths = [];
        if (!$error && !empty($_FILES['videos']['name'][0])) {
            $totalNew = count($_FILES['videos']['name']);
            if ($totalNew > 10) {
                $error = "Maksimal 10 video per artikel.";
            } else {
                $video_paths = upload_videos($_FILES['videos'], '../../uploads/');
                if (!$video_paths) {
                    $error = "Gagal upload salah satu video.";
                }
            }
        }

        // Gambar tambahan (multiple)
        $article_images = [];
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
                    $article_images[] = $imgPath;
                }
            }
        }

        // Simpan ke DB
        if (!$error) {
            $pdo->beginTransaction();
            try {
                // Insert artikel
                $stmt = $pdo->prepare("INSERT INTO articles 
                    (title, slug, excerpt, content, category_id, author_id, status, featured_image, is_premium, video_path) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title, $slug, $excerpt, $content, $category_id, $_SESSION['user_id'],
                    $status, $featured_image, $is_premium, implode(',', $video_paths)
                ]);
                $article_id = $pdo->lastInsertId();

                // Insert gambar tambahan
                if ($article_images) {
                    $ins = $pdo->prepare("INSERT INTO article_images (article_id, image_path) VALUES (?, ?)");
                    foreach ($article_images as $path) {
                        $ins->execute([$article_id, $path]);
                    }
                }

                $pdo->commit();
                set_flash('success', 'Artikel berhasil dibuat.');
                redirect('edit.php?id=' . $article_id);
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$page_title = 'Tambah Artikel';
include '../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="mb-3">
        <a href="index.php" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>

    <h2>Tambah Artikel</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="mb-3">
            <label>Judul Artikel <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Kategori</label>
            <select name="category_id" class="form-select">
                <option value="">-- Pilih --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Ringkasan</label>
            <textarea name="excerpt" class="form-control" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label>Konten <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control" rows="10" required></textarea>
        </div>

        <!-- Gambar Utama -->
        <div class="mb-3">
            <label>Gambar Utama</label>
            <input type="file" name="featured_image" class="form-control" accept="image/*">
            <small class="form-text text-muted">Mendukung JPG, PNG, GIF, WEBP, dll.</small>
        </div>

        <!-- Video (multiple) -->
        <div class="mb-3">
            <label>Video (Maks. 10)</label>
            <input type="file" name="videos[]" class="form-control" accept="video/*" multiple>
            <small class="form-text text-muted">Pilih banyak file sekaligus (Max 10).</small>
        </div>

        <!-- Gambar Tambahan -->
        <div class="mb-3">
            <label>Gambar Konten Tambahan (Max 10)</label>
            <input type="file" name="article_images[]" class="form-control" accept="image/*" multiple>
            <small class="form-text text-muted">Pilih hingga 10 foto sekaligus.</small>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="is_premium" class="form-check-input">
            <label class="form-check-label">Konten Premium</label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>