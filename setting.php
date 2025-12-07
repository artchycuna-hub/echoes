<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Add user_id to session if not set
if (!isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    include 'connection.php';
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - QuizMaster</title>
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
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .back-btn {
            position: absolute;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
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
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .settings-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }
        
        .settings-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.12);
        }
        
        .settings-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #4facfe;
        }
        
        .settings-title {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #4facfe;
        }
        
        .settings-description {
            color: #d1d1d1;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .settings-options {
            text-align: left;
            margin: 20px 0;
        }
        
        .settings-options li {
            margin-bottom: 12px;
            color: #d1d1d1;
            display: flex;
            align-items: center;
            gap: 10px;
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
            margin: 5px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-danger {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }
        
        .btn-secondary {
            background: linear-gradient(to right, #667eea, #764ba2);
        }
        
        .btn-success {
            background: linear-gradient(to right, #2ecc71, #27ae60);
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .back-btn {
                position: relative;
                margin-bottom: 15px;
                left: auto;
            }
            
            .header-content {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <a href="game.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <div>
                    <h1><i class="fas fa-cog"></i> Settings</h1>
                    <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
                    <p>Manage your account and preferences</p>
                </div>
            </div>
        </header>
        
        <div class="settings-grid">
            <div class="settings-card">
                <i class="fas fa-user-cog settings-icon"></i>
                <h2 class="settings-title">Account Settings</h2>
                <p class="settings-description">Manage your account information and preferences</p>
                
                <div class="settings-options">
                    <li><i class="fas fa-user"></i> Username: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></li>
                    <li><i class="fas fa-calendar"></i> Member since: <?php 
                        include 'connection.php';
                        $sql = "SELECT created_at FROM users WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            echo date('F j, Y', strtotime($row['created_at']));
                        }
                        $stmt->close();
                        $conn->close();
                    ?></li>
                    <li><i class="fas fa-chart-line"></i> <a href="stats_display.php" style="color: #4facfe; text-decoration: none;">View your statistics</a></li>
                </div>
                
                <div class="action-buttons">
                    <a href="stats_display.php" class="btn btn-success">
                        <i class="fas fa-chart-bar"></i> View Statistics
                    </a>
                </div>
            </div>
            
            <div class="settings-card">
                <i class="fas fa-shield-alt settings-icon"></i>
                <h2 class="settings-title">Security</h2>
                <p class="settings-description">Manage your security preferences and account access</p>
                
                <div class="settings-options">
                    <li><i class="fas fa-key"></i> Change password (coming soon)</li>
                    <li><i class="fas fa-history"></i> Login history</li>
                    <li><i class="fas fa-bell"></i> Notification preferences</li>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </div>
            
            <div class="settings-card">
                <i class="fas fa-gamepad settings-icon"></i>
                <h2 class="settings-title">Game Preferences</h2>
                <p class="settings-description">Customize your gaming experience</p>
                
                <div class="settings-options">
                    <li><i class="fas fa-volume-up"></i> Sound effects</li>
                    <li><i class="fas fa-music"></i> Background music</li>
                    <li><i class="fas fa-palette"></i> Theme customization</li>
                    <li><i class="fas fa-clock"></i> Question timer settings</li>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-sliders-h"></i> Customize
                    </button>
                </div>
            </div>
            
            <div class="settings-card">
                <i class="fas fa-sign-out-alt settings-icon"></i>
                <h2 class="settings-title">Session Management</h2>
                <p class="settings-description">Manage your current session and account access</p>
                
                <div class="settings-options">
                    <li><i class="fas fa-info-circle"></i> Current session: Active</li>
                    <li><i class="fas fa-clock"></i> Last login: <?php 
                        include 'connection.php';
                        $sql = "SELECT last_login FROM users WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            if ($row['last_login']) {
                                echo date('F j, Y g:i A', strtotime($row['last_login']));
                            } else {
                                echo 'First login';
                            }
                        }
                        $stmt->close();
                        $conn->close();
                    ?></li>
                    <li><i class="fas fa-exclamation-triangle"></i> End your current session</li>
                </div>
                
                <div class="action-buttons">
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="game.php" class="btn">
                <i class="fas fa-home"></i> Back to Main Menu
            </a>
        </div>
    </div>

    <script>
        // Add click effects to settings cards
        document.querySelectorAll('.settings-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Confirmation for logout
        document.querySelector('.btn-danger').addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to logout?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>