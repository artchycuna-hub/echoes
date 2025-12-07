<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Redirect admin users to dashboard
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: admin/dashboard.php");
    exit();
}

// Add user_id to session if not set
if (!isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    include 'connection.php';
    $sql = "SELECT id, email FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['email'] = $row['email'];
    }
    $stmt->close();
    $conn->close();
}

// Fetch user statistics for achievements
include 'connection.php';
$user_id = $_SESSION['user_id'];

// Get total games played - FIXED: Changed game_sessions to quiz_sessions
$sql_games = "SELECT COUNT(*) as total_games FROM quiz_sessions WHERE user_id = ?";
$stmt_games = $conn->prepare($sql_games);
$stmt_games->bind_param("i", $user_id);
$stmt_games->execute();
$result_games = $stmt_games->get_result();
$games_data = $result_games->fetch_assoc();
$total_games = $games_data['total_games'];
$stmt_games->close();

// Get average score - FIXED: Changed game_sessions to quiz_sessions
$sql_score = "SELECT AVG(score) as avg_score FROM quiz_sessions WHERE user_id = ?";
$stmt_score = $conn->prepare($sql_score);
$stmt_score->bind_param("i", $user_id);
$stmt_score->execute();
$result_score = $stmt_score->get_result();
$score_data = $result_score->fetch_assoc();
$avg_score = $score_data['avg_score'] ? round($score_data['avg_score'], 2) : 0;
$stmt_score->close();

// Get highest score - FIXED: Changed game_sessions to quiz_sessions
$sql_high = "SELECT MAX(score) as high_score FROM quiz_sessions WHERE user_id = ?";
$stmt_high = $conn->prepare($sql_high);
$stmt_high->bind_param("i", $user_id);
$stmt_high->execute();
$result_high = $stmt_high->get_result();
$high_data = $result_high->fetch_assoc();
$high_score = $high_data['high_score'] ? $high_data['high_score'] : 0;
$stmt_high->close();

// Get recent games - FIXED: Changed game_sessions to quiz_sessions and adjusted columns
$sql_recent = "SELECT 
                c.name as game_type, 
                qs.score, 
                qs.created_at as played_at 
               FROM quiz_sessions qs 
               JOIN categories c ON qs.category_id = c.id 
               WHERE qs.user_id = ? 
               ORDER BY qs.created_at DESC 
               LIMIT 5";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();
