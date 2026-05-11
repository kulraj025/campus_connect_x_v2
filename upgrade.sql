-- ============================================================
-- Campus Connect X — UPGRADE SQL
-- Run this in phpMyAdmin on your existing campus_connect DB
-- ============================================================

USE campus_connect;

-- FOLLOWS system
CREATE TABLE IF NOT EXISTS follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uf (follower_id, following_id),
    FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
);

-- NOTIFICATIONS
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    from_user_id INT,
    type ENUM('like','follow','comment','job','tip','welcome') NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- COMMENTS on posts
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add comments_count to posts if missing
ALTER TABLE posts ADD COLUMN IF NOT EXISTS comments_count INT DEFAULT 0;

-- SERVICE ORDERS / contact requests
CREATE TABLE IF NOT EXISTS service_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    buyer_id INT NOT NULL,
    message TEXT,
    status ENUM('pending','accepted','rejected','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- JOB APPLICATIONS
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    cover_letter TEXT,
    status ENUM('pending','reviewing','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ua (job_id, user_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add avatar upload path to users if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS cover_url VARCHAR(255) AFTER avatar;

-- Welcome notification for all existing users
INSERT IGNORE INTO notifications (user_id, type, message, link)
SELECT id, 'welcome',
  CONCAT('Welcome to Campus Connect X, ', name, '! Start by completing your profile.'),
  '/profile.php'
FROM users;
