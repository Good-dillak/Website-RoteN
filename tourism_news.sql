CREATE DATABASE tourism_news;
USE tourism_news;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE, -- Ditambahkan dari pemrosesan register.php
    password VARCHAR(255),
    full_name VARCHAR(100),
    role ENUM('admin','editor','user') DEFAULT 'user', -- Diperluas untuk user
    is_verified BOOLEAN DEFAULT FALSE, -- Untuk verifikasi email
    verification_token VARCHAR(255) NULL, -- Untuk verifikasi email
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(100) UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    content TEXT,
    excerpt TEXT,
    featured_image VARCHAR(255),
    category_id INT,
    author_id INT,
    status ENUM('draft','pending','published','premium') DEFAULT 'draft', -- Diperluas untuk 'premium'
    is_premium BOOLEAN DEFAULT FALSE, -- Field tambahan
    views INT DEFAULT 0, -- Untuk analytics
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tambahan Tabel Videos
CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    video_type ENUM('local', 'youtube') NOT NULL, -- Tipe video: upload lokal atau embed YouTube
    embed_url VARCHAR(255) NULL, -- URL embed YouTube (jika video_type='youtube')
    local_video VARCHAR(255) NULL, -- Path file video lokal (jika video_type='local')
    description TEXT,
    featured_image VARCHAR(255) NULL, -- Thumbnail/featured image
    author_id INT,
    status ENUM('draft','published') DEFAULT 'draft',
    sort_order INT DEFAULT 0, -- Untuk pengurutan manual
    views INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Tambahan untuk fitur portal (Komentar, Analytics, Menu, Pembayaran)
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    user_id INT NULL,
    parent_id INT NULL,
    name VARCHAR(100),
    email VARCHAR(100),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NULL, -- NULL untuk kunjungan umum (index, kategori)
    view_date DATE,
    count INT DEFAULT 1,
    url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (view_date, url)
);

CREATE TABLE main_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    slug VARCHAR(100) UNIQUE,
    url VARCHAR(255),
    sort_order INT
);

CREATE TABLE article_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    user_id INT,
    reaction ENUM('like', 'dislike') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (article_id, user_id)
);

CREATE TABLE admin_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE,
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    article_id INT,
    order_id VARCHAR(100) UNIQUE,
    amount DECIMAL(10, 2),
    status ENUM('pending', 'success', 'failure') DEFAULT 'pending',
    transaction_data TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE article_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    lang_code VARCHAR(10) NOT NULL,
    title VARCHAR(255) NULL,
    excerpt TEXT NULL,
    content TEXT NULL,
    UNIQUE KEY (article_id, lang_code),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
);

-- Data Default
INSERT INTO users (username, password, full_name, role, is_verified) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', TRUE);