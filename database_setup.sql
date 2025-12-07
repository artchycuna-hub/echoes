-- Database setup for Echoes of Memories
-- Run this in phpMyAdmin or MySQL command line to set up the database

CREATE DATABASE IF NOT EXISTS challenge;
USE challenge;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    total_quizzes_completed INT DEFAULT 0,
    total_time_played INT DEFAULT 0,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

-- Quiz sessions table (main player game records)
CREATE TABLE IF NOT EXISTS quiz_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    score INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    correct_answers INT DEFAULT 0,
    incorrect_answers INT DEFAULT 0,
    time_taken INT DEFAULT 0,
    highest_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_created_at (created_at)
);

-- Player statistics table (aggregated stats)
CREATE TABLE IF NOT EXISTS player_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    total_games INT DEFAULT 0,
    total_score INT DEFAULT 0,
    highest_score INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0.00,
    total_time_played INT DEFAULT 0,
    last_played DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player (user_id, player_name),
    INDEX idx_user_id (user_id),
    INDEX idx_highest_score (highest_score)
);

-- Insert default categories
INSERT IGNORE INTO categories (name) VALUES 
('General Knowledge'),
('Science'),
('History'),
('Geography'),
('Programming'),
('Security'),
('Network'),
('Web Development');

-- Create admin user (username: admin, password: admin123)
INSERT IGNORE INTO users (username, password_hash, is_admin) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Create a view for admin to see all players
CREATE OR REPLACE VIEW admin_player_view AS
SELECT 
    qs.id,
    qs.user_id,
    u.username,
    qs.player_name,
    qs.score,
    qs.highest_score,
    qs.total_questions,
    qs.percentage,
    qs.correct_answers,
    qs.incorrect_answers,
    qs.time_taken,
    c.name AS category_name,
    qs.created_at
FROM quiz_sessions qs
JOIN users u ON qs.user_id = u.id
JOIN categories c ON qs.category_id = c.id
ORDER BY qs.created_at DESC;

-- Create a view for player summary statistics
CREATE OR REPLACE VIEW admin_player_summary AS
SELECT 
    u.id AS user_id,
    u.username,
    qs.player_name,
    COUNT(qs.id) AS total_games,
    MAX(qs.score) AS highest_score,
    AVG(qs.score) AS average_score,
    SUM(qs.time_taken) AS total_time_played,
    MAX(qs.created_at) AS last_played
FROM users u
LEFT JOIN quiz_sessions qs ON u.id = qs.user_id
WHERE qs.id IS NOT NULL
GROUP BY u.id, u.username, qs.player_name
ORDER BY highest_score DESC;

-- Grant permissions (adjust as needed for your XAMPP setup)
GRANT ALL PRIVILEGES ON challenge.* TO 'root'@'localhost';
FLUSH PRIVILEGES;

------------------------------------------------------------------------------------------


CREATE DATABASE challenge;
USE challenge;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    total_quizzes_completed INT DEFAULT 0,
    total_time_played INT DEFAULT 0,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE quiz_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    score INT DEFAULT 0,
    total_questions INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    correct_answers INT DEFAULT 0,
    incorrect_answers INT DEFAULT 0,
    time_taken INT DEFAULT 0,
    highest_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_created_at (created_at)
);

CREATE TABLE player_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    total_games INT DEFAULT 0,
    total_score INT DEFAULT 0,
    highest_score INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0.00,
    total_time_played INT DEFAULT 0,
    last_played DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_player (user_id, player_name),
    INDEX idx_user_id (user_id),
    INDEX idx_highest_score (highest_score)
);

INSERT IGNORE INTO categories (name) VALUES 
('General Knowledge'),
('Science'),
('History'),
('Geography'),
('Programming'),
('Security'),
('Network'),
('Web Development');

INSERT IGNORE INTO users (username, password_hash, is_admin) 
VALUES ('admin','admin321', 1);

CREATE OR REPLACE VIEW admin_player_view AS
SELECT 
    qs.id,
    qs.user_id,
    u.username,
    qs.player_name,
    qs.score,
    qs.highest_score,
    qs.total_questions,
    qs.percentage,
    qs.correct_answers,
    qs.incorrect_answers,
    qs.time_taken,
    c.name AS category_name,
    qs.created_at
FROM quiz_sessions qs
JOIN users u ON qs.user_id = u.id
JOIN categories c ON qs.category_id = c.id
ORDER BY qs.created_at DESC;

CREATE OR REPLACE VIEW admin_player_summary AS
SELECT 
    u.id AS user_id,
    u.username,
    qs.player_name,
    COUNT(qs.id) AS total_games,
    MAX(qs.score) AS highest_score,
    AVG(qs.score) AS average_score,
    SUM(qs.time_taken) AS total_time_played,
    MAX(qs.created_at) AS last_played
FROM users u
LEFT JOIN quiz_sessions qs ON u.id = qs.user_id
WHERE qs.id IS NOT NULL
GROUP BY u.id, u.username, qs.player_name
ORDER BY highest_score DESC;

GRANT ALL PRIVILEGES ON challenge.* TO 'root'@'localhost';
FLUSH PRIVILEGES;


