<?php
// includes/functions.php – fungsi umum & helper LolehRote.com

/* ---------- STRING & SLUG ---------- */
if (!function_exists('slugify')) {
    function slugify($text) {
        $text = (string) $text;
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('~[^\\pL\\pN\\s-]+~u', '', $text);
        $text = preg_replace('~[\\s-]+~u', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return empty($text) ? 'n-' . bin2hex(random_bytes(3)) : substr($text, 0, 100);
    }
}

/* ---------- CSRF PROTECTION (DITAMBAHKAN UNTUK FIX ERROR) ---------- */
// Asumsikan sesi sudah dimulai di includes/config.php atau sebelumnya
if (!function_exists('csrf_generate')) {
    function csrf_generate() {
        if (empty($_SESSION['csrf_token'])) {
            // Gunakan fungsi kriptografi yang aman untuk token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input() {
        // Output field tersembunyi
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_generate()) . '">';
    }
}

if (!function_exists('csrf_check')) {
    function csrf_check() {
        // Cek token saat POST/pengiriman form
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            // Asumsikan set_flash dan redirect sudah tersedia atau didefinisikan di auth.php
            set_flash('danger', 'Kesalahan keamanan: Token CSRF tidak valid atau hilang.');
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
            exit;
        }
    }
}


/* ---------- FLASH MESSAGE ---------- */
if (!function_exists('set_flash')) {
    function set_flash($type, $message) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('display_flash')) {
    function display_flash() {
        if (isset($_SESSION['flash'])) {
            $type = $_SESSION['flash']['type'];
            $msg  = $_SESSION['flash']['message'];
            echo '<div class="container mt-3">
                    <div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">
                        ' . htmlspecialchars($msg) . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                  </div>';
            unset($_SESSION['flash']);
        }
    }
}

/* ---------- IMAGE UPLOAD (DIREVISI: Menggunakan MIME Type untuk Keamanan & Dukungan File) ---------- */
if (!function_exists('upload_image')) {
    function upload_image($file, $target_dir) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false; // Error upload dasar
        }

        // DAFTAR JENIS MIME YANG DIIZINKAN (Mendukung semua jenis gambar umum)
        $allowed_mime_types = [
            'image/jpeg', 'image/pjpeg', 'image/png', 'image/gif', 
            'image/webp', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'
        ];
        
        // Cek MIME type menggunakan fileinfo (paling aman & terpercaya)
        if (!extension_loaded('fileinfo')) {
            // Fallback: Jika ekstensi fileinfo tidak tersedia, gunakan ekstensi file
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) return false;
            // Asumsi: Jika ekstensi diperbolehkan, lanjutkan (KURANG AMAN!)
            $mime_type = 'image/' . $ext; 
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, $allowed_mime_types)) {
                return false; // Jenis file tidak diizinkan!
            }
        }

        // Tentukan ekstensi file
        $extension = '';
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/pjpeg': $extension = '.jpg'; break;
            case 'image/png':   $extension = '.png'; break;
            case 'image/gif':   $extension = '.gif'; break;
            case 'image/webp':  $extension = '.webp'; break;
            case 'image/svg+xml': $extension = '.svg'; break;
            case 'image/x-icon':
            case 'image/vnd.microsoft.icon': $extension = '.ico'; break;
            default: $extension = '.' . pathinfo($file['name'], PATHINFO_EXTENSION); 
        }
        
        // Buat nama file unik
        $file_name = uniqid('img_', true) . $extension;
        $target_file = rtrim($target_dir, '/') . '/' . $file_name;

        // Pastikan direktori target ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true); 
        }

        // Pindahkan file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Mengembalikan path RELATIF untuk disimpan di database: 'uploads/namafile.jpg'
            // Catatan: Asumsi $target_dir di settings.php adalah '../uploads/'
            $relative_path_segment = basename(rtrim($target_dir, '/'));
            return $relative_path_segment . '/' . $file_name;
        } 
        return false;
    }
}

