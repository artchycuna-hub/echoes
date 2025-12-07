<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $fullname = trim($_POST['fullname'] ?? '');

    // Validate input
    if (empty($username)) {
        $_SESSION['message'] = "Username cannot be empty!";
        $_SESSION['message_type'] = "error";
        header("Location: game.php");
        exit();
    }

    // Check if username already exists (excluding current user)
    $check_sql = "SELECT id FROM users WHERE username = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $username, $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['message'] = "Username already exists!";
        $_SESSION['message_type'] = "error";
        header("Location: game.php");
        exit();
    }
    $check_stmt->close();

    // Update user profile
    $update_sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssi", $username, $email, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['message'] = "Profile updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating profile!";
        $_SESSION['message_type'] = "error";
    }
    
    $update_stmt->close();
}

$conn->close();
header("Location: game.php");
exit();
?>