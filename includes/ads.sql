-- ============================================
-- Campus Connect X — Ads System
-- Run in phpMyAdmin on campus_connectv3
-- ============================================

USE campus_connectv3;

CREATE TABLE IF NOT EXISTS ads (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    subtitle    VARCHAR(255),
    body        TEXT,
    cta_text    VARCHAR(100) DEFAULT 'Learn More',
    cta_link    VARCHAR(500),
    image_path  VARCHAR(255),
    type        ENUM('info','news','promo','event','tip','alert') DEFAULT 'info',
    placement   SET('login','dashboard','community','sidebar') DEFAULT 'login,dashboard',
    bg_from     VARCHAR(20) DEFAULT '#1E3A5F',
    bg_to       VARCHAR(20) DEFAULT '#2D1B69',
    text_color  VARCHAR(20) DEFAULT '#FFFFFF',
    is_active   TINYINT(1) DEFAULT 1,
    start_date  DATE,
    end_date    DATE,
    click_count INT DEFAULT 0,
    view_count  INT DEFAULT 0,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sample ads
INSERT INTO ads (title, subtitle, body, cta_text, cta_link, type, placement, bg_from, bg_to) VALUES
('Welcome to Campus Connect X', 'Your Student Ecosystem', 'Connect with verified students, find internships, sell your skills and build your career — all in one place.', 'Get Started', '/register.php', 'info', 'login,dashboard', '#0F172A', '#1E3A5F'),
('New Feature: CV Builder', 'Generate a Professional CV in Seconds', 'Fill your profile once and auto-generate a Word-standard CV. Download as PDF instantly.', 'Build Your CV', '/cv.php', 'news', 'login,dashboard', '#1E3A5F', '#2D1B69'),
('Abroad Hub is Live 🌍', 'Survival Guides for Students Going Abroad', 'Visa tips, housing guides, scam alerts and country-specific survival guides — all from real students.', 'Explore Abroad Hub', '/abroad.php', 'promo', 'login,dashboard', '#064E3B', '#065F46'),
('Find Your Dream Internship', 'Career Hub — Student Jobs Board', 'Browse internships, part-time jobs and student gigs. Post opportunities for free.', 'Browse Jobs', '/career.php', 'event', 'login,dashboard', '#1E2A4A', '#2563EB'),
('Skill Marketplace is Open 💼', 'Earn Money from Your Skills', 'Design, code, tutor or translate — list your services and get hired by fellow students.', 'List a Service', '/marketplace.php', 'promo', 'login,dashboard', '#2D1B69', '#4C1D95'),
('Share Your Campus Story', 'Join the Community Feed', 'Post your thoughts, share skills, connect with peers at your university and beyond.', 'Join Community', '/community.php', 'info', 'dashboard', '#0C4A6E', '#0EA5E9');