/* ---------- VIDEO UPLOAD ---------- */
if (!function_exists('upload_video')) {
    function upload_video($file, $target_dir) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $allowed_mime_types = ['video/mp4', 'video/webm', 'video/avi'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_mime_types)) {
            return false;
        }

        $file_name = uniqid('vid_') . basename($file['name']);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $file_name;
        }

        return false;
    }
}

if (!function_exists('upload_videos')) {
    function upload_videos($files, $target_dir) {
        $uploaded_videos = [];
        $allowed_mime_types = ['video/mp4', 'video/webm', 'video/avi'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        foreach ($files['name'] as $index => $name) {
            if ($files['error'][$index] !== UPLOAD_ERR_OK) {
                continue;
            }

            $mime_type = finfo_file($finfo, $files['tmp_name'][$index]);
            if (!in_array($mime_type, $allowed_mime_types)) {
                continue;
            }

            $file_name = uniqid('vid_') . basename($name);
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($files['tmp_name'][$index], $target_file)) {
                $uploaded_videos[] = $file_name;
            }
        }

        finfo_close($finfo);
        return $uploaded_videos;
    }
}

/* ---------- VIDEO HELPER (DITAMBAHKAN UNTUK FIX VIDEO) ---------- */

if (!function_exists('convert_to_youtube_embed')) {
    /**
     * Mengkonversi URL video mentah (YouTube/TikTok) menjadi URL embed bersih untuk iframe.
     * @param string $url URL mentah (misalnya watch?v=)
     * @param string $video_type 'youtube' atau 'tiktok'
     * @return string|false URL embed bersih atau false jika tidak valid
     */
    function convert_to_youtube_embed($url, $video_type = 'youtube') {
        // Cek jika URL sudah dalam format embed (agar tidak memproses ulang)
        if (preg_match('/^(\/\/|https?:\/\/)(www\.)?(youtube\.com\/embed\/|youtu\.be\/|tiktok\.com\/@).*/', $url)) {
            return $url; 
        }

        if ($video_type === 'youtube') {
            // Pola untuk youtube.com/watch?v= dan youtu.be/
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $match)) {
                // Mengembalikan protokol relatif agar tidak ada masalah mixed content
                return '//www.youtube.com/embed/' . $match[1] . '?rel=0'; 
            }
        } elseif ($video_type === 'tiktok') {
            // Untuk TikTok, kita asumsikan URL post penuh akan digunakan sebagai embed URL 
            // (karena TikTok embed membutuhkan skrip, kita simpan URL penuh untuk diproses di watch.php)
            if (preg_match('/^https?:\/\/(www\.)?tiktok\.com\/.+/', $url)) {
                return $url; 
            }
        }
        
        return false;
    }
}

if (!function_exists('get_video_thumbnail')) {
    /**
     * Mendapatkan URL thumbnail dari URL embed video.
     * @param string $embed_url URL embed bersih
     * @param string $video_type 'youtube' atau 'tiktok'
     * @return string|null URL thumbnail atau null
     */
    function get_video_thumbnail($embed_url, $video_type = 'youtube') {
        if ($video_type === 'youtube') {
            // Ambil ID dari URL embed YouTube (//www.youtube.com/embed/ID?rel=0)
            if (preg_match('/youtube\.com\/embed\/([^"&?\/ ]{11})/i', $embed_url, $match)) {
                $video_id = $match[1];
                // Menggunakan resolusi tertinggi yang umum tersedia
                return 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg'; 
            }
        } 
        
        // Untuk TikTok atau tipe lain, kembalikan null
        return null;
    }
}

/* ---------- DELETE OLD FILE ---------- */
if (!function_exists('delete_old_file')) {
    function delete_old_file($relativePath) {
        if (!$relativePath) return;
        
        // Menghilangkan '/' di awal jika ada
        $relativePath = ltrim($relativePath, '/'); 

        // Tentukan path mutlak: __DIR__ (lokasi includes/) + /../ (root app) + path relatif
        $full_path = __DIR__ . '/../' . $relativePath;
        
        if (file_exists($full_path) && is_file($full_path)) {
            // Cek keamanan: Hanya hapus file di folder 'uploads/'
            if (strpos($relativePath, 'uploads/') === 0) {
                unlink($full_path);
            }
        }
    }
}

