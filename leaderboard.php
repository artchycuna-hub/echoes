
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Get global leaderboard (top 20 players)
$leaderboard_sql = "
    SELECT 
        u.username,
        COUNT(qs.id) as total_quizzes,
        MAX(qs.percentage) as best_score,
        ROUND(AVG(qs.percentage), 2) as average_score,
        SUM(qs.correct_answers) as total_correct,
        SUM(qs.total_questions) as total_questions,
        ROUND((SUM(qs.correct_answers) / GREATEST(SUM(qs.total_questions), 1)) * 100, 2) as accuracy,
        SUM(qs.time_taken) as total_time_played
    FROM users u
    JOIN quiz_sessions qs ON u.id = qs.user_id
    WHERE u.is_admin = 0
    GROUP BY u.id, u.username
    ORDER BY average_score DESC, total_correct DESC
    LIMIT 20
";
$leaderboard_result = $conn->query($leaderboard_sql);

// Get current user's rank
$user_id = $_SESSION['user_id'];
$user_rank_sql = "
    SELECT ranked.username, ranked.rank, ranked.average_score
    FROM (
        SELECT 
            u.username,
            u.id,
            ROUND(AVG(qs.percentage), 2) as average_score,
            RANK() OVER (ORDER BY AVG(qs.percentage) DESC) as rank
        FROM users u
        JOIN quiz_sessions qs ON u.id = qs.user_id
        WHERE u.is_admin = 0
        GROUP BY u.id, u.username
    ) as ranked
    WHERE ranked.id = ?
