-- =====================================================================
-- Fan Hub Plus - Database Schema
-- Project: Fan Hub Plus (TechWiz - Fandom Universe)
-- Import this file once in phpMyAdmin (or `mysql -u root fanhub < fanhub.sql`)
-- It creates the database, all tables, and seeds the 8 fixed categories.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS fanhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fanhub;

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(500) DEFAULT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    bio VARCHAR(255) DEFAULT NULL,
    dark_mode TINYINT(1) NOT NULL DEFAULT 1,
    font_size ENUM('small','medium','large') NOT NULL DEFAULT 'medium',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES (the 8 fandom categories from the SRS)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(10) DEFAULT NULL,
    color VARCHAR(20) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- USER <-> CATEGORY (favorite fandoms, many-to-many)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_favorite_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    UNIQUE KEY uniq_user_category (user_id, category_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONTENT (articles, videos, audio, images - the main library)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS content (
    content_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('article','video','audio','image') NOT NULL DEFAULT 'article',
    description TEXT,
    body LONGTEXT DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT NULL,
    audio_url VARCHAR(500) DEFAULT NULL,
    genre VARCHAR(150) DEFAULT NULL,
    tag VARCHAR(50) DEFAULT NULL,
    release_date DATE DEFAULT NULL,
    popularity_score INT NOT NULL DEFAULT 0,
    view_count INT NOT NULL DEFAULT 0,
    source_api VARCHAR(50) DEFAULT NULL,
    external_id VARCHAR(100) DEFAULT NULL,
    status ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved',
    submitted_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CHARACTER PROFILES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS character_profiles (
    character_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    bio TEXT,
    image_url VARCHAR(500) DEFAULT NULL,
    source_api VARCHAR(50) DEFAULT NULL,
    external_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MERCHANDISE ITEMS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS merchandise_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    tag VARCHAR(50) DEFAULT NULL,
    is_upcoming TINYINT(1) NOT NULL DEFAULT 0,
    view_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BOOKMARKS (works for content, character_profiles or merchandise_items)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookmarks (
    bookmark_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_type ENUM('content','character','merchandise') NOT NULL DEFAULT 'content',
    item_id INT NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_bookmark (user_id, item_type, item_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONTENT RATINGS (5-star / thumbs up-down on media)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS content_ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    rating TINYINT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_rating (user_id, content_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES content(content_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CHATBOT FAQ KNOWLEDGE BASE
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chatbot_faqs (
    faq_id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    keywords VARCHAR(255) DEFAULT NULL,
    category_id INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CHATBOT CONVERSATION LOG
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chatbot_queries (
    query_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    response TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FEEDBACK
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    type ENUM('bug','suggestion','query') NOT NULL DEFAULT 'suggestion',
    message TEXT NOT NULL,
    status ENUM('new','reviewed','resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVENTS (conventions, meetups, screenings - map + calendar)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT DEFAULT NULL,
    city VARCHAR(100) NOT NULL,
    venue VARCHAR(200) DEFAULT NULL,
    lat DECIMAL(10,7) DEFAULT NULL,
    lng DECIMAL(10,7) DEFAULT NULL,
    event_date DATE NOT NULL,
    ticket_link VARCHAR(500) DEFAULT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA: the 8 fixed fandom categories
-- =====================================================================
INSERT INTO categories (slug, name, description, icon, color) VALUES
('anime',    'Anime',    'Japanese animated series and films.',        '🎬', '#FF3D9A'),
('gaming',   'Gaming',   'Video games across every platform.',         '🎮', '#22E4FF'),
('movies',   'Movies',   'Blockbusters, cult classics and indie hits.', '🎥', '#7A5CFF'),
('tv-shows', 'TV Shows', 'Binge-worthy series and dramas.',            '📺', '#FF9A3D'),
('kpop',     'K-Pop',    'Korean pop groups, idols and music.',        '🎤', '#FF3D9A'),
('comics',   'Comics',   'Superheroes and comic book universes.',      '📖', '#22E4FF'),
('manga',    'Manga',    'Japanese comics and light novels.',          '📚', '#7A5CFF'),
('cosplay',  'Cosplay',  'Costume artistry and fan performances.',     '🎭', '#FF9A3D')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Default admin account (password: Admin@123 - change after first login)
-- password_hash below is a real bcrypt hash for "Admin@123"
INSERT INTO users (name, email, password_hash, role) VALUES
('Fan Hub Admin', 'admin@fanhub.local', '$2y$10$kDhjJ.klTxrTspgqaSLroOoKZgL9sK1j9.X8FvAo5LyeMXSHYZx9O', 'admin')
ON DUPLICATE KEY UPDATE name = VALUES(name);
