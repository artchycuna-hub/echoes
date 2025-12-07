
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$user_id = $_SESSION['user_id'];

// Get user statistics for achievements
$user_stats_sql = "SELECT 
    (SELECT COUNT(*) FROM quiz_sessions WHERE user_id = ?) as total_quizzes,
    (SELECT MAX(percentage) FROM quiz_sessions WHERE user_id = ?) as best_score,
    (SELECT COUNT(DISTINCT category_id) FROM quiz_sessions WHERE user_id = ?) as categories_played,
    (SELECT SUM(time_taken) FROM quiz_sessions WHERE user_id = ?) as total_time_played,
    (SELECT COUNT(*) FROM quiz_sessions WHERE user_id = ? AND percentage >= 90) as perfect_scores,
    (SELECT COUNT(*) FROM quiz_sessions WHERE user_id = ? AND percentage >= 80) as excellent_scores,
    (SELECT COUNT(*) FROM quiz_sessions WHERE user_id = ?) as total_attempts
FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_stats_sql);
$user_stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$user_stmt->execute();
$user_stats = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Define achievements
$achievements = [
    'first_quiz' => [
        'name' => 'First Steps',
        'description' => 'Complete your first quiz',
        'icon' => 'fas fa-baby',
        'condition' => $user_stats['total_quizzes'] >= 1,
        'progress' => min($user_stats['total_quizzes'], 1),
        'target' => 1
    ],
    'quiz_master' => [
        'name' => 'Quiz Master',
        'description' => 'Complete 10 quizzes',
        'icon' => 'fas fa-graduation-cap',
        'condition' => $user_stats['total_quizzes'] >= 10,
        'progress' => min($user_stats['total_quizzes'], 10),
        'target' => 10
    ],
    'quiz_expert' => [
        'name' => 'Quiz Expert',
        'description' => 'Complete 50 quizzes',
        'icon' => 'fas fa-crown',
        'condition' => $user_stats['total_quizzes'] >= 50,
        'progress' => min($user_stats['total_quizzes'], 50),
        'target' => 50
    ],
    'perfectionist' => [
        'name' => 'Perfectionist',
        'description' => 'Score 100% on any quiz',
        'icon' => 'fas fa-star',
        'condition' => $user_stats['best_score'] >= 100,
        'progress' => $user_stats['best_score'] >= 100 ? 1 : 0,
        'target' => 1
    ],
    'excellent_student' => [
        'name' => 'Excellent Student',
        'description' => 'Score 90% or higher on 5 quizzes',
        'icon' => 'fas fa-award',
        'condition' => $user_stats['perfect_scores'] >= 5,
        'progress' => min($user_stats['perfect_scores'], 5),
        'target' => 5
    ],
    'jack_of_all_trades' => [
        'name' => 'Jack of All Trades',
        'description' => 'Play in 5 different categories',
        'icon' => 'fas fa-globe',
        'condition' => $user_stats['categories_played'] >= 5,
        'progress' => min($user_stats['categories_played'], 5),
        'target' => 5
    ],
    'marathon_runner' => [
        'name' => 'Marathon Runner',
        'description' => 'Spend 1 hour total playing quizzes',
        'icon' => 'fas fa-running',
        'condition' => $user_stats['total_time_played'] >= 3600,
        'progress' => min($user_stats['total_time_played'], 3600),
        'target' => 3600
    ],
    'speed_demon' => [
        'name' => 'Speed Demon',
        'description' => 'Complete a quiz in under 2 minutes',
        'icon' => 'fas fa-bolt',
        'condition' => false, // This would need additional tracking
        'progress' => 0,
        'target' => 1
    ],
    'consistent_player' => [
        'name' => 'Consistent Player',
        'description' => 'Play quizzes for 7 consecutive days',
        'icon' => 'fas fa-calendar-check',
        'condition' => false, // This would need additional tracking
        'progress' => 0,
        'target' => 7
    ]
];

