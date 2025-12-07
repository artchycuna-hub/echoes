
<?php
session_start();
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Get the current user
$current_username = $_SESSION['username'];
$current_user_id = $_SESSION['user_id'] ?? null;

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Delete the quiz session
    $delete_sql = "DELETE FROM quiz_sessions WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Player record deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting record: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    
    $stmt->close();
    
    // Redirect to avoid resubmission
    header("Location: dashboard.php");
    exit();
}

// Determine current section
$current_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Fetch all quiz sessions with user and category details
$sql = "SELECT 
    qs.id,
    qs.player_name,
    qs.score,
    qs.total_questions,
    qs.percentage,
    qs.time_taken,
    qs.created_at,
    u.username,
    c.name AS category_name
FROM quiz_sessions qs
JOIN users u ON qs.user_id = u.id
JOIN categories c ON qs.category_id = c.id
ORDER BY qs.created_at DESC";

$result = $conn->query($sql);

$all_scores = [];
$total_players = 0;
$total_games = 0;
$total_score = 0;
$highest_score = 0;
$players_today = 0;
$unique_players = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $all_scores[] = $row;
        $total_games++;
        $total_score += $row['score'];
        if ($row['score'] > $highest_score) {
            $highest_score = $row['score'];
        }
        
        // Check if created today
        $created_date = date('Y-m-d', strtotime($row['created_at']));
        $today = date('Y-m-d');
        if ($created_date === $today) {
            $players_today++;
        }
        
        // Track unique players
        if (!isset($unique_players[$row['username']])) {
            $unique_players[$row['username']] = true;
            $total_players++;
        }
    }
}

$avg_score = $total_games > 0 ? round($total_score / $total_games, 1) : 0;

// Fetch unique player names for Players section
$players_sql = "SELECT DISTINCT player_name, username, COUNT(*) as game_count, 
                MAX(score) as highest_score, AVG(percentage) as avg_percentage
                FROM quiz_sessions 
                JOIN users ON quiz_sessions.user_id = users.id
                GROUP BY player_name, username 
                ORDER BY game_count DESC";
$players_result = $conn->query($players_sql);
$unique_players_list = [];

if ($players_result && $players_result->num_rows > 0) {
    while ($row = $players_result->fetch_assoc()) {
        $unique_players_list[] = $row;
    }
}

// Fetch analytics data
// Category performance
$category_sql = "SELECT c.name AS category_name, 
                COUNT(*) as total_games,
                AVG(qs.score) as avg_score,
                AVG(qs.percentage) as avg_percentage
                FROM quiz_sessions qs
                JOIN categories c ON qs.category_id = c.id
                GROUP BY c.name
                ORDER BY total_games DESC";
$category_result = $conn->query($category_sql);
$category_stats = [];

if ($category_result && $category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $category_stats[] = $row;
    }
}

// Daily performance
$daily_sql = "SELECT DATE(created_at) as game_date, 
              COUNT(*) as games_played,
              AVG(score) as avg_score
              FROM quiz_sessions 
              GROUP BY DATE(created_at) 
              ORDER BY game_date DESC 
              LIMIT 7";
$daily_result = $conn->query($daily_sql);
$daily_stats = [];

if ($daily_result && $daily_result->num_rows > 0) {
    while ($row = $daily_result->fetch_assoc()) {
        $daily_stats[] = $row;
    }
}

// Performance distribution
$performance_sql = "SELECT 
    COUNT(CASE WHEN percentage >= 80 THEN 1 END) as excellent,
    COUNT(CASE WHEN percentage >= 60 AND percentage < 80 THEN 1 END) as good,
    COUNT(CASE WHEN percentage < 60 THEN 1 END) as poor
    FROM quiz_sessions";