/* ---------- PAGE VIEW (per artikel per date) ---------- */
if (!function_exists('log_page_view')) {
    function log_page_view($pdo, $article_id) {
        $article_id = (int) $article_id;
        $today      = date('Y-m-d');

        $sql = "INSERT INTO article_views (article_id, view_date, count)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE count = count + 1";
        $pdo->prepare($sql)->execute([$article_id, $today]);
    }
}

/* ---------- KOMENTAR ---------- */
if (!function_exists('get_comments')) {
    function get_comments($pdo, $article_id, $status = 'approved') {
        $sql = "SELECT * FROM comments
                WHERE article_id = ? AND status = ?
                ORDER BY created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$article_id, $status]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('insert_comment')) {
    function insert_comment($pdo, $article_id, $name, $email, $content, $user_id = null) {
        $stmt = $pdo->prepare("INSERT INTO comments (article_id, user_id, name, email, content, status, created_at) VALUES (?, ?, ?, ?, ?, 'approved', NOW())");
        return $stmt->execute([$article_id, $user_id, $name, $email, $content]);
    }
}

/* ---------- REAKSI (SUPER/LIKE) ---------- */

/**
 * Mendapatkan jumlah 'like' dan 'dislike' untuk sebuah artikel.
 * Digunakan untuk mengetahui jumlah 'Super'.
 *
 * @return array ['like' => count, 'dislike' => count]
 */
if (!function_exists('get_article_reactions')) {
    function get_article_reactions($pdo, $article_id) {
        $sql = "SELECT reaction, COUNT(id) as count
                FROM article_reactions
                WHERE article_id = :article_id
                GROUP BY reaction";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':article_id', (int)$article_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'like'      => (int)($results['like'] ?? 0), 
            'dislike' => (int)($results['dislike'] ?? 0)
        ];
    }
}

/**
 * Mengecek apakah pengguna (berdasarkan IP) telah memberikan reaksi ('like')
 *
 * @return string|null Mengembalikan 'like', 'dislike', atau null
 */
