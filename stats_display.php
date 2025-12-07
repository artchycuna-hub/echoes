
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$user_id = $_SESSION['user_id'];

// Get user statistics from users table and calculated stats
$user_stats_sql = "SELECT 
    u.*,
    (SELECT COUNT(*) FROM quiz_sessions WHERE user_id = ?) as total_quizzes_completed,
    (SELECT SUM(time_taken) FROM quiz_sessions WHERE user_id = ?) as total_time_played,
    (SELECT MAX(percentage) FROM quiz_sessions WHERE user_id = ?) as best_score,
    (SELECT AVG(percentage) FROM quiz_sessions WHERE user_id = ?) as average_score,
    (SELECT COUNT(DISTINCT category_id) FROM quiz_sessions WHERE user_id = ?) as categories_played,
    (SELECT ROUND((SUM(correct_answers) / GREATEST(SUM(total_questions), 1)) * 100, 2) FROM quiz_sessions WHERE user_id = ?) as overall_accuracy
FROM users u WHERE id = ?";
$user_stmt = $conn->prepare($user_stats_sql);
$user_stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$user_stmt->execute();
$user_stats = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Get category-wise performance
$category_stats_sql = "
    SELECT 
        c.name as category_name,
        COUNT(qs.id) as total_attempts,
        MAX(qs.percentage) as best_score,
        ROUND(AVG(qs.percentage), 2) as average_score,
        ROUND((SUM(qs.correct_answers) / GREATEST(SUM(qs.total_questions), 1)) * 100, 2) as accuracy,
        SUM(qs.time_taken) as total_time_spent,
        MAX(qs.created_at) as last_attempt
    FROM categories c
    LEFT JOIN quiz_sessions qs ON c.id = qs.category_id AND qs.user_id = ?
    WHERE qs.id IS NOT NULL
    GROUP BY c.id, c.name
    ORDER BY average_score DESC
";
$category_stmt = $conn->prepare($category_stats_sql);
$category_stmt->bind_param("i", $user_id);
$category_stmt->execute();
$category_stats = $category_stmt->get_result();
$category_stmt->close();

// Get recent sessions
$recent_sql = "
    SELECT 
        c.name as category_name,
        qs.percentage as score,
        qs.correct_answers,
        qs.total_questions,
        qs.time_taken,
        qs.created_at
    FROM quiz_sessions qs
    JOIN categories c ON qs.category_id = c.id
    WHERE qs.user_id = ?
    ORDER BY qs.created_at DESC
    LIMIT 5
";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_sessions = $recent_stmt->get_result();
$recent_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Statistics - QuizMaster</title>
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
        
        .stats-grid {
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
        
        .section {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #4facfe;
            border-bottom: 2px solid #4facfe;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        th {
            background: rgba(79, 172, 254, 0.2);
            color: #4facfe;
        }
        
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
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
        
        .accuracy-high { color: #2ecc71; }
        .accuracy-medium { color: #f39c12; }
        .accuracy-low { color: #e74c3c; }
        
        .progress-bar {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            height: 10px;
            margin: 5px 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            border-radius: 10px;
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
                    <h1><i class="fas fa-chart-line"></i> Your Statistics</h1>
                    <p>Track your quiz performance and progress</p>
                </div>
            </div>
        </header>
        
        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-trophy stat-icon"></i>
                <div class="stat-value"><?php echo $user_stats['best_score'] ?? 0; ?>%</div>
                <div class="stat-label">Best Score</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-chart-bar stat-icon"></i>
                <div class="stat-value"><?php echo $user_stats['average_score'] ?? 0; ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-gamepad stat-icon"></i>
                <div class="stat-value"><?php echo $user_stats['total_quizzes_completed'] ?? 0; ?></div>
                <div class="stat-label">Quizzes Completed</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock stat-icon"></i>
                <div class="stat-value"><?php echo round(($user_stats['total_time_played'] ?? 0) / 60, 1); ?>h</div>
                <div class="stat-label">Total Time Played</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-bullseye stat-icon"></i>
                <div class="stat-value"><?php echo $user_stats['overall_accuracy'] ?? 0; ?>%</div>
                <div class="stat-label">Overall Accuracy</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-layer-group stat-icon"></i>
                <div class="stat-value"><?php echo $user_stats['categories_played'] ?? 0; ?></div>
                <div class="stat-label">Categories Played</div>
            </div>
        </div>
        
        <!-- Category Performance -->
        <div class="section">
            <h2 class="section-title"><i class="fas fa-star"></i> Category Performance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Attempts</th>
                        <th>Best Score</th>
                        <th>Average Score</th>
                        <th>Accuracy</th>
                        <th>Time Spent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($category = $category_stats->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                        <td><?php echo $category['total_attempts'] ?? 0; ?></td>
                        <td><?php echo number_format($category['best_score'] ?? 0, 1); ?>%</td>
                        <td><?php echo number_format($category['average_score'] ?? 0, 1); ?>%</td>
                        <td class="accuracy-<?php 
                            $acc = $category['accuracy'] ?? 0;
                            echo $acc >= 80 ? 'high' : 
                                 ($acc >= 60 ? 'medium' : 'low'); 
                        ?>">
                            <?php echo number_format($acc, 1); ?>%
                        </td>
                        <td><?php echo round(($category['total_time_spent'] ?? 0) / 60, 1); ?>m</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Activity -->
        <div class="section">
            <h2 class="section-title"><i class="fas fa-history"></i> Recent Activity</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Score</th>
                        <th>Correct Answers</th>
                        <th>Time Taken</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($session = $recent_sessions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($session['category_name']); ?></td>
                        <td class="accuracy-<?php 
                            echo $session['score'] >= 80 ? 'high' : 
                                 ($session['score'] >= 60 ? 'medium' : 'low'); 
                        ?>">
                            <?php echo $session['score']; ?>%
                        </td>
                        <td><?php echo $session['correct_answers']; ?>/<?php echo $session['total_questions']; ?></td>
                        <td><?php echo $session['time_taken']; ?>s</td>
                        <td><?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Action Buttons -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="game.php" class="btn">
                <i class="fas fa-gamepad"></i> Play Another Quiz
            </a>
            <a href="leaderboard.php" class="btn btn-secondary">
                <i class="fas fa-trophy"></i> View Leaderboard
            </a>
            <a href="achievements.php" class="btn">
                <i class="fas fa-medal"></i> View Achievements
            </a>
        </div>
    </div>
</body>
</html>