$result_recent = $stmt_recent->get_result();
$recent_games = [];
while ($row = $result_recent->fetch_assoc()) {
    $recent_games[] = $row;
}
$stmt_recent->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Your Quiz - QuizMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a3a, #2d3e50);
            min-height: 100vh;
            color: #fff;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
            position: relative;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100vh;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            z-index: 1000;
            padding: 20px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
            overflow-y: auto;
        }
        
        .sidebar.active {
            right: 0;
        }
        
        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-title {
            font-size: 1.5rem;
            color: #4facfe;
        }
        
        .close-sidebar {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close-sidebar:hover {
            color: #4facfe;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        .user-avatar {
            font-size: 3rem;
            color: #4facfe;
            margin-bottom: 10px;
        }
        
        .user-name {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        .user-email {
            color: #d1d1d1;
            font-size: 0.9rem;
        }
        
        .sidebar-menu {
            list-style: none;
            margin-bottom: 30px;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover {
            background: rgba(79, 172, 254, 0.2);
            transform: translateX(5px);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Player Mode Buttons */
        .player-mode {
            margin-bottom: 25px;
        }
        
        .player-mode-title {
            font-size: 1.1rem;
            color: #4facfe;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }
        
        .player-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .player-btn {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
            border-radius: 8px;
            padding: 12px 15px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .player-btn:hover {
            background: rgba(79, 172, 254, 0.2);
            transform: translateY(-2px);
        }
        
        .player-btn.active {
            background: rgba(79, 172, 254, 0.3);
            border-color: #4facfe;
            box-shadow: 0 0 15px rgba(79, 172, 254, 0.4);
        }
        
        .player-btn i {
            font-size: 1.2rem;
        }
        
        .sidebar-logout {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            border: none;
            border-radius: 8px;
            padding: 12px 15px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-logout i {
            margin-right: 10px;
        }
        
        .menu-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.4);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            z-index: 999;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .menu-toggle:hover {
            background: rgba(79, 172, 254, 0.8);
            transform: scale(1.1);
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .welcome {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #d1d1d1;
        }
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .game-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .game-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.12);
        }
        
        .game-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #4facfe;
        }
        
        .game-title {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #4facfe;
        }
        
        .game-description {
            color: #d1d1d1;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .game-features {
            text-align: left;
            margin: 20px 0;
        }
        
        .game-features li {
            margin-bottom: 8px;
            color: #d1d1d1;
        }
        
        .btn {
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .logout-btn {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            margin-top: 20px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1001;
            overflow-y: auto;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #1a2a3a, #2d3e50);
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.5);
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.8rem;
            color: #fff;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: #4facfe;
        }
        
        .modal-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #4facfe;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d1d1d1;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4facfe;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #4facfe;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #d1d1d1;
            font-size: 1rem;
        }
        
        .recent-games {
            margin-top: 30px;
        }
        
        .game-history {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .game-history th, .game-history td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .game-history th {
            color: #4facfe;
            font-weight: 600;
        }
        
        .game-history tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Two Player Modal */
        .player-input-group {
            margin-bottom: 20px;
        }
        
        .player-input-group label {
            display: block;
            margin-bottom: 8px;
            color: #d1d1d1;
            font-weight: 600;
        }
        
        .player-input {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        
        .player-input:focus {
            outline: none;
            border-color: #4facfe;
        }
        
        @media (max-width: 768px) {
            .games-grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .sidebar {
                width: 280px;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
            }
            
            .player-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3 class="sidebar-title">User Profile</h3>
            <button class="close-sidebar" id="closeSidebar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <div class="user-email"><?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'user@example.com'; ?></div>
        </div>
        
        <!-- Player Mode Selection -->
        <div class="player-mode">
            <div class="player-mode-title">
                <i class="fas fa-users"></i> Game Mode
            </div>
            <div class="player-buttons">
                <button class="player-btn active" id="singlePlayerBtn" data-mode="1player">
                    <i class="fas fa-user"></i>
                    <span>1 Player</span>
                </button>
                <button class="player-btn" id="multiPlayerBtn" data-mode="2player">
                    <i class="fas fa-users"></i>
                    <span>2 Players</span>
                </button>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="#" id="editProfileBtn">
                    <i class="fas fa-user-edit"></i>
                    <span>Edit Profile</span>
                </a>
            </li>
            <li>
                <a href="#" id="accountSettingsBtn">
                    <i class="fas fa-cog"></i>
                    <span>Account Settings</span>
                </a>
            </li>
            <li>
                <a href="#" id="achievementsBtn">
                    <i class="fas fa-trophy"></i>
                    <span>Achievements</span>
                </a>
            </li>
            <li>
                <a href="stats_display.php" id="statsBtn">
                    <i class="fas fa-chart-bar"></i>
                    <span>Statistics</span>
                </a>
            </li>
            <!-- ADD MULTIPLAYER OPTION HERE -->
            <li>
                <a href="multiplayer.php" id="multiplayerBtn">
                    <i class="fas fa-users"></i>
                    <span>Multiplayer</span>
                </a>
            </li>
        </ul>
        
        <a href="logout.php" class="sidebar-logout">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
    
    <div class="container">
        <header>
            <h1><i class="fas fa-gamepad"></i> Echoes of Memories</h1>
            <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
            <p>Choose your quiz challenge below</p>
            <div id="playerModeIndicator" style="margin-top: 10px; color: #4facfe; font-weight: 600;">
                <i class="fas fa-user"></i> Playing in 1 Player Mode
            </div>
        </header>
        
        <div class="games-grid">
            <div class="game-card" onclick="startGame('game1.php')">
                <i class="fas fa-globe-americas game-icon"></i>
                <h2 class="game-title">General Knowledge Quiz</h2>
                <p class="game-description">Test your knowledge across various topics including history, science, geography, and more!</p>
                <div class="game-features">
                    <li><i class="fas fa-check"></i> 5 categories to choose from</li>
                    <li><i class="fas fa-check"></i> 30-second timer per question</li>
                    <li><i class="fas fa-check"></i> Multiple difficulty levels</li>
                </div>
                <button class="btn">Play Now</button>
            </div>
            
            <div class="game-card" onclick="startGame('itgame.php')">
                <i class="fas fa-laptop-code game-icon"></i>
                <h2 class="game-title">IT Knowledge Challenge</h2>
                <p class="game-description">Prove your tech expertise with questions on programming, cybersecurity, networking, and web technologies.</p>
                <div class="game-features">
                    <li><i class="fas fa-check"></i> 4 IT categories</li>
                    <li><i class="fas fa-check"></i> 10 questions per category</li>
                    <li><i class="fas fa-check"></i> Real-time scoring</li>
                </div>
                <button class="btn">Play Now</button>
            </div>

            <!-- ADD MULTIPLAYER GAME CARD -->
            <div class="game-card" onclick="location.href='multiplayer.php'">
                <i class="fas fa-users game-icon"></i>
                <h2 class="game-title">Multiplayer Challenge</h2>
                <p class="game-description">Challenge your friends in real-time quiz battles! Create or join rooms with 6-digit codes.</p>
                <div class="game-features">
                    <li><i class="fas fa-check"></i> Play with friends in real-time</li>
                    <li><i class="fas fa-check"></i> 6-digit room codes</li>
                    <li><i class="fas fa-check"></i> Live score tracking</li>
                    <li><i class="fas fa-check"></i> Choose between General or IT quizzes</li>
                </div>
                <button class="btn">Play Multiplayer</button>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <span class="close-modal" id="closeEditProfile">&times;</span>
            <h2 class="modal-title">Edit Profile</h2>
            <form id="editProfileForm" action="update_profile.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : ''; ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Account Settings Modal -->
    <div class="modal" id="accountSettingsModal">
        <div class="modal-content">
            <span class="close-modal" id="closeAccountSettings">&times;</span>
            <h2 class="modal-title">Account Settings</h2>
            <form id="accountSettingsForm" action="update_settings.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="notifications">
                        <input type="checkbox" id="notifications" name="notifications" checked> Enable Email Notifications
                    </label>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Achievements Modal -->
    <div class="modal" id="achievementsModal">
        <div class="modal-content">
            <span class="close-modal" id="closeAchievements">&times;</span>
            <h2 class="modal-title">Your Achievements</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_games; ?></div>
                    <div class="stat-label">Total Games Played</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $avg_score; ?></div>
                    <div class="stat-label">Average Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $high_score; ?></div>
                    <div class="stat-label">Highest Score</div>
                </div>
            </div>
            
            <div class="recent-games">
                <h3 style="color: #4facfe; margin-bottom: 15px;">Recent Games</h3>
                <?php if (count($recent_games) > 0): ?>
                    <table class="game-history">
                        <thead>
                            <tr>
                                <th>Game Type</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_games as $game): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($game['game_type']); ?></td>
                                    <td><?php echo htmlspecialchars($game['score']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($game['played_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #d1d1d1; text-align: center;">No games played yet. Start playing to see your achievements!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Two Player Setup Modal -->
    <div class="modal" id="twoPlayerModal">
        <div class="modal-content">
            <span class="close-modal" id="closeTwoPlayer">&times;</span>
            <h2 class="modal-title"><i class="fas fa-users"></i> Two Player Setup</h2>
            
            <form id="twoPlayerForm">
                <div class="player-input-group">
                    <label for="player1Name">Player 1 Name</label>
                    <input type="text" id="player1Name" class="player-input" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                </div>
                
                <div class="player-input-group">
                    <label for="player2Name">Player 2 Name</label>
                    <input type="text" id="player2Name" class="player-input" placeholder="Enter second player name" required>
                </div>
                
                <div class="form-group" style="text-align: center;">
                    <button type="submit" class="btn">Start 2 Player Game</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Game state
        let currentGameMode = '1player';
        let playerNames = {
            player1: '<?php echo htmlspecialchars($_SESSION['username']); ?>',
            player2: ''
        };

        // Sidebar functionality
        const menuToggle = document.getElementById('menuToggle');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
        
        // Player mode selection
        const singlePlayerBtn = document.getElementById('singlePlayerBtn');
        const multiPlayerBtn = document.getElementById('multiPlayerBtn');
        const playerModeIndicator = document.getElementById('playerModeIndicator');
        
        singlePlayerBtn.addEventListener('click', function() {
            setPlayerMode('1player');
        });
        
        multiPlayerBtn.addEventListener('click', function() {
            setPlayerMode('2player');
        });
        
        function setPlayerMode(mode) {
            currentGameMode = mode;
            
            // Update button states
            singlePlayerBtn.classList.toggle('active', mode === '1player');
            multiPlayerBtn.classList.toggle('active', mode === '2player');
            
            // Update indicator
            if (mode === '1player') {
                playerModeIndicator.innerHTML = '<i class="fas fa-user"></i> Playing in 1 Player Mode';
            } else {
                playerModeIndicator.innerHTML = '<i class="fas fa-users"></i> Playing in 2 Player Mode';
            }
            
            // Close sidebar when mode is selected
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Game start function
        function startGame(gameUrl) {
            if (currentGameMode === '2player') {
                // Show two player setup modal
                const twoPlayerModal = document.getElementById('twoPlayerModal');
                twoPlayerModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Handle two player form submission
                const twoPlayerForm = document.getElementById('twoPlayerForm');
                twoPlayerForm.onsubmit = function(e) {
                    e.preventDefault();
                    const player1Name = document.getElementById('player1Name').value;
                    const player2Name = document.getElementById('player2Name').value;
                    
                    if (player1Name && player2Name) {
                        playerNames.player1 = player1Name;
                        playerNames.player2 = player2Name;
                        
                        // Store player names in sessionStorage for the game page
                        sessionStorage.setItem('gameMode', '2player');
                        sessionStorage.setItem('player1Name', player1Name);
                        sessionStorage.setItem('player2Name', player2Name);
                        
                        // Redirect to game
                        window.location.href = gameUrl + '?mode=2player';
                    }
                };
            } else {
                // Single player - redirect directly
                sessionStorage.setItem('gameMode', '1player');
                sessionStorage.setItem('player1Name', playerNames.player1);
                window.location.href = gameUrl;
            }
        }
        
        // Add click effects to game cards
        document.querySelectorAll('.game-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
        
        // Close sidebar when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = 'auto';
                
                // Also close any open modals
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
        
        // Modal functionality
        const editProfileBtn = document.getElementById('editProfileBtn');
        const accountSettingsBtn = document.getElementById('accountSettingsBtn');
        const achievementsBtn = document.getElementById('achievementsBtn');
        
        const editProfileModal = document.getElementById('editProfileModal');
        const accountSettingsModal = document.getElementById('accountSettingsModal');
        const achievementsModal = document.getElementById('achievementsModal');
        const twoPlayerModal = document.getElementById('twoPlayerModal');
        
        const closeEditProfile = document.getElementById('closeEditProfile');
        const closeAccountSettings = document.getElementById('closeAccountSettings');
        const closeAchievements = document.getElementById('closeAchievements');
        const closeTwoPlayer = document.getElementById('closeTwoPlayer');
        
        // Open modals
        editProfileBtn.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            editProfileModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
        
        accountSettingsBtn.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            accountSettingsModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
        
        achievementsBtn.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            achievementsModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
        
        // Close modals
        closeEditProfile.addEventListener('click', function() {
            editProfileModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        closeAccountSettings.addEventListener('click', function() {
            accountSettingsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        closeAchievements.addEventListener('click', function() {
            achievementsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        closeTwoPlayer.addEventListener('click', function() {
            twoPlayerModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editProfileModal) {
                editProfileModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            if (event.target === accountSettingsModal) {
                accountSettingsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            if (event.target === achievementsModal) {
                achievementsModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            if (event.target === twoPlayerModal) {
                twoPlayerModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Form validation for account settings
        const accountSettingsForm = document.getElementById('accountSettingsForm');
        accountSettingsForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New password and confirmation do not match!');
            }
        });
    </script>
</body>
</html>