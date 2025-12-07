<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Handle room creation and joining
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_room'])) {
        // Generate a unique 6-digit room code
        $room_code = sprintf("%06d", mt_rand(1, 999999));
        $user_id = $_SESSION['user_id'];
        $player_name = $_SESSION['username'];
        
        // Check if code already exists (very unlikely but just in case)
        $check_sql = "SELECT id FROM multiplayer_rooms WHERE room_code = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $room_code);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows == 0) {
            // Create the room
            $create_sql = "INSERT INTO multiplayer_rooms (room_code, player1_id, player1_name, created_at) VALUES (?, ?, ?, NOW())";
            $create_stmt = $conn->prepare($create_sql);
            $create_stmt->bind_param("sis", $room_code, $user_id, $player_name);
            
            if ($create_stmt->execute()) {
                $_SESSION['room_code'] = $room_code;
                $_SESSION['is_host'] = true;
                header("Location: multiplayer_waiting.php");
                exit();
            }
            $create_stmt->close();
        }
        $check_stmt->close();
    }
    
    if (isset($_POST['join_room'])) {
        $room_code = trim($_POST['room_code']);
        $user_id = $_SESSION['user_id'];
        $player_name = $_SESSION['username'];
        
        // Check if room exists and has space
        $check_sql = "SELECT id, player2_id FROM multiplayer_rooms WHERE room_code = ? AND (player2_id IS NULL OR player2_id = ?)";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $room_code, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $room = $result->fetch_assoc();
            
            if ($room['player2_id'] === null) {
                // Join as player 2
                $update_sql = "UPDATE multiplayer_rooms SET player2_id = ?, player2_name = ?, joined_at = NOW() WHERE room_code = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iss", $user_id, $player_name, $room_code);
                
                if ($update_stmt->execute()) {
                    $_SESSION['room_code'] = $room_code;
                    $_SESSION['is_host'] = false;
                    header("Location: multiplayer_waiting.php");
                    exit();
                }
                $update_stmt->close();
            } else {
                // Player is already in this room
                $_SESSION['room_code'] = $room_code;
                $_SESSION['is_host'] = ($room['player2_id'] == $user_id) ? false : true;
                header("Location: multiplayer_waiting.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Room not found or already full!";
        }
        $check_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multiplayer - QuizMaster</title>
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
            max-width: 500px;
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
        
        .welcome {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #d1d1d1;
        }
        
        .multiplayer-options {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .option-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 30px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            border-color: #4facfe;
        }
        
        .option-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #4facfe;
        }
        
        .option-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4facfe;
        }
        
        .option-description {
            color: #d1d1d1;
            margin-bottom: 15px;
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
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: linear-gradient(to right, #667eea, #764ba2);
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="game.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        
        <header>
            <h1><i class="fas fa-users"></i> Multiplayer</h1>
            <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
            <p>Challenge your friends in real-time quiz battles</p>
        </header>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="multiplayer-options">
            <!-- Create Room -->
            <form method="POST" class="option-card">
                <div class="option-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <h3 class="option-title">Create New Room</h3>
                <p class="option-description">Create a new game room and share the code with your friend</p>
                <button type="submit" name="create_room" class="btn">
                    <i class="fas fa-gamepad"></i> Create Room
                </button>
            </form>
            
            <!-- Join Room -->
            <div class="option-card">
                <div class="option-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h3 class="option-title">Join Existing Room</h3>
                <p class="option-description">Enter a 6-digit code to join your friend's room</p>
                <form method="POST">
                    <div class="form-group">
                        <label for="room_code">Room Code:</label>
                        <input type="text" name="room_code" id="room_code" class="form-control" 
                               placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required>
                    </div>
                    <button type="submit" name="join_room" class="btn btn-secondary">
                        <i class="fas fa-users"></i> Join Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Format room code input
        document.getElementById('room_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>