$performance_result = $conn->query($performance_sql);
$performance_stats = $performance_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Score Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --dark: #1a1a2e;
            --darker: #16213e;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
        }

        body {
            background-color: var(--darker);
            color: var(--light);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: var(--dark);
            padding: 20px;
            height: 100vh;
            position: fixed;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo i {
            font-size: 28px;
            color: var(--primary);
            margin-right: 10px;
        }

        .logo h1 {
            font-size: 22px;
            font-weight: 600;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 15px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            color: var(--light);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover, .nav-links a.active {
            background-color: var(--primary);
            color: white;
        }

        .nav-links i {
            margin-right: 10px;
            font-size: 18px;
        }

        .logout-link {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-link a {
            display: flex;
            align-items: center;
            color: var(--danger);
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-link a:hover {
            background-color: var(--danger);
            color: white;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 28px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: var(--dark);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }

        .icon-1 {
            background-color: rgba(67, 97, 238, 0.2);
            color: var(--primary);
        }

        .icon-2 {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }

        .icon-3 {
            background-color: rgba(248, 149, 30, 0.2);
            color: var(--warning);
        }

        .icon-4 {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--danger);
        }

        .stat-info h3 {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 5px;
        }

        .stat-info p {
            font-size: 24px;
            font-weight: 600;
        }

        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .content-card {
            background-color: var(--dark);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h3 {
            font-size: 20px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            color: #aaa;
            font-weight: 500;
        }

        .player-row:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .player-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .player-info {
            display: flex;
            align-items: center;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-high {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }

        .badge-medium {
            background-color: rgba(248, 149, 30, 0.2);
            color: var(--warning);
        }

        .badge-low {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--danger);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #aaa;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 16px;
            max-width: 400px;
            margin: 0 auto 20px;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
            margin: 0 2px;
        }

        .view-btn {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }

        .view-btn:hover {
            background-color: rgba(76, 201, 240, 0.4);
        }

        .delete-btn {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--danger);
        }

        .delete-btn:hover {
            background-color: rgba(247, 37, 133, 0.4);
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .message {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .message.error {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: var(--dark);
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h3 {
            font-size: 22px;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            color: #aaa;
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: var(--light);
        }

        .player-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 16px;
            font-weight: 500;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background-color: var(--dark);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 300px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        .player-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .stat-box .value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stat-box .label {
            font-size: 14px;
            color: #aaa;
        }

        @media (max-width: 992px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                width: 80px;
            }
            
            .sidebar .logo h1, .sidebar .nav-links span {
                display: none;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .nav-links a {
                justify-content: center;
            }
            
            .nav-links i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .player-details {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-gamepad"></i>
            <h1>GameAdmin</h1>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php?section=dashboard" class="<?php echo $current_section == 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="dashboard.php?section=players" class="<?php echo $current_section == 'players' ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Players</span></a></li>
            <li><a href="dashboard.php?section=analytics" class="<?php echo $current_section == 'analytics' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> <span>Analytics</span></a></li>
        </ul>
        
        <!-- Logout Link -->
        <div class="logout-link">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2>
                <?php 
                switch($current_section) {
                    case 'players':
                        echo 'Players Management';
                        break;
                    case 'analytics':
                        echo 'Game Analytics';
                        break;
                    default:
                        echo 'Player Score Dashboard';
                }
                ?>
            </h2>
            <div class="user-info">
                <div class="user-avatar"><?php echo strtoupper(substr($current_username, 0, 2)); ?></div>
                <div>
                    <h4><?php echo htmlspecialchars($current_username); ?></h4>
                    <p>
                        <?php 
                        switch($current_section) {
                            case 'players':
                                echo 'Players';
                                break;
                            case 'analytics':
                                echo 'Analytics';
                                break;
                            default:
                                echo 'Dashboard';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Display messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($current_section == 'dashboard'): ?>
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="card stat-card">
                    <div class="stat-icon icon-1">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Players</h3>
                        <p><?php echo $total_players; ?></p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-icon icon-2">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Avg. Score</h3>
                        <p><?php echo number_format($avg_score, 1); ?></p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-icon icon-3">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Top Score</h3>
                        <p><?php echo $highest_score; ?></p>
                    </div>
                </div>
                <div class="card stat-card">
                    <div class="stat-icon icon-4">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Games Today</h3>
                        <p><?php echo $players_today; ?></p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Player List -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>All Player Scores (<?php echo count($all_scores); ?> records)</h3>
                    </div>
                    <div id="player-table-container">
                        <table id="player-table">
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>Username</th>
                                    <th>Category</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="player-table-body">
                                <?php if (count($all_scores) > 0): ?>
                                    <?php foreach ($all_scores as $player): ?>
                                        <tr class="player-row">
                                            <td>
                                                <div class="player-info">
                                                    <div class="player-avatar">
                                                        <?php 
                                                        $initials = strtoupper(substr($player['player_name'], 0, 2));
                                                        echo $initials;
                                                        ?>
                                                    </div>
                                                    <div><?php echo htmlspecialchars($player['player_name']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($player['username']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($player['category_name'])); ?></td>
                                            <td><?php echo $player['score']; ?>/<?php echo $player['total_questions']; ?></td>
                                            <td><?php echo number_format($player['percentage'], 1); ?>%</td>
                                            <td><?php echo date('M d, Y', strtotime($player['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                                $percentage = $player['percentage'];
                                                $badge = 'badge-low';
                                                if ($percentage >= 80) {
                                                    $badge = 'badge-high';
                                                } elseif ($percentage >= 60) {
                                                    $badge = 'badge-medium';
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge; ?>">
                                                    <?php 
                                                    if ($percentage >= 80) echo 'Excellent';
                                                    elseif ($percentage >= 60) echo 'Good';
                                                    else echo 'Try Again';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="actions">
                                                    <button class="action-btn view-btn" onclick="viewPlayer(<?php echo $player['id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="action-btn delete-btn" onclick="deletePlayer(<?php echo $player['id']; ?>, '<?php echo htmlspecialchars($player['player_name']); ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8">
                                            <div class="empty-state">
                                                <i class="fas fa-user-friends"></i>
                                                <h3>No Player Records Yet</h3>
                                                <p>Player scores will appear here once users start playing games.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($current_section == 'players'): ?>
            <!-- Players Section -->
            <div class="dashboard-content">
                <div class="content-card">
                    <div class="card-header">
                        <h3>All Players (<?php echo count($unique_players_list); ?> unique players)</h3>
                    </div>
                    
                    <div class="player-stats">
                        <div class="stat-box">
                            <div class="value"><?php echo count($unique_players_list); ?></div>
                            <div class="label">Total Players</div>
                        </div>
                        <div class="stat-box">
                            <div class="value"><?php echo $total_games; ?></div>
                            <div class="label">Total Games Played</div>
                        </div>
                        <div class="stat-box">
                            <div class="value"><?php echo number_format($avg_score, 1); ?></div>
                            <div class="label">Average Score</div>
                        </div>
                        <div class="stat-box">
                            <div class="value"><?php echo $highest_score; ?></div>
                            <div class="label">Highest Score</div>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Player Name</th>
                                <th>Username</th>
                                <th>Games Played</th>
                                <th>Highest Score</th>
                                <th>Avg. Percentage</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($unique_players_list) > 0): ?>
                                <?php foreach ($unique_players_list as $player): ?>
                                    <tr class="player-row">
                                        <td>
                                            <div class="player-info">
                                                <div class="player-avatar">
                                                    <?php echo strtoupper(substr($player['player_name'], 0, 2)); ?>
                                                </div>
                                                <div><?php echo htmlspecialchars($player['player_name']); ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($player['username']); ?></td>
                                        <td><?php echo $player['game_count']; ?></td>
                                        <td><?php echo $player['highest_score']; ?></td>
                                        <td><?php echo number_format($player['avg_percentage'], 1); ?>%</td>
                                        <td>
                                            <?php 
                                            $avg_percentage = $player['avg_percentage'];
                                            $badge = 'badge-low';
                                            if ($avg_percentage >= 80) {
                                                $badge = 'badge-high';
                                            } elseif ($avg_percentage >= 60) {
                                                $badge = 'badge-medium';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge; ?>">
                                                <?php 
                                                if ($avg_percentage >= 80) echo 'Excellent';
                                                elseif ($avg_percentage >= 60) echo 'Good';
                                                else echo 'Needs Improvement';
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-user-friends"></i>
                                            <h3>No Players Found</h3>
                                            <p>Player data will appear here once users start playing games.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($current_section == 'analytics'): ?>
            <!-- Analytics Section -->
            <div class="dashboard-content">
                <div class="analytics-grid">
                    <div class="chart-container">
                        <div class="chart-title">Performance Distribution</div>
                        <canvas id="performanceChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-title">Category Performance</div>
                        <canvas id="categoryChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-title">Daily Games (Last 7 Days)</div>
                        <canvas id="dailyChart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-title">Score Distribution</div>
                        <canvas id="scoreChart"></canvas>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3>Category Performance Details</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Total Games</th>
                                <th>Average Score</th>
                                <th>Average Percentage</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($category_stats) > 0): ?>
                                <?php foreach ($category_stats as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo $category['total_games']; ?></td>
                                        <td><?php echo number_format($category['avg_score'], 1); ?></td>
                                        <td><?php echo number_format($category['avg_percentage'], 1); ?>%</td>
                                        <td>
                                            <?php 
                                            $avg_percentage = $category['avg_percentage'];
                                            $badge = 'badge-low';
                                            if ($avg_percentage >= 80) {
                                                $badge = 'badge-high';
                                            } elseif ($avg_percentage >= 60) {
                                                $badge = 'badge-medium';
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge; ?>">
                                                <?php 
                                                if ($avg_percentage >= 80) echo 'Excellent';
                                                elseif ($avg_percentage >= 60) echo 'Good';
                                                else echo 'Needs Improvement';
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-chart-bar"></i>
                                            <h3>No Analytics Data</h3>
                                            <p>Analytics data will appear here once there are enough game records.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Player Details Modal -->
    <div id="playerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Player Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="playerDetails" class="player-details">
                <!-- Player details will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Add auto-refresh every 30 seconds to update the dashboard
        setInterval(function() {
            location.reload();
        }, 30000);

        // View player details
        function viewPlayer(playerId) {
            // In a real application, you would fetch this data via AJAX
            // For this example, we'll use the existing data
            const playerRow = document.querySelector(`button[onclick="viewPlayer(${playerId})"]`).closest('tr');
            const cells = playerRow.querySelectorAll('td');
            
            const playerName = cells[0].querySelector('.player-info div:last-child').textContent;
            const username = cells[1].textContent;
            const category = cells[2].textContent;
            const score = cells[3].textContent;
            const percentage = cells[4].textContent;
            const date = cells[5].textContent;
            const status = cells[6].querySelector('.badge').textContent;
            
            // Get additional details (in a real app, you'd fetch these from the server)
            const timeTaken = '<?php echo isset($player["time_taken"]) ? $player["time_taken"] : "N/A"; ?>';
            const totalQuestions = score.split('/')[1];
            
            document.getElementById('playerDetails').innerHTML = `
                <div class="detail-item">
                    <div class="detail-label">Player Name</div>
                    <div class="detail-value">${playerName}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Username</div>
                    <div class="detail-value">${username}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Category</div>
                    <div class="detail-value">${category}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Score</div>
                    <div class="detail-value">${score}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Percentage</div>
                    <div class="detail-value">${percentage}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Time Taken</div>
                    <div class="detail-value">${timeTaken} seconds</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Date</div>
                    <div class="detail-value">${date}</div>
                </div>
                <div class="detail-item full-width">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">${status}</div>
                </div>
            `;
            
            document.getElementById('playerModal').style.display = 'flex';
        }

        // Delete player record
        function deletePlayer(playerId, playerName) {
            if (confirm(`Are you sure you want to delete the record for "${playerName}"?`)) {
                window.location.href = `dashboard.php?delete_id=${playerId}`;
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('playerModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('playerModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Initialize charts for analytics
        <?php if ($current_section == 'analytics'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Performance Distribution Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            const performanceChart = new Chart(performanceCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Excellent (80-100%)', 'Good (60-79%)', 'Needs Improvement (<60%)'],
                    datasets: [{
                        data: [
                            <?php echo $performance_stats['excellent'] ?? 0; ?>,
                            <?php echo $performance_stats['good'] ?? 0; ?>,
                            <?php echo $performance_stats['poor'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            'rgba(76, 201, 240, 0.8)',
                            'rgba(248, 149, 30, 0.8)',
                            'rgba(247, 37, 133, 0.8)'
                        ],
                        borderColor: [
                            'rgba(76, 201, 240, 1)',
                            'rgba(248, 149, 30, 1)',
                            'rgba(247, 37, 133, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#f8f9fa',
                                padding: 15
                            }
                        }
                    }
                }
            });

            // Category Performance Chart
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'bar',
                data: {
                    labels: [<?php 
                        if (count($category_stats) > 0) {
                            $labels = [];
                            foreach ($category_stats as $category) {
                                $labels[] = "'" . $category['category_name'] . "'";
                            }
                            echo implode(', ', $labels);
                        }
                    ?>],
                    datasets: [{
                        label: 'Average Score',
                        data: [<?php 
                            if (count($category_stats) > 0) {
                                $scores = [];
                                foreach ($category_stats as $category) {
                                    $scores[] = $category['avg_score'];
                                }
                                echo implode(', ', $scores);
                            }
                        ?>],
                        backgroundColor: 'rgba(67, 97, 238, 0.7)',
                        borderColor: 'rgba(67, 97, 238, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#f8f9fa'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#f8f9fa'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#f8f9fa'
                            }
                        }
                    }
                }
            });

            // Daily Games Chart
            const dailyCtx = document.getElementById('dailyChart').getContext('2d');
            const dailyChart = new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: [<?php 
                        if (count($daily_stats) > 0) {
                            $dates = [];
                            foreach ($daily_stats as $daily) {
                                $dates[] = "'" . date('M d', strtotime($daily['game_date'])) . "'";
                            }
                            echo implode(', ', array_reverse($dates));
                        }
                    ?>],
                    datasets: [{
                        label: 'Games Played',
                        data: [<?php 
                            if (count($daily_stats) > 0) {
                                $games = [];
                                foreach ($daily_stats as $daily) {
                                    $games[] = $daily['games_played'];
                                }
                                echo implode(', ', array_reverse($games));
                            }
                        ?>],
                        backgroundColor: 'rgba(76, 201, 240, 0.2)',
                        borderColor: 'rgba(76, 201, 240, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#f8f9fa'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#f8f9fa'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#f8f9fa'
                            }
                        }
                    }
                }
            });

            // Score Distribution Chart
            const scoreCtx = document.getElementById('scoreChart').getContext('2d');
            const scoreChart = new Chart(scoreCtx, {
                type: 'pie',
                data: {
                    labels: ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'],
                    datasets: [{
                        data: [5, 10, 15, 25, 45], // Example data - in a real app, calculate from actual scores
                        backgroundColor: [
                            'rgba(247, 37, 133, 0.8)',
                            'rgba(248, 149, 30, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(76, 201, 240, 0.8)',
                            'rgba(67, 97, 238, 0.8)'
                        ],
                        borderColor: [
                            'rgba(247, 37, 133, 1)',
                            'rgba(248, 149, 30, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(76, 201, 240, 1)',
                            'rgba(67, 97, 238, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#f8f9fa',
                                padding: 15
                            }
                        }
                    }
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
<?php
$conn->close();
?>
