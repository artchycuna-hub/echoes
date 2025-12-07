<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['room_code'])) {
    header("Location: multiplayer.php");
    exit();
}

include 'connection.php';

$room_code = $_SESSION['room_code'];
$user_id = $_SESSION['user_id'];
$is_host = $_SESSION['is_host'];

// Get room info
$room_sql = "SELECT * FROM multiplayer_rooms WHERE room_code = ?";
$room_stmt = $conn->prepare($room_sql);
$room_stmt->bind_param("s", $room_code);
$room_stmt->execute();
$room = $room_stmt->get_result()->fetch_assoc();
$room_stmt->close();

// Check if both players are ready
$both_players_ready = ($room['player1_id'] && $room['player2_id']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting Room - QuizMaster</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        header {
            margin-bottom: 30px;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .room-code {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border: 2px solid #4facfe;
        }
        
        .code-display {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 10px;
            color: #00f2fe;
            text-shadow: 0 0 20px rgba(0, 242, 254, 0.5);
        }
        
        .players-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }
        
        .player-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .player-card.waiting {
            opacity: 0.6;
        }
        
        .player-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #4facfe;
        }
        
        .player-name {
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: #4facfe;
        }
        
        .player-status {
            color: #d1d1d1;
            font-size: 0.9rem;
        }
        
        .waiting-message {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #ffc107;
        }
        
        .ready-message {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #2ecc71;
        }
        
        .btn {
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            margin: 10px 5px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: linear-gradient(to right, #667eea, #764ba2);
        }
        
        .btn-success {
            background: linear-gradient(to right, #2ecc71, #27ae60);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-clock"></i> Waiting Room</h1>
            <p>Room Code: <strong><?php echo $room_code; ?></strong></p>
        </header>
        
        <div class="room-code">
            <div style="color: #d1d1d1; margin-bottom: 10px;">Share this code with your friend:</div>
            <div class="code-display"><?php echo chunk_split($room_code, 3, ' '); ?></div>
        </div>
        
        <div class="players-container">
            <!-- Player 1 -->
            <div class="player-card">
                <div class="player-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="player-name"><?php echo htmlspecialchars($room['player1_name']); ?></div>
                <div class="player-status">Host</div>
            </div>
            
            <!-- Player 2 -->
            <div class="player-card <?php echo !$room['player2_name'] ? 'waiting' : ''; ?>">
                <div class="player-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="player-name">
                    <?php echo $room['player2_name'] ? htmlspecialchars($room['player2_name']) : 'Waiting...'; ?>
                </div>
                <div class="player-status">
                    <?php echo $room['player2_name'] ? 'Player 2' : 'Waiting for player'; ?>
                </div>
            </div>
        </div>
        
        <?php if (!$both_players_ready): ?>
            <div class="waiting-message">
                <i class="fas fa-clock"></i> Waiting for another player to join...
            </div>
        <?php else: ?>
            <div class="ready-message">
                <i class="fas fa-check-circle"></i> Both players are ready! The game will start soon.
            </div>
        <?php endif; ?>
        
        <div>
            <button class="btn btn-success" id="startGame" <?php echo !$both_players_ready || !$is_host ? 'disabled' : ''; ?>>
                <i class="fas fa-play"></i> Start Game
            </button>
            <a href="multiplayer.php?leave=1" class="btn btn-secondary">
                <i class="fas fa-sign-out-alt"></i> Leave Room
            </a>
        </div>
    </div>

    <script>
        // Auto-refresh every 3 seconds to check for player 2
        setTimeout(() => {
            window.location.reload();
        }, 3000);

        // Start game functionality
        document.getElementById('startGame').addEventListener('click', function() {
            // Redirect to game selection
            window.location.href = 'multiplayer_game.php';
        });
    </script>
</body>
</html>