// Calculate unlocked and total achievements
$unlocked_achievements = 0;
$total_achievements = count($achievements);
foreach ($achievements as $achievement) {
    if ($achievement['condition']) {
        $unlocked_achievements++;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - QuizMaster</title>
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
            max-width: 1200px;
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
        
        .achievement-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.12);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #4facfe;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
            color: #00f2fe;
        }
        
        .stat-label {
            color: #d1d1d1;
            font-size: 1rem;
        }
        
        .achievements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .achievement-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .achievement-card.unlocked {
            border-color: #4facfe;
            box-shadow: 0 0 20px rgba(79, 172, 254, 0.3);
        }
        
        .achievement-card.locked {
            opacity: 0.6;
        }
        
        .achievement-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.12);
        }
        
        .achievement-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .achievement-card.unlocked .achievement-icon {
            color: #4facfe;
        }
        
        .achievement-card.locked .achievement-icon {
            color: #666;
        }
        
        .achievement-name {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #4facfe;
        }
        
        .achievement-description {
            color: #d1d1d1;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .progress-container {
            margin-top: 15px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #d1d1d1;
        }
        
        .progress-bar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .locked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .achievement-card.locked:hover .locked-overlay {
            opacity: 1;
        }
        
        .locked-text {
            color: #fff;
            font-size: 1.2rem;
            font-weight: bold;
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
            margin: 10px 5px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: linear-gradient(to right, #667eea, #764ba2);
        }
        
        @media (max-width: 768px) {
            .back-btn {
                position: relative;
                margin-bottom: 15px;
                left: auto;
            }
            
            .header-content {
                flex-direction: column;
            }
            
            .achievements-grid {
                grid-template-columns: 1fr;
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
                    <h1><i class="fas fa-trophy"></i> Your Achievements</h1>
                    <p>Unlock achievements by playing quizzes and improving your skills</p>
                </div>
            </div>
        </header>
        
        <!-- Achievement Stats -->
        <div class="achievement-stats">
            <div class="stat-card">
                <i class="fas fa-trophy stat-icon"></i>
                <div class="stat-value"><?php echo $unlocked_achievements; ?>/<?php echo $total_achievements; ?></div>
                <div class="stat-label">Achievements Unlocked</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-gamepad stat-icon"></i>
                <div class="stat-value"><?php echo $user_stats['total_quizzes'] ?? 0; ?></div>
                <div class="stat-label">Quizzes Completed</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-star stat-icon"></i>
                <div class="stat-value"><?php echo $user_stats['best_score'] ?? 0; ?>%</div>
                <div class="stat-label">Best Score</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-value"><?php echo round(($user_stats['total_time_played'] ?? 0) / 3600, 1); ?>h</div>
                <div class="stat-label">Total Time Played</div>
            </div>
        </div>
        
        <!-- Achievements Grid -->
        <div class="achievements-grid">
            <?php foreach ($achievements as $key => $achievement): ?>
            <div class="achievement-card <?php echo $achievement['condition'] ? 'unlocked' : 'locked'; ?>">
                <div class="achievement-icon">
                    <i class="<?php echo $achievement['icon']; ?>"></i>
                </div>
                <h3 class="achievement-name"><?php echo $achievement['name']; ?></h3>
                <p class="achievement-description"><?php echo $achievement['description']; ?></p>
                
                <div class="progress-container">
                    <div class="progress-info">
                        <span>Progress</span>
                        <span><?php echo $achievement['progress']; ?>/<?php echo $achievement['target']; ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($achievement['progress'] / $achievement['target']) * 100; ?>%"></div>
                    </div>
                </div>
                
                <?php if (!$achievement['condition']): ?>
                <div class="locked-overlay">
                    <div class="locked-text">
                        <i class="fas fa-lock"></i> Locked
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Action Buttons -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="game.php" class="btn">
                <i class="fas fa-gamepad"></i> Play Quizzes
            </a>
            <a href="stats_display.php" class="btn btn-secondary">
                <i class="fas fa-chart-line"></i> View Statistics
            </a>
            <a href="leaderboard.php" class="btn">
                <i class="fas fa-trophy"></i> View Leaderboard
            </a>
        </div>
    </div>
</body>
</html>
