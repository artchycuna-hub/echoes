<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $category = $_POST['category'];
    $score = intval($_POST['score']);
    $total_questions = intval($_POST['total_questions']);
    $percentage = floatval($_POST['percentage']);
    $time_taken = intval($_POST['time_taken']);
    $correct_answers = isset($_POST['correct_answers']) ? intval($_POST['correct_answers']) : $score;
    $incorrect_answers = isset($_POST['incorrect_answers']) ? intval($_POST['incorrect_answers']) : ($total_questions - $score);
    
    // Get player name from session username
    $player_name = $_SESSION['username'];
    
    // Get category ID
    $catStmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $catStmt->bind_param("s", $category);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    
    if ($catResult->num_rows > 0) {
        $category_id = $catResult->fetch_assoc()['id'];
        $catStmt->close();
        
        // Get the highest raw score (count of correct answers) for this player and category
        $highScoreStmt = $conn->prepare("SELECT MAX(score) as highest_raw_score FROM quiz_sessions WHERE user_id = ? AND category_id = ?");
        $highScoreStmt->bind_param("ii", $user_id, $category_id);
        $highScoreStmt->execute();
        $highScoreResult = $highScoreStmt->get_result();
        $highest_score = $score; // default to current score (raw count)
        if ($highScoreResult->num_rows > 0) {
            $highScoreData = $highScoreResult->fetch_assoc();
            $highest_score = max($score, intval($highScoreData['highest_raw_score'] ?? 0));
        }
        $highScoreStmt->close();
        
        // Insert quiz session record
        $stmt = $conn->prepare("INSERT INTO quiz_sessions 
            (user_id, category_id, player_name, score, total_questions, percentage, correct_answers, incorrect_answers, time_taken, highest_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisiiiidii", $user_id, $category_id, $player_name, $score, $total_questions, $percentage, $correct_answers, $incorrect_answers, $time_taken, $highest_score);
        
        if ($stmt->execute()) {
            // Update user statistics
            $updateStmt = $conn->prepare("UPDATE users SET 
                total_quizzes_completed = total_quizzes_completed + 1,
                total_time_played = total_time_played + ?
                WHERE id = ?");
            $updateStmt->bind_param("ii", $time_taken, $user_id);
            $updateStmt->execute();
            $updateStmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Statistics updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Category not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