";
$user_stmt = $conn->prepare($user_rank_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_rank = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Get category-wise leaderboards
$categories_sql = "SELECT id, name FROM categories";
$categories_result = $conn->query($categories_sql);
$category_leaderboards = [];

while ($category = $categories_result->fetch_assoc()) {
    $category_id = $category['id'];
    $category_name = $category['name'];
    
    $category_leaderboard_sql = "
        SELECT 
            u.username,
            MAX(qs.percentage) as best_score,
            COUNT(qs.id) as attempts,
            ROUND(AVG(qs.percentage), 2) as average_score
        FROM users u
        JOIN quiz_sessions qs ON u.id = qs.user_id
        WHERE qs.category_id = ? AND u.is_admin = 0
        GROUP BY u.id, u.username
        ORDER BY best_score DESC, average_score DESC
        LIMIT 10
    ";
    
    $cat_stmt = $conn->prepare($category_leaderboard_sql);
    $cat_stmt->bind_param("i", $category_id);
    $cat_stmt->execute();
    $category_leaderboards[$category_name] = $cat_stmt->get_result();
    $cat_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - QuizMaster</title>
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
        
        .user-rank-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            border: 2px solid #4facfe;
        }
        
        .user-rank-title {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #d1d1d1;
        }
        
        .user-rank-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #00f2fe;
        }
        
        .user-rank-details {
            margin-top: 10px;
            color: #d1d1d1;
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
        
        .rank-1 {
            background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
            border-left: 4px solid gold;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, rgba(192, 192, 192, 0.1), rgba(192, 192, 192, 0.05));
            border-left: 4px solid silver;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, rgba(205, 127, 50, 0.1), rgba(205, 127, 50, 0.05));
            border-left: 4px solid #cd7f32;
        }
        
        .rank-number {
            font-weight: bold;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .rank-1 .rank-number {
            color: gold;
        }
        
        .rank-2 .rank-number {
            color: silver;
        }
        
        .rank-3 .rank-number {
            color: #cd7f32;
        }
        
        .category-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .category-tab {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category-tab.active {
            background: linear-gradient(to right, #4facfe, #00f2fe);
        }
        
        .category-tab:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .category-content {
            display: none;
        }
        
        .category-content.active {
            display: block;
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
            
            table {
                font-size: 0.9rem;
            }
            
            th, td {
                padding: 8px 10px;
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
                    <h1><i class="fas fa-trophy"></i> QuizMaster Leaderboard</h1>
                    <p>Compete with other players and climb to the top!</p>
                </div>
            </div>
        </header>
        
        <!-- User's Rank -->
        <div class="user-rank-card">
            <div class="user-rank-title">Your Global Rank</div>
            <div class="user-rank-value">#<?php echo $user_rank['rank'] ?? 'N/A'; ?></div>
            <div class="user-rank-details">
                Username: <?php echo $_SESSION['username']; ?> | 
                Average Score: <?php echo $user_rank['average_score'] ?? '0'; ?>%
            </div>
        </div>
        
        <!-- Global Leaderboard -->
        <div class="section">
            <h2 class="section-title"><i class="fas fa-globe"></i> Global Leaderboard</h2>
            <table>
                <thead>
                    <tr>
                        <th width="80">Rank</th>
                        <th>Player</th>
                        <th>Quizzes</th>
                        <th>Best Score</th>
                        <th>Average Score</th>
                        <th>Accuracy</th>
                        <th>Total Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($player = $leaderboard_result->fetch_assoc()): 
                        $row_class = "rank-$rank";
                        if ($rank > 3) $row_class = "";
                    ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td class="rank-number">
                            <?php 
                            if ($rank == 1) echo '<i class="fas fa-trophy"></i>';
                            elseif ($rank == 2) echo '<i class="fas fa-medal"></i>';
                            elseif ($rank == 3) echo '<i class="fas fa-award"></i>';
                            else echo $rank;
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($player['username']); ?></td>
                        <td><?php echo $player['total_quizzes']; ?></td>
                        <td><?php echo $player['best_score']; ?>%</td>
                        <td><?php echo $player['average_score']; ?>%</td>
                        <td><?php echo $player['accuracy']; ?>%</td>
                        <td><?php echo round($player['total_time_played'] / 60, 1); ?>m</td>
                    </tr>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Category Leaderboards -->
        <div class="section">
            <h2 class="section-title"><i class="fas fa-layer-group"></i> Category Leaderboards</h2>
            
            <div class="category-tabs">
                <?php 
                $first = true;
                foreach ($category_leaderboards as $category_name => $leaderboard): 
                ?>
                <button class="category-tab <?php echo $first ? 'active' : ''; ?>" 
                        data-target="<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $category_name); ?>">
                    <?php echo htmlspecialchars($category_name); ?>
                </button>
                <?php 
                $first = false;
                endforeach; 
                ?>
            </div>
            
            <?php 
            $first = true;
            foreach ($category_leaderboards as $category_name => $leaderboard): 
                $category_id = preg_replace('/[^a-zA-Z0-9]/', '', $category_name);
            ?>
            <div class="category-content <?php echo $first ? 'active' : ''; ?>" id="<?php echo $category_id; ?>">
                <table>
                    <thead>
                        <tr>
                            <th width="80">Rank</th>
                            <th>Player</th>
                            <th>Attempts</th>
                            <th>Best Score</th>
                            <th>Average Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cat_rank = 1;
                        while ($player = $leaderboard->fetch_assoc()): 
                            $row_class = "rank-$cat_rank";
                            if ($cat_rank > 3) $row_class = "";
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td class="rank-number">
                                <?php 
                                if ($cat_rank == 1) echo '<i class="fas fa-trophy"></i>';
                                elseif ($cat_rank == 2) echo '<i class="fas fa-medal"></i>';
                                elseif ($cat_rank == 3) echo '<i class="fas fa-award"></i>';
                                else echo $cat_rank;
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($player['username']); ?></td>
                            <td><?php echo $player['attempts']; ?></td>
                            <td><?php echo $player['best_score']; ?>%</td>
                            <td><?php echo $player['average_score']; ?>%</td>
                        </tr>
                        <?php 
                        $cat_rank++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </div>
            <?php 
            $first = false;
            endforeach; 
            ?>
        </div>
        
        <!-- Action Buttons -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="game.php" class="btn">
                <i class="fas fa-gamepad"></i> Play Quizzes
            </a>
            <a href="stats_display.php" class="btn btn-secondary">
                <i class="fas fa-chart-line"></i> View Statistics
            </a>
            <a href="achievements.php" class="btn">
                <i class="fas fa-medal"></i> View Achievements
            </a>
        </div>
    </div>

    <script>
        // Category tabs functionality
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.category-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                const targetId = tab.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');
            });
        });
    </script>
</body>
</html>
