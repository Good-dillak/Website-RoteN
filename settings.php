<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_admin();

// Proses update
if ($_POST) {
    csrf_check();

    // TANGKAP SEMUA VARIABEL FORM
    $site_name = trim($_POST['site_name']);
    $hero_description = trim($_POST['hero_description']); 
    $hero_tagline = trim($_POST['hero_tagline']); 
    $hero_main_title = trim($_POST['hero_main_title']); 
    $logo_path = $_POST['existing_logo'];

    // Upload logo baru
    if (!empty($_FILES['logo']['name'])) {
        $file = $_FILES['logo'];
        $upload = upload_image($file, '../uploads/');
        if ($upload) {
            // Hapus logo lama jika ada
            $old = $pdo->query("SELECT value FROM settings WHERE key_name = 'logo_path'")->fetchColumn();
            if ($old && strpos($old, 'uploads/') === 0) {
                delete_old_file($old);
            }
            $logo_path = $upload;
        } else {
            set_flash('danger', 'Logo gagal diunggah.');
            redirect('settings.php');
        }
    }

    // Simpan ke DB (Urutan di DB tidak harus sama dengan urutan form)
    $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES ('site_name', ?)")->execute([$site_name]);
    $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES ('hero_main_title', ?)")->execute([$hero_main_title]);
    $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES ('hero_tagline', ?)")->execute([$hero_tagline]);
    $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES ('hero_description', ?)")->execute([$hero_description]); 
    $pdo->prepare("REPLACE INTO settings (key_name, value) VALUES ('logo_path', ?)")->execute([$logo_path]);

    set_flash('success', 'Pengaturan berhasil disimpan.');
    redirect('settings.php');
}

// Ambil nilai saat ini dari DB
$site_name = $pdo->query("SELECT value FROM settings WHERE key_name = 'site_name'")->fetchColumn() ?: 'NusaRoteMalole';
$hero_main_title = $pdo->query("SELECT value FROM settings WHERE key_name = 'hero_main_title'")->fetchColumn() ?: 'Selamat Datang di NusaRoteMalole';
$hero_tagline = $pdo->query("SELECT value FROM settings WHERE key_name = 'hero_tagline'")->fetchColumn() ?: 'Portal berita & informasi terkini seputar Rote, NTT.';
$hero_description = $pdo->query("SELECT value FROM settings WHERE key_name = 'hero_description'")->fetchColumn() ?: 'Selamat Datang! Temukan berita terbaru dari Pulau Rote.'; 
$logo_path = $pdo->query("SELECT value FROM settings WHERE key_name = 'logo_path'")->fetchColumn() ?: 'assets/img/logo.png';

include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <h2><i class="bi bi-gear-fill"></i> Pengaturan Portal</h2>
    <?= display_flash() ?>

    <form method="post" enctype="multipart/form-data" class="card shadow-sm p-4 mt-4">
        <?= csrf_input() ?>

        <div class="mb-3">
            <label class="form-label fw-bold">Nama Portal</label>
            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($site_name) ?>" required>
            
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Judul Utama</label>
            <input type="text" name="hero_main_title" class="form-control" value="<?= htmlspecialchars($hero_main_title) ?>" required>
            
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">Slogan</label>
            <textarea name="hero_tagline" class="form-control" rows="2" required><?= htmlspecialchars($hero_tagline) ?></textarea>
           
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Pengumuman</label>
            <textarea name="hero_description" class="form-control" rows="2" required><?= htmlspecialchars($hero_description) ?></textarea>
           
        </div>
        
        <hr>

        <div class="mb-3">
            <label class="form-label fw-bold">Logo Portal</label>
            <div class="mb-2">
                <img id="logo-preview" src="<?= BASE_URL . $logo_path ?>" alt="Logo" style="max-height: 100px;">
            </div>
            <input type="file" name="logo" class="form-control" accept="image/*" onchange="previewLogo(event)">
            <input type="hidden" name="existing_logo" value="<?= htmlspecialchars($logo_path) ?>">
            
        </div>

        <button class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<script>
function previewLogo(e) {
    const [file] = e.target.files;
    if (file) {
        document.getElementById('logo-preview').src = URL.createObjectURL(file);
    }
}
</script>

<?php include '../includes/footer.php'; ?>