if (!function_exists('user_has_reacted')) {
    function user_has_reacted($pdo, $article_id, $ip) {
        $stmt = $pdo->prepare("SELECT reaction FROM article_reactions
                               WHERE article_id = ? AND ip_address = ?");
        $stmt->execute([$article_id, $ip]);
        return $stmt->fetchColumn() ?: null; 
    }
}

/**
 * Menyimpan atau mengubah reaksi pengguna.
 */
if (!function_exists('insert_reaction')) {
    function insert_reaction($pdo, $article_id, $ip, $reaction) {
        // Karena kita hanya mendukung 'like' (Super), reaction pasti 'like'
        $stmt = $pdo->prepare("INSERT INTO article_reactions (article_id, ip_address, reaction, created_at)
                               VALUES (?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE reaction = ?");
        return $stmt->execute([$article_id, $ip, $reaction, $reaction]);
    }
}

/**
 * Menghapus reaksi pengguna (REMOVE).
 */
if (!function_exists('remove_reaction')) {
    function remove_reaction($pdo, $article_id, $ip) {
        // Hapus hanya reaksi 'like' (Super)
        $stmt = $pdo->prepare("DELETE FROM article_reactions WHERE article_id = ? AND ip_address = ? AND reaction = 'like'");
        return $stmt->execute([$article_id, $ip]);
    }
}

/**
 * [FUNGSI UTAMA] Menangani semua logika reaksi (HANYA LIKE/SUPER: ADD, REMOVE)
 * dan mengembalikan status serta hitungan terbaru.
 */
if (!function_exists('handle_reaction')) {
    function handle_reaction($pdo, $article_id, $reaction_type, $ip_address) {
        
        // Hanya izinkan reaksi 'like'
        if ($reaction_type !== 'like') {
            return [
                'status' => 'error', 
                'message' => 'Reaksi yang diizinkan hanya Like (Super).'
            ];
        }

        $current_reaction = user_has_reacted($pdo, $article_id, $ip_address);

        $pdo->beginTransaction();
        try {
            $new_state = '';
            
            if ($current_reaction === 'like') {
                // 1. User mengklik SUPER lagi (HAPUS/REMOVE)
                remove_reaction($pdo, $article_id, $ip_address);
                $message = "Reaksi Super berhasil dihapus.";
                $new_state = 'removed';
            } else {
                // 2. User belum bereaksi (TAMBAH/ADD)
                // Ini juga akan menimpa reaksi 'dislike' lama jika ada
                insert_reaction($pdo, $article_id, $ip_address, 'like');
                $message = "Terima kasih atas Super (Suka) Anda.";
                $new_state = 'added';
            }

            // [PENTING] Pastikan data DISLIKE yang lama dihapus (jika ada) untuk pembersihan data
            $pdo->prepare("DELETE FROM article_reactions WHERE article_id = ? AND ip_address = ? AND reaction = 'dislike'")
                ->execute([$article_id, $ip_address]);

            $pdo->commit();
            
            // Ambil hitungan terbaru setelah transaksi
            $reactions = get_article_reactions($pdo, $article_id);
            
            return [
                'status' => 'success', 
                'message' => $message, 
                'likes' => $reactions['like'], 
                'dislikes' => 0, // Dipaksakan nol karena fitur dislike dihapus
                'new_state' => $new_state
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            return [
                'status' => 'error', 
                'message' => 'Gagal memproses reaksi: ' . $e->getMessage()
            ];
        }
    }
}


/* ---------- USER & PREMIUM ---------- */
if (!function_exists('has_premium_access')) {
    function has_premium_access($pdo, $article_id, $user_id) {
        $stmt = $pdo->prepare("SELECT is_premium FROM articles WHERE id = ? AND status IN ('published','premium')");
        $stmt->execute([$article_id]);
        $article = $stmt->fetch();
        if (!$article || !$article['is_premium']) return true;
        if (!$user_id) return false;
        $pay = $pdo->prepare("SELECT 1 FROM payments
                              WHERE user_id = ? AND article_id = ? AND status = 'success'
                              LIMIT 1");
        $pay->execute([$user_id, $article_id]);
        return (bool) $pay->fetchColumn();
    }
}

/* ---------- ANALYTICS ---------- */
if (!function_exists('get_analytics_summary')) {
    function get_analytics_summary($pdo, $days) {
        $date = date('Y-m-d', strtotime("-$days days"));
        $stmt = $pdo->prepare("SELECT SUM(count) FROM article_views WHERE view_date >= ?");
        $stmt->execute([$date]);
        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('get_top_articles')) {
    function get_top_articles($pdo, $limit = 10) {
        $sql = "SELECT a.id, a.title, a.slug, SUM(v.count) AS views
                FROM articles a
                LEFT JOIN article_views v ON a.id = v.article_id
                WHERE a.status = 'published'
                GROUP BY a.id, a.title, a.slug
                ORDER BY views DESC
                LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ---------- NEWSLETTER ---------- */
if (!function_exists('subscribe_newsletter')) {
    function subscribe_newsletter($pdo, $email) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
        return $stmt->execute([$email]);
    }
}

/* ---------- LIVE SEARCH ---------- */
if (!function_exists('live_search_articles')) {
    function live_search_articles($pdo, $keyword) {
        $kw = "%$keyword%";
        $sql = "SELECT a.title, a.slug, a.excerpt, a.content, a.created_at,
                       u.username AS author_name, c.name AS category_name, a.featured_image
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE (a.title LIKE :kw OR a.excerpt LIKE :kw2 OR a.content LIKE :kw3)
                  AND a.status = 'published'
                ORDER BY a.created_at DESC
                LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':kw',  $kw, PDO::PARAM_STR);
        $stmt->bindValue(':kw2', $kw, PDO::PARAM_STR);
        $stmt->bindValue(':kw3', $kw, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}