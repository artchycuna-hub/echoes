<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['room_code'])) {
    header("Location: multiplayer.php");
    exit();
}

include 'connection.php';

$room_code = $_SESSION['room_code'];

// Handle game selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_type'])) {
    $game_type = $_POST['game_type'];
    
    $update_sql = "UPDATE multiplayer_rooms SET game_type = ? WHERE room_code = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $game_type, $room_code);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Redirect to the selected game
    if ($game_type === 'general') {
        header("Location: game1.php?multiplayer=1&room=" . $room_code);
    } else {
        header("Location: itgame.php?multiplayer=1&room=" . $room_code);
    }
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Game - Multiplayer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Add the same styles as multiplayer.php with game selection options */
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
        
        .games-grid {
            display: grid;
            gap: 20px;
            margin: 30px 0;
        }
        
        .game-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .game-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            border-color: #4facfe;
        }
        
        .game-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #4facfe;
        }
        
        .game-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4facfe;
        }
        
        .game-description {
            color: #d1d1d1;
            margin-bottom: 15px;
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
            margin: 5px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-gamepad"></i> Choose Game Mode</h1>
            <p>Room: <strong><?php echo $_SESSION['room_code']; ?></strong></p>
        </header>
        
        <div class="games-grid">
            <form method="POST">
                <div class="game-card" onclick="this.closest('form').submit();">
                    <input type="hidden" name="game_type" value="general">
                    <i class="fas fa-globe-americas game-icon"></i>
                    <h3 class="game-title">General Knowledge</h3>
                    <p class="game-description">Test your knowledge across various topics</p>
                    <button type="submit" class="btn">Select General Quiz</button>
                </div>
            </form>
            
            <form method="POST">
                <div class="game-card" onclick="this.closest('form').submit();">
                    <input type="hidden" name="game_type" value="it">
                    <i class="fas fa-laptop-code game-icon"></i>
                    <h3 class="game-title">IT Challenge</h3>
                    <p class="game-description">Prove your tech expertise</p>
                    <button type="submit" class="btn">Select IT Quiz</button>
                </div>
            </form>
        </div>
        
        <a href="multiplayer_waiting.php" class="btn" style="background: linear-gradient(to right, #667eea, #764ba2);">
            <i class="fas fa-arrow-left"></i> Back to Waiting Room
        </a>
    </div>
</body>
</html>