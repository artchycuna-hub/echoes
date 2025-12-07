<?php
// Add at the beginning of connection.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting - only show errors in development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production

// Custom error handler
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("Error: [$errno] $errstr in $errfile on line $errline");
    if (ini_get('display_errors')) {
        echo "<div style='color: red; padding: 10px; margin: 10px; border: 1px solid red;'>Error: $errstr</div>";
    }
}
set_error_handler("handleError");

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "challenge";

// Create connection without selecting database first
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Create database if it doesn't exist
$createDbSql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($createDbSql)) {
    error_log("Error creating database: " . $conn->error);
    die("Database setup failed.");
}

// Select the database
$conn->select_db($dbname);

// Create users table if it doesn't exist
$createUsersTable = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT DEFAULT 0,
    total_quizzes_completed INT DEFAULT 0,
    total_time_played INT DEFAULT 0,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createUsersTable)) {
    die("Error creating users table: " . $conn->error);
}

// Create categories table if it doesn't exist
$createCategoriesTable = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
)";

if (!$conn->query($createCategoriesTable)) {
    die("Error creating categories table: " . $conn->error);
}

// Insert default categories
$defaultCategories = ['history', 'science', 'geography', 'arts', 'sports', 'programming', 'security', 'network', 'web'];
foreach ($defaultCategories as $category) {
    $insertSql = "INSERT IGNORE INTO categories (name) VALUES (?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $stmt->close();
}

// Create quiz_sessions table if it doesn't exist
$createQuizSessionsTable = "CREATE TABLE IF NOT EXISTS quiz_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    category_id INT,
    player_name VARCHAR(100),
    score INT,
    total_questions INT,
    percentage DECIMAL(5,2),
    correct_answers INT,
    incorrect_answers INT,
    time_taken INT,
    highest_score INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createQuizSessionsTable)) {
    die("Error creating quiz_sessions table: " . $conn->error);
}

// ADD MULTIPLAYER ROOMS TABLE
$createMultiplayerTable = "CREATE TABLE IF NOT EXISTS multiplayer_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_code VARCHAR(6) UNIQUE NOT NULL,
    player1_id INT,
    player1_name VARCHAR(100),
    player2_id INT,
    player2_name VARCHAR(100),
    game_type VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    joined_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    FOREIGN KEY (player1_id) REFERENCES users(id),
    FOREIGN KEY (player2_id) REFERENCES users(id)
)";

if (!$conn->query($createMultiplayerTable)) {
    die("Error creating multiplayer_rooms table: " . $conn->error);
}

// Create admin user if it doesn't exist
$adminCheck = $conn->query("SELECT id FROM users WHERE username = 'admin'");
if ($adminCheck->num_rows == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $insertAdmin = "INSERT INTO users (username, password_hash, is_admin) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($insertAdmin);
    $stmt->bind_param("ss", $adminUsername, $hashedPassword);
    $adminUsername = 'admin';
    $stmt->execute();
    $stmt->close();
}

// Set charset to utf8
$conn->set_charset("utf8mb4");